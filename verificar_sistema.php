<?php
/**
 * VERIFICADOR DE INTEGRIDADE DO SISTEMA
 * Execute para verificar se todos os arquivos e funções estão corretos
 * DELETE após verificar!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Verificação de Integridade do Sistema v3.0</h1>";
echo "<hr>";

$erros = [];
$avisos = [];
$sucessos = [];

// ============================================================================
// 1. VERIFICAR ARQUIVOS ESSENCIAIS
// ============================================================================

echo "<h2>1. Verificando Arquivos Essenciais</h2>";

$arquivosEssenciais = [
    'config/database.php',
    'config/config.php',
    'admin/login.php',
    'admin/logout.php',
    'admin/index.php',
    'admin/competicoes.php',
    'equipe/login.php',
    'equipe/logout.php',
    'equipe/index.php',
    'equipe/cadastrar_atleta.php',
    'equipe/atletas.php',
    'equipe/get_atleta.php',
    'public/css/style.css',
];

foreach ($arquivosEssenciais as $arquivo) {
    if (file_exists($arquivo)) {
        $sucessos[] = "✅ $arquivo encontrado";
    } else {
        $erros[] = "❌ $arquivo NÃO ENCONTRADO!";
    }
}

// ============================================================================
// 2. VERIFICAR PASTAS DE UPLOAD
// ============================================================================

echo "<h2>2. Verificando Pastas de Upload</h2>";

$pastasUpload = [
    'public/uploads/',
    'public/uploads/banners/',
    'public/uploads/fotos/',
    'public/uploads/documentos/',
];

foreach ($pastasUpload as $pasta) {
    if (is_dir($pasta)) {
        if (is_writable($pasta)) {
            $sucessos[] = "✅ $pasta existe e tem permissão de escrita";
        } else {
            $avisos[] = "⚠️ $pasta existe mas NÃO TEM permissão de escrita (chmod 775)";
        }
    } else {
        $erros[] = "❌ $pasta NÃO EXISTE!";
    }
}

// ============================================================================
// 3. VERIFICAR CONFIGURAÇÃO
// ============================================================================

echo "<h2>3. Verificando Configuração</h2>";

if (file_exists('config/config.php')) {
    require_once 'config/config.php';

    // Verificar constantes
    $constantes = [
        'BASE_URL',
        'UPLOAD_PATH',
        'BANNER_PATH',
        'FOTO_PATH',
        'UPLOAD_URL',
        'BANNER_URL',
        'FOTO_URL',
        'MAX_FILE_SIZE',
        'MAX_FOTO_SIZE',
    ];

    foreach ($constantes as $const) {
        if (defined($const)) {
            $valor = constant($const);
            $sucessos[] = "✅ Constante $const definida: $valor";
        } else {
            $erros[] = "❌ Constante $const NÃO DEFINIDA!";
        }
    }
} else {
    $erros[] = "❌ config/config.php não encontrado!";
}

// ============================================================================
// 4. VERIFICAR FUNÇÕES
// ============================================================================

echo "<h2>4. Verificando Funções</h2>";

$funcoes = [
    'isAdminLoggedIn',
    'isEquipeLoggedIn',
    'requireAdminLogin',
    'requireEquipeLogin',
    'sanitize',
    'validarCPF',
    'calcularIdade',
    'formatarCPF',
    'formatarTelefone',
    'formatarData',
    'formatarDataHora',
    'uploadFile',
    'registrarHistoricoAtleta',
    'registrarHistoricoEquipe',
    'gerarProtocolo',
    'redirect',
    'exibirMensagem',
];

foreach ($funcoes as $funcao) {
    if (function_exists($funcao)) {
        $sucessos[] = "✅ Função $funcao() existe";
    } else {
        $erros[] = "❌ Função $funcao() NÃO EXISTE!";
    }
}

// ============================================================================
// 5. VERIFICAR CONEXÃO COM BANCO
// ============================================================================

echo "<h2>5. Verificando Conexão com Banco de Dados</h2>";

if (function_exists('getDBConnection')) {
    try {
        $pdo = getDBConnection();
        $sucessos[] = "✅ Conexão com banco de dados OK!";

        // Verificar tabelas
        $tabelas = [
            'usuarios',
            'modalidades',
            'categorias',
            'competicoes',
            'equipes',
            'atletas',
            'inscricoes_competicoes',
            'inscricoes_atletas',
            'historico_atletas',
            'historico_equipes',
        ];

        $stmt = $pdo->query("SHOW TABLES");
        $tabelasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tabelas as $tabela) {
            if (in_array($tabela, $tabelasExistentes)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
                $count = $stmt->fetchColumn();
                $sucessos[] = "✅ Tabela $tabela existe ($count registros)";
            } else {
                $erros[] = "❌ Tabela $tabela NÃO EXISTE!";
            }
        }

    } catch (Exception $e) {
        $erros[] = "❌ Erro ao conectar no banco: " . $e->getMessage();
    }
} else {
    $erros[] = "❌ Função getDBConnection() não existe!";
}

// ============================================================================
// 6. VERIFICAR PHP
// ============================================================================

echo "<h2>6. Verificando PHP</h2>";

$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4', '>=')) {
    $sucessos[] = "✅ PHP $phpVersion (OK)";
} else {
    $avisos[] = "⚠️ PHP $phpVersion (recomendado 7.4+)";
}

// Extensões necessárias
$extensoes = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        $sucessos[] = "✅ Extensão PHP $ext carregada";
    } else {
        $erros[] = "❌ Extensão PHP $ext NÃO CARREGADA!";
    }
}

// ============================================================================
// EXIBIR RESULTADOS
// ============================================================================

echo "<hr>";
echo "<h2>📊 Resumo</h2>";

echo "<h3 style='color: green;'>✅ Sucessos (" . count($sucessos) . ")</h3>";
echo "<ul>";
foreach ($sucessos as $s) {
    echo "<li>$s</li>";
}
echo "</ul>";

if (!empty($avisos)) {
    echo "<h3 style='color: orange;'>⚠️ Avisos (" . count($avisos) . ")</h3>";
    echo "<ul>";
    foreach ($avisos as $a) {
        echo "<li>$a</li>";
    }
    echo "</ul>";
}

if (!empty($erros)) {
    echo "<h3 style='color: red;'>❌ Erros (" . count($erros) . ")</h3>";
    echo "<ul>";
    foreach ($erros as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul>";
} else {
    echo "<h2 style='color: green;'>🎉 SISTEMA 100% OK!</h2>";
}

echo "<hr>";
echo "<p><strong>⚠️ DELETE este arquivo após verificar!</strong></p>";
?>
