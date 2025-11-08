<?php
/**
 * Migration Runner
 *
 * Executa todas as migrations SQL automaticamente
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

echo "======================================\n";
echo "Migration Runner - Sistema Multi-Tenant\n";
echo "======================================\n\n";

// Lista de migrations na ordem correta
$migrations = [
    '001_create_multi_tenancy_structure.sql',
    '002_add_organization_id_to_existing_tables.sql'
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

    echo "Verificando migrations...\n\n";

    foreach ($migrations as $migration_file) {
        // Verificar se já foi executada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration_file]);
        $already_executed = $stmt->fetchColumn() > 0;

        if ($already_executed) {
            echo "✓ $migration_file - Já executada (pulando)\n";
            continue;
        }

        echo "⏳ Executando $migration_file...\n";

        // Ler arquivo SQL
        $sql_file = __DIR__ . '/' . $migration_file;

        if (!file_exists($sql_file)) {
            echo "❌ ERRO: Arquivo não encontrado: $sql_file\n";
            exit(1);
        }

        $sql = file_get_contents($sql_file);

        // Remover comentários e dividir por statements
        $sql = preg_replace('/--.*$/m', '', $sql); // Remover comentários de linha
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remover comentários de bloco

        // Executar
        try {
            $pdo->exec($sql);

            // Registrar migration como executada
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration_file]);

            echo "✅ $migration_file - Executada com sucesso\n\n";

        } catch (PDOException $e) {
            echo "❌ ERRO ao executar $migration_file:\n";
            echo $e->getMessage() . "\n\n";
            exit(1);
        }
    }

    echo "\n======================================\n";
    echo "✅ Todas as migrations foram executadas!\n";
    echo "======================================\n\n";

    // Exibir estatísticas
    echo "Estatísticas do sistema:\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM planos_assinatura");
    echo "- Planos cadastrados: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM organizacoes");
    echo "- Organizações cadastradas: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM equipes");
    echo "- Equipes no sistema: " . $stmt->fetchColumn() . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM competicoes");
    echo "- Competições no sistema: " . $stmt->fetchColumn() . "\n";

    echo "\n✨ Sistema pronto para uso!\n\n";

} catch (PDOException $e) {
    echo "❌ ERRO DE CONEXÃO:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
