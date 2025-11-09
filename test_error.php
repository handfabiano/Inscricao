<?php
/**
 * Arquivo de Diagnóstico - Erro 500
 *
 * Este arquivo ajuda a identificar o problema
 */

// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do Sistema</h1>";
echo "<pre>";

// 1. Testar PHP
echo "✓ PHP está funcionando!\n";
echo "Versão PHP: " . phpversion() . "\n\n";

// 2. Testar extensões necessárias
echo "=== Extensões PHP ===\n";
$required_extensions = ['pdo', 'pdo_mysql', 'openssl', 'curl', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '✓' : '✗') . " $ext: " . ($loaded ? 'OK' : 'NÃO INSTALADO') . "\n";
}

echo "\n=== Teste de Conexão ao Banco ===\n";

// 3. Verificar se o arquivo de configuração existe
if (!file_exists(__DIR__ . '/config/database.php')) {
    echo "✗ ERRO: config/database.php não encontrado!\n";
    exit;
}

echo "✓ config/database.php existe\n";

// 4. Tentar incluir o arquivo
try {
    require_once __DIR__ . '/config/database.php';
    echo "✓ config/database.php carregado\n";
} catch (Exception $e) {
    echo "✗ ERRO ao carregar config: " . $e->getMessage() . "\n";
    exit;
}

// 5. Testar conexão
echo "\nTentando conectar ao banco...\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
echo "Database: " . DB_NAME . "\n\n";

try {
    $pdo = getDBConnection();

    if ($pdo) {
        echo "✓ CONEXÃO COM BANCO OK!\n\n";

        // Testar query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "MySQL Version: " . $version['version'] . "\n";

        // Verificar se tabelas existem
        echo "\n=== Tabelas no Banco ===\n";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            echo "⚠ ATENÇÃO: Nenhuma tabela encontrada!\n";
            echo "Você precisa executar as migrations:\n";
            echo "php migrations/run_migrations.php\n";
        } else {
            echo "Total de tabelas: " . count($tables) . "\n";
            echo "Primeiras 10 tabelas:\n";
            foreach (array_slice($tables, 0, 10) as $table) {
                echo "  - $table\n";
            }
        }

    } else {
        echo "✗ ERRO: Conexão retornou NULL\n";
        echo "Verifique as credenciais do banco de dados\n";
    }

} catch (PDOException $e) {
    echo "✗ ERRO DE CONEXÃO:\n";
    echo $e->getMessage() . "\n\n";
    echo "POSSÍVEIS SOLUÇÕES:\n";
    echo "1. Verifique se o banco 'inscricao_atletas' existe\n";
    echo "2. Verifique usuário e senha no config/database.php\n";
    echo "3. No Hostinger, use as credenciais do painel de controle\n";
}

echo "\n=== Permissões de Arquivos ===\n";
$dirs_to_check = ['storage', 'public/uploads', 'public/reports'];
foreach ($dirs_to_check as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path);
        echo ($writable ? '✓' : '✗') . " $dir: " . ($writable ? 'OK' : 'SEM PERMISSÃO DE ESCRITA') . "\n";
    } else {
        echo "⚠ $dir: Diretório não existe\n";
    }
}

echo "\n=== Informações do Sistema ===\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . __FILE__ . "\n";
echo "Working Directory: " . getcwd() . "\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Próximos Passos:</h2>";
echo "<ol>";
echo "<li>Se houver erro de conexão: Configure o banco no <code>config/database.php</code></li>";
echo "<li>Se não houver tabelas: Execute <code>php migrations/run_migrations.php</code></li>";
echo "<li>Se tudo estiver OK: Tente acessar <a href='/admin/dashboard_executivo.php'>/admin/dashboard_executivo.php</a></li>";
echo "</ol>";
?>
