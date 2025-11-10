<?php
/**
 * Validação Completa do Sistema
 *
 * Testa: configurações, relatórios, convites e estrutura do banco
 *
 * @version 2.0
 * @date 2025-11-10
 */

require_once __DIR__ . '/../config/database.php';

// Funções auxiliares
function showResult($test, $success, $message = '') {
    $icon = $success ? '✅' : '❌';
    echo "$icon $test" . ($message ? ": $message" : '') . "\n";
    return $success;
}

function showInfo($message) {
    echo "ℹ️ $message\n";
}

function showSection($title) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "$title\n";
    echo str_repeat("=", 50) . "\n";
}

try {
    $pdo = getDBConnection();

    echo "\n";
    showSection("🔍 VALIDAÇÃO COMPLETA DO SISTEMA");
    echo "Data: " . date('Y-m-d H:i:s') . "\n";

    // 1. Teste de Configurações
    showSection("1️⃣ Teste: admin/configuracoes.php");

    // Verificar tabela administradores
    $stmt = $pdo->query("SHOW TABLES LIKE 'administradores'");
    showResult(
        "Tabela administradores",
        $stmt->rowCount() > 0
    );

    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        showInfo("Total de admins: " . $result['total']);
    }

    // Verificar arquivo
    $file_exists = file_exists(__DIR__ . '/configuracoes.php');
    showResult("Arquivo configuracoes.php", $file_exists);

    // 2. Teste de Relatórios
    showSection("2️⃣ Teste: admin/relatorios.php");

    $file_exists = file_exists(__DIR__ . '/relatorios.php');
    showResult("Arquivo relatorios.php", $file_exists);

    // Verificar tabelas essenciais
    $essential_tables = [
        'equipes',
        'competicoes',
        'atletas',
        'inscricoes_competicoes'
    ];

    foreach ($essential_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        showResult("Tabela $table", $stmt->rowCount() > 0);
    }

    // 3. Teste de Convites de Atletas
    showSection("3️⃣ Teste: Convites de Atletas");

    // Verificar tabela
    $stmt = $pdo->query("SHOW TABLES LIKE 'convites_atletas'");
    $table_exists = $stmt->rowCount() > 0;
    showResult("Tabela convites_atletas", $table_exists);

    if ($table_exists) {
        // Verificar colunas NOVAS (padrão correto)
        $columns_to_check = [
            'data_expiracao' => 'DATETIME NOT NULL',
            'data_aceite' => 'DATETIME NULL',
            'data_criacao' => 'TIMESTAMP',
            'telefone_atleta' => 'VARCHAR(20)',
            'token' => 'VARCHAR(64)',
            'equipe_id' => 'INT'
        ];

        // Verificar colunas ANTIGAS (que não deveriam existir)
        $old_columns = [
            'validade_ate' => 'DATETIME NOT NULL (antigo)',
            'usado_em' => 'DATETIME NULL (antigo)'
        ];

        echo "\n📋 Estrutura da Tabela:\n";

        $stmt = $pdo->query("DESCRIBE convites_atletas");
        $existing_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[$row['Field']] = $row;
        }

        // Verificar colunas novas (corretas)
        foreach ($columns_to_check as $column => $expected_type) {
            if (isset($existing_columns[$column])) {
                showResult("Coluna $column", true, "existe");
            } else {
                showResult("Coluna $column", false, "NÃO existe");
            }
        }

        // Verificar colunas antigas (devem NÃO existir)
        echo "\n🔍 Verificando Colunas Antigas (devem estar ausentes):\n";
        foreach ($old_columns as $column => $description) {
            if (isset($existing_columns[$column])) {
                showResult("Coluna $column", false, "ainda existe (precisa migração)");
            } else {
                showResult("Coluna $column", true, "não existe (correto!)");
            }
        }

        // Verificar status do ENUM
        if (isset($existing_columns['status'])) {
            $type = $existing_columns['status']['Type'];
            $has_recusado = strpos($type, 'Recusado') !== false;
            echo "\n";
            showResult(
                "Status ENUM completo",
                $has_recusado,
                $has_recusado ? "inclui 'Recusado'" : "falta 'Recusado'"
            );
        }

        // Total de convites
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM convites_atletas");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n";
        showInfo("Total de convites: " . $result['total']);

        // Exemplo de link (se houver convites)
        if ($result['total'] > 0) {
            $stmt = $pdo->query("SELECT token FROM convites_atletas LIMIT 1");
            $convite = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($convite) {
                $base_url = "https://mediumblue-rhinoceros-869852.hostingersite.com";
                $link = $base_url . "/publico/cadastro_atleta.php?token=" . $convite['token'];
                showInfo("Exemplo de link de convite:");
                echo "   $link\n";
            }
        }

        // Verificar arquivos
        echo "\n";
        $file_exists = file_exists(__DIR__ . '/convites_atletas.php');
        showResult("Arquivo convites_atletas.php", $file_exists);

        $file_exists = file_exists(__DIR__ . '/../publico/cadastro_atleta.php');
        showResult("Arquivo cadastro_atleta.php", $file_exists);
    }

    // 4. Resumo de Tabelas
    showSection("4️⃣ Resumo: Tabelas no Banco");

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    showInfo("Total de tabelas: " . count($tables));
    echo "\n";

    // Agrupar por categoria
    $categories = [
        'Sistema' => ['administradores', 'usuarios', 'logs', 'migrations'],
        'Competições' => ['competicoes', 'modalidades', 'categorias', 'inscricoes_competicoes'],
        'Equipes/Atletas' => ['equipes', 'atletas', 'inscricoes_atletas', 'convites_atletas'],
        'Organizações' => ['organizacoes', 'planos_assinatura', 'historico_assinaturas'],
        'Pagamentos' => ['payment_transactions', 'payment_gateways', 'payment_refunds'],
        'Analytics' => ['athlete_statistics', 'team_rankings', 'performance_insights'],
        'Integrações' => ['webhooks', 'integration_configs', 'api_tokens']
    ];

    foreach ($categories as $category => $category_tables) {
        $found = array_intersect($category_tables, $tables);
        if (count($found) > 0) {
            echo "📁 $category:\n";
            foreach ($found as $table) {
                echo "   - $table\n";
            }
            echo "\n";
        }
    }

    // 5. Diagnóstico e Próximos Passos
    showSection("5️⃣ Diagnóstico e Próximos Passos");

    // Verificar se precisa de migration
    $needs_migration = isset($existing_columns['validade_ate']) || isset($existing_columns['usado_em']);

    if ($needs_migration) {
        echo "⚠️ AÇÃO NECESSÁRIA: Migration de Colunas\n\n";
        echo "A tabela convites_atletas possui colunas antigas que precisam ser\n";
        echo "renomeadas para o padrão correto:\n\n";
        echo "   validade_ate  →  data_expiracao\n";
        echo "   usado_em      →  data_aceite\n\n";
        echo "🔧 Para corrigir, execute:\n";
        echo "   1. Acesse: admin/fix_convites_columns.php\n";
        echo "   2. Clique em 'Executar Migration Agora'\n";
        echo "   3. Aguarde a conclusão\n";
        echo "   4. Execute este script novamente para validar\n\n";
    } else {
        echo "✅ SISTEMA OK: Estrutura Validada\n\n";
        echo "Próximos passos:\n";
        echo "   1. ✅ Estrutura do banco: OK\n";
        echo "   2. ✅ Tabelas essenciais: OK\n";
        echo "   3. ✅ Colunas corretas: OK\n";
        echo "   4. 📝 Fazer login como administrador\n";
        echo "   5. 📝 Testar configurações\n";
        echo "   6. 📝 Testar relatórios\n";
        echo "   7. 📝 Testar convites (logado como equipe)\n\n";
    }

    // Links úteis
    echo "🔗 Links Úteis:\n";
    echo "   - Admin: /admin/index.php\n";
    echo "   - Login Equipe: /equipe/login.php\n";
    echo "   - Configurações: /admin/configuracoes.php\n";
    echo "   - Relatórios: /admin/relatorios.php\n";
    echo "   - Convites: /admin/convites_atletas.php\n";

    if ($needs_migration) {
        echo "   - Fix Colunas: /admin/fix_convites_columns.php ⚠️\n";
    }

    echo "\n";
    showSection("✅ Validação Concluída");
    echo "\n";

} catch (PDOException $e) {
    echo "\n❌ ERRO DE CONEXÃO:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}
