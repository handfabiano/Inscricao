<?php
/**
 * Gerador de Hash de Senha
 * Execute e depois DELETE!
 */

echo "<h2>🔐 Gerador de Hash de Senha</h2>";
echo "<hr>";

// Gerar hash para equipe123
$senha = 'equipe123';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<h3>✅ Hash Gerado com Sucesso!</h3>";
echo "<p><strong>Senha:</strong> $senha</p>";
echo "<p><strong>Hash:</strong></p>";
echo "<textarea style='width: 100%; height: 80px; font-family: monospace;'>$hash</textarea>";

echo "<hr>";
echo "<h3>📋 SQL para Executar no phpMyAdmin:</h3>";
echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
echo "INSERT INTO equipes (
    nome, sigla, responsavel_nome, responsavel_cpf,
    responsavel_email, responsavel_telefone,
    cidade, estado, usuario, senha,
    status, ativo, created_at
) VALUES (
    'Equipe Teste FC',
    'ETFC',
    'João da Silva',
    '12345678901',
    'joao@equipeteste.com',
    '95999887766',
    'Boa Vista',
    'RR',
    'equipe1',
    '$hash',
    'Aprovada',
    1,
    NOW()
);";
echo "</textarea>";

echo "<hr>";
echo "<h3>🧪 Testar Hash</h3>";
echo "<form method='POST'>";
echo "Senha de teste: <input type='text' name='test_senha' value='equipe123'><br><br>";
echo "<button type='submit'>Testar</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_senha'])) {
    $testSenha = $_POST['test_senha'];
    echo "<br><strong>Testando senha: '$testSenha'</strong><br>";

    if (password_verify($testSenha, $hash)) {
        echo "<p style='color: green; font-weight: bold;'>✅ SENHA CORRETA! O hash funciona!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ SENHA INCORRETA!</p>";
    }
}

echo "<hr>";
echo "⚠️ <strong>DELETE este arquivo após usar!</strong>";
?>
