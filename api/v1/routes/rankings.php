<?php
/**
 * API Routes - Rankings
 */

function registerRankingRoutes($router) {
    // GET /rankings
    $router->register('GET', '/rankings', function() {
        $org_id = getCurrentOrganizationId();
        $competicao_id = $_GET['competicao_id'] ?? null;

        if (!$competicao_id) {
            ApiRouter::sendError('competicao_id é obrigatório', 400);
        }

        if (!validateOrganizationOwnership('competicoes', $competicao_id)) {
            ApiRouter::sendError('Competição não encontrada', 404);
        }

        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare("
                SELECT ic.*, e.nome as equipe_nome, e.cidade, e.estado
                FROM inscricoes_competicoes ic
                INNER JOIN equipes e ON ic.equipe_id = e.id
                WHERE ic.competicao_id = ? AND ic.status = 'Confirmada'
                ORDER BY ic.colocacao ASC, ic.pontuacao DESC
            ");
            $stmt->execute([$competicao_id]);
            $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess(['ranking' => $ranking]);

        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar ranking', 500);
        }
    });
}
