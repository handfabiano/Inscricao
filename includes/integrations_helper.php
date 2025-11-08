<?php
/**
 * Integrations Helper - Core
 *
 * Funções principais para gerenciamento de integrações externas
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

// =====================================================
// GERENCIAMENTO DE CONFIGURAÇÕES
// =====================================================

/**
 * Obtém configuração de integração
 *
 * @param int $organizacao_id
 * @param string $provider_slug
 * @return array|null
 */
function getIntegrationConfig($organizacao_id, $provider_slug) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT ic.*, ip.slug, ip.base_url, ip.autenticacao_tipo
        FROM integration_configs ic
        INNER JOIN integration_providers ip ON ic.provider_id = ip.id
        WHERE ic.organizacao_id = ?
          AND ip.slug = ?
          AND ic.ativo = TRUE
    ");

    $stmt->execute([$organizacao_id, $provider_slug]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config && $config['credentials']) {
        // Descriptografar credenciais
        $config['credentials'] = decryptCredentials($config['credentials']);
    }

    return $config ?: null;
}

/**
 * Salva configuração de integração
 *
 * @param int $organizacao_id
 * @param string $provider_slug
 * @param array $credentials
 * @param array $settings
 * @param int $criado_por
 * @return array
 */
function saveIntegrationConfig($organizacao_id, $provider_slug, $credentials, $settings = [], $criado_por = null) {
    $pdo = getDBConnection();

    try {
        // Buscar provider
        $stmt = $pdo->prepare("SELECT id FROM integration_providers WHERE slug = ?");
        $stmt->execute([$provider_slug]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            return ['success' => false, 'message' => 'Provider não encontrado'];
        }

        // Criptografar credenciais
        $encrypted_credentials = encryptCredentials($credentials);

        // Inserir ou atualizar
        $stmt = $pdo->prepare("
            INSERT INTO integration_configs (
                organizacao_id, provider_id, nome_integracao,
                credentials, settings, criado_por, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'pendente_aprovacao')
            ON DUPLICATE KEY UPDATE
                credentials = VALUES(credentials),
                settings = VALUES(settings),
                updated_at = CURRENT_TIMESTAMP
        ");

        $nome = ucfirst(str_replace('_', ' ', $provider_slug));

        $stmt->execute([
            $organizacao_id,
            $provider['id'],
            $nome,
            $encrypted_credentials,
            json_encode($settings),
            $criado_por
        ]);

        return [
            'success' => true,
            'config_id' => $pdo->lastInsertId() ?: $stmt->rowCount(),
            'message' => 'Configuração salva com sucesso'
        ];

    } catch (Exception $e) {
        error_log("Erro ao salvar integração: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao salvar configuração'];
    }
}

/**
 * Ativa/desativa integração
 *
 * @param int $config_id
 * @param bool $ativar
 * @param int $aprovado_por
 * @return bool
 */
function toggleIntegration($config_id, $ativar = true, $aprovado_por = null) {
    $pdo = getDBConnection();

    $status = $ativar ? 'ativo' : 'inativo';

    $stmt = $pdo->prepare("
        UPDATE integration_configs
        SET ativo = ?,
            status = ?,
            aprovado_por = ?,
            aprovado_em = IF(? = TRUE AND aprovado_por IS NULL, NOW(), aprovado_em)
        WHERE id = ?
    ");

    return $stmt->execute([$ativar, $status, $aprovado_por, $ativar, $config_id]);
}

// =====================================================
// CHAMADAS API
// =====================================================

/**
 * Executa chamada para API externa
 *
 * @param int $config_id
 * @param string $endpoint
 * @param string $method
 * @param array $data
 * @param array $headers
 * @return array
 */
function apiCall($config_id, $endpoint, $method = 'GET', $data = [], $headers = []) {
    $pdo = getDBConnection();

    try {
        // Buscar configuração
        $stmt = $pdo->prepare("
            SELECT ic.*, ip.base_url, ip.autenticacao_tipo
            FROM integration_configs ic
            INNER JOIN integration_providers ip ON ic.provider_id = ip.id
            WHERE ic.id = ?
        ");
        $stmt->execute([$config_id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config || !$config['ativo']) {
            return ['success' => false, 'message' => 'Integração não disponível'];
        }

        // Verificar rate limit
        if (!checkRateLimit($config_id, $config)) {
            return ['success' => false, 'message' => 'Rate limit excedido'];
        }

        // Descriptografar credenciais
        $credentials = decryptCredentials($config['credentials']);

        // Preparar URL
        $url = rtrim($config['base_url'], '/') . '/' . ltrim($endpoint, '/');

        // Preparar headers de autenticação
        $auth_headers = prepareAuthHeaders($config['autenticacao_tipo'], $credentials);
        $headers = array_merge($auth_headers, $headers);

        // Executar chamada
        $start_time = microtime(true);
        $response = executeHttpRequest($url, $method, $data, $headers);
        $response_time = round((microtime(true) - $start_time) * 1000); // ms

        // Registrar log
        logApiCall($config_id, $method, $endpoint, $headers, $data, $response, $response_time);

        // Atualizar estatísticas
        updateIntegrationStats($config_id, $response['success']);

        return $response;

    } catch (Exception $e) {
        error_log("Erro em API call: " . $e->getMessage());

        // Registrar erro
        logApiCall($config_id, $method, $endpoint, $headers ?? [], $data, [
            'success' => false,
            'error' => $e->getMessage()
        ], 0);

        return ['success' => false, 'message' => 'Erro na chamada API'];
    }
}

/**
 * Executa requisição HTTP
 */
function executeHttpRequest($url, $method, $data = [], $headers = []) {
    $ch = curl_init();

    // Configurações básicas
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Método
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Headers
    $header_strings = [];
    foreach ($headers as $key => $value) {
        $header_strings[] = "{$key}: {$value}";
    }
    if (!empty($header_strings)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header_strings);
    }

    // Body
    if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $json_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    }

    // Capturar headers de resposta
    $response_headers = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) return $len;
        $response_headers[trim($header[0])] = trim($header[1]);
        return $len;
    });

    // Executar
    $response_body = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'status_code' => $status_code
        ];
    }

    // Parse JSON
    $response_data = json_decode($response_body, true);

    return [
        'success' => $status_code >= 200 && $status_code < 300,
        'status_code' => $status_code,
        'data' => $response_data ?? $response_body,
        'headers' => $response_headers
    ];
}

/**
 * Prepara headers de autenticação
 */
function prepareAuthHeaders($tipo, $credentials) {
    $headers = ['Content-Type' => 'application/json'];

    switch ($tipo) {
        case 'bearer_token':
            if (isset($credentials['token'])) {
                $headers['Authorization'] = 'Bearer ' . $credentials['token'];
            }
            break;

        case 'api_key':
            if (isset($credentials['api_key'])) {
                $headers['X-API-Key'] = $credentials['api_key'];
            }
            break;

        case 'basic_auth':
            if (isset($credentials['username']) && isset($credentials['password'])) {
                $auth = base64_encode($credentials['username'] . ':' . $credentials['password']);
                $headers['Authorization'] = 'Basic ' . $auth;
            }
            break;

        case 'oauth2':
            if (isset($credentials['access_token'])) {
                $headers['Authorization'] = 'Bearer ' . $credentials['access_token'];
            }
            break;
    }

    return $headers;
}

/**
 * Verifica rate limit
 */
function checkRateLimit($config_id, $config) {
    $pdo = getDBConnection();

    // Contar requests na última hora
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM integration_api_logs
        WHERE integration_config_id = ?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$config_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] >= $config['rate_limit_per_hour']) {
        return false;
    }

    // Contar requests no último dia
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM integration_api_logs
        WHERE integration_config_id = ?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    $stmt->execute([$config_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['total'] < $config['rate_limit_per_day'];
}

/**
 * Registra log de chamada API
 */
function logApiCall($config_id, $method, $endpoint, $request_headers, $request_body, $response, $response_time) {
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO integration_api_logs (
                integration_config_id, metodo, endpoint,
                request_headers, request_body,
                status_code, response_body, response_headers,
                response_time_ms, erro, erro_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $config_id,
            $method,
            $endpoint,
            json_encode($request_headers),
            json_encode($request_body),
            $response['status_code'] ?? null,
            json_encode($response['data'] ?? ''),
            json_encode($response['headers'] ?? []),
            $response_time,
            !($response['success'] ?? false),
            $response['error'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Atualiza estatísticas de integração
 */
function updateIntegrationStats($config_id, $success) {
    $pdo = getDBConnection();

    $campo_incrementar = $success ? 'total_success' : 'total_errors';
    $campo_timestamp = $success ? 'last_request_at' : 'last_error_at';

    $stmt = $pdo->prepare("
        UPDATE integration_configs
        SET total_requests = total_requests + 1,
            {$campo_incrementar} = {$campo_incrementar} + 1,
            {$campo_timestamp} = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$config_id]);
}

// =====================================================
// CRIPTOGRAFIA
// =====================================================

/**
 * Criptografa credenciais
 */
function encryptCredentials($credentials) {
    $key = getEncryptionKey();
    $iv = random_bytes(16);

    $encrypted = openssl_encrypt(
        json_encode($credentials),
        'AES-256-CBC',
        $key,
        0,
        $iv
    );

    return base64_encode($iv . $encrypted);
}

/**
 * Descriptografa credenciais
 */
function decryptCredentials($encrypted_data) {
    if (!$encrypted_data) {
        return [];
    }

    $key = getEncryptionKey();
    $decoded = base64_decode($encrypted_data);
    $iv = substr($decoded, 0, 16);
    $encrypted = substr($decoded, 16);

    $decrypted = openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );

    return json_decode($decrypted, true) ?: [];
}

/**
 * Obtém chave de criptografia
 */
function getEncryptionKey() {
    // Em produção, armazenar em variável de ambiente ou arquivo seguro
    $key = getenv('INTEGRATION_ENCRYPTION_KEY');

    if (!$key) {
        // Fallback - ALTERAR EM PRODUÇÃO
        $key = 'change-this-key-in-production-use-env-var-32-chars-min';
    }

    return hash('sha256', $key, true);
}

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

/**
 * Lista integrações disponíveis
 */
function listAvailableProviders($categoria = null) {
    $pdo = getDBConnection();

    $where = "ativo = TRUE";
    $params = [];

    if ($categoria) {
        $where .= " AND categoria = ?";
        $params[] = $categoria;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM integration_providers
        WHERE {$where}
        ORDER BY categoria, nome
    ");

    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lista integrações configuradas da organização
 */
function listOrganizationIntegrations($organizacao_id) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM active_integrations WHERE organizacao_id = ?");
    $stmt->execute([$organizacao_id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Testa conexão com integração
 */
function testIntegrationConnection($config_id) {
    // Implementar endpoint de teste específico para cada provider
    // Por exemplo, para WhatsApp: GET /v1/settings/business/profile

    return apiCall($config_id, '/health', 'GET');
}
