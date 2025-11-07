<?php
// Script de debug para identificar o erro em inscricoes.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
requireAdminLogin();

echo "<h2>Debug - Verificando inscricoes.php</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Conexão com banco OK</p>";

    // Testar query de estatísticas
    echo "<h3>Testando queries de estatísticas...</h3>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Pendente'");
    $totalPendentes = $stmt->fetch()['total'];
    echo "<p>✓ Total Pendentes: $totalPendentes</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
    $totalConfirmadas = $stmt->fetch()['total'];
    echo "<p>✓ Total Confirmadas: $totalConfirmadas</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Cancelada'");
    $totalCanceladas = $stmt->fetch()['total'];
    echo "<p>✓ Total Canceladas: $totalCanceladas</p>";

    // Testar query principal
    echo "<h3>Testando query principal de inscrições...</h3>";

    $sql = "
        SELECT
            i.*,
            c.nome as competicao_nome,
            c.status as competicao_status,
            e.nome as equipe_nome,
            e.responsavel_nome as equipe_responsavel,
            m.nome as modalidade_nome,
            (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = i.id) as total_atletas
        FROM inscricoes_competicoes i
        INNER JOIN competicoes c ON i.competicao_id = c.id
        INNER JOIN equipes e ON i.equipe_id = e.id
        LEFT JOIN modalidades m ON c.modalidade_id = m.id
        WHERE 1=1
        ORDER BY i.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $inscricoes = $stmt->fetchAll();

    echo "<p style='color: green;'>✓ Query principal OK - Total de inscrições: " . count($inscricoes) . "</p>";

    // Mostrar primeira inscrição como exemplo
    if (!empty($inscricoes)) {
        echo "<h3>Exemplo de inscrição (primeira da lista):</h3>";
        echo "<pre>";
        print_r($inscricoes[0]);
        echo "</pre>";

        // Testar formatação de data
        echo "<h3>Testando formatação de data...</h3>";
        $dataFormatada = formatarDataHora($inscricoes[0]['created_at']);
        echo "<p>✓ Data formatada: $dataFormatada</p>";
    }

    // Testar busca de competições
    echo "<h3>Testando busca de competições...</h3>";
    $competicoes = $pdo->query("SELECT id, nome FROM competicoes ORDER BY created_at DESC")->fetchAll();
    echo "<p style='color: green;'>✓ Total de competições: " . count($competicoes) . "</p>";

    echo "<h2 style='color: green;'>✓ TODOS OS TESTES PASSARAM!</h2>";
    echo "<p>O arquivo inscricoes.php deveria funcionar corretamente.</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ ERRO ENCONTRADO:</h2>";
    echo "<p style='color: red; font-weight: bold;'>" . $e->getMessage() . "</p>";
    echo "<h3>Detalhes do erro:</h3>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
?>
