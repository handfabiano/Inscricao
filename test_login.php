<?php
/**
 * Script de Teste de Login
 * DELETE após usar!
 */

require_once 'config/config.php';

echo "<h2>🔍 Teste de Login</h2>";
echo "<hr>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexão OK<br><br>";

    // Buscar usuário admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = 'admin'");
    $stmt->execute();
    $usuario = $stmt->fetch();

    if ($usuario) {
        echo "✅ Usuário 'admin' encontrado<br>";
        echo "ID: " . $usuario['id'] . "<br>";
        echo "Nome: " . $usuario['nome'] . "<br>";
        echo "Ativo: " . ($usuario['ativo'] ? 'Sim' : 'Não') . "<br>";
        echo "Hash atual: " . substr($usuario['password'], 0, 60) . "...<br><br>";

        // Testar senha admin123
        $testPassword = 'admin123';
        echo "<strong>Testando senha: '$testPassword'</strong><br>";

        if (password_verify($testPassword, $usuario['password'])) {
            echo "✅ <strong style='color: green;'>SENHA CORRETA!</strong><br>";
            echo "<br>👉 Você pode fazer login com:<br>";
            echo "Usuário: <strong>admin</strong><br>";
            echo "Senha: <strong>admin123</strong><br><br>";
            echo "<a href='admin/login.php'>→ Ir para o login</a>";
        } else {
            echo "❌ <strong style='color: red;'>SENHA INCORRETA!</strong><br><br>";
            echo "O hash no banco NÃO corresponde a 'admin123'<br>";
            echo "<br><strong>Solução:</strong> Execute este SQL no phpMyAdmin:<br>";
            echo "<textarea style='width: 100%; height: 100px; font-family: monospace;'>";
            echo "UPDATE usuarios SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE username = 'admin';";
            echo "</textarea>";
        }
    } else {
        echo "❌ Usuário 'admin' NÃO encontrado!<br>";
        echo "<br><strong>Solução:</strong> Execute este SQL no phpMyAdmin:<br>";
        echo "<textarea style='width: 100%; height: 150px; font-family: monospace;'>";
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "INSERT INTO usuarios (username, password, nome, email, nivel, ativo) VALUES ('admin', '$hash', 'Administrador', 'admin@sistema.com', 'Super Admin', 1);";
        echo "</textarea>";
    }

    echo "<br><hr>";
    echo "⚠️ <strong>DELETE este arquivo após usar!</strong>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
