<?php
/**
 * API Routes - Authentication
 *
 * Rotas de autenticação da API
 *
 * @version 1.0
 * @date 2025-11-08
 */

function registerAuthRoutes($router) {
    /**
     * POST /auth/login
     * Autentica e gera token de acesso
     */
    $router->register('POST', '/auth/login', function() {
        $data = ApiRouter::getJsonBody();

        // Validar campos obrigatórios
        ApiRouter::validateRequired($data, ['email', 'senha']);

        $email = $data['email'];
        $senha = $data['senha'];
        $tipo = $data['tipo'] ?? 'admin'; // admin ou equipe

        try {
            $pdo = getDBConnection();

            if ($tipo === 'admin') {
                // Login de administrador
                $stmt = $pdo->prepare("
                    SELECT a.*, o.nome as organizacao_nome, o.ativo as org_ativa
                    FROM administradores a
                    LEFT JOIN organizacoes o ON a.organizacao_id = o.id
                    WHERE a.email = ? AND a.ativo = TRUE
                ");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                    ApiRouter::sendError('Credenciais inválidas', 401);
                }

                // Se tem organização associada, validar
                if ($usuario['organizacao_id']) {
                    if (!$usuario['org_ativa']) {
                        ApiRouter::sendError('Organização inativa', 403);
                    }
                    $organizacao_id = $usuario['organizacao_id'];
                } else {
                    // Super admin - usar primeira organização ou criar uma
                    $stmt = $pdo->query("SELECT id FROM organizacoes WHERE ativo = TRUE LIMIT 1");
                    $org = $stmt->fetch(PDO::FETCH_ASSOC);
                    $organizacao_id = $org['id'] ?? null;

                    if (!$organizacao_id) {
                        ApiRouter::sendError('Nenhuma organização disponível', 500);
                    }
                }

                $user_data = [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo' => 'admin',
                    'organizacao_id' => $organizacao_id,
                    'organizacao_nome' => $usuario['organizacao_nome'] ?? 'Sistema'
                ];

            } else {
                // Login de equipe
                $stmt = $pdo->prepare("
                    SELECT e.*, o.nome as organizacao_nome, o.ativo as org_ativa
                    FROM equipes e
                    LEFT JOIN organizacoes o ON e.organizacao_id = o.id
                    WHERE e.responsavel_email = ? AND e.ativo = TRUE
                ");
                $stmt->execute([$email]);
                $equipe = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$equipe || !password_verify($senha, $equipe['senha'])) {
                    ApiRouter::sendError('Credenciais inválidas', 401);
                }

                if (!$equipe['org_ativa']) {
                    ApiRouter::sendError('Organização inativa', 403);
                }

                if ($equipe['status'] !== 'Aprovada') {
                    ApiRouter::sendError('Equipe ainda não aprovada', 403);
                }

                $user_data = [
                    'id' => $equipe['id'],
                    'nome' => $equipe['nome'],
                    'email' => $equipe['responsavel_email'],
                    'tipo' => 'equipe',
                    'organizacao_id' => $equipe['organizacao_id'],
                    'organizacao_nome' => $equipe['organizacao_nome']
                ];
            }

            // Obter API key da organização
            setCurrentOrganization($organizacao_id);
            $org = getCurrentOrganization();

            if (!$org['api_ativa'] || !$org['api_key']) {
                ApiRouter::sendError('API não configurada para esta organização', 403);
            }

            // Gerar token JWT (por enquanto retornar os dados básicos)
            // TODO: Implementar JWT real
            $token_data = [
                'user' => $user_data,
                'api_key' => $org['api_key'],
                'expires_at' => date('c', strtotime('+24 hours'))
            ];

            ApiRouter::sendSuccess($token_data, 'Login realizado com sucesso');

        } catch (PDOException $e) {
            error_log("Erro no login da API: " . $e->getMessage());
            ApiRouter::sendError('Erro ao processar login', 500);
        }
    });

    /**
     * POST /auth/logout
     * Invalida o token atual
     */
    $router->register('POST', '/auth/logout', function() {
        // TODO: Implementar blacklist de tokens JWT
        ApiRouter::sendSuccess([], 'Logout realizado com sucesso');
    });

    /**
     * GET /auth/me
     * Retorna informações do usuário autenticado
     */
    $router->register('GET', '/auth/me', function() {
        $org = getCurrentOrganization();

        if (!$org) {
            ApiRouter::sendError('Organização não encontrada', 404);
        }

        $plan = getOrganizationPlan();

        ApiRouter::sendSuccess([
            'organizacao' => [
                'id' => $org['id'],
                'nome' => $org['nome'],
                'sigla' => $org['sigla'],
                'tipo' => $org['tipo'],
                'email' => $org['email']
            ],
            'plano' => $plan,
            'limites' => [
                'eventos' => checkOrganizationLimit('eventos'),
                'equipes' => checkOrganizationLimit('equipes'),
                'atletas' => checkOrganizationLimit('atletas'),
                'storage' => checkOrganizationLimit('storage'),
                'emails' => checkOrganizationLimit('emails')
            ]
        ], 'Informações da organização');
    });
}
