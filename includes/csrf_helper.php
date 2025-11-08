<?php
/**
 * CSRF Protection Helper
 *
 * Funções para proteção contra Cross-Site Request Forgery (CSRF)
 *
 * @version 1.0
 * @date 2025-11-08
 */

/**
 * Gera um token CSRF
 *
 * @param string $form_name Nome do formulário (opcional, para múltiplos tokens)
 * @return string Token CSRF
 */
function generateCSRFToken($form_name = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Gerar token aleatório
    $token = bin2hex(random_bytes(32));

    // Armazenar na sessão com timestamp
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    $_SESSION['csrf_tokens'][$form_name] = [
        'token' => $token,
        'created_at' => time()
    ];

    return $token;
}

/**
 * Obtém token CSRF existente ou gera novo
 *
 * @param string $form_name Nome do formulário
 * @return string Token CSRF
 */
function getCSRFToken($form_name = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se já existe token válido
    if (isset($_SESSION['csrf_tokens'][$form_name])) {
        $token_data = $_SESSION['csrf_tokens'][$form_name];

        // Token ainda é válido (24 horas)
        if (time() - $token_data['created_at'] < 86400) {
            return $token_data['token'];
        }
    }

    // Gerar novo token
    return generateCSRFToken($form_name);
}

/**
 * Verifica token CSRF
 *
 * @param string $token Token fornecido
 * @param string $form_name Nome do formulário
 * @param bool $single_use Se true, token é invalidado após uso
 * @return bool Token válido
 */
function verifyCSRFToken($token, $form_name = 'default', $single_use = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se token existe na sessão
    if (!isset($_SESSION['csrf_tokens'][$form_name])) {
        return false;
    }

    $token_data = $_SESSION['csrf_tokens'][$form_name];

    // Verificar se token expirou (24 horas)
    if (time() - $token_data['created_at'] > 86400) {
        unset($_SESSION['csrf_tokens'][$form_name]);
        return false;
    }

    // Verificar se token corresponde (timing-safe comparison)
    if (!hash_equals($token_data['token'], $token)) {
        return false;
    }

    // Invalidar token se single-use
    if ($single_use) {
        unset($_SESSION['csrf_tokens'][$form_name]);
    }

    return true;
}

/**
 * Gera campo hidden HTML com token CSRF
 *
 * @param string $form_name Nome do formulário
 * @return string HTML do campo hidden
 */
function csrfTokenField($form_name = 'default') {
    $token = getCSRFToken($form_name);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Gera meta tag com token CSRF para AJAX
 *
 * @param string $form_name Nome do formulário
 * @return string HTML da meta tag
 */
function csrfMetaTag($form_name = 'default') {
    $token = getCSRFToken($form_name);
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

/**
 * Valida CSRF da requisição atual
 *
 * @param string $form_name Nome do formulário
 * @param bool $die_on_failure Se true, mata o script em caso de falha
 * @return bool Válido
 */
function validateCSRF($form_name = 'default', $die_on_failure = true) {
    // Obter token da requisição
    $token = null;

    // Tentar POST
    if (isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    // Tentar header (AJAX)
    elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // Tentar GET (menos seguro, apenas para requests específicos)
    elseif (isset($_GET['csrf_token'])) {
        $token = $_GET['csrf_token'];
    }

    $valid = $token && verifyCSRFToken($token, $form_name);

    if (!$valid && $die_on_failure) {
        logCSRFViolation();
        http_response_code(403);
        die('Requisição inválida. Token CSRF ausente ou inválido.');
    }

    return $valid;
}

/**
 * Middleware para validar CSRF automaticamente em POSTs
 *
 * @param string $form_name Nome do formulário
 */
function requireCSRF($form_name = 'default') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRF($form_name, true);
    }
}

/**
 * Limpa tokens CSRF expirados
 *
 * Deve ser chamado periodicamente (ex: no login)
 */
function cleanExpiredCSRFTokens() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_tokens'])) {
        return;
    }

    $current_time = time();
    foreach ($_SESSION['csrf_tokens'] as $form_name => $token_data) {
        // Remover tokens com mais de 24 horas
        if ($current_time - $token_data['created_at'] > 86400) {
            unset($_SESSION['csrf_tokens'][$form_name]);
        }
    }
}

/**
 * Registra violação de CSRF
 */
function logCSRFViolation() {
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema (tipo, descricao, ip_address, user_agent)
            VALUES ('csrf_violation', ?, ?, ?)
        ");

        $descricao = sprintf(
            'CSRF Violation: %s %s',
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        );

        $stmt->execute([
            $descricao,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

    } catch (Exception $e) {
        error_log("Erro ao registrar violação CSRF: " . $e->getMessage());
    }
}

/**
 * Configura headers de segurança adicionais
 *
 * Deve ser chamado no início de páginas importantes
 */
function setSecurityHeaders() {
    // Prevenir clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevenir MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // XSS Protection (navegadores antigos)
    header('X-XSS-Protection: 1; mode=block');

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy (básico)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://chart.googleapis.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");

    // Strict Transport Security (apenas se HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Valida origem da requisição
 *
 * @return bool Origem válida
 */
function validateOrigin() {
    // Obter origin esperado
    $expected_origin = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

    // Verificar Origin header
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        return $_SERVER['HTTP_ORIGIN'] === $expected_origin;
    }

    // Verificar Referer header (fallback)
    if (isset($_SERVER['HTTP_REFERER'])) {
        return strpos($_SERVER['HTTP_REFERER'], $expected_origin) === 0;
    }

    // Se não tem nem Origin nem Referer, permitir (navegadores antigos)
    return true;
}

/**
 * Middleware completo de proteção CSRF
 *
 * Combina validação de token, origem e headers de segurança
 *
 * @param string $form_name Nome do formulário
 */
function protectCSRF($form_name = 'default') {
    // Configurar headers de segurança
    setSecurityHeaders();

    // Validar apenas POSTs, PUTs e DELETEs
    $protected_methods = ['POST', 'PUT', 'DELETE', 'PATCH'];
    if (!in_array($_SERVER['REQUEST_METHOD'], $protected_methods)) {
        return;
    }

    // Validar origem
    if (!validateOrigin()) {
        logCSRFViolation();
        http_response_code(403);
        die('Requisição de origem não autorizada.');
    }

    // Validar token CSRF
    validateCSRF($form_name, true);
}

/**
 * Função auxiliar para AJAX
 * Retorna token CSRF em formato JSON
 */
function getCSRFTokenJSON($form_name = 'default') {
    header('Content-Type: application/json');
    echo json_encode([
        'csrf_token' => getCSRFToken($form_name)
    ]);
    exit;
}
