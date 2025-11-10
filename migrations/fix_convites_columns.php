<?php
/**
 * Fix Convites Atletas Columns
 *
 * Renomeia colunas validade_ate e usado_em para data_expiracao e data_aceite
 *
 * @version 1.0
 * @date 2025-11-10
 */

require_once __DIR__ . '/../config/database.php';

echo "\n";
echo "======================================\n";
echo "Fix Convites Atletas - Column Names\n";
echo "======================================\n\n";

try {
    $pdo = getDBConnection();

    echo "📋 Verificando estrutura ANTES da migration...\n\n";

    // Verificar estrutura atual
    $stmt = $pdo->query("DESCRIBE convites_atletas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_validade_ate = false;
    $has_usado_em = false;
    $has_data_expiracao = false;
    $has_data_aceite = false;

    foreach ($columns as $col) {
        if ($col['Field'] === 'validade_ate') $has_validade_ate = true;
        if ($col['Field'] === 'usado_em') $has_usado_em = true;
        if ($col['Field'] === 'data_expiracao') $has_data_expiracao = true;
        if ($col['Field'] === 'data_aceite') $has_data_aceite = true;
    }

    echo "Status das colunas:\n";
    echo "- validade_ate: " . ($has_validade_ate ? "✅ Existe (será renomeada)" : "❌ Não existe") . "\n";
    echo "- usado_em: " . ($has_usado_em ? "✅ Existe (será renomeada)" : "❌ Não existe") . "\n";
    echo "- data_expiracao: " . ($has_data_expiracao ? "✅ Já existe" : "❌ Não existe") . "\n";
    echo "- data_aceite: " . ($has_data_aceite ? "✅ Já existe" : "❌ Não existe") . "\n\n";

    // Executar migration apenas se necessário
    if (($has_validade_ate || $has_usado_em) && !$has_data_expiracao && !$has_data_aceite) {
        echo "⏳ Executando migration...\n\n";

        // Ler e executar arquivo SQL
        $sql_file = __DIR__ . '/001_fix_convites_atletas_columns.sql';

        if (!file_exists($sql_file)) {
            echo "❌ ERRO: Arquivo de migration não encontrado!\n";
            exit(1);
        }

        $sql = file_get_contents($sql_file);

        // Dividir por statements (usando ; como delimitador)
        $statements = explode(';', $sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;

            // Pular comentários
            if (substr($statement, 0, 2) === '--') continue;

            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignorar alguns erros esperados
                if (strpos($e->getMessage(), "Duplicate column name") !== false) {
                    continue;
                }
                if (strpos($e->getMessage(), "Unknown column") !== false) {
                    continue;
                }
                echo "⚠️ Aviso: " . $e->getMessage() . "\n";
            }
        }

        echo "✅ Migration executada com sucesso!\n\n";

    } else if ($has_data_expiracao && $has_data_aceite) {
        echo "✅ Colunas já estão corretas! Nada a fazer.\n\n";
    } else {
        echo "⚠️ Estado inconsistente das colunas. Verifique manualmente.\n\n";
    }

    // Verificar estrutura final
    echo "📋 Estrutura DEPOIS da migration:\n\n";
    $stmt = $pdo->query("DESCRIBE convites_atletas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        $field = $col['Field'];
        $type = $col['Type'];
        $null = $col['Null'];
        $key = $col['Key'];
        $default = $col['Default'];

        if (in_array($field, ['data_expiracao', 'data_aceite', 'data_criacao', 'telefone_atleta', 'status'])) {
            echo "✅ $field: $type " . ($null === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }

    echo "\n";
    echo "📊 Verificando convites existentes...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM convites_atletas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de convites: " . $result['total'] . "\n\n";

    if ($result['total'] > 0) {
        echo "Exemplos de convites:\n";
        $stmt = $pdo->query("
            SELECT
                id,
                token,
                status,
                data_expiracao,
                data_aceite,
                created_at
            FROM convites_atletas
            LIMIT 3
        ");
        $convites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($convites as $convite) {
            echo "- ID: {$convite['id']}, Status: {$convite['status']}, ";
            echo "Expira em: {$convite['data_expiracao']}\n";
        }
        echo "\n";
    }

    echo "======================================\n";
    echo "✅ Processo concluído com sucesso!\n";
    echo "======================================\n\n";

} catch (PDOException $e) {
    echo "❌ ERRO:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
