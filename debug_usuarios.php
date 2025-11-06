<?php
/**
 * Script de Debug - Verificar usuários no banco
 * Execute e depois DELETE!
 */

require_once 'config/database.php';

echo "<h2>🔍 Debug - Verificação de Usuários</h2>";
echo "<hr>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexão com banco OK!<br><br>";

    // Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    $tabelaExiste = $stmt->fetch();

    if (!$tabelaExiste) {
        echo "❌ <strong>ERRO:</strong> Tabela 'usuarios' NÃO EXISTE!<br>";
        echo "→ Você precisa importar o SQL primeiro!<br>";
        exit;
    }

    echo "✅ Tabela 'usuarios' existe<br><br>";

    // Listar usuários
    $stmt = $pdo->query("SELECT id, username, nome, email, nivel, ativo, created_at FROM usuarios");
    $usuarios = $stmt->fetchAll();

    if (count($usuarios) == 0) {
        echo "❌ <strong>NENHUM usuário cadastrado!</strong><br><br>";
        echo "→ Execute o script <a href='criar_admin.php'>criar_admin.php</a> para criar o admin<br>";
    } else {
        echo "✅ Encontrados <strong>" . count($usuarios) . "</strong> usuário(s):<br><br>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Nome</th><th>Email</th><th>Nível</th><th>Ativo</th><th>Criado em</th></tr>";

        foreach ($usuarios as $user) {
            $ativo = $user['ativo'] ? '✅ Sim' : '❌ Não';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['username']}</strong></td>";
            echo "<td>{$user['nome']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['nivel']}</td>";
            echo "<td>{$ativo}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";

        // Testar senha manualmente
        echo "<hr>";
        echo "<h3>🔐 Testar Senha</h3>";
        echo "<form method='POST'>";
        echo "Username: <input type='text' name='test_user' value='admin' required><br><br>";
        echo "Senha: <input type='text' name='test_pass' value='admin123' required><br><br>";
        echo "<button type='submit'>Testar</button>";
        echo "</form>";

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_user'])) {
            $test_user = $_POST['test_user'];
            $test_pass = $_POST['test_pass'];

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
            $stmt->execute([$test_user]);
            $usuario = $stmt->fetch();

            echo "<br><hr>";
            if ($usuario) {
                echo "✅ Usuário '<strong>{$test_user}</strong>' encontrado<br>";
                echo "Hash no banco: <code>" . substr($usuario['password'], 0, 50) . "...</code><br>";

                if (password_verify($test_pass, $usuario['password'])) {
                    echo "<br>✅ <strong style='color: green;'>SENHA CORRETA!</strong><br>";
                    echo "→ Você pode fazer login com: <strong>{$test_user}</strong> / <strong>{$test_pass}</strong><br>";
                    echo "<br><a href='admin/login.php'>→ Ir para o login</a>";
                } else {
                    echo "<br>❌ <strong style='color: red;'>SENHA INCORRETA!</strong><br>";
                    echo "→ Execute <a href='criar_admin.php'>criar_admin.php</a> para resetar a senha<br>";
                }
            } else {
                echo "❌ Usuário '<strong>{$test_user}</strong>' NÃO encontrado<br>";
                echo "→ Execute <a href='criar_admin.php'>criar_admin.php</a> para criar<br>";
            }
        }
    }

    echo "<br><hr>";
    echo "⚠️ <strong>DELETE este arquivo após usar!</strong>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
    echo "<br><br>Detalhes: <pre>" . print_r($e, true) . "</pre>";
}
?>
