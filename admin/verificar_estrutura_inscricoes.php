<?php
require_once '../config/config.php';

$pdo = getDBConnection();

echo "<h2>Estrutura da tabela 'inscricoes_competicoes'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE inscricoes_competicoes");
    $colunas = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td><strong>" . $coluna['Field'] . "</strong></td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . ($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Dados de exemplo (primeira inscrição):</h3>";
    $stmt = $pdo->query("SELECT * FROM inscricoes_competicoes LIMIT 1");
    $exemplo = $stmt->fetch();

    if ($exemplo) {
        echo "<pre>";
        print_r($exemplo);
        echo "</pre>";
    } else {
        echo "<p>Nenhuma inscrição encontrada na tabela.</p>";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
