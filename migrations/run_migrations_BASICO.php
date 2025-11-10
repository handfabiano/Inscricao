<?php
/**
 * Migration Runner - VERSÃO BÁSICA
 *
 * Executa APENAS a migration 000 (schema base)
 * Use esta versão se estiver em ambiente compartilhado (Hostinger)
 * ou se não precisar de funcionalidades enterprise
 *
 * @version 1.0
 * @date 2025-11-10
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>Migration Runner - Versão Básica</h1>\n";
echo "<p>Executando apenas schema base (migration 000)</p>\n";
echo "<hr>\n\n";

// Lista de migrations básicas
$migrations = [
    '000_create_base_schema.sql'  // Schema base (tabelas fundamentais)
];

try {
    $pdo = getDBConnection();

    // Criar tabela de controle de migrations se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "<p><strong>Verificando migrations...</strong></p>\n\n";

    foreach ($migrations as $migration_file) {
        // Verificar se já foi executada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration_file]);
        $already_executed = $stmt->fetchColumn() > 0;

        if ($already_executed) {
            echo "<p style='color: green;'>✓ $migration_file - Já executada (pulando)</p>\n";
            continue;
        }

        echo "<p style='color: blue;'>⏳ Executando $migration_file...</p>\n";

        // Ler arquivo SQL
        $sql_file = __DIR__ . '/' . $migration_file;

        if (!file_exists($sql_file)) {
            echo "<p style='color: red;'>❌ ERRO: Arquivo não encontrado: $sql_file</p>\n";
            exit(1);
        }

        $sql = file_get_contents($sql_file);

        // Executar
        try {
            $pdo->exec($sql);

            // Registrar migration como executada
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration_file]);

            echo "<p style='color: green;'><strong>✅ $migration_file - Executada com sucesso!</strong></p>\n\n";

        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ ERRO ao executar $migration_file:</p>\n";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n\n";
            echo "<p><strong>Possíveis soluções:</strong></p>\n";
            echo "<ul>\n";
            echo "<li>Verifique se o usuário do banco tem permissões adequadas</li>\n";
            echo "<li>Se o erro for 'Duplicate column', ignore e continue</li>\n";
            echo "<li>Consulte SETUP_ORDEM_CORRETA.md para mais informações</li>\n";
            echo "</ul>\n";
            exit(1);
        }
    }

    echo "\n<hr>\n";
    echo "<h2 style='color: green;'>✅ Schema base criado com sucesso!</h2>\n";
    echo "<hr>\n\n";

    // Exibir estatísticas
    echo "<h3>Estatísticas do sistema:</h3>\n";
    echo "<ul>\n";

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM modalidades");
        echo "<li>Modalidades cadastradas: <strong>" . $stmt->fetchColumn() . "</strong></li>\n";
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM categorias");
        echo "<li>Categorias cadastradas: <strong>" . $stmt->fetchColumn() . "</strong></li>\n";
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM equipes");
        echo "<li>Equipes no sistema: <strong>" . $stmt->fetchColumn() . "</strong></li>\n";
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM competicoes");
        echo "<li>Competições no sistema: <strong>" . $stmt->fetchColumn() . "</strong></li>\n";
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM administradores");
        echo "<li>Administradores no sistema: <strong>" . $stmt->fetchColumn() . "</strong></li>\n";
    } catch (Exception $e) {}

    echo "</ul>\n\n";

    echo "<hr>\n";
    echo "<h2>✨ Sistema Básico Pronto!</h2>\n";
    echo "<h3>Próximos passos:</h3>\n";
    echo "<ol>\n";
    echo "<li><a href='../admin/criar_admin.php'>Criar Administrador Inicial</a></li>\n";
    echo "<li><a href='../admin/login.php'>Fazer Login no Sistema</a></li>\n";
    echo "<li>Se precisar de funcionalidades enterprise, execute <code>run_migrations.php</code> (completo)</li>\n";
    echo "</ol>\n";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>\n";
    echo "<h2 style='color: #721c24;'>❌ ERRO DE CONEXÃO</h2>\n";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    echo "<h3>Verifique:</h3>\n";
    echo "<ul>\n";
    echo "<li>Se as credenciais em <code>config/database.php</code> estão corretas</li>\n";
    echo "<li>Se o servidor MySQL está rodando</li>\n";
    echo "<li>Se o banco de dados existe</li>\n";
    echo "<li>Consulte <code>CONFIGURAR_HOSTINGER.md</code> para mais ajuda</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    exit(1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Migration Runner - Básico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1, h2, h3 {
            color: #333;
        }
        code {
            background: #eee;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #dc3545;
            overflow-x: auto;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
</body>
</html>
