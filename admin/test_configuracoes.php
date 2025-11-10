<?php
/**
 * Teste de Diagnóstico - admin/configuracoes.php
 * Execute este arquivo para ver onde está o problema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico - admin/configuracoes.php</h1>\n";
echo "<hr>\n";

// Teste 1: Verificar se config.php carrega
echo "<h2>1. Testando config/config.php</h2>\n";
try {
    require_once '../config/config.php';
    echo "<p style='color: green;'>✅ config.php carregado com sucesso</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao carregar config.php: " . $e->getMessage() . "</p>\n";
    exit;
}

// Teste 2: Verificar sessão
echo "<h2>2. Testando Sessão</h2>\n";
echo "<p>Status da sessão: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Ativa" : "❌ Inativa") . "</p>\n";
echo "<p>Dados da sessão:</p>\n";
echo "<pre>";
print_r($_SESSION);
echo "</pre>\n";

// Teste 3: Verificar se está logado como admin
echo "<h2>3. Verificando Login Admin</h2>\n";
if (isset($_SESSION['admin_id'])) {
    echo "<p style='color: green;'>✅ Admin logado com ID: " . $_SESSION['admin_id'] . "</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Nenhum admin logado</p>\n";
    echo "<p><strong>Ação necessária:</strong> <a href='login.php'>Fazer login</a> ou <a href='criar_admin.php'>criar admin</a></p>\n";
}

// Teste 4: Testar conexão com banco
echo "<h2>4. Testando Conexão com Banco</h2>\n";
try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Conexão com banco OK</p>\n";

    // Verificar tabela administradores
    $stmt = $pdo->query("SHOW TABLES LIKE 'administradores'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabela 'administradores' existe</p>\n";

        // Contar admins
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
        $total = $stmt->fetch()['total'];
        echo "<p>Total de administradores cadastrados: <strong>$total</strong></p>\n";

        if ($total == 0) {
            echo "<p style='color: orange;'>⚠️ Nenhum administrador cadastrado</p>\n";
            echo "<p><a href='criar_admin.php' class='btn'>Criar Administrador</a></p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Tabela 'administradores' não existe</p>\n";
        echo "<p><strong>Ação necessária:</strong> Execute <a href='/migrations/run_migrations_BASICO.php'>/migrations/run_migrations_BASICO.php</a></p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar ao banco: " . $e->getMessage() . "</p>\n";
}

// Teste 5: Testar função requireAdminLogin
echo "<h2>5. Testando requireAdminLogin()</h2>\n";
if (function_exists('requireAdminLogin')) {
    echo "<p style='color: green;'>✅ Função requireAdminLogin() existe</p>\n";

    if (isset($_SESSION['admin_id'])) {
        echo "<p style='color: green;'>✅ Você passaria pela verificação</p>\n";
        echo "<p><a href='configuracoes.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir para Configurações</a></p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Você seria redirecionado para login</p>\n";
        echo "<p>Caminho atual: " . $_SERVER['PHP_SELF'] . "</p>\n";
        echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDO') . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Função requireAdminLogin() não existe</p>\n";
}

// Teste 6: Verificar permissões do arquivo
echo "<h2>6. Verificando Permissões</h2>\n";
$file = '/home/user/Inscricao/admin/configuracoes.php';
if (file_exists($file)) {
    $perms = fileperms($file);
    $perms_str = substr(sprintf('%o', $perms), -4);
    echo "<p>Permissões de configuracoes.php: <strong>$perms_str</strong></p>\n";

    if (is_readable($file)) {
        echo "<p style='color: green;'>✅ Arquivo é legível</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Arquivo NÃO é legível</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Arquivo não encontrado</p>\n";
}

echo "<hr>\n";
echo "<h2>Resumo</h2>\n";
echo "<p>Se todos os testes acima passaram, o problema pode ser:</p>\n";
echo "<ol>\n";
echo "<li>Você não está logado como admin</li>\n";
echo "<li>A sessão expirou</li>\n";
echo "<li>Problema com cookies do navegador</li>\n";
echo "</ol>\n";
echo "<p><strong>Solução:</strong> <a href='login.php'>Fazer login como admin</a></p>\n";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico - Configurações</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1, h2 {
            color: #333;
        }
        pre {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #007bff;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
</body>
</html>
