<?php
/**
 * Verifica se as tabelas de inscrições existem
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();

    echo "<h2>Verificando Tabelas do Sistema</h2>";

    // Listar todas as tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>Tabelas existentes:</h3>";
    echo "<ul>";
    foreach ($tabelas as $tabela) {
        echo "<li>$tabela</li>";
    }
    echo "</ul>";

    // Verificar tabelas necessárias
    $tabelasNecessarias = [
        'usuarios',
        'modalidades',
        'categorias',
        'competicoes',
        'equipes',
        'atletas',
        'inscricoes_competicoes',
        'inscricoes_atletas',
        'historico_atletas',
        'historico_equipes'
    ];

    echo "<h3>Status das Tabelas Necessárias:</h3>";
    echo "<ul>";
    foreach ($tabelasNecessarias as $tabela) {
        $existe = in_array($tabela, $tabelas);
        $status = $existe ? '✅' : '❌';
        echo "<li>$status $tabela</li>";

        if ($existe) {
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
            $count = $stmt->fetchColumn();
            echo " ($count registros)";
        }
    }
    echo "</ul>";

    // Se faltam tabelas, mostrar SQL
    $tabelasFaltando = array_diff($tabelasNecessarias, $tabelas);
    if (!empty($tabelasFaltando)) {
        echo "<h3 style='color: red;'>❌ FALTAM TABELAS: " . implode(', ', $tabelasFaltando) . "</h3>";
        echo "<p>Execute o SQL abaixo no banco de dados:</p>";
    }

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro: " . $e->getMessage() . "</h3>";
}
?>
