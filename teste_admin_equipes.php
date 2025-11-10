<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico Rápido - Admin Equipes</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    echo "<div class='success'>✅ Conexão com banco estabelecida</div>";

    // Teste 1: Contar equipes
    echo "<h2>1. Total de Equipes</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes");
    $total = $stmt->fetch()['total'];
    echo "<div class='info'>Total: <strong>{$total}</strong> equipe(s)</div>";

    // Teste 2: Query simples
    echo "<h2>2. Listar Equipes (query simples)</h2>";
    try {
        $stmt = $pdo->query("SELECT id, nome, cidade, estado, status FROM equipes LIMIT 5");
        $equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($equipes)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Cidade</th><th>Estado</th><th>Status</th></tr>";
            foreach ($equipes as $eq) {
                echo "<tr>";
                echo "<td>{$eq['id']}</td>";
                echo "<td>" . htmlspecialchars($eq['nome']) . "</td>";
                echo "<td>{$eq['cidade']}</td>";
                echo "<td>{$eq['estado']}</td>";
                echo "<td>{$eq['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<div class='success'>✅ Query simples funcionou!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro na query simples: " . $e->getMessage() . "</div>";
    }

    // Teste 3: Query da página admin/equipes.php
    echo "<h2>3. Query Completa (mesma da página admin)</h2>";
    try {
        $stmt = $pdo->query("
            SELECT
                e.*,
                (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id AND ativo = 1) as total_atletas,
                (SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = e.id) as total_inscricoes
            FROM equipes e
            ORDER BY e.created_at DESC
            LIMIT 3
        ");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<div class='success'>✅ Query completa executou com sucesso!</div>";
        echo "<div class='info'>Retornou <strong>" . count($result) . "</strong> equipe(s)</div>";

        if (!empty($result)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Atletas</th><th>Inscrições</th></tr>";
            foreach ($result as $eq) {
                echo "<tr>";
                echo "<td>{$eq['id']}</td>";
                echo "<td>" . htmlspecialchars($eq['nome']) . "</td>";
                echo "<td>{$eq['status']}</td>";
                echo "<td>{$eq['total_atletas']}</td>";
                echo "<td>{$eq['total_inscricoes']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro na query completa: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }

    // Teste 4: Verificar se admin/equipes.php existe e tem permissão
    echo "<h2>4. Verificar Arquivo admin/equipes.php</h2>";
    $adminFile = __DIR__ . '/admin/equipes.php';
    if (file_exists($adminFile)) {
        echo "<div class='success'>✅ Arquivo existe: {$adminFile}</div>";
        echo "<div class='info'>Tamanho: " . filesize($adminFile) . " bytes</div>";
        echo "<div class='info'>Permissões: " . substr(sprintf('%o', fileperms($adminFile)), -4) . "</div>";
        echo "<div class='info'>Última modificação: " . date("Y-m-d H:i:s", filemtime($adminFile)) . "</div>";
    } else {
        echo "<div class='error'>❌ Arquivo não encontrado: {$adminFile}</div>";
    }

    // Teste 5: Tentar incluir o arquivo (sem executar)
    echo "<h2>5. Teste de Sintaxe PHP</h2>";
    $output = shell_exec("php -l {$adminFile} 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "<div class='success'>✅ Sintaxe PHP válida</div>";
    } else {
        echo "<div class='error'>❌ Erro de sintaxe: <pre>{$output}</pre></div>";
    }

    // Conclusão
    echo "<h2>📊 Conclusão</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Se todas as queries acima funcionaram:</strong></p>";
    echo "<ul>";
    echo "<li>Os dados no banco estão OK ✅</li>";
    echo "<li>As queries estão funcionando ✅</li>";
    echo "<li>O problema pode ser:</li>";
    echo "<ul>";
    echo "<li>Sessão/Login (faça logout e login novamente)</li>";
    echo "<li>Cache do navegador (Ctrl+Shift+R)</li>";
    echo "<li>Permissões do arquivo</li>";
    echo "<li>Erro de PHP no arquivo admin/equipes.php</li>";
    echo "</ul>";
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Tente acessar: <a href='admin/equipes.php'>admin/equipes.php</a></li>";
    echo "<li>Se não funcionar, veja os logs de erro do PHP</li>";
    echo "<li>Ou faça logout e login novamente</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ ERRO FATAL</h2>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
