<?php
/**
 * API Routes - Athletes
 */

function registerAthleteRoutes($router) {
    // GET /athletes
    $router->register('GET', '/athletes', function() {
        $org_id = getCurrentOrganizationId();
        $equipe_id = $_GET['equipe_id'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, intval($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $per_page;

        try {
            $pdo = getDBConnection();

            $where = "a.organizacao_id = ?";
            $params = [$org_id];

            if ($equipe_id) {
                $where .= " AND a.equipe_atual_id = ?";
                $params[] = $equipe_id;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM atletas a WHERE $where");
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT a.*, e.nome as equipe_nome
                FROM atletas a
                LEFT JOIN equipes e ON a.equipe_atual_id = e.id
                WHERE $where
                ORDER BY a.nome_completo
                LIMIT ? OFFSET ?
            ");
            $params[] = $per_page;
            $params[] = $offset;
            $stmt->execute($params);
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess([
                'atletas' => $atletas,
                'pagination' => ['total' => $total, 'page' => $page, 'per_page' => $per_page]
            ]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar atletas', 500);
        }
    });

    // GET /athletes/{id}
    $router->register('GET', '/athletes/{id}', function($id) {
        if (!validateOrganizationOwnership('atletas', $id)) {
            ApiRouter::sendError('Atleta não encontrado', 404);
        }

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT a.*, e.nome as equipe_nome
                FROM atletas a
                LEFT JOIN equipes e ON a.equipe_atual_id = e.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $atleta = $stmt->fetch(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess(['atleta' => $atleta]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar atleta', 500);
        }
    });

    // POST /athletes
    $router->register('POST', '/athletes', function() {
        $org_id = getCurrentOrganizationId();
        $data = ApiRouter::getJsonBody();

        ApiRouter::validateRequired($data, ['nome_completo', 'cpf', 'data_nascimento', 'genero', 'equipe_atual_id']);

        $limite = checkOrganizationLimit('atletas');
        if ($limite['atingido']) {
            ApiRouter::sendError('Limite de atletas atingido', 403, $limite);
        }

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO atletas (organizacao_id, nome_completo, cpf, data_nascimento, genero, equipe_atual_id, email, telefone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $org_id,
                $data['nome_completo'],
                $data['cpf'],
                $data['data_nascimento'],
                $data['genero'],
                $data['equipe_atual_id'],
                $data['email'] ?? null,
                $data['telefone'] ?? null
            ]);

            incrementOrganizationUsage('atletas');

            ApiRouter::sendSuccess(['atleta_id' => $pdo->lastInsertId()], 'Atleta criado com sucesso', 201);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao criar atleta', 500);
        }
    });
}
