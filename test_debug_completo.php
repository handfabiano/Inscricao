<?php
/**
 * DEBUG COMPLETO - Testa todos os problemas reportados
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Completo</title>";
echo "<style>body{font-family:Arial;max-width:1000px;margin:30px auto;padding:20px;background:#f5f5f5;}";
echo ".ok{background:#d4edda;padding:10px;margin:10px 0;border-left:4px solid green;}";
echo ".erro{background:#f8d7da;padding:10px;margin:10px 0;border-left:4px solid red;}";
echo ".aviso{background:#fff3cd;padding:10px;margin:10px 0;border-left:4px solid orange;}";
echo "code{background:#eee;padding:2px 6px;border-radius:3px;}</style></head><body>";

echo "<h1>🔍 Debug Completo do Sistema</h1>\n";
echo "<p>Testando: configurações, relatórios e convites</p>\n";
echo "<hr>\n";

// 1. Testar configurações
echo "<h2>1️⃣ Teste: admin/configuracoes.php</h2>\n";
try {
    require_once __DIR__ . '/config/config.php';
    $pdo = getDBConnection();

    // Verificar tabela administradores
    $stmt = $pdo->query("SHOW TABLES LIKE 'administradores'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='ok'>✅ Tabela administradores existe</div>\n";

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
        $total = $stmt->fetchColumn();
        echo "<div class='ok'>✅ Total de admins: $total</div>\n";

        if ($total == 0) {
            echo "<div class='aviso'>⚠️ Nenhum admin cadastrado. Execute <a href='admin/setup_emergencial.php'>setup_emergencial.php</a></div>\n";
        }
    } else {
        echo "<div class='erro'>❌ Tabela administradores NÃO existe<br>";
        echo "<strong>Solução:</strong> Execute <a href='admin/setup_emergencial.php'>admin/setup_emergencial.php</a></div>\n";
    }

    // Verificar se arquivo existe
    if (file_exists(__DIR__ . '/admin/configuracoes.php')) {
        echo "<div class='ok'>✅ Arquivo configuracoes.php existe</div>\n";
    } else {
        echo "<div class='erro'>❌ Arquivo configuracoes.php NÃO EXISTE</div>\n";
    }

} catch (Exception $e) {
    echo "<div class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

// 2. Testar relatórios
echo "<h2>2️⃣ Teste: admin/relatorios.php</h2>\n";
try {
    if (file_exists(__DIR__ . '/admin/relatorios.php')) {
        echo "<div class='ok'>✅ Arquivo relatorios.php existe</div>\n";

        // Verificar tabelas necessárias
        $tabelas_relatorios = ['equipes', 'competicoes', 'atletas', 'inscricoes_competicoes'];
        $faltando = [];

        foreach ($tabelas_relatorios as $tabela) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='ok'>✅ Tabela $tabela existe</div>\n";
            } else {
                $faltando[] = $tabela;
                echo "<div class='erro'>❌ Tabela $tabela NÃO existe</div>\n";
            }
        }

        if (!empty($faltando)) {
            echo "<div class='aviso'>⚠️ Faltam tabelas. Execute <a href='admin/setup_emergencial.php'>setup_emergencial.php</a></div>\n";
        }
    } else {
        echo "<div class='erro'>❌ Arquivo relatorios.php NÃO EXISTE</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

// 3. Testar convites de atletas
echo "<h2>3️⃣ Teste: Convites de Atletas</h2>\n";
try {
    // Verificar tabela convites_atletas
    $stmt = $pdo->query("SHOW TABLES LIKE 'convites_atletas'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='ok'>✅ Tabela convites_atletas existe</div>\n";

        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE convites_atletas");
        $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $colunas_necessarias = ['data_expiracao', 'data_aceite', 'token', 'equipe_id'];
        foreach ($colunas_necessarias as $coluna) {
            if (in_array($coluna, $colunas)) {
                echo "<div class='ok'>✅ Coluna $coluna existe</div>\n";
            } else {
                echo "<div class='erro'>❌ Coluna $coluna NÃO existe</div>\n";
            }
        }

        // Verificar se existe algum convite
        $stmt = $pdo->query("SELECT COUNT(*) FROM convites_atletas");
        $total_convites = $stmt->fetchColumn();
        echo "<div class='ok'>ℹ️ Total de convites: $total_convites</div>\n";

        // Testar geração de link
        $protocolo = 'https';
        $host = 'mediumblue-rhinoceros-869852.hostingersite.com';
        $token_teste = bin2hex(random_bytes(32));
        $link_teste = "$protocolo://$host/publico/cadastro_atleta.php?token=$token_teste";

        echo "<div class='ok'>✅ Exemplo de link de convite:<br>";
        echo "<code style='word-break:break-all;'>$link_teste</code></div>\n";

        // Verificar arquivo de convites
        if (file_exists(__DIR__ . '/equipe/convites_atletas.php')) {
            echo "<div class='ok'>✅ Arquivo convites_atletas.php existe</div>\n";
        } else {
            echo "<div class='erro'>❌ Arquivo convites_atletas.php NÃO existe</div>\n";
        }

        // Verificar arquivo de cadastro
        if (file_exists(__DIR__ . '/publico/cadastro_atleta.php')) {
            echo "<div class='ok'>✅ Arquivo cadastro_atleta.php existe</div>\n";
        } else {
            echo "<div class='erro'>❌ Arquivo cadastro_atleta.php NÃO existe</div>\n";
        }

    } else {
        echo "<div class='erro'>❌ Tabela convites_atletas NÃO existe<br>";
        echo "<strong>Solução:</strong> Execute <a href='admin/setup_emergencial.php'>setup_emergencial.php</a></div>\n";
    }

} catch (Exception $e) {
    echo "<div class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

// 4. Resumo
echo "<hr>\n";
echo "<h2>📊 Resumo e Soluções</h2>\n";

$stmt = $pdo->query("SHOW TABLES");
$tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Tabelas no banco (total: " . count($tabelas) . ")</h3>\n";
if (empty($tabelas)) {
    echo "<div class='erro'><strong>❌ NENHUMA TABELA NO BANCO!</strong><br><br>";
    echo "<strong>SOLUÇÃO URGENTE:</strong><br>";
    echo "1. <a href='admin/setup_emergencial.php' style='padding:10px 20px;background:red;color:white;text-decoration:none;border-radius:5px;'>EXECUTAR SETUP EMERGENCIAL AGORA</a><br><br>";
    echo "2. Depois acesse <a href='admin/login.php'>admin/login.php</a></div>\n";
} else {
    echo "<ul>\n";
    foreach ($tabelas as $tabela) {
        echo "<li>$tabela</li>\n";
    }
    echo "</ul>\n";

    // Verificar tabelas essenciais
    $essenciais = ['administradores', 'equipes', 'competicoes', 'atletas', 'convites_atletas', 'modalidades', 'categorias'];
    $faltam = array_diff($essenciais, $tabelas);

    if (!empty($faltam)) {
        echo "<div class='aviso'><h4>⚠️ Faltam tabelas essenciais:</h4><ul>\n";
        foreach ($faltam as $falta) {
            echo "<li>$falta</li>\n";
        }
        echo "</ul>\n";
        echo "<p><strong>Solução:</strong> <a href='admin/setup_emergencial.php'>Executar setup emergencial</a></p></div>\n";
    } else {
        echo "<div class='ok'><h4>✅ Todas as tabelas essenciais existem!</h4>";
        echo "<p><strong>Próximos passos:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>Se não tem admin, execute <a href='admin/setup_emergencial.php'>setup emergencial</a></li>\n";
        echo "<li><a href='admin/login.php'>Fazer login</a></li>\n";
        echo "<li>Testar <a href='admin/configuracoes.php'>configurações</a></li>\n";
        echo "<li>Testar <a href='admin/relatorios.php'>relatórios</a></li>\n";
        echo "<li>Testar <a href='equipe/convites_atletas.php'>convites (precisa estar logado como equipe)</a></li>\n";
        echo "</ol></div>\n";
    }
}

echo "</body></html>";
?>
