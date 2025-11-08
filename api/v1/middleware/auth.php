<?php
/**
 * API Authentication Middleware
 *
 * Sistema de autenticação para API usando API Key + Secret
 * Suporte futuro para JWT tokens
 *
 * @version 1.0
 * @date 2025-11-08
 */

class ApiAuth {
    /**
     * Autentica a requisição
     *
     * @return array ['success' => bool, 'organizacao_id' => int|null, 'message' => string, 'code' => int]
     */
    public static function authenticate() {
        // Verificar se é autenticação por API Key/Secret
        $api_key = self::getApiKey();
        $api_secret = self::getApiSecret();

        if ($api_key && $api_secret) {
            return self::authenticateWithApiKey($api_key, $api_secret);
        }

        // Verificar se é autenticação por Bearer token (JWT)
        $bearer_token = self::getBearerToken();
        if ($bearer_token) {
            return self::authenticateWithJWT($bearer_token);
        }

        return [
            'success' => false,
            'organizacao_id' => null,
            'message' => 'Credenciais de autenticação não fornecidas',
            'code' => 401
        ];
    }

    /**
     * Obtém API Key do header
     */
    private static function getApiKey() {
        // Tentar header X-API-Key
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }

        // Tentar query parameter (menos seguro, apenas para testes)
        if (isset($_GET['api_key'])) {
            return $_GET['api_key'];
        }

        return null;
    }

    /**
     * Obtém API Secret do header
     */
    private static function getApiSecret() {
        // Tentar header X-API-Secret
        if (isset($_SERVER['HTTP_X_API_SECRET'])) {
            return $_SERVER['HTTP_X_API_SECRET'];
        }

        // Tentar query parameter (menos seguro, apenas para testes)
        if (isset($_GET['api_secret'])) {
            return $_GET['api_secret'];
        }

        return null;
    }

    /**
     * Obtém Bearer token do header Authorization
     */
    private static function getBearerToken() {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Autentica usando API Key + Secret
     */
    private static function authenticateWithApiKey($api_key, $api_secret) {
        $organizacao_id = validateApiCredentials($api_key, $api_secret);

        if (!$organizacao_id) {
            // Log de tentativa de autenticação falhada
            self::logFailedAuth('api_key', $api_key);

            return [
                'success' => false,
                'organizacao_id' => null,
                'message' => 'Credenciais de API inválidas',
                'code' => 401
            ];
        }

        // Verificar se a assinatura está ativa
        setCurrentOrganization($organizacao_id);
        if (!isOrganizationSubscriptionActive()) {
            return [
                'success' => false,
                'organizacao_id' => null,
                'message' => 'Assinatura inativa ou expirada',
                'code' => 403
            ];
        }

        // Verificar se tem permissão para usar API
        if (!organizationHasFeature('permite_api')) {
            return [
                'success' => false,
                'organizacao_id' => null,
                'message' => 'Plano atual não permite acesso à API',
                'code' => 403
            ];
        }

        // Log de acesso bem-sucedido
        logApiUsage(
            $organizacao_id,
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            self::getClientIp()
        );

        return [
            'success' => true,
            'organizacao_id' => $organizacao_id,
            'message' => 'Autenticado com sucesso',
            'code' => 200
        ];
    }

    /**
     * Autentica usando JWT token
     * TODO: Implementar JWT completo com biblioteca
     */
    private static function authenticateWithJWT($token) {
        // Por enquanto, JWT não implementado
        // Futura implementação usará Firebase JWT ou similar

        return [
            'success' => false,
            'organizacao_id' => null,
            'message' => 'Autenticação JWT não implementada ainda',
            'code' => 501
        ];
    }

    /**
     * Registra tentativa de autenticação falhada
     */
    private static function logFailedAuth($method, $identifier) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO logs_sistema (tipo, descricao, ip_address, user_agent)
                VALUES ('auth_failed', ?, ?, ?)
            ");
            $stmt->execute([
                "Failed $method authentication: $identifier",
                self::getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar falha de autenticação: " . $e->getMessage());
        }
    }

    /**
     * Obtém IP do cliente
     */
    private static function getClientIp() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }

        return '0.0.0.0';
    }

    /**
     * Gera um JWT token (para implementação futura)
     */
    public static function generateJWT($organizacao_id, $expiry_hours = 24) {
        // TODO: Implementar geração de JWT
        // Biblioteca sugerida: firebase/php-jwt

        return [
            'token' => 'NOT_IMPLEMENTED',
            'expires_at' => date('c', strtotime("+$expiry_hours hours"))
        ];
    }
}
