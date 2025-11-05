<?php
// Configurações Gerais do Sistema
session_start();

// URL Base
define('BASE_URL', 'http://localhost/inscricao');

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads/');
define('UPLOAD_URL', BASE_URL . '/public/uploads/');

// Configurações de Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Configurações de Email (para notificações)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@gmail.com');
define('SMTP_PASS', 'sua_senha');
define('SMTP_FROM', 'noreply@inscricoes.com');
define('SMTP_FROM_NAME', 'Sistema de Inscrições');

// Timezone
date_default_timezone_set('America/Boa_Vista');

// Includes
require_once 'database.php';

// Funções Auxiliares
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateProtocol() {
    return 'INSC' . date('Ymd') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

function formatCPF($cpf) {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf);
}

function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", $phone);
    } else {
        return preg_replace("/(\d{2})(\d{4})(\d{4})/", "($1) $2-$3", $phone);
    }
}

function validateCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function calcularIdade($dataNascimento) {
    $data = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($data);
    return $idade->y;
}
?>
