<?php
/**
 * API Routes - Registrations
 */

function registerRegistrationRoutes($router) {
    // GET /registrations
    $router->register('GET', '/registrations', function() {
        $org_id = getCurrentOrganizationId();
        $competicao_id = $_GET['competicao_id'] ?? null;
        $equipe_id = $_GET['equipe_id'] ?? null;
        $status = $_GET['status'] ?? null;

        try {
            $pdo = getDBConnection();

            $where = "ic.organizacao_id = ?";
            $params = [$org_id];

            if ($competicao_id) {
                $where .= " AND ic.competicao_id = ?";
                $params[] = $competicao_id;
            }
            if ($equipe_id) {
                $where .= " AND ic.equipe_id = ?";
                $params[] = $equipe_id;
            }
            if ($status) {
                $where .= " AND ic.status = ?";
                $params[] = $status;
            }

            $stmt = $pdo->prepare("
                SELECT ic.*, c.nome as competicao_nome, e.nome as equipe_nome,
                    (SELECT COUNT(*) FROM inscricoes_atletas ia WHERE ia.inscricao_competicao_id = ic.id) as total_atletas
                FROM inscricoes_competicoes ic
                INNER JOIN competicoes c ON ic.competicao_id = c.id
                INNER JOIN equipes e ON ic.equipe_id = e.id
                WHERE $where
                ORDER BY ic.data_inscricao DESC
            ");
            $stmt->execute($params);
            $inscricoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess(['inscricoes' => $inscricoes]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar inscrições', 500);
        }
    });

    // GET /registrations/{id}
    $router->register('GET', '/registrations/{id}', function($id) {
        if (!validateOrganizationOwnership('inscricoes_competicoes', $id)) {
            ApiRouter::sendError('Inscrição não encontrada', 404);
        }

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT ic.*, c.nome as competicao_nome, e.nome as equipe_nome
                FROM inscricoes_competicoes ic
                INNER JOIN competicoes c ON ic.competicao_id = c.id
                INNER JOIN equipes e ON ic.equipe_id = e.id
                WHERE ic.id = ?
            ");
            $stmt->execute([$id]);
            $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

            // Buscar atletas
            $stmt = $pdo->prepare("
                SELECT a.* FROM atletas a
                INNER JOIN inscricoes_atletas ia ON a.id = ia.atleta_id
                WHERE ia.inscricao_competicao_id = ?
            ");
            $stmt->execute([$id]);
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $inscricao['atletas'] = $atletas;

            ApiRouter::sendSuccess(['inscricao' => $inscricao]);
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao buscar inscrição', 500);
        }
    });

    // POST /registrations
    $router->register('POST', '/registrations', function() {
        $org_id = getCurrentOrganizationId();
        $data = ApiRouter::getJsonBody();

        ApiRouter::validateRequired($data, ['competicao_id', 'equipe_id', 'atletas']);

        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();

            // Gerar protocolo
            $protocolo = 'INSC' . rand(1000, 9999) . strtoupper(substr(md5(uniqid()), 0, 4));

            // Criar inscrição
            $stmt = $pdo->prepare("
                INSERT INTO inscricoes_competicoes (organizacao_id, competicao_id, equipe_id, protocolo, status)
                VALUES (?, ?, ?, ?, 'Pendente')
            ");
            $stmt->execute([$org_id, $data['competicao_id'], $data['equipe_id'], $protocolo]);
            $inscricao_id = $pdo->lastInsertId();

            // Adicionar atletas
            $stmt = $pdo->prepare("
                INSERT INTO inscricoes_atletas (inscricao_competicao_id, atleta_id)
                VALUES (?, ?)
            ");
            foreach ($data['atletas'] as $atleta_id) {
                $stmt->execute([$inscricao_id, $atleta_id]);
            }

            $pdo->commit();

            ApiRouter::sendSuccess([
                'inscricao_id' => $inscricao_id,
                'protocolo' => $protocolo
            ], 'Inscrição criada com sucesso', 201);

        } catch (PDOException $e) {
            $pdo->rollBack();
            ApiRouter::sendError('Erro ao criar inscrição', 500);
        }
    });

    // PUT /registrations/{id}/status
    $router->register('PUT', '/registrations/{id}/status', function($id) {
        if (!validateOrganizationOwnership('inscricoes_competicoes', $id)) {
            ApiRouter::sendError('Inscrição não encontrada', 404);
        }

        $data = ApiRouter::getJsonBody();
        ApiRouter::validateRequired($data, ['status']);

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE inscricoes_competicoes SET status = ? WHERE id = ?");
            $stmt->execute([$data['status'], $id]);

            ApiRouter::sendSuccess([], 'Status atualizado com sucesso');
        } catch (PDOException $e) {
            ApiRouter::sendError('Erro ao atualizar status', 500);
        }
    });
}
