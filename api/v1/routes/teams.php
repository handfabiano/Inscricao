<?php
/**
 * API Routes - Teams
 */

function registerTeamRoutes($router) {
    // GET /teams - Listar equipes
    $router->register('GET', '/teams', function() {
        $org_id = getCurrentOrganizationId();
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, intval($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $per_page;

        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipes WHERE organizacao_id = ?");
            $stmt->execute([$org_id]);
            $total = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT e.*,
                    (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id) as total_atletas
                FROM equipes e
                WHERE e.organizacao_id = ?
                ORDER BY e.nome
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$org_id, $per_page, $offset]);
            $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess([
                'equipes' => $equipes,
                'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]
            ]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar equipes', 500);
        }
    });

    // GET /teams/{id}
    $router->register('GET', '/teams/{id}', function($id) {
        if (!validateOrganizationOwnership('equipes', $id)) {
            ApiRouter::sendError('Equipe não encontrada', 404);
        }

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
            $stmt->execute([$id]);
            $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess(['equipe' => $equipe]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar equipe', 500);
        }
    });

    // POST /teams
    $router->register('POST', '/teams', function() {
        $org_id = getCurrentOrganizationId();
        $data = ApiRouter::getJsonBody();

        ApiRouter::validateRequired($data, ['nome', 'responsavel_nome', 'responsavel_email']);

        $limite = checkOrganizationLimit('equipes');
        if ($limite['atingido']) {
            ApiRouter::sendError('Limite de equipes atingido', 403, $limite);
        }

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO equipes (organizacao_id, nome, sigla, cidade, estado, responsavel_nome, responsavel_email, responsavel_telefone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aprovada')
            ");
            $stmt->execute([
                $org_id,
                $data['nome'],
                $data['sigla'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $data['responsavel_nome'],
                $data['responsavel_email'],
                $data['responsavel_telefone'] ?? null
            ]);

            incrementOrganizationUsage('equipes');

            ApiRouter::sendSuccess(['equipe_id' => $pdo->lastInsertId()], 'Equipe criada com sucesso', 201);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao criar equipe', 500);
        }
    });
}
