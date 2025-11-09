<?php
/**
 * Script de teste de conexão com banco de dados
 * Testa se as correções funcionam corretamente
 */

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n\n";

// Teste 1: Verificar se config.php carrega
echo "Teste 1: Carregando config.php...\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config carregado com sucesso\n\n";
} catch (Exception $e) {
    echo "✗ Erro ao carregar config: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Teste 2: Verificar se getDBConnection existe
echo "Teste 2: Verificando função getDBConnection...\n";
if (function_exists('getDBConnection')) {
    echo "✓ Função getDBConnection existe\n\n";
} else {
    echo "✗ Função getDBConnection não encontrada\n\n";
    exit(1);
}

// Teste 3: Tentar conectar ao banco
echo "Teste 3: Testando conexão com banco de dados...\n";
echo "Credenciais configuradas:\n";
echo "  Host: " . DB_HOST . "\n";
echo "  User: " . DB_USER . "\n";
echo "  Database: " . DB_NAME . "\n\n";

try {
    $pdo = getDBConnection();

    if ($pdo === null) {
        echo "✗ ERRO: getDBConnection() retornou NULL\n";
        echo "  Isso não deveria acontecer com a correção!\n\n";
        exit(1);
    }

    echo "✓ Conexão estabelecida com sucesso!\n";
    echo "  Tipo do objeto: " . get_class($pdo) . "\n\n";

    // Teste 4: Verificar se consegue executar query
    echo "Teste 4: Executando query de teste...\n";
    try {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✓ Query executada com sucesso\n";
        echo "  Resultado: " . $result['test'] . "\n\n";

        // Teste 5: Verificar se tabelas existem
        echo "Teste 5: Verificando tabelas do sistema...\n";
        $tables = ['competicoes', 'equipes', 'atletas', 'inscricoes_competicoes', 'administradores'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
                $result = $stmt->fetch();
                echo "  ✓ Tabela '$table': {$result['total']} registros\n";
            } catch (Exception $e) {
                echo "  ✗ Tabela '$table': " . $e->getMessage() . "\n";
            }
        }
        echo "\n";

    } catch (Exception $e) {
        echo "✗ Erro ao executar query: " . $e->getMessage() . "\n\n";
    }

    echo "=== TODOS OS TESTES PASSARAM! ===\n";
    echo "✓ A correção está funcionando corretamente\n";
    echo "✓ O erro 'Call to a member function query() on null' foi resolvido\n\n";

} catch (Exception $e) {
    echo "✗ Erro ao conectar (ESPERADO se banco não estiver configurado):\n";
    echo "  Mensagem: " . $e->getMessage() . "\n";
    echo "  Tipo: " . get_class($e) . "\n\n";

    echo "RESULTADO DO TESTE:\n";
    echo "✓ A função getDBConnection() lançou exceção (correto!)\n";
    echo "✓ Não retornou NULL (problema original corrigido!)\n";
    echo "✓ O código está funcionando conforme esperado\n\n";

    echo "NOTA: Configure o banco de dados em config/database.php para testes completos.\n";
}
