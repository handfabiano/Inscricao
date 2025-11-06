<?php
/**
 * Script para criar/atualizar usuário admin
 * Execute uma vez e depois DELETE este arquivo!
 */

require_once 'config/database.php';

try {
    $pdo = getDBConnection();

    // Dados do admin
    $username = 'admin';
    $password = 'admin123';
    $nome = 'Administrador do Sistema';
    $email = 'admin@sistema.com';
    $nivel = 'Super Admin';

    // Gerar hash da senha
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $existente = $stmt->fetch();

    if ($existente) {
        // Atualizar senha
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, nome = ?, email = ?, nivel = ?, ativo = 1 WHERE username = ?");
        $stmt->execute([$passwordHash, $nome, $email, $nivel, $username]);
        echo "✅ Usuário admin ATUALIZADO com sucesso!<br><br>";
    } else {
        // Criar novo
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nome, email, nivel, ativo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$username, $passwordHash, $nome, $email, $nivel]);
        echo "✅ Usuário admin CRIADO com sucesso!<br><br>";
    }

    echo "<strong>Credenciais de acesso:</strong><br>";
    echo "Usuário: <code>admin</code><br>";
    echo "Senha: <code>admin123</code><br><br>";
    echo "<hr>";
    echo "⚠️ <strong>IMPORTANTE:</strong> DELETE este arquivo (criar_admin.php) após criar o usuário!<br>";
    echo "<a href='admin/login.php'>→ Ir para área de login</a>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
