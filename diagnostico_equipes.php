<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico - Tabela Equipes</h1>";

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    echo "<h2>✅ Conexão com banco OK</h2>";

    // 1. Verificar estrutura da tabela
    echo "<h2>Estrutura da Tabela Equipes:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM equipes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 2. Contar registros
    echo "<h2>Total de Registros:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes");
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total de equipes: {$total}</strong></p>";

    // 3. Mostrar todos os registros
    echo "<h2>Todos os Registros:</h2>";
    $stmt = $pdo->query("SELECT * FROM equipes");
    $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($equipes)) {
        echo "<p>Nenhuma equipe encontrada.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='font-size: 12px;'>";
        echo "<tr>";
        foreach (array_keys($equipes[0]) as $header) {
            echo "<th>{$header}</th>";
        }
        echo "</tr>";

        foreach ($equipes as $equipe) {
            echo "<tr>";
            foreach ($equipe as $key => $value) {
                $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
                echo "<td>{$displayValue}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }

    // 4. Verificar campos NULL obrigatórios
    echo "<h2>Verificação de Dados:</h2>";
    $stmt = $pdo->query("
        SELECT id, nome, responsavel_nome, responsavel_cpf, responsavel_email, cidade, estado, organizacao_id, status
        FROM equipes
        WHERE nome IS NULL OR responsavel_nome IS NULL OR responsavel_cpf IS NULL
    ");
    $problemRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($problemRows)) {
        echo "<p style='color: red;'><strong>❌ Encontrados {count($problemRows)} registros com campos obrigatórios NULL:</strong></p>";
        echo "<pre>" . print_r($problemRows, true) . "</pre>";
    } else {
        echo "<p style='color: green;'><strong>✅ Todos os registros têm campos obrigatórios preenchidos</strong></p>";
    }

    // 5. Testar a query que a página usa
    echo "<h2>Testando Query da Página Equipes:</h2>";
    try {
        $stmt = $pdo->query("
            SELECT
                e.*,
                (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id AND ativo = 1) as total_atletas,
                (SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = e.id) as total_inscricoes
            FROM equipes e
            ORDER BY e.created_at DESC
        ");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'><strong>✅ Query principal executada com sucesso!</strong></p>";
        echo "<p>Retornou " . count($result) . " equipes</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ ERRO na query principal:</strong></p>";
        echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
        echo "<p><strong>Stack trace:</strong></p>";
        echo "<pre style='font-size: 11px;'>" . $e->getTraceAsString() . "</pre>";
    }

    // 6. Verificar chaves estrangeiras
    echo "<h2>Verificação de Chaves Estrangeiras:</h2>";
    $stmt = $pdo->query("
        SELECT e.id, e.nome, e.organizacao_id, o.id as org_exists
        FROM equipes e
        LEFT JOIN organizacoes o ON e.organizacao_id = o.id
        WHERE e.organizacao_id IS NOT NULL
    ");
    $fkCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fkProblems = array_filter($fkCheck, function($row) {
        return $row['org_exists'] === null;
    });

    if (!empty($fkProblems)) {
        echo "<p style='color: red;'><strong>❌ Encontrados registros com organizacao_id inválido:</strong></p>";
        echo "<pre>" . print_r($fkProblems, true) . "</pre>";
    } else {
        echo "<p style='color: green;'><strong>✅ Todas as chaves estrangeiras estão válidas</strong></p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERRO FATAL:</h2>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre style='font-size: 11px;'>" . $e->getTraceAsString() . "</pre>";
}
?>
