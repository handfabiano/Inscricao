<?php
/**
 * API Rate Limiting Middleware
 *
 * Sistema de limitação de taxa para prevenir abuso da API
 * Utiliza banco de dados para persistência (pode ser migrado para Redis)
 *
 * @version 1.0
 * @date 2025-11-08
 */

class RateLimit {
    /**
     * Verifica se a organização pode fazer mais requisições
     *
     * @param int $organizacao_id ID da organização
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => string, 'retry_after' => int]
     */
    public static function check($organizacao_id) {
        // Obter limite da organização
        $org = getCurrentOrganization();
        if (!$org) {
            return ['allowed' => false, 'remaining' => 0, 'reset_at' => '', 'retry_after' => 60];
        }

        $limite = $org['api_rate_limit'] ?? 100; // Requisições por minuto
        $janela = 60; // Janela de 1 minuto

        // Limpar requisições antigas
        self::cleanOldRequests($organizacao_id, $janela);

        // Contar requisições na janela atual
        $count = self::countRequests($organizacao_id, $janela);

        // Verificar se atingiu o limite
        if ($count >= $limite) {
            $reset_at = self::getResetTime($organizacao_id, $janela);
            $retry_after = max(1, strtotime($reset_at) - time());

            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $reset_at,
                'retry_after' => $retry_after
            ];
        }

        // Registrar esta requisição
        self::recordRequest($organizacao_id);

        return [
            'allowed' => true,
            'remaining' => $limite - $count - 1,
            'reset_at' => date('c', time() + $janela),
            'retry_after' => 0
        ];
    }

    /**
     * Limpa requisições antigas
     */
    private static function cleanOldRequests($organizacao_id, $janela_segundos) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                DELETE FROM api_rate_limit
                WHERE organizacao_id = ?
                  AND timestamp < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$organizacao_id, $janela_segundos]);
        } catch (PDOException $e) {
            error_log("Erro ao limpar rate limit: " . $e->getMessage());
        }
    }

    /**
     * Conta requisições na janela atual
     */
    private static function countRequests($organizacao_id, $janela_segundos) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM api_rate_limit
                WHERE organizacao_id = ?
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$organizacao_id, $janela_segundos]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar rate limit: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Registra uma requisição
     */
    private static function recordRequest($organizacao_id) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO api_rate_limit (organizacao_id, ip_address, endpoint)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $organizacao_id,
                self::getClientIp(),
                $_SERVER['REQUEST_URI']
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar rate limit: " . $e->getMessage());
        }
    }

    /**
     * Obtém tempo de reset
     */
    private static function getResetTime($organizacao_id, $janela_segundos) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT DATE_ADD(MIN(timestamp), INTERVAL ? SECOND) as reset_at
                FROM api_rate_limit
                WHERE organizacao_id = ?
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$janela_segundos, $organizacao_id, $janela_segundos]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['reset_at'] ?? date('c', time() + $janela_segundos);
        } catch (PDOException $e) {
            error_log("Erro ao obter reset time: " . $e->getMessage());
            return date('c', time() + $janela_segundos);
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
}

// Criar tabela de rate limiting se não existir
function createRateLimitTable() {
    try {
        $pdo = getDBConnection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_rate_limit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                organizacao_id INT NOT NULL,
                ip_address VARCHAR(45),
                endpoint VARCHAR(255),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_org_time (organizacao_id, timestamp),
                INDEX idx_cleanup (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de rate limit: " . $e->getMessage());
    }
}

// Criar tabela automaticamente
createRateLimitTable();
