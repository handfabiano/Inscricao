<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Captura de Erro - Admin Equipes</h1>";
echo "<style>body { font-family: monospace; padding: 20px; }</style>";

// Buffer de saída para capturar erros
ob_start();

try {
    echo "<p>1. Carregando config...</p>";
    require_once __DIR__ . '/config/config.php';

    echo "<p>2. Testando requireAdminLogin...</p>";
    // Comentar temporariamente para ver se é isso que está quebrando
    // requireAdminLogin();

    echo "<p>3. Conectando ao banco...</p>";
    $pdo = getDBConnection();

    echo "<p>4. Testando queries básicas...</p>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes");
    echo "<p>Total equipes: " . $stmt->fetch()['total'] . "</p>";

    echo "<p>5. Testando query completa...</p>";
    $stmt = $pdo->query("
        SELECT
            e.*,
            (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id AND ativo = 1) as total_atletas,
            (SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = e.id) as total_inscricoes
        FROM equipes e
        ORDER BY e.created_at DESC
        LIMIT 1
    ");
    $equipe = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Primeira equipe: " . ($equipe ? $equipe['nome'] : 'nenhuma') . "</p>";

    echo "<p>6. Testando filtros...</p>";
    $filtroNome = '';
    $filtroStatus = '';
    $filtroCidade = '';

    $sql = "
        SELECT
            e.*,
            (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id AND ativo = 1) as total_atletas,
            (SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = e.id) as total_inscricoes
        FROM equipes e
        WHERE 1=1
    ";

    $params = [];

    if (!empty($filtroNome)) {
        $sql .= " AND e.nome LIKE ?";
        $params[] = "%$filtroNome%";
    }

    if (!empty($filtroStatus)) {
        $sql .= " AND e.status = ?";
        $params[] = $filtroStatus;
    }

    if (!empty($filtroCidade)) {
        $sql .= " AND e.cidade LIKE ?";
        $params[] = "%$filtroCidade%";
    }

    $sql .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $equipes = $stmt->fetchAll();

    echo "<p>Query com filtros retornou: " . count($equipes) . " equipes</p>";

    echo "<hr>";
    echo "<h2 style='color: green;'>✅ TUDO FUNCIONOU!</h2>";
    echo "<p>O problema NÃO é nas queries ou nos dados.</p>";
    echo "<p><strong>Possíveis causas do erro 500:</strong></p>";
    echo "<ol>";
    echo "<li><strong>requireAdminLogin()</strong> - função de autenticação pode estar quebrando</li>";
    echo "<li><strong>Alguma função indefinida</strong> - como formatarData(), redirect(), etc</li>";
    echo "<li><strong>Erro na parte HTML</strong> - sintaxe ou variável undefined</li>";
    echo "<li><strong>Memory limit</strong> - página muito pesada</li>";
    echo "</ol>";

    echo "<hr>";
    echo "<p><strong>Próximo passo:</strong> Vou criar uma versão simplificada de admin/equipes.php para você testar</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERRO CAPTURADO:</h2>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Capturar erros do buffer
$output = ob_get_clean();
echo $output;

// Mostrar erros que podem ter ocorrido
$error = error_get_last();
if ($error !== null) {
    echo "<hr>";
    echo "<h2 style='color: red;'>Último erro PHP:</h2>";
    echo "<pre>";
    print_r($error);
    echo "</pre>";
}
?>
