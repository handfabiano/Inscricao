<?php
/**
 * Sistema de Gestão de Competições - v3.0
 * Arquivo de Configuração Principal
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importar conexão com banco de dados
require_once __DIR__ . '/database.php';

// ============================================================================
// CONFIGURAÇÕES GERAIS
// ============================================================================

// Detectar ambiente e definir URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;

// URL base do sistema (detectada automaticamente)
define('BASE_URL', $protocol . '://' . $host . $scriptDir);

// Timezone
date_default_timezone_set('America/Boa_Vista');

// ============================================================================
// CAMINHOS DE UPLOAD
// ============================================================================

define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('BANNER_PATH', UPLOAD_PATH . 'banners/');
define('FOTO_PATH', UPLOAD_PATH . 'fotos/');
define('DOCUMENTO_PATH', UPLOAD_PATH . 'documentos/');

// URLs públicas (usar caminhos relativos para funcionar em qualquer servidor)
// Se você acessar exemplo.com/admin/atletas.php, vai buscar exemplo.com/public/uploads/
define('UPLOAD_URL', '/public/uploads/');
define('BANNER_URL', UPLOAD_URL . 'banners/');
define('FOTO_URL', UPLOAD_URL . 'fotos/');
define('DOCUMENTO_URL', UPLOAD_URL . 'documentos/');

// ============================================================================
// CONFIGURAÇÕES DE UPLOAD
// ============================================================================

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FOTO_SIZE', 2 * 1024 * 1024); // 2MB para fotos
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_DOC_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// ============================================================================
// FUNÇÕES DE AUTENTICAÇÃO
// ============================================================================

/**
 * Verificar se admin está logado
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Verificar se equipe está logada
 */
function isEquipeLoggedIn() {
    return isset($_SESSION['equipe_id']) && !empty($_SESSION['equipe_id']);
}

/**
 * Redirecionar se não estiver logado (admin)
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        // Determinar o caminho correto baseado no diretório atual
        $currentPath = $_SERVER['PHP_SELF'];
        if (strpos($currentPath, '/admin/') !== false) {
            // Já está no diretório admin
            header('Location: login.php');
        } else {
            // Está em outro diretório
            header('Location: /admin/login.php');
        }
        exit;
    }
}

/**
 * Redirecionar se não estiver logado (equipe)
 */
function requireEquipeLogin() {
    if (!isEquipeLoggedIn()) {
        // Determinar o caminho correto baseado no diretório atual
        $currentPath = $_SERVER['PHP_SELF'];
        if (strpos($currentPath, '/equipe/') !== false) {
            // Já está no diretório equipe
            header('Location: login.php');
        } else {
            // Está em outro diretório
            header('Location: /equipe/login.php');
        }
        exit;
    }
}

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Sanitizar string
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar CPF
 */
function validarCPF($cpf) {
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

/**
 * Calcular idade
 */
function calcularIdade($dataNascimento) {
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}

/**
 * Formatar CPF
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Formatar telefone
 */
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) == 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) == 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
    return $telefone;
}

/**
 * Formatar data para PT-BR
 */
function formatarData($data) {
    if (empty($data)) return '';
    $dt = new DateTime($data);
    return $dt->format('d/m/Y');
}

/**
 * Formatar data e hora para PT-BR
 */
function formatarDataHora($dataHora) {
    if (empty($dataHora)) return '';
    $dt = new DateTime($dataHora);
    return $dt->format('d/m/Y H:i');
}

// ============================================================================
// FUNÇÕES DE UPLOAD
// ============================================================================

/**
 * Upload de arquivo
 */
function uploadFile($file, $fieldName, $targetDir, $allowedExtensions, $maxSize) {
    // Verificar se arquivo foi enviado
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Verificar erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro ao fazer upload do arquivo: $fieldName");
    }

    // Validar extensão
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception("Extensão não permitida para: $fieldName. Use: " . implode(', ', $allowedExtensions));
    }

    // Validar tamanho
    if ($fileSize > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024, 1);
        throw new Exception("Arquivo muito grande: $fieldName (máximo {$maxMB}MB)");
    }

    // Criar diretório se não existir
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    // Gerar nome único
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = $targetDir . $newFileName;

    // Mover arquivo
    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception("Erro ao salvar arquivo: $fieldName");
    }

    return $newFileName;
}

// ============================================================================
// FUNÇÕES DE HISTÓRICO
// ============================================================================

/**
 * Registrar histórico de atleta
 */
function registrarHistoricoAtleta($pdo, $atletaId, $eventoTipo, $descricao, $dados = []) {
    $stmt = $pdo->prepare("
        INSERT INTO historico_atletas (atleta_id, evento_tipo, descricao, dados_novos, data_evento)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $atletaId,
        $eventoTipo,
        $descricao,
        !empty($dados) ? json_encode($dados) : null
    ]);
}

/**
 * Registrar histórico de equipe
 */
function registrarHistoricoEquipe($pdo, $equipeId, $eventoTipo, $descricao, $dados = []) {
    $stmt = $pdo->prepare("
        INSERT INTO historico_equipes (equipe_id, evento_tipo, descricao, dados_adicionais, data_evento)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $equipeId,
        $eventoTipo,
        $descricao,
        !empty($dados) ? json_encode($dados) : null
    ]);
}

// ============================================================================
// FUNÇÕES DE UTILIDADE
// ============================================================================

/**
 * Gerar protocolo único
 */
function gerarProtocolo() {
    return strtoupper(uniqid('INSC'));
}

/**
 * Redirecionar com mensagem
 */
function redirect($url, $mensagem = '', $tipo = 'success') {
    if ($mensagem) {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['mensagem_tipo'] = $tipo;
    }
    header("Location: $url");
    exit;
}

/**
 * Exibir mensagem flash
 */
function exibirMensagem() {
    if (isset($_SESSION['mensagem'])) {
        $tipo = $_SESSION['mensagem_tipo'] ?? 'info';
        $mensagem = $_SESSION['mensagem'];

        $class = 'alert-info';
        if ($tipo === 'success') $class = 'alert-success';
        if ($tipo === 'error') $class = 'alert-danger';
        if ($tipo === 'warning') $class = 'alert-warning';

        echo "<div class='alert $class alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($mensagem);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";

        unset($_SESSION['mensagem']);
        unset($_SESSION['mensagem_tipo']);
    }
}
?>
