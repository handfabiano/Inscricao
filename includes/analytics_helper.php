<?php
/**
 * Analytics Helper
 *
 * Funções para análises preditivas, identificação de talentos e BI
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ranking_helper.php';

// =====================================================
// PREVISÕES DE PARTIDAS
// =====================================================

/**
 * Prevê resultado de uma partida
 *
 * @param int $match_id ID da partida
 * @param int $model_id ID do modelo (padrão: 1)
 * @return array Previsão com probabilidades
 */
function preverResultadoPartida($match_id, $model_id = 1) {
    $pdo = getDBConnection();

    try {
        // Buscar partida
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            return ['success' => false, 'message' => 'Partida não encontrada'];
        }

        $casa_id = $match['equipe_casa_id'];
        $visitante_id = $match['equipe_visitante_id'];
        $modalidade_id = $match['modalidade_id'];

        // Buscar modelo
        $stmt = $pdo->prepare("SELECT * FROM prediction_models WHERE id = ? AND ativo = TRUE");
        $stmt->execute([$model_id]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$model) {
            return ['success' => false, 'message' => 'Modelo não encontrado'];
        }

        // Coletar features
        $features = coletarFeaturesPartida($casa_id, $visitante_id, $modalidade_id);

        // Calcular probabilidades
        $probabilidades = calcularProbabilidades($features, $model);

        // Prever gols esperados
        $gols_esperados = preverGolsEsperados($features);

        // Calcular confiança
        $confianca = calcularConfianca($features, $probabilidades);

        // Salvar previsão
        $stmt = $pdo->prepare("
            INSERT INTO match_predictions (
                match_id, model_id,
                vitoria_casa_prob, empate_prob, vitoria_visitante_prob,
                gols_esperados_casa, gols_esperados_visitante,
                confianca, fatores_analise
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $match_id,
            $model_id,
            $probabilidades['casa'],
            $probabilidades['empate'],
            $probabilidades['visitante'],
            $gols_esperados['casa'],
            $gols_esperados['visitante'],
            $confianca,
            json_encode($features)
        ]);

        return [
            'success' => true,
            'previsao' => [
                'vitoria_casa' => $probabilidades['casa'],
                'empate' => $probabilidades['empate'],
                'vitoria_visitante' => $probabilidades['visitante'],
                'gols_casa' => $gols_esperados['casa'],
                'gols_visitante' => $gols_esperados['visitante'],
                'resultado_mais_provavel' => $probabilidades['mais_provavel'],
                'confianca' => $confianca
            ],
            'features' => $features
        ];

    } catch (Exception $e) {
        error_log("Erro ao prever partida: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar previsão'];
    }
}

/**
 * Coleta features para análise da partida
 */
function coletarFeaturesPartida($casa_id, $visitante_id, $modalidade_id) {
    $pdo = getDBConnection();

    $features = [];

    // 1. ELO Rating (peso: 0.35)
    $elo_casa = getOrCreateTeamRanking($casa_id, $modalidade_id);
    $elo_visitante = getOrCreateTeamRanking($visitante_id, $modalidade_id);

    $features['elo_casa'] = $elo_casa['elo_rating'];
    $features['elo_visitante'] = $elo_visitante['elo_rating'];
    $features['elo_diferenca'] = $elo_casa['elo_rating'] - $elo_visitante['elo_rating'];

    // 2. Forma recente (últimos 5 jogos) (peso: 0.25)
    $features['forma_casa'] = calcularFormaRecente($casa_id, $modalidade_id, 5);
    $features['forma_visitante'] = calcularFormaRecente($visitante_id, $modalidade_id, 5);

    // 3. Confronto direto (peso: 0.15)
    $h2h = getHeadToHead($casa_id, $visitante_id, $modalidade_id);
    $features['h2h_vantagem_casa'] = $h2h['vantagem_casa'];

    // 4. Mando de campo (peso: 0.15)
    $features['aproveitamento_casa_mandante'] = $elo_casa['vitorias'] > 0 ?
        round(($elo_casa['vitorias'] / $elo_casa['total_partidas']) * 100, 2) : 50;

    // 5. Descanso (peso: 0.10)
    $features['dias_descanso_casa'] = getDiasDescanso($casa_id);
    $features['dias_descanso_visitante'] = getDiasDescanso($visitante_id);

    return $features;
}

/**
 * Calcula forma recente da equipe (últimos N jogos)
 */
function calcularFormaRecente($equipe_id, $modalidade_id, $num_jogos = 5) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT resultado,
               CASE
                   WHEN (equipe_casa_id = ? AND resultado = 'casa') OR
                        (equipe_visitante_id = ? AND resultado = 'visitante') THEN 3
                   WHEN resultado = 'empate' THEN 1
                   ELSE 0
               END as pontos
        FROM matches
        WHERE (equipe_casa_id = ? OR equipe_visitante_id = ?)
          AND modalidade_id = ?
          AND status = 'finalizado'
        ORDER BY data_hora DESC
        LIMIT ?
    ");

    $stmt->execute([$equipe_id, $equipe_id, $equipe_id, $equipe_id, $modalidade_id, $num_jogos]);
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jogos)) {
        return 50; // Neutro se não houver histórico
    }

    $pontos_total = array_sum(array_column($jogos, 'pontos'));
    $pontos_max = count($jogos) * 3;

    return round(($pontos_total / $pontos_max) * 100, 2);
}

/**
 * Obtém confronto direto entre duas equipes
 */
function getHeadToHead($equipe1_id, $equipe2_id, $modalidade_id) {
    $pdo = getDBConnection();

    // Garantir ordem
    if ($equipe1_id > $equipe2_id) {
        list($equipe1_id, $equipe2_id) = [$equipe2_id, $equipe1_id];
        $invertido = true;
    } else {
        $invertido = false;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM head_to_head
        WHERE equipe1_id = ? AND equipe2_id = ? AND modalidade_id = ?
    ");
    $stmt->execute([$equipe1_id, $equipe2_id, $modalidade_id]);
    $h2h = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$h2h) {
        return ['vantagem_casa' => 50]; // Neutro
    }

    $vit1 = $h2h['vitorias_equipe1'];
    $vit2 = $h2h['vitorias_equipe2'];

    $total = $vit1 + $vit2 + $h2h['empates'];
    if ($total == 0) {
        return ['vantagem_casa' => 50];
    }

    $vantagem = $invertido ?
        round(($vit2 / $total) * 100, 2) :
        round(($vit1 / $total) * 100, 2);

    return ['vantagem_casa' => $vantagem];
}

/**
 * Calcula dias de descanso desde último jogo
 */
function getDiasDescanso($equipe_id) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT data_hora
        FROM matches
        WHERE (equipe_casa_id = ? OR equipe_visitante_id = ?)
          AND status = 'finalizado'
        ORDER BY data_hora DESC
        LIMIT 1
    ");
    $stmt->execute([$equipe_id, $equipe_id]);
    $ultimo_jogo = $stmt->fetch(PDO::FETCH_COLUMN);

    if (!$ultimo_jogo) {
        return 7; // Padrão 7 dias se não houver histórico
    }

    $diff = (new DateTime())->diff(new DateTime($ultimo_jogo));
    return $diff->days;
}

/**
 * Calcula probabilidades usando regressão logística simplificada
 */
function calcularProbabilidades($features, $model) {
    $weights = json_decode($model['features'], true);

    // Normalizar features (0-100)
    $elo_diff_norm = max(-100, min(100, $features['elo_diferenca'] / 4)); // -400 a +400 -> -100 a 100
    $forma_diff = $features['forma_casa'] - $features['forma_visitante'];
    $h2h = $features['h2h_vantagem_casa'] - 50; // Centralizar em 0
    $descanso_diff = ($features['dias_descanso_visitante'] - $features['dias_descanso_casa']) * 2;

    // Score ponderado
    $score_casa = (
        $elo_diff_norm * $weights['elo_rating'] +
        $forma_diff * $weights['forma_recente'] +
        $h2h * $weights['confronto_direto'] +
        15 * $weights['mando_campo'] + // Vantagem fixa de 15%
        $descanso_diff * $weights['descanso_dias']
    );

    // Converter para probabilidades usando função logística
    $prob_casa = 1 / (1 + exp(-$score_casa / 20));

    // Ajustar para incluir empate (usando distribuição beta)
    $prob_empate = 0.25 * (1 - abs($score_casa) / 100); // Mais provável quando equilibrado
    $prob_casa_ajustada = $prob_casa * (1 - $prob_empate);
    $prob_visitante = (1 - $prob_empate - $prob_casa_ajustada);

    // Garantir soma = 100%
    $total = $prob_casa_ajustada + $prob_empate + $prob_visitante;
    $prob_casa_ajustada /= $total;
    $prob_empate /= $total;
    $prob_visitante /= $total;

    $probs = [
        'casa' => round($prob_casa_ajustada * 100, 2),
        'empate' => round($prob_empate * 100, 2),
        'visitante' => round($prob_visitante * 100, 2)
    ];

    // Determinar mais provável
    $probs['mais_provavel'] = array_keys($probs, max($probs))[0];

    return $probs;
}

/**
 * Prevê gols esperados usando distribuição de Poisson
 */
function preverGolsEsperados($features) {
    // Média de gols baseada em força relativa
    $forca_casa = ($features['elo_casa'] / 1500) * ($features['forma_casa'] / 100);
    $forca_visitante = ($features['elo_visitante'] / 1500) * ($features['forma_visitante'] / 100);

    $gols_casa = round($forca_casa * 2.5, 2); // 2.5 = média geral de gols
    $gols_visitante = round($forca_visitante * 2.0, 2); // Visitante sofre desvantagem

    return [
        'casa' => max(0, $gols_casa),
        'visitante' => max(0, $gols_visitante)
    ];
}

/**
 * Calcula confiança da previsão
 */
function calcularConfianca($features, $probabilidades) {
    // Confiança baseada em:
    // 1. Quantidade de dados disponíveis
    // 2. Clareza do resultado (quanto maior a diferença, mais confiante)

    $max_prob = max($probabilidades['casa'], $probabilidades['empate'], $probabilidades['visitante']);
    $clareza = ($max_prob - 33.33) / 66.67 * 100; // 33.33 = empate triplo

    // Reduzir confiança se faltar dados
    $fator_dados = 1.0;
    if ($features['forma_casa'] == 50) $fator_dados *= 0.8; // Sem histórico
    if ($features['h2h_vantagem_casa'] == 50) $fator_dados *= 0.9;

    $confianca = $clareza * $fator_dados;

    return round(max(0, min(100, $confianca)), 2);
}

/**
 * Atualiza resultado real da previsão após partida
 */
function atualizarResultadoPrevisao($match_id) {
    $pdo = getDBConnection();

    try {
        // Buscar partida finalizada
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ? AND status = 'finalizado'");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            return ['success' => false, 'message' => 'Partida não finalizada'];
        }

        // Buscar previsão
        $stmt = $pdo->prepare("SELECT * FROM match_predictions WHERE match_id = ?");
        $stmt->execute([$match_id]);
        $previsao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$previsao) {
            return ['success' => false, 'message' => 'Previsão não encontrada'];
        }

        $resultado_real = $match['resultado'];

        // Determinar se previsão estava correta
        $probs = [
            'casa' => $previsao['vitoria_casa_prob'],
            'empate' => $previsao['empate_prob'],
            'visitante' => $previsao['vitoria_visitante_prob']
        ];

        $previsto = array_keys($probs, max($probs))[0];
        $acertou = ($previsto === $resultado_real);

        // Atualizar previsão
        $stmt = $pdo->prepare("
            UPDATE match_predictions
            SET resultado_real = ?,
                previsao_correta = ?
            WHERE id = ?
        ");
        $stmt->execute([$resultado_real, $acertou, $previsao['id']]);

        return [
            'success' => true,
            'previsto' => $previsto,
            'real' => $resultado_real,
            'acertou' => $acertou
        ];

    } catch (Exception $e) {
        error_log("Erro ao atualizar previsão: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao atualizar'];
    }
}

// =====================================================
// IDENTIFICAÇÃO DE TALENTOS
// =====================================================

/**
 * Executa scout de talentos
 *
 * @param int $scout_id ID da configuração do scout
 * @param int $modalidade_id ID da modalidade
 * @return array Talentos identificados
 */
function executarScoutTalentos($scout_id, $modalidade_id) {
    $pdo = getDBConnection();

    try {
        // Buscar configuração do scout
        $stmt = $pdo->prepare("SELECT * FROM talent_scouts WHERE id = ?");
        $stmt->execute([$scout_id]);
        $scout = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$scout) {
            return ['success' => false, 'message' => 'Scout não encontrado'];
        }

        $criterios = json_decode($scout['criterios'], true);
        $min_jogos = $scout['min_jogos'];
        $max_idade = $scout['max_idade'];
        $score_minimo = $scout['score_minimo'];

        // Buscar atletas elegíveis
        $stmt = $pdo->prepare("
            SELECT
                a.*,
                ast.*,
                TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
            FROM atletas a
            INNER JOIN athlete_statistics ast ON a.id = ast.atleta_id
            WHERE ast.modalidade_id = ?
              AND ast.total_jogos >= ?
              AND TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) <= ?
        ");
        $stmt->execute([$modalidade_id, $min_jogos, $max_idade]);
        $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $talentos_identificados = [];

        foreach ($atletas as $atleta) {
            // Calcular score do talento
            $score_result = calcularTalentScore($atleta, $criterios);

            if ($score_result['score'] >= $score_minimo) {
                // Categorizar potencial
                $potencial = categorizarPotencial($score_result['score']);

                // Salvar ou atualizar talento
                $stmt = $pdo->prepare("
                    INSERT INTO identified_talents (
                        atleta_id, scout_id, modalidade_id,
                        talent_score, potencial, posicao_sugerida,
                        metricas_detalhadas, pontos_fortes, pontos_desenvolver
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        talent_score = VALUES(talent_score),
                        potencial = VALUES(potencial),
                        metricas_detalhadas = VALUES(metricas_detalhadas),
                        pontos_fortes = VALUES(pontos_fortes),
                        pontos_desenvolver = VALUES(pontos_desenvolver)
                ");

                $stmt->execute([
                    $atleta['id'],
                    $scout_id,
                    $modalidade_id,
                    $score_result['score'],
                    $potencial,
                    $atleta['posicao'] ?? 'A definir',
                    json_encode($score_result['metricas']),
                    $score_result['pontos_fortes'],
                    $score_result['pontos_desenvolver']
                ]);

                $talentos_identificados[] = [
                    'atleta_id' => $atleta['id'],
                    'nome' => $atleta['nome_completo'],
                    'score' => $score_result['score'],
                    'potencial' => $potencial
                ];
            }
        }

        // Atualizar scout
        $pdo->prepare("
            UPDATE talent_scouts
            SET ultima_execucao = NOW(),
                total_talentos_identificados = ?
            WHERE id = ?
        ")->execute([count($talentos_identificados), $scout_id]);

        return [
            'success' => true,
            'talentos' => $talentos_identificados,
            'total' => count($talentos_identificados)
        ];

    } catch (Exception $e) {
        error_log("Erro ao executar scout: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao identificar talentos'];
    }
}

/**
 * Calcula score de talento baseado em critérios
 */
function calcularTalentScore($atleta, $criterios) {
    $metricas = [];
    $score_total = 0;

    // 1. Nota média (peso variável)
    if (isset($criterios['nota_media'])) {
        $nota_normalizada = ($atleta['nota_media'] / 10) * 100;
        $score_nota = $nota_normalizada * ($criterios['nota_media'] / 100);
        $metricas['nota_media'] = $score_nota;
        $score_total += $score_nota;
    }

    // 2. Gols por jogo (peso variável)
    if (isset($criterios['gols_por_jogo'])) {
        $gols_por_jogo = $atleta['total_jogos'] > 0 ?
            $atleta['total_gols'] / $atleta['total_jogos'] : 0;
        // Normalizar (1 gol/jogo = 100%)
        $score_gols = min(100, $gols_por_jogo * 100) * ($criterios['gols_por_jogo'] / 100);
        $metricas['gols_por_jogo'] = $score_gols;
        $score_total += $score_gols;
    }

    // 3. Assistências por jogo (peso variável)
    if (isset($criterios['assistencias_por_jogo'])) {
        $assist_por_jogo = $atleta['total_jogos'] > 0 ?
            $atleta['total_assistencias'] / $atleta['total_jogos'] : 0;
        $score_assist = min(100, $assist_por_jogo * 150) * ($criterios['assistencias_por_jogo'] / 100);
        $metricas['assistencias_por_jogo'] = $score_assist;
        $score_total += $score_assist;
    }

    // 4. Consistência (variação da nota) (peso variável)
    if (isset($criterios['consistencia'])) {
        // Simulado - em produção, calcular desvio padrão real
        $consistencia = 80; // Placeholder
        $score_consistencia = $consistencia * ($criterios['consistencia'] / 100);
        $metricas['consistencia'] = $score_consistencia;
        $score_total += $score_consistencia;
    }

    // 5. Evolução (melhoria ao longo do tempo) (peso variável)
    if (isset($criterios['evolucao'])) {
        // Simulado - em produção, comparar performance de períodos
        $evolucao = 75; // Placeholder
        $score_evolucao = $evolucao * ($criterios['evolucao'] / 100);
        $metricas['evolucao'] = $score_evolucao;
        $score_total += $score_evolucao;
    }

    // 6. Disciplina (menos cartões = melhor) (peso variável)
    if (isset($criterios['disciplina'])) {
        $cartoes_por_jogo = $atleta['total_jogos'] > 0 ?
            ($atleta['cartoes_amarelos'] + $atleta['cartoes_vermelhos'] * 2) / $atleta['total_jogos'] : 0;
        $score_disciplina = max(0, 100 - ($cartoes_por_jogo * 20)) * ($criterios['disciplina'] / 100);
        $metricas['disciplina'] = $score_disciplina;
        $score_total += $score_disciplina;
    }

    // 7. Versatilidade (simulado) (peso variável)
    if (isset($criterios['versatilidade'])) {
        $versatilidade = 70; // Placeholder
        $score_versatilidade = $versatilidade * ($criterios['versatilidade'] / 100);
        $metricas['versatilidade'] = $score_versatilidade;
        $score_total += $score_versatilidade;
    }

    // Análise qualitativa
    $pontos_fortes = [];
    $pontos_desenvolver = [];

    if ($metricas['gols_por_jogo'] ?? 0 > 15) {
        $pontos_fortes[] = "Excelente finalizador";
    }
    if ($metricas['assistencias_por_jogo'] ?? 0 > 12) {
        $pontos_fortes[] = "Ótima visão de jogo";
    }
    if ($metricas['disciplina'] ?? 0 > 8) {
        $pontos_fortes[] = "Conduta disciplinar exemplar";
    }
    if ($metricas['disciplina'] ?? 0 < 5) {
        $pontos_desenvolver[] = "Melhorar controle emocional";
    }

    return [
        'score' => round($score_total, 2),
        'metricas' => $metricas,
        'pontos_fortes' => implode('; ', $pontos_fortes),
        'pontos_desenvolver' => implode('; ', $pontos_desenvolver)
    ];
}

/**
 * Categoriza potencial baseado no score
 */
function categorizarPotencial($score) {
    if ($score >= 85) {
        return 'elite';
    } elseif ($score >= 75) {
        return 'destaque';
    } else {
        return 'promissor';
    }
}

// =====================================================
// ANÁLISE DE PADRÕES E INSIGHTS
// =====================================================

/**
 * Gera insights automáticos de performance
 *
 * @param string $tipo team, player, competition
 * @param int $entidade_id ID da entidade
 * @return array Insights gerados
 */
function gerarInsights($tipo, $entidade_id) {
    $pdo = getDBConnection();
    $insights = [];

    try {
        switch ($tipo) {
            case 'team':
                $insights = gerarInsightsEquipe($entidade_id);
                break;
            case 'player':
                $insights = gerarInsightsJogador($entidade_id);
                break;
            case 'competition':
                $insights = gerarInsightsCompeticao($entidade_id);
                break;
        }

        // Salvar insights no banco
        foreach ($insights as $insight) {
            $stmt = $pdo->prepare("
                INSERT INTO performance_insights (
                    tipo, entidade_id, categoria, titulo, descricao,
                    metricas_relacionadas, confianca, prioridade, acoes_sugeridas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $tipo,
                $entidade_id,
                $insight['categoria'],
                $insight['titulo'],
                $insight['descricao'],
                json_encode($insight['metricas'] ?? []),
                $insight['confianca'] ?? 80,
                $insight['prioridade'] ?? 'media',
                json_encode($insight['acoes'] ?? [])
            ]);
        }

        return ['success' => true, 'insights' => $insights, 'total' => count($insights)];

    } catch (Exception $e) {
        error_log("Erro ao gerar insights: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar insights'];
    }
}

/**
 * Gera insights para uma equipe
 */
function gerarInsightsEquipe($equipe_id) {
    $pdo = getDBConnection();
    $insights = [];

    // Buscar dados da equipe
    $stmt = $pdo->prepare("
        SELECT * FROM team_rankings
        WHERE equipe_id = ?
        ORDER BY ultima_atualizacao DESC
        LIMIT 1
    ");
    $stmt->execute([$equipe_id]);
    $ranking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ranking) {
        return $insights;
    }

    // Insight 1: Sequência de vitórias
    if ($ranking['sequencia_vitorias'] >= 3) {
        $insights[] = [
            'categoria' => 'performance',
            'titulo' => 'Equipe em excelente momento',
            'descricao' => "Sequência de {$ranking['sequencia_vitorias']} vitórias consecutivas. A equipe está consistente e confiante.",
            'prioridade' => 'alta',
            'confianca' => 95,
            'acoes' => ['Manter escalação', 'Reforçar treinos táticos', 'Monitorar desgaste físico']
        ];
    }

    // Insight 2: Sequência de derrotas
    if ($ranking['sequencia_derrotas'] >= 3) {
        $insights[] = [
            'categoria' => 'risco',
            'titulo' => 'Alerta: Queda de performance',
            'descricao' => "Sequência de {$ranking['sequencia_derrotas']} derrotas. Necessário análise técnica e psicológica.",
            'prioridade' => 'critica',
            'confianca' => 90,
            'acoes' => ['Revisar tática', 'Sessão psicológica', 'Avaliar condição física']
        ];
    }

    // Insight 3: ELO em ascensão
    $elo_variacao = $ranking['elo_rating'] - ($ranking['elo_mes_anterior'] ?? 1500);
    if ($elo_variacao > 50) {
        $insights[] = [
            'categoria' => 'tendencia',
            'titulo' => 'Evolução consistente no ranking',
            'descricao' => "ELO aumentou {$elo_variacao} pontos no último mês. Equipe em trajetória ascendente.",
            'prioridade' => 'media',
            'confianca' => 85,
            'acoes' => ['Buscar competições de nível superior', 'Reforçar elenco']
        ];
    }

    // Insight 4: Saldo de gols negativo
    $saldo = $ranking['gols_pro'] - $ranking['gols_contra'];
    if ($saldo < -10 && $ranking['total_partidas'] > 5) {
        $insights[] = [
            'categoria' => 'risco',
            'titulo' => 'Defesa vulnerável',
            'descricao' => "Saldo de gols negativo ({$saldo}). Sistema defensivo precisa de atenção.",
            'prioridade' => 'alta',
            'confianca' => 88,
            'acoes' => ['Treinos defensivos intensivos', 'Avaliar goleiro', 'Ajustar esquema tático']
        ];
    }

    return $insights;
}

/**
 * Gera insights para um jogador
 */
function gerarInsightsJogador($atleta_id) {
    $pdo = getDBConnection();
    $insights = [];

    // Buscar estatísticas
    $stmt = $pdo->prepare("SELECT * FROM athlete_statistics WHERE atleta_id = ?");
    $stmt->execute([$atleta_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats) {
        return $insights;
    }

    // Insight: Artilheiro em potencial
    $gols_por_jogo = $stats['total_jogos'] > 0 ? $stats['total_gols'] / $stats['total_jogos'] : 0;
    if ($gols_por_jogo > 0.7) {
        $insights[] = [
            'categoria' => 'performance',
            'titulo' => 'Artilheiro consistente',
            'descricao' => sprintf("Média de %.2f gols por jogo. Desempenho ofensivo excepcional.", $gols_por_jogo),
            'prioridade' => 'alta',
            'confianca' => 92
        ];
    }

    // Insight: Disciplina
    if ($stats['cartoes_vermelhos'] >= 3) {
        $insights[] = [
            'categoria' => 'risco',
            'titulo' => 'Problemas disciplinares',
            'descricao' => "{$stats['cartoes_vermelhos']} cartões vermelhos. Comportamento precisa melhorar.",
            'prioridade' => 'alta',
            'confianca' => 95,
            'acoes' => ['Trabalho psicológico', 'Conversas individuais', 'Penalizações se necessário']
        ];
    }

    return $insights;
}

/**
 * Gera insights para uma competição
 */
function gerarInsightsCompeticao($competicao_id) {
    // Placeholder - implementar conforme necessidade
    return [];
}
