<?php
/**
 * Script de Criação de Administrador Inicial
 * Use este script APENAS se não conseguir acessar o sistema
 */

// Desabilitar por segurança após usar
$SCRIPT_HABILITADO = true;

if (!$SCRIPT_HABILITADO) {
    die('Script desabilitado. Edite o arquivo para habilitar.');
}

require_once '../config/database.php';

try {
    $pdo = getDBConnection();

    echo "<h2>🔧 Criação de Administrador Inicial</h2>";

    // Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'administradores'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>❌ Tabela 'administradores' não existe. Execute as migrations primeiro!</p>";
        echo "<p><a href='/migrations/run_migrations.php'>Executar Migrations</a></p>";
        exit;
    }

    // Verificar administradores existentes
    $stmt = $pdo->query("SELECT * FROM administradores");
    $admins = $stmt->fetchAll();

    echo "<h3>Administradores Existentes:</h3>";
    if (empty($admins)) {
        echo "<p style='color: orange;'>⚠️ Nenhum administrador encontrado!</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Nível</th><th>Ativo</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['nome']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['nivel']}</td>";
            echo "<td>" . ($admin['ativo'] ? '✅ Sim' : '❌ Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Criar admin padrão se não existir
    if (empty($admins)) {
        echo "<hr>";
        echo "<h3>Criando Administrador Padrão...</h3>";

        $senha = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO administradores (nome, email, senha, nivel, ativo, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");

        $stmt->execute([
            'Administrador',
            'admin@admin.com',
            $senha,
            'Master'
        ]);

        echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px;'>";
        echo "<h3 style='color: #155724;'>✅ Administrador Criado com Sucesso!</h3>";
        echo "<p><strong>Email:</strong> admin@admin.com</p>";
        echo "<p><strong>Senha:</strong> admin123</p>";
        echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Altere a senha após o primeiro login!</p>";
        echo "</div>";

        echo "<p><a href='login.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir para Login</a></p>";
    } else {
        echo "<hr>";
        echo "<p style='color: green;'>✅ Já existem administradores cadastrados.</p>";
        echo "<p><a href='login.php'>Ir para Login</a></p>";
    }

    echo "<hr>";
    echo "<h3>⚠️ Segurança</h3>";
    echo "<p style='color: red;'><strong>IMPORTANTE:</strong> Após criar o administrador, desabilite este script editando o arquivo e alterando \$SCRIPT_HABILITADO para false.</p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ Erro</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";

    echo "<hr>";
    echo "<h3>Possíveis Soluções:</h3>";
    echo "<ol>";
    echo "<li>Verifique se o banco de dados está configurado corretamente em <code>config/database.php</code></li>";
    echo "<li>Execute as migrations: <a href='/migrations/run_migrations.php'>Executar Migrations</a></li>";
    echo "<li>Verifique se o servidor MySQL está rodando</li>";
    echo "</ol>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Criar Administrador</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3 {
            color: #333;
        }
        code {
            background: #eee;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
        }
        th {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
</body>
</html>
