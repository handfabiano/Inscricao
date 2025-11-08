<?php
/**
 * Report Generation Helper
 *
 * Sistema de geração de relatórios personalizáveis
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gera relatório baseado em template
 *
 * @param int $template_id ID do template
 * @param array $parametros Parâmetros do relatório
 * @param int $gerado_por ID do usuário
 * @param int $organizacao_id ID da organização
 * @return array Resultado da geração
 */
function gerarRelatorio($template_id, $parametros, $gerado_por, $organizacao_id) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Buscar template
        $stmt = $pdo->prepare("SELECT * FROM report_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            return ['success' => false, 'message' => 'Template não encontrado'];
        }

        $inicio = microtime(true);

        // Criar registro do relatório
        $stmt = $pdo->prepare("
            INSERT INTO generated_reports (
                template_id, titulo, parametros, periodo_inicio, periodo_fim,
                gerado_por, organizacao_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'processando')
        ");

        $titulo = $parametros['titulo'] ?? $template['nome'];
        $periodo_inicio = $parametros['periodo_inicio'] ?? null;
        $periodo_fim = $parametros['periodo_fim'] ?? null;

        $stmt->execute([
            $template_id,
            $titulo,
            json_encode($parametros),
            $periodo_inicio,
            $periodo_fim,
            $gerado_por,
            $organizacao_id
        ]);

        $report_id = $pdo->lastInsertId();

        // Coletar dados baseado no tipo
        $dados = coletarDadosRelatorio($template['tipo'], $parametros);

        if (!$dados['success']) {
            throw new Exception($dados['message'] ?? 'Erro ao coletar dados');
        }

        // Processar seções
        $secoes = json_decode($template['secoes'], true);
        $dados_processados = processarSecoes($secoes, $dados['data'], $template['tipo']);

        // Gerar gráficos se configurado
        $graficos_config = json_decode($template['graficos'], true);
        $graficos = [];
        if ($graficos_config) {
            $graficos = gerarGraficos($graficos_config, $dados['data']);
        }

        $tempo_geracao = round(microtime(true) - $inicio, 2);

        // Atualizar relatório com dados
        $stmt = $pdo->prepare("
            UPDATE generated_reports
            SET dados = ?,
                graficos_base64 = ?,
                tempo_geracao = ?,
                status = 'concluido'
            WHERE id = ?
        ");

        $stmt->execute([
            json_encode($dados_processados),
            json_encode($graficos),
            $tempo_geracao,
            $report_id
        ]);

        // Gerar arquivos se solicitado
        $formatos = $parametros['formatos'] ?? ['pdf'];
        $arquivos = [];

        foreach ($formatos as $formato) {
            $resultado = exportarRelatorio($report_id, $formato, $dados_processados, $graficos);
            if ($resultado['success']) {
                $arquivos[$formato] = $resultado['path'];
            }
        }

        // Atualizar paths dos arquivos
        if (isset($arquivos['pdf'])) {
            $pdo->prepare("UPDATE generated_reports SET pdf_path = ? WHERE id = ?")
                ->execute([$arquivos['pdf'], $report_id]);
        }
        if (isset($arquivos['excel'])) {
            $pdo->prepare("UPDATE generated_reports SET excel_path = ? WHERE id = ?")
                ->execute([$arquivos['excel'], $report_id]);
        }

        $pdo->commit();

        return [
            'success' => true,
            'report_id' => $report_id,
            'tempo_geracao' => $tempo_geracao,
            'arquivos' => $arquivos,
            'dados' => $dados_processados
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Atualizar status de erro se relatório foi criado
        if (isset($report_id)) {
            $pdo->prepare("UPDATE generated_reports SET status = 'erro', erro_mensagem = ? WHERE id = ?")
                ->execute([$e->getMessage(), $report_id]);
        }

        error_log("Erro ao gerar relatório: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar relatório: ' . $e->getMessage()];
    }
}

/**
 * Coleta dados baseado no tipo de relatório
 */
function coletarDadosRelatorio($tipo, $parametros) {
    try {
        switch ($tipo) {
            case 'performance_team':
                return coletarDadosPerformanceEquipe($parametros);

            case 'performance_athlete':
                return coletarDadosPerformanceAtleta($parametros);

            case 'competition_summary':
                return coletarDadosCompeticao($parametros);

            case 'scouting':
                return coletarDadosScouting($parametros);

            case 'financial':
                return coletarDadosFinanceiros($parametros);

            default:
                return ['success' => false, 'message' => 'Tipo de relatório não suportado'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Coleta dados de performance de equipe
 */
function coletarDadosPerformanceEquipe($parametros) {
    $pdo = getDBConnection();

    $equipe_id = $parametros['equipe_id'] ?? null;
    $modalidade_id = $parametros['modalidade_id'] ?? null;
    $periodo_inicio = $parametros['periodo_inicio'] ?? null;
    $periodo_fim = $parametros['periodo_fim'] ?? null;

    if (!$equipe_id || !$modalidade_id) {
        return ['success' => false, 'message' => 'equipe_id e modalidade_id são obrigatórios'];
    }

    $dados = [];

    // Informações básicas da equipe
    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
    $stmt->execute([$equipe_id]);
    $dados['equipe'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ranking atual
    $stmt = $pdo->prepare("
        SELECT * FROM team_rankings
        WHERE equipe_id = ? AND modalidade_id = ?
    ");
    $stmt->execute([$equipe_id, $modalidade_id]);
    $dados['ranking'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Partidas no período
    $where_periodo = "";
    $params = [$equipe_id, $equipe_id];

    if ($periodo_inicio && $periodo_fim) {
        $where_periodo = "AND data_hora BETWEEN ? AND ?";
        $params[] = $periodo_inicio;
        $params[] = $periodo_fim;
    }

    $stmt = $pdo->prepare("
        SELECT
            m.*,
            ec.nome as equipe_casa_nome,
            ev.nome as equipe_visitante_nome
        FROM matches m
        INNER JOIN equipes ec ON m.equipe_casa_id = ec.id
        INNER JOIN equipes ev ON m.equipe_visitante_id = ev.id
        WHERE (m.equipe_casa_id = ? OR m.equipe_visitante_id = ?)
          AND m.status = 'finalizado'
          $where_periodo
        ORDER BY m.data_hora DESC
    ");
    $stmt->execute($params);
    $dados['partidas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas do período
    $dados['estatisticas_periodo'] = calcularEstatisticasPeriodo($dados['partidas'], $equipe_id);

    // Top jogadores
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.nome_completo,
            ast.*
        FROM athlete_statistics ast
        INNER JOIN atletas a ON ast.atleta_id = a.id
        WHERE a.equipe_atual_id = ?
          AND ast.modalidade_id = ?
        ORDER BY ast.total_gols DESC
        LIMIT 10
    ");
    $stmt->execute([$equipe_id, $modalidade_id]);
    $dados['top_jogadores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Padrões de jogo
    $stmt = $pdo->prepare("
        SELECT * FROM team_patterns
        WHERE equipe_id = ? AND modalidade_id = ?
    ");
    $stmt->execute([$equipe_id, $modalidade_id]);
    $dados['padroes'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $dados];
}

/**
 * Calcula estatísticas de um período
 */
function calcularEstatisticasPeriodo($partidas, $equipe_id) {
    $stats = [
        'jogos' => count($partidas),
        'vitorias' => 0,
        'empates' => 0,
        'derrotas' => 0,
        'gols_pro' => 0,
        'gols_contra' => 0
    ];

    foreach ($partidas as $partida) {
        $eh_casa = $partida['equipe_casa_id'] == $equipe_id;

        $gols_pro = $eh_casa ? $partida['placar_casa'] : $partida['placar_visitante'];
        $gols_contra = $eh_casa ? $partida['placar_visitante'] : $partida['placar_casa'];

        $stats['gols_pro'] += $gols_pro;
        $stats['gols_contra'] += $gols_contra;

        if ($partida['resultado'] === 'empate') {
            $stats['empates']++;
        } elseif (
            ($eh_casa && $partida['resultado'] === 'casa') ||
            (!$eh_casa && $partida['resultado'] === 'visitante')
        ) {
            $stats['vitorias']++;
        } else {
            $stats['derrotas']++;
        }
    }

    $stats['saldo'] = $stats['gols_pro'] - $stats['gols_contra'];
    $stats['aproveitamento'] = $stats['jogos'] > 0 ?
        round((($stats['vitorias'] * 3 + $stats['empates']) / ($stats['jogos'] * 3)) * 100, 2) : 0;

    return $stats;
}

/**
 * Coleta dados de performance de atleta
 */
function coletarDadosPerformanceAtleta($parametros) {
    $pdo = getDBConnection();

    $atleta_id = $parametros['atleta_id'] ?? null;

    if (!$atleta_id) {
        return ['success' => false, 'message' => 'atleta_id é obrigatório'];
    }

    $dados = [];

    // Informações do atleta
    $stmt = $pdo->prepare("
        SELECT a.*, e.nome as equipe_nome
        FROM atletas a
        LEFT JOIN equipes e ON a.equipe_atual_id = e.id
        WHERE a.id = ?
    ");
    $stmt->execute([$atleta_id]);
    $dados['atleta'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estatísticas
    $stmt = $pdo->prepare("SELECT * FROM athlete_statistics WHERE atleta_id = ?");
    $stmt->execute([$atleta_id]);
    $dados['estatisticas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ranking de talentos (se aplicável)
    $stmt = $pdo->prepare("SELECT * FROM identified_talents WHERE atleta_id = ? ORDER BY talent_score DESC LIMIT 1");
    $stmt->execute([$atleta_id]);
    $dados['talent_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $dados];
}

/**
 * Coleta dados de competição
 */
function coletarDadosCompeticao($parametros) {
    $pdo = getDBConnection();

    $competicao_id = $parametros['competicao_id'] ?? null;

    if (!$competicao_id) {
        return ['success' => false, 'message' => 'competicao_id é obrigatório'];
    }

    $dados = [];

    // Informações da competição
    $stmt = $pdo->prepare("SELECT * FROM competicoes WHERE id = ?");
    $stmt->execute([$competicao_id]);
    $dados['competicao'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Classificação (simulado - depende do sistema de grupos)
    $stmt = $pdo->prepare("
        SELECT gt.*, e.nome as equipe_nome
        FROM group_teams gt
        INNER JOIN equipes e ON gt.equipe_id = e.id
        INNER JOIN competition_groups cg ON gt.grupo_id = cg.id
        INNER JOIN competition_phases cp ON cg.fase_id = cp.id
        WHERE cp.competicao_id = ?
        ORDER BY gt.pontos DESC, gt.saldo_gols DESC
        LIMIT 20
    ");
    $stmt->execute([$competicao_id]);
    $dados['classificacao'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Artilharia
    $stmt = $pdo->prepare("
        SELECT * FROM artilharia
        WHERE competicao_id = ?
        LIMIT 10
    ");
    $stmt->execute([$competicao_id]);
    $dados['artilharia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_jogos,
            SUM(placar_casa + placar_visitante) as total_gols,
            AVG(placar_casa + placar_visitante) as media_gols_jogo
        FROM matches
        WHERE competicao_id = ? AND status = 'finalizado'
    ");
    $stmt->execute([$competicao_id]);
    $dados['estatisticas_gerais'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $dados];
}

/**
 * Coleta dados de scouting
 */
function coletarDadosScouting($parametros) {
    $pdo = getDBConnection();

    $modalidade_id = $parametros['modalidade_id'] ?? null;
    $score_minimo = $parametros['score_minimo'] ?? 70;

    $dados = [];

    // Top talentos
    $where = "it.status = 'ativo'";
    $params = [];

    if ($modalidade_id) {
        $where .= " AND it.modalidade_id = ?";
        $params[] = $modalidade_id;
    }

    if ($score_minimo) {
        $where .= " AND it.talent_score >= ?";
        $params[] = $score_minimo;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM top_talents
        WHERE $where
        ORDER BY talent_score DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $dados['talentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distribuição por potencial
    $stmt = $pdo->prepare("
        SELECT
            potencial,
            COUNT(*) as total,
            AVG(talent_score) as score_medio
        FROM identified_talents
        WHERE status = 'ativo'
        GROUP BY potencial
    ");
    $stmt->execute();
    $dados['distribuicao_potencial'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $dados];
}

/**
 * Coleta dados financeiros
 */
function coletarDadosFinanceiros($parametros) {
    $pdo = getDBConnection();

    $organizacao_id = $parametros['organizacao_id'] ?? null;
    $periodo_inicio = $parametros['periodo_inicio'] ?? null;
    $periodo_fim = $parametros['periodo_fim'] ?? null;

    if (!$organizacao_id) {
        return ['success' => false, 'message' => 'organizacao_id é obrigatório'];
    }

    $dados = [];

    // Transações do período
    $where_periodo = "";
    $params = [$organizacao_id];

    if ($periodo_inicio && $periodo_fim) {
        $where_periodo = "AND created_at BETWEEN ? AND ?";
        $params[] = $periodo_inicio;
        $params[] = $periodo_fim;
    }

    $stmt = $pdo->prepare("
        SELECT
            status,
            tipo_pagamento,
            COUNT(*) as quantidade,
            SUM(valor) as total
        FROM payment_transactions
        WHERE organizacao_id = ?
          $where_periodo
        GROUP BY status, tipo_pagamento
    ");
    $stmt->execute($params);
    $dados['transacoes_resumo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Receita total
    $stmt = $pdo->prepare("
        SELECT
            SUM(valor) as receita_total,
            AVG(valor) as ticket_medio
        FROM payment_transactions
        WHERE organizacao_id = ?
          AND status = 'approved'
          $where_periodo
    ");
    $stmt->execute($params);
    $dados['receita'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $dados];
}

/**
 * Processa seções do relatório
 */
function processarSecoes($secoes, $dados, $tipo) {
    $processado = [];

    foreach ($secoes as $secao) {
        $processado[$secao] = processarSecao($secao, $dados, $tipo);
    }

    return $processado;
}

/**
 * Processa uma seção individual
 */
function processarSecao($secao, $dados, $tipo) {
    // Implementação básica - expandir conforme necessário
    return $dados;
}

/**
 * Gera gráficos para o relatório
 */
function gerarGraficos($config, $dados) {
    // Placeholder - em produção, usar biblioteca de gráficos
    // Como Chart.js no servidor ou geração de imagens

    $graficos = [];

    foreach ($config as $grafico) {
        $graficos[] = [
            'tipo' => $grafico['tipo'] ?? 'line',
            'titulo' => $grafico['titulo'] ?? 'Gráfico',
            'dados' => [], // Processar dados conforme tipo
            'base64' => null // Imagem em base64
        ];
    }

    return $graficos;
}

/**
 * Exporta relatório para formato específico
 */
function exportarRelatorio($report_id, $formato, $dados, $graficos) {
    try {
        $filename = "relatorio_{$report_id}_" . date('YmdHis');

        switch ($formato) {
            case 'pdf':
                return exportarPDF($filename, $dados, $graficos);

            case 'excel':
                return exportarExcel($filename, $dados);

            case 'csv':
                return exportarCSV($filename, $dados);

            default:
                return ['success' => false, 'message' => 'Formato não suportado'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Exporta para PDF
 */
function exportarPDF($filename, $dados, $graficos) {
    // Placeholder - em produção, usar TCPDF ou mPDF

    $output_dir = __DIR__ . '/../public/reports/';
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0755, true);
    }

    $filepath = $output_dir . $filename . '.pdf';

    // Simulação - criar arquivo vazio
    file_put_contents($filepath, "PDF Report Content\n" . json_encode($dados));

    return [
        'success' => true,
        'path' => '/public/reports/' . $filename . '.pdf',
        'full_path' => $filepath
    ];
}

/**
 * Exporta para Excel
 */
function exportarExcel($filename, $dados) {
    // Placeholder - em produção, usar PhpSpreadsheet

    $output_dir = __DIR__ . '/../public/reports/';
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0755, true);
    }

    $filepath = $output_dir . $filename . '.xlsx';

    // Simulação - criar arquivo CSV
    $csv_content = "Excel Report Content\n";
    file_put_contents($filepath, $csv_content);

    return [
        'success' => true,
        'path' => '/public/reports/' . $filename . '.xlsx',
        'full_path' => $filepath
    ];
}

/**
 * Exporta para CSV
 */
function exportarCSV($filename, $dados) {
    $output_dir = __DIR__ . '/../public/reports/';
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0755, true);
    }

    $filepath = $output_dir . $filename . '.csv';

    $fp = fopen($filepath, 'w');

    // Exemplo básico - expandir conforme necessário
    foreach ($dados as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $row) {
                if (is_array($row)) {
                    fputcsv($fp, $row);
                }
            }
        }
    }

    fclose($fp);

    return [
        'success' => true,
        'path' => '/public/reports/' . $filename . '.csv',
        'full_path' => $filepath
    ];
}

/**
 * Gera link de compartilhamento para relatório
 */
function gerarLinkCompartilhamento($report_id, $expira_em_dias = 7) {
    $pdo = getDBConnection();

    try {
        $token = bin2hex(random_bytes(16));
        $expira_em = date('Y-m-d H:i:s', strtotime("+{$expira_em_dias} days"));

        $stmt = $pdo->prepare("
            UPDATE generated_reports
            SET compartilhado = TRUE,
                link_compartilhamento = ?,
                expira_em = ?
            WHERE id = ?
        ");

        $stmt->execute([$token, $expira_em, $report_id]);

        return [
            'success' => true,
            'link' => "/relatorio/compartilhado/{$token}",
            'expira_em' => $expira_em
        ];

    } catch (Exception $e) {
        error_log("Erro ao gerar link: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar link'];
    }
}
