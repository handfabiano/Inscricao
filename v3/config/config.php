<?php
// Configurações Gerais do Sistema v3.0
session_start();

// URL Base
define('BASE_URL', 'http://localhost/inscricao/v3');

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads/');
define('BANNER_PATH', UPLOAD_PATH . 'banners/');
define('FOTO_PATH', UPLOAD_PATH . 'fotos/');
define('DOC_PATH', UPLOAD_PATH . 'documentos/');

// URLs
define('UPLOAD_URL', BASE_URL . '/public/uploads/');
define('BANNER_URL', UPLOAD_URL . 'banners/');
define('FOTO_URL', UPLOAD_URL . 'fotos/');
define('DOC_URL', UPLOAD_URL . 'documentos/');

// Configurações de Upload
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FOTO_SIZE', 2 * 1024 * 1024); // 2MB para fotos
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_DOC_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Timezone
date_default_timezone_set('America/Boa_Vista');

// Includes
require_once 'database.php';

// Funções Auxiliares
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateProtocol($prefix = 'COMP') {
    return $prefix . date('Ymd') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
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

    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
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

function calcularIdade($dataNascimento) {
    $data = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($data);
    return $idade->y;
}

// Funções de Autenticação - ADMIN
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

// Funções de Autenticação - EQUIPE (Responsável)
function isEquipeLoggedIn() {
    return isset($_SESSION['equipe_id']) && !empty($_SESSION['equipe_id']);
}

function requireEquipeLogin() {
    if (!isEquipeLoggedIn()) {
        header('Location: ' . BASE_URL . '/equipe/login.php');
        exit;
    }
}

// Função de Upload de Arquivo
function uploadFile($file, $fieldName, $targetDir, $allowedExtensions, $maxSize) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro ao fazer upload do arquivo: $fieldName");
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception("Extensão não permitida para o arquivo: $fieldName");
    }

    if ($fileSize > $maxSize) {
        throw new Exception("Arquivo muito grande: $fieldName");
    }

    // Gerar nome único
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = $targetDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception("Erro ao mover arquivo: $fieldName");
    }

    return $newFileName;
}

// Registrar histórico
function registrarHistorico($pdo, $tipo, $tabelaId, $eventoTipo, $descricao, $dados = []) {
    $tabela = '';
    $campo_id = '';

    switch($tipo) {
        case 'atleta':
            $tabela = 'historico_atletas';
            $campo_id = 'atleta_id';
            break;
        case 'equipe':
            $tabela = 'historico_equipes';
            $campo_id = 'equipe_id';
            break;
    }

    if (!$tabela) return false;

    $sql = "INSERT INTO {$tabela} ({$campo_id}, evento_tipo, descricao, dados_adicionais)
            VALUES (?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $tabelaId,
        $eventoTipo,
        $descricao,
        json_encode($dados)
    ]);
}

// Formatar data brasileira
function formatarData($data, $formato = 'd/m/Y') {
    if (!$data) return '';
    return date($formato, strtotime($data));
}

function formatarDataHora($data, $formato = 'd/m/Y H:i') {
    if (!$data) return '';
    return date($formato, strtotime($data));
}

// Formatar moeda
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
