<?php
/**
 * Championship Helper
 *
 * Funções para gerar chaveamentos automáticos e gerenciar competições
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gera chaveamento de eliminatória simples
 *
 * @param int $competicao_id ID da competição
 * @param array $equipes_ids IDs das equipes
 * @param array $opcoes Opções (data_inicio, intervalo_dias, etc)
 * @return array Resultado da geração
 */
function gerarChaveamentoEliminatoriaSimples($competicao_id, $equipes_ids, $opcoes = []) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $num_equipes = count($equipes_ids);

        // Verificar se é potência de 2
        if (!isPowerOfTwo($num_equipes)) {
            // Adicionar byes para próxima potência de 2
            $proxima_potencia = nextPowerOfTwo($num_equipes);
            $num_byes = $proxima_potencia - $num_equipes;

            // Equipes com bye passam direto
            $equipes_ids = array_pad($equipes_ids, $proxima_potencia, null);
        }

        // Embaralhar equipes (sorteio)
        if ($opcoes['sortear'] ?? true) {
            shuffle($equipes_ids);
        }

        // Criar fases
        $num_fases = log($num_equipes, 2);
        $fases_nomes = ['Final', 'Semifinal', 'Quartas', 'Oitavas', '16-avos', '32-avos'];

        for ($i = 0; $i < ceil($num_fases); $i++) {
            $ordem = ceil($num_fases) - $i;
            $nome_fase = $fases_nomes[ceil($num_fases) - $i - 1] ?? "Fase $ordem";

            $stmt = $pdo->prepare("
                INSERT INTO competition_phases (competicao_id, nome, tipo, ordem, formato_id, num_equipes)
                SELECT ?, ?, ?, ?, id, ?
                FROM competition_formats
                WHERE codigo = 'eliminatoria_simples'
            ");
            $stmt->execute([$competicao_id, $nome_fase, strtolower(str_replace('-', '_', $nome_fase)), $ordem, pow(2, $i + 1)]);
        }

        // Gerar jogos da primeira fase
        $phase_id = $pdo->query("
            SELECT id FROM competition_phases
            WHERE competicao_id = $competicao_id
            ORDER BY ordem DESC
            LIMIT 1
        ")->fetchColumn();

        $data_inicio = new DateTime($opcoes['data_inicio'] ?? '+7 days');
        $intervalo_dias = $opcoes['intervalo_dias'] ?? 7;

        for ($i = 0; $i < count($equipes_ids); $i += 2) {
            if ($equipes_ids[$i] !== null && $equipes_ids[$i + 1] !== null) {
                $stmt = $pdo->prepare("
                    INSERT INTO matches (
                        competicao_id, phase_id, equipe_casa_id, equipe_visitante_id,
                        data_hora, status
                    ) VALUES (?, ?, ?, ?, ?, 'agendado')
                ");

                $stmt->execute([
                    $competicao_id,
                    $phase_id,
                    $equipes_ids[$i],
                    $equipes_ids[$i + 1],
                    $data_inicio->format('Y-m-d H:i:s')
                ]);

                $data_inicio->modify("+$intervalo_dias days");
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Chaveamento gerado com sucesso',
            'num_fases' => ceil($num_fases),
            'num_jogos_primeira_fase' => count($equipes_ids) / 2
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao gerar chaveamento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar chaveamento'];
    }
}

/**
 * Gera chaveamento de pontos corridos (todos contra todos)
 *
 * @param int $competicao_id ID da competição
 * @param array $equipes_ids IDs das equipes
 * @param array $opcoes Opções
 * @return array Resultado
 */
function gerarChaveamentoPontosCorridos($competicao_id, $equipes_ids, $opcoes = []) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $num_equipes = count($equipes_ids);
        $ida_volta = $opcoes['ida_volta'] ?? true;

        // Criar fase única
        $stmt = $pdo->prepare("
            INSERT INTO competition_phases (competicao_id, nome, tipo, ordem, formato_id, num_equipes, jogos_ida_volta)
            SELECT ?, 'Pontos Corridos', 'pontos_corridos', 1, id, ?, ?
            FROM competition_formats
            WHERE codigo = 'pontos_corridos'
        ");
        $stmt->execute([$competicao_id, $num_equipes, $ida_volta]);
        $phase_id = $pdo->lastInsertId();

        // Gerar rodadas (algoritmo round-robin)
        $rodadas = gerarRodadasRoundRobin($equipes_ids);

        $data_inicio = new DateTime($opcoes['data_inicio'] ?? '+7 days');
        $intervalo_dias = $opcoes['intervalo_dias'] ?? 7;
        $rodada_num = 1;

        foreach ($rodadas as $rodada) {
            foreach ($rodada as $jogo) {
                $stmt = $pdo->prepare("
                    INSERT INTO matches (
                        competicao_id, phase_id, equipe_casa_id, equipe_visitante_id,
                        data_hora, status, rodada
                    ) VALUES (?, ?, ?, ?, ?, 'agendado', ?)
                ");

                $stmt->execute([
                    $competicao_id,
                    $phase_id,
                    $jogo[0],
                    $jogo[1],
                    $data_inicio->format('Y-m-d H:i:s'),
                    $rodada_num
                ]);
            }

            $data_inicio->modify("+$intervalo_dias days");
            $rodada_num++;
        }

        // Se ida e volta, duplicar jogos invertendo mando
        if ($ida_volta) {
            $stmt = $pdo->prepare("
                INSERT INTO matches (
                    competicao_id, phase_id, equipe_casa_id, equipe_visitante_id,
                    data_hora, status, rodada, tipo_jogo
                )
                SELECT competicao_id, phase_id, equipe_visitante_id, equipe_casa_id,
                       DATE_ADD(data_hora, INTERVAL ? DAY), status, rodada + ?, 'volta'
                FROM matches
                WHERE competicao_id = ? AND phase_id = ?
            ");

            $intervalo_turno = ($rodada_num - 1) * $intervalo_dias;
            $stmt->execute([$intervalo_turno, $rodada_num - 1, $competicao_id, $phase_id]);
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Chaveamento gerado com sucesso',
            'num_rodadas' => $ida_volta ? ($rodada_num - 1) * 2 : $rodada_num - 1,
            'total_jogos' => $ida_volta ? $num_equipes * ($num_equipes - 1) : $num_equipes * ($num_equipes - 1) / 2
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao gerar pontos corridos: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar chaveamento'];
    }
}

/**
 * Gera chaveamento de grupos + mata-mata
 *
 * @param int $competicao_id ID da competição
 * @param array $equipes_ids IDs das equipes
 * @param array $opcoes Opções (num_grupos, classificados_por_grupo, etc)
 * @return array Resultado
 */
function gerarChaveamentoGrupos($competicao_id, $equipes_ids, $opcoes = []) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $num_equipes = count($equipes_ids);
        $num_grupos = $opcoes['num_grupos'] ?? 4;
        $classificados_por_grupo = $opcoes['classificados_por_grupo'] ?? 2;

        // Embaralhar equipes
        if ($opcoes['sortear'] ?? true) {
            shuffle($equipes_ids);
        }

        // Criar fase de grupos
        $stmt = $pdo->prepare("
            INSERT INTO competition_phases (competicao_id, nome, tipo, ordem, formato_id, num_equipes, num_classificados)
            SELECT ?, 'Fase de Grupos', 'grupos', 1, id, ?, ?
            FROM competition_formats
            WHERE codigo = 'grupos_mata_mata'
        ");
        $stmt->execute([$competicao_id, $num_equipes, $num_grupos * $classificados_por_grupo]);
        $phase_id = $pdo->lastInsertId();

        // Criar grupos e distribuir equipes
        $letras = range('A', 'Z');
        $equipes_por_grupo = ceil($num_equipes / $num_grupos);

        for ($g = 0; $g < $num_grupos; $g++) {
            // Criar grupo
            $stmt = $pdo->prepare("
                INSERT INTO competition_groups (phase_id, nome, ordem)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$phase_id, $letras[$g], $g + 1]);
            $group_id = $pdo->lastInsertId();

            // Adicionar equipes ao grupo
            $inicio = $g * $equipes_por_grupo;
            $fim = min(($g + 1) * $equipes_por_grupo, $num_equipes);

            for ($i = $inicio; $i < $fim; $i++) {
                if (isset($equipes_ids[$i])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO group_teams (group_id, equipe_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$group_id, $equipes_ids[$i]]);
                }
            }

            // Gerar jogos do grupo (todos contra todos)
            $equipes_grupo = array_slice($equipes_ids, $inicio, $fim - $inicio);
            $rodadas = gerarRodadasRoundRobin($equipes_grupo);

            $data_inicio = new DateTime($opcoes['data_inicio'] ?? '+7 days');
            $intervalo_dias = $opcoes['intervalo_dias'] ?? 3;
            $rodada_num = 1;

            foreach ($rodadas as $rodada) {
                foreach ($rodada as $jogo) {
                    $stmt = $pdo->prepare("
                        INSERT INTO matches (
                            competicao_id, phase_id, group_id, equipe_casa_id, equipe_visitante_id,
                            data_hora, status, rodada
                        ) VALUES (?, ?, ?, ?, ?, ?, 'agendado', ?)
                    ");

                    $stmt->execute([
                        $competicao_id,
                        $phase_id,
                        $group_id,
                        $jogo[0],
                        $jogo[1],
                        $data_inicio->format('Y-m-d H:i:s'),
                        $rodada_num
                    ]);
                }

                $data_inicio->modify("+$intervalo_dias days");
                $rodada_num++;
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Grupos gerados com sucesso',
            'num_grupos' => $num_grupos,
            'equipes_por_grupo' => $equipes_por_grupo
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao gerar grupos: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao gerar grupos'];
    }
}

/**
 * Gera rodadas usando algoritmo Round-Robin
 *
 * @param array $equipes Array de IDs das equipes
 * @return array Rodadas com jogos
 */
function gerarRodadasRoundRobin($equipes) {
    $num_equipes = count($equipes);

    // Se número ímpar, adicionar "bye"
    if ($num_equipes % 2 !== 0) {
        $equipes[] = null; // bye
        $num_equipes++;
    }

    $num_rodadas = $num_equipes - 1;
    $jogos_por_rodada = $num_equipes / 2;

    $rodadas = [];

    for ($rodada = 0; $rodada < $num_rodadas; $rodada++) {
        $jogos_rodada = [];

        for ($jogo = 0; $jogo < $jogos_por_rodada; $jogo++) {
            $casa = ($rodada + $jogo) % ($num_equipes - 1);
            $visitante = ($num_equipes - 1 - $jogo + $rodada) % ($num_equipes - 1);

            // Último sempre fica fixo
            if ($jogo == 0) {
                $visitante = $num_equipes - 1;
            }

            // Alternar mando de campo
            if ($rodada % 2 == 1) {
                list($casa, $visitante) = [$visitante, $casa];
            }

            // Não adicionar se algum é bye
            if ($equipes[$casa] !== null && $equipes[$visitante] !== null) {
                $jogos_rodada[] = [$equipes[$casa], $equipes[$visitante]];
            }
        }

        if (!empty($jogos_rodada)) {
            $rodadas[] = $jogos_rodada;
        }
    }

    return $rodadas;
}

/**
 * Verifica se número é potência de 2
 */
function isPowerOfTwo($n) {
    return ($n > 0) && (($n & ($n - 1)) == 0);
}

/**
 * Retorna próxima potência de 2
 */
function nextPowerOfTwo($n) {
    $power = 1;
    while ($power < $n) {
        $power *= 2;
    }
    return $power;
}

/**
 * Atualiza classificação de um grupo
 *
 * @param int $group_id ID do grupo
 */
function atualizarClassificacaoGrupo($group_id) {
    $pdo = getDBConnection();

    try {
        // Buscar todos os jogos do grupo finalizados
        $stmt = $pdo->prepare("
            SELECT * FROM matches
            WHERE group_id = ? AND status = 'finalizado'
        ");
        $stmt->execute([$group_id]);
        $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Resetar estatísticas
        $pdo->prepare("
            UPDATE group_teams
            SET jogos = 0, vitorias = 0, empates = 0, derrotas = 0,
                gols_pro = 0, gols_contra = 0, saldo_gols = 0, pontos = 0
            WHERE group_id = ?
        ")->execute([$group_id]);

        // Processar cada jogo
        foreach ($jogos as $jogo) {
            $casa_id = $jogo['equipe_casa_id'];
            $visitante_id = $jogo['equipe_visitante_id'];
            $placar_casa = $jogo['placar_casa'];
            $placar_visitante = $jogo['placar_visitante'];

            // Atualizar casa
            atualizarEstatisticasEquipeGrupo($group_id, $casa_id, $placar_casa, $placar_visitante);

            // Atualizar visitante
            atualizarEstatisticasEquipeGrupo($group_id, $visitante_id, $placar_visitante, $placar_casa);
        }

        // Ordenar por pontos, vitórias, saldo
        $stmt = $pdo->prepare("
            SELECT id FROM group_teams
            WHERE group_id = ?
            ORDER BY pontos DESC, vitorias DESC, saldo_gols DESC, gols_pro DESC
        ");
        $stmt->execute([$group_id]);
        $equipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Atualizar posições
        $posicao = 1;
        foreach ($equipes as $equipe_id) {
            $pdo->prepare("UPDATE group_teams SET posicao = ? WHERE id = ?")
                ->execute([$posicao, $equipe_id]);
            $posicao++;
        }

        return true;

    } catch (Exception $e) {
        error_log("Erro ao atualizar classificação: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza estatísticas de uma equipe no grupo
 */
function atualizarEstatisticasEquipeGrupo($group_id, $equipe_id, $gols_pro, $gols_contra) {
    $pdo = getDBConnection();

    $pontos = 0;
    $vitorias = 0;
    $empates = 0;
    $derrotas = 0;

    if ($gols_pro > $gols_contra) {
        $pontos = 3;
        $vitorias = 1;
    } elseif ($gols_pro == $gols_contra) {
        $pontos = 1;
        $empates = 1;
    } else {
        $derrotas = 1;
    }

    $saldo = $gols_pro - $gols_contra;

    $stmt = $pdo->prepare("
        UPDATE group_teams
        SET jogos = jogos + 1,
            vitorias = vitorias + ?,
            empates = empates + ?,
            derrotas = derrotas + ?,
            gols_pro = gols_pro + ?,
            gols_contra = gols_contra + ?,
            saldo_gols = saldo_gols + ?,
            pontos = pontos + ?
        WHERE group_id = ? AND equipe_id = ?
    ");

    $stmt->execute([
        $vitorias, $empates, $derrotas,
        $gols_pro, $gols_contra, $saldo, $pontos,
        $group_id, $equipe_id
    ]);
}

/**
 * Registra resultado de partida
 *
 * @param int $match_id ID da partida
 * @param int $placar_casa Gols da casa
 * @param int $placar_visitante Gols do visitante
 * @param array $detalhes Detalhes adicionais
 * @return bool Sucesso
 */
function registrarResultado($match_id, $placar_casa, $placar_visitante, $detalhes = []) {
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Determinar resultado
        if ($placar_casa > $placar_visitante) {
            $resultado = 'casa';
            $vencedor_id = $detalhes['equipe_casa_id'] ?? null;
        } elseif ($placar_visitante > $placar_casa) {
            $resultado = 'visitante';
            $vencedor_id = $detalhes['equipe_visitante_id'] ?? null;
        } else {
            $resultado = 'empate';
            $vencedor_id = null;
        }

        // Atualizar partida
        $stmt = $pdo->prepare("
            UPDATE matches
            SET placar_casa = ?,
                placar_visitante = ?,
                resultado = ?,
                vencedor_id = ?,
                status = 'finalizado'
            WHERE id = ?
        ");
        $stmt->execute([$placar_casa, $placar_visitante, $resultado, $vencedor_id, $match_id]);

        // Se jogo de grupo, atualizar classificação
        $stmt = $pdo->prepare("SELECT group_id FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $group_id = $stmt->fetchColumn();

        if ($group_id) {
            atualizarClassificacaoGrupo($group_id);
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao registrar resultado: " . $e->getMessage());
        return false;
    }
}
