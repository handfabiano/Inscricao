<?php
/**
 * Ranking Helper
 *
 * Funções para cálculo de rankings ELO e gerenciamento de rankings
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Calcula mudança de ELO rating
 *
 * @param int $elo_atual ELO atual da equipe
 * @param int $elo_oponente ELO do oponente
 * @param float $resultado 1 = vitória, 0.5 = empate, 0 = derrota
 * @param int $k_factor Fator K (importância da partida, padrão 32)
 * @return int Nova pontuação ELO
 */
function calcularELO($elo_atual, $elo_oponente, $resultado, $k_factor = 32) {
    // Probabilidade esperada de vitória
    $expected = 1 / (1 + pow(10, ($elo_oponente - $elo_atual) / 400));

    // Nova pontuação
    $new_elo = $elo_atual + ($k_factor * ($resultado - $expected));

    return round($new_elo);
}

/**
 * Atualiza ELO após partida
 *
 * @param int $match_id ID da partida
 * @param int $modalidade_id ID da modalidade
 * @return array Resultado da atualização
 */
function atualizarELOAposPartida($match_id, $modalidade_id) {
    $pdo = getDBConnection();

    try {
        // Buscar partida
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ? AND status = 'finalizado'");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            return ['success' => false, 'message' => 'Partida não encontrada ou não finalizada'];
        }

        $casa_id = $match['equipe_casa_id'];
        $visitante_id = $match['equipe_visitante_id'];

        // Buscar ou criar rankings
        $elo_casa = getOrCreateTeamRanking($casa_id, $modalidade_id);
        $elo_visitante = getOrCreateTeamRanking($visitante_id, $modalidade_id);

        // Determinar resultado para casa (1 = vitória, 0.5 = empate, 0 = derrota)
        $resultado_casa = 0;
        if ($match['resultado'] === 'casa') {
            $resultado_casa = 1;
        } elseif ($match['resultado'] === 'empate') {
            $resultado_casa = 0.5;
        }

        $resultado_visitante = 1 - $resultado_casa;

        // Calcular novos ELOs
        $novo_elo_casa = calcularELO($elo_casa['elo_rating'], $elo_visitante['elo_rating'], $resultado_casa);
        $novo_elo_visitante = calcularELO($elo_visitante['elo_rating'], $elo_casa['elo_rating'], $resultado_visitante);

        // Atualizar banco
        $pdo->beginTransaction();

        // Atualizar ranking da casa
        atualizarRanking($elo_casa['id'], $novo_elo_casa, $match, 'casa');

        // Atualizar ranking do visitante
        atualizarRanking($elo_visitante['id'], $novo_elo_visitante, $match, 'visitante');

        // Registrar histórico
        registrarHistoricoELO($elo_casa['id'], $match_id, $elo_casa['elo_rating'], $novo_elo_casa, $match['resultado'], $visitante_id, $elo_visitante['elo_rating']);
        registrarHistoricoELO($elo_visitante['id'], $match_id, $elo_visitante['elo_rating'], $novo_elo_visitante, $match['resultado'] === 'casa' ? 'derrota' : ($match['resultado'] === 'visitante' ? 'vitoria' : 'empate'), $casa_id, $elo_casa['elo_rating']);

        // Atualizar confronto direto
        atualizarHeadToHead($casa_id, $visitante_id, $modalidade_id, $match_id, $match);

        $pdo->commit();

        return [
            'success' => true,
            'elo_casa_antes' => $elo_casa['elo_rating'],
            'elo_casa_depois' => $novo_elo_casa,
            'variacao_casa' => $novo_elo_casa - $elo_casa['elo_rating'],
            'elo_visitante_antes' => $elo_visitante['elo_rating'],
            'elo_visitante_depois' => $novo_elo_visitante,
            'variacao_visitante' => $novo_elo_visitante - $elo_visitante['elo_rating']
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar ELO: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao atualizar ELO'];
    }
}

/**
 * Busca ou cria ranking de equipe
 */
function getOrCreateTeamRanking($equipe_id, $modalidade_id) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM team_rankings WHERE equipe_id = ? AND modalidade_id = ?");
    $stmt->execute([$equipe_id, $modalidade_id]);
    $ranking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ranking) {
        // Criar novo ranking com ELO inicial 1500
        $stmt = $pdo->prepare("
            INSERT INTO team_rankings (equipe_id, modalidade_id, elo_rating, elo_peak)
            VALUES (?, ?, 1500, 1500)
        ");
        $stmt->execute([$equipe_id, $modalidade_id]);

        return [
            'id' => $pdo->lastInsertId(),
            'equipe_id' => $equipe_id,
            'modalidade_id' => $modalidade_id,
            'elo_rating' => 1500,
            'elo_peak' => 1500,
            'total_partidas' => 0,
            'vitorias' => 0,
            'empates' => 0,
            'derrotas' => 0
        ];
    }

    return $ranking;
}

/**
 * Atualiza dados do ranking
 */
function atualizarRanking($ranking_id, $novo_elo, $match, $tipo) {
    $pdo = getDBConnection();

    $placar_pro = $tipo === 'casa' ? $match['placar_casa'] : $match['placar_visitante'];
    $placar_contra = $tipo === 'casa' ? $match['placar_visitante'] : $match['placar_casa'];

    $vitoria = ($tipo === 'casa' && $match['resultado'] === 'casa') || ($tipo === 'visitante' && $match['resultado'] === 'visitante');
    $empate = $match['resultado'] === 'empate';
    $derrota = !$vitoria && !$empate;

    $stmt = $pdo->prepare("
        UPDATE team_rankings
        SET elo_rating = ?,
            elo_peak = GREATEST(elo_peak, ?),
            total_partidas = total_partidas + 1,
            vitorias = vitorias + ?,
            empates = empates + ?,
            derrotas = derrotas + ?,
            gols_pro = gols_pro + ?,
            gols_contra = gols_contra + ?,
            sequencia_vitorias = IF(? = 1, sequencia_vitorias + 1, 0),
            sequencia_derrotas = IF(? = 1, sequencia_derrotas + 1, 0),
            maior_sequencia_vitorias = GREATEST(maior_sequencia_vitorias, IF(? = 1, sequencia_vitorias + 1, sequencia_vitorias)),
            maior_sequencia_derrotas = GREATEST(maior_sequencia_derrotas, IF(? = 1, sequencia_derrotas + 1, sequencia_derrotas))
        WHERE id = ?
    ");

    $stmt->execute([
        $novo_elo,
        $novo_elo,
        $vitoria ? 1 : 0,
        $empate ? 1 : 0,
        $derrota ? 1 : 0,
        $placar_pro,
        $placar_contra,
        $vitoria ? 1 : 0,
        $derrota ? 1 : 0,
        $vitoria ? 1 : 0,
        $derrota ? 1 : 0,
        $ranking_id
    ]);
}

/**
 * Registra histórico de mudança no ELO
 */
function registrarHistoricoELO($ranking_id, $match_id, $elo_antes, $elo_depois, $resultado, $oponente_id, $oponente_elo) {
    $pdo = getDBConnection();

    $variacao = $elo_depois - $elo_antes;

    $stmt = $pdo->prepare("
        INSERT INTO elo_history (
            ranking_id, match_id, elo_antes, elo_depois, variacao,
            oponente_id, oponente_elo_antes, resultado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $ranking_id,
        $match_id,
        $elo_antes,
        $elo_depois,
        $variacao,
        $oponente_id,
        $oponente_elo,
        $resultado
    ]);
}

/**
 * Atualiza confronto direto
 */
function atualizarHeadToHead($equipe1_id, $equipe2_id, $modalidade_id, $match_id, $match) {
    $pdo = getDBConnection();

    // Garantir ordem (menor ID primeiro)
    if ($equipe1_id > $equipe2_id) {
        list($equipe1_id, $equipe2_id) = [$equipe2_id, $equipe1_id];
        $invertido = true;
    } else {
        $invertido = false;
    }

    // Buscar ou criar
    $stmt = $pdo->prepare("
        SELECT * FROM head_to_head
        WHERE equipe1_id = ? AND equipe2_id = ? AND modalidade_id = ?
    ");
    $stmt->execute([$equipe1_id, $equipe2_id, $modalidade_id]);
    $h2h = $stmt->fetch(PDO::FETCH_ASSOC);

    $gols1 = $invertido ? $match['placar_visitante'] : $match['placar_casa'];
    $gols2 = $invertido ? $match['placar_casa'] : $match['placar_visitante'];

    $vit1 = 0;
    $emp = 0;
    $vit2 = 0;

    if ($gols1 > $gols2) {
        $vit1 = 1;
    } elseif ($gols1 === $gols2) {
        $emp = 1;
    } else {
        $vit2 = 1;
    }

    if (!$h2h) {
        // Criar
        $stmt = $pdo->prepare("
            INSERT INTO head_to_head (
                equipe1_id, equipe2_id, modalidade_id,
                total_jogos, vitorias_equipe1, empates, vitorias_equipe2,
                gols_equipe1, gols_equipe2,
                ultimo_jogo_id, ultimo_jogo_data, ultimo_resultado
            ) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $equipe1_id, $equipe2_id, $modalidade_id,
            $vit1, $emp, $vit2, $gols1, $gols2,
            $match_id, $match['data_hora'], $match['resultado']
        ]);
    } else {
        // Atualizar
        $stmt = $pdo->prepare("
            UPDATE head_to_head
            SET total_jogos = total_jogos + 1,
                vitorias_equipe1 = vitorias_equipe1 + ?,
                empates = empates + ?,
                vitorias_equipe2 = vitorias_equipe2 + ?,
                gols_equipe1 = gols_equipe1 + ?,
                gols_equipe2 = gols_equipe2 + ?,
                ultimo_jogo_id = ?,
                ultimo_jogo_data = ?,
                ultimo_resultado = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $vit1, $emp, $vit2, $gols1, $gols2,
            $match_id, $match['data_hora'], $match['resultado'],
            $h2h['id']
        ]);
    }
}

/**
 * Recalcula todas as posições do ranking
 *
 * @param int $modalidade_id ID da modalidade
 * @param string $tipo nacional, estadual, regional
 */
function recalcularPosicoes($modalidade_id, $tipo = 'nacional') {
    $pdo = getDBConnection();

    $campo_posicao = "posicao_$tipo";

    if ($tipo === 'estadual') {
        // Agrupar por estado
        $stmt = $pdo->prepare("
            SELECT tr.*, e.estado
            FROM team_rankings tr
            INNER JOIN equipes e ON tr.equipe_id = e.id
            WHERE tr.modalidade_id = ?
            ORDER BY e.estado, tr.elo_rating DESC
        ");
        $stmt->execute([$modalidade_id]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $estado_atual = null;
        $posicao = 1;

        foreach ($rankings as $ranking) {
            if ($ranking['estado'] !== $estado_atual) {
                $estado_atual = $ranking['estado'];
                $posicao = 1;
            }

            $pdo->prepare("UPDATE team_rankings SET $campo_posicao = ? WHERE id = ?")
                ->execute([$posicao, $ranking['id']]);

            $posicao++;
        }
    } else {
        // Nacional
        $stmt = $pdo->prepare("
            SELECT id FROM team_rankings
            WHERE modalidade_id = ?
            ORDER BY elo_rating DESC
        ");
        $stmt->execute([$modalidade_id]);
        $rankings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $posicao = 1;
        foreach ($rankings as $ranking_id) {
            $pdo->prepare("UPDATE team_rankings SET $campo_posicao = ? WHERE id = ?")
                ->execute([$posicao, $ranking_id]);
            $posicao++;
        }
    }
}

/**
 * Obtém ranking de uma modalidade
 *
 * @param int $modalidade_id ID da modalidade
 * @param int $limit Limite de resultados
 * @param string $estado Filtrar por estado (opcional)
 * @return array Rankings
 */
function getRanking($modalidade_id, $limit = 50, $estado = null) {
    $pdo = getDBConnection();

    $where = "tr.modalidade_id = ?";
    $params = [$modalidade_id];

    if ($estado) {
        $where .= " AND e.estado = ?";
        $params[] = $estado;
    }

    $stmt = $pdo->prepare("
        SELECT
            tr.*,
            e.nome as equipe_nome,
            e.cidade,
            e.estado,
            e.escudo_path,
            ROUND((tr.vitorias / NULLIF(tr.total_partidas, 0)) * 100, 2) as aproveitamento
        FROM team_rankings tr
        INNER JOIN equipes e ON tr.equipe_id = e.id
        WHERE $where
        ORDER BY tr.elo_rating DESC
        LIMIT ?
    ");

    $params[] = $limit;
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
