<?php
/**
 * API Routes - Competitions
 *
 * Rotas de gestão de competições
 *
 * @version 1.0
 * @date 2025-11-08
 */

function registerCompetitionRoutes($router) {
    /**
     * GET /competitions
     * Lista todas as competições da organização
     */
    $router->register('GET', '/competitions', function() {
        $org_id = getCurrentOrganizationId();

        $status = $_GET['status'] ?? null;
        $modalidade_id = $_GET['modalidade_id'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(100, max(1, intval($_GET['per_page'] ?? 20)));
        $offset = ($page - 1) * $per_page;

        try {
            $pdo = getDBConnection();

            // Construir query
            $where = ["c.organizacao_id = ?"];
            $params = [$org_id];

            if ($status) {
                $where[] = "c.status = ?";
                $params[] = $status;
            }

            if ($modalidade_id) {
                $where[] = "c.modalidade_id = ?";
                $params[] = $modalidade_id;
            }

            $where_clause = implode(' AND ', $where);

            // Contar total
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM competicoes c WHERE $where_clause");
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Buscar dados
            $stmt = $pdo->prepare("
                SELECT
                    c.*,
                    m.nome as modalidade_nome,
                    (SELECT COUNT(*) FROM inscricoes_competicoes ic WHERE ic.competicao_id = c.id) as total_inscricoes
                FROM competicoes c
                LEFT JOIN modalidades m ON c.modalidade_id = m.id
                WHERE $where_clause
                ORDER BY c.data_inicio_evento DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $per_page;
            $params[] = $offset;
            $stmt->execute($params);
            $competicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ApiRouter::sendSuccess([
                'competicoes' => $competicoes,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => ceil($total / $per_page)
                ]
            ]);

        } catch (PDOException $e) {
            error_log("Erro ao listar competições: " . $e->getMessage());
            ApiRouter::sendError('Erro ao buscar competições', 500);
        }
    });

    /**
     * GET /competitions/{id}
     * Obtém detalhes de uma competição
     */
    $router->register('GET', '/competitions/{id}', function($id) {
        $org_id = getCurrentOrganizationId();

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT
                    c.*,
                    m.nome as modalidade_nome,
                    (SELECT COUNT(*) FROM inscricoes_competicoes ic WHERE ic.competicao_id = c.id) as total_inscricoes,
                    (SELECT COUNT(*) FROM inscricoes_competicoes ic WHERE ic.competicao_id = c.id AND ic.status = 'Confirmada') as inscricoes_confirmadas
                FROM competicoes c
                LEFT JOIN modalidades m ON c.modalidade_id = m.id
                WHERE c.id = ? AND c.organizacao_id = ?
            ");
            $stmt->execute([$id, $org_id]);
            $competicao = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$competicao) {
                ApiRouter::sendError('Competição não encontrada', 404);
            }

            ApiRouter::sendSuccess(['competicao' => $competicao]);

        } catch (PDOException $e) {
            error_log("Erro ao buscar competição: " . $e->getMessage());
            ApiRouter::sendError('Erro ao buscar competição', 500);
        }
    });

    /**
     * POST /competitions
     * Cria uma nova competição
     */
    $router->register('POST', '/competitions', function() {
        $org_id = getCurrentOrganizationId();
        $data = ApiRouter::getJsonBody();

        // Validar campos obrigatórios
        ApiRouter::validateRequired($data, [
            'nome', 'modalidade_id', 'data_inicio_inscricao',
            'data_fim_inscricao', 'data_inicio_evento', 'data_fim_evento'
        ]);

        // Verificar limite de eventos
        $limite = checkOrganizationLimit('eventos');
        if ($limite['atingido']) {
            ApiRouter::sendError('Limite de eventos atingido para seu plano', 403, $limite);
        }

        try {
            $pdo = getDBConnection();

            $stmt = $pdo->prepare("
                INSERT INTO competicoes (
                    organizacao_id, nome, descricao, modalidade_id,
                    data_inicio_inscricao, data_fim_inscricao,
                    data_inicio_evento, data_fim_evento,
                    categorias_permitidas, genero_permitido,
                    min_atletas, max_atletas, taxa_inscricao,
                    local_evento, cidade, estado, regulamento, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $org_id,
                $data['nome'],
                $data['descricao'] ?? null,
                $data['modalidade_id'],
                $data['data_inicio_inscricao'],
                $data['data_fim_inscricao'],
                $data['data_inicio_evento'],
                $data['data_fim_evento'],
                isset($data['categorias_permitidas']) ? json_encode($data['categorias_permitidas']) : null,
                $data['genero_permitido'] ?? 'Todos',
                $data['min_atletas'] ?? null,
                $data['max_atletas'] ?? null,
                $data['taxa_inscricao'] ?? 0.00,
                $data['local_evento'] ?? null,
                $data['cidade'] ?? null,
                $data['estado'] ?? null,
                $data['regulamento'] ?? null,
                $data['status'] ?? 'Aberta'
            ]);

            $competicao_id = $pdo->lastInsertId();

            // Incrementar contador de eventos
            incrementOrganizationUsage('eventos');

            ApiRouter::sendSuccess([
                'competicao_id' => $competicao_id
            ], 'Competição criada com sucesso', 201);

        } catch (PDOException $e) {
            error_log("Erro ao criar competição: " . $e->getMessage());
            ApiRouter::sendError('Erro ao criar competição', 500);
        }
    });

    /**
     * PUT /competitions/{id}
     * Atualiza uma competição
     */
    $router->register('PUT', '/competitions/{id}', function($id) {
        $org_id = getCurrentOrganizationId();
        $data = ApiRouter::getJsonBody();

        // Validar propriedade
        if (!validateOrganizationOwnership('competicoes', $id)) {
            ApiRouter::sendError('Competição não encontrada ou sem permissão', 404);
        }

        try {
            $pdo = getDBConnection();

            $campos_permitidos = [
                'nome', 'descricao', 'data_inicio_inscricao', 'data_fim_inscricao',
                'data_inicio_evento', 'data_fim_evento', 'categorias_permitidas',
                'genero_permitido', 'min_atletas', 'max_atletas', 'taxa_inscricao',
                'local_evento', 'cidade', 'estado', 'regulamento', 'status'
            ];

            $updates = [];
            $params = [];

            foreach ($campos_permitidos as $campo) {
                if (isset($data[$campo])) {
                    if ($campo === 'categorias_permitidas' && is_array($data[$campo])) {
                        $updates[] = "$campo = ?";
                        $params[] = json_encode($data[$campo]);
                    } else {
                        $updates[] = "$campo = ?";
                        $params[] = $data[$campo];
                    }
                }
            }

            if (empty($updates)) {
                ApiRouter::sendError('Nenhum campo para atualizar', 400);
            }

            $params[] = $id;
            $params[] = $org_id;

            $sql = "UPDATE competicoes SET " . implode(', ', $updates) . " WHERE id = ? AND organizacao_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            ApiRouter::sendSuccess([], 'Competição atualizada com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao atualizar competição: " . $e->getMessage());
            ApiRouter::sendError('Erro ao atualizar competição', 500);
        }
    });

    /**
     * DELETE /competitions/{id}
     * Exclui uma competição
     */
    $router->register('DELETE', '/competitions/{id}', function($id) {
        $org_id = getCurrentOrganizationId();

        // Validar propriedade
        if (!validateOrganizationOwnership('competicoes', $id)) {
            ApiRouter::sendError('Competição não encontrada ou sem permissão', 404);
        }

        try {
            $pdo = getDBConnection();

            // Verificar se há inscrições
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscricoes_competicoes WHERE competicao_id = ?");
            $stmt->execute([$id]);
            $total_inscricoes = $stmt->fetchColumn();

            if ($total_inscricoes > 0) {
                ApiRouter::sendError('Não é possível excluir competição com inscrições', 400);
            }

            // Excluir
            $stmt = $pdo->prepare("DELETE FROM competicoes WHERE id = ? AND organizacao_id = ?");
            $stmt->execute([$id, $org_id]);

            // Decrementar contador
            decrementOrganizationUsage('eventos');

            ApiRouter::sendSuccess([], 'Competição excluída com sucesso');

        } catch (PDOException $e) {
            error_log("Erro ao excluir competição: " . $e->getMessage());
            ApiRouter::sendError('Erro ao excluir competição', 500);
        }
    });
}
