<?php
/**
 * Arquivo de teste para diagnosticar problemas em inscricoes.php
 */

// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Teste de Diagnóstico - admin/inscricoes.php</h2>";

// Teste 1: Incluir config
echo "<h3>1. Testando inclusão do config.php</h3>";
try {
    require_once '../config/config.php';
    echo "✅ config.php incluído com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao incluir config.php: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 2: Verificar sessão admin
echo "<h3>2. Verificando sessão de admin</h3>";
if (isset($_SESSION['admin_id'])) {
    echo "✅ Sessão admin ativa: ID = " . $_SESSION['admin_id'] . "<br>";
    echo "✅ Nome: " . $_SESSION['admin_nome'] . "<br>";
} else {
    echo "❌ Sem sessão de admin. Você precisa fazer login em admin/login.php primeiro<br>";
    echo "<a href='login.php'>Fazer login</a><br>";
    exit;
}

// Teste 3: Conectar ao banco
echo "<h3>3. Testando conexão com banco de dados</h3>";
try {
    $pdo = getDBConnection();
    echo "✅ Conexão com banco estabelecida<br>";
} catch (Exception $e) {
    echo "❌ Erro ao conectar ao banco: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 4: Verificar se tabelas existem
echo "<h3>4. Verificando tabelas necessárias</h3>";
$tabelas = ['inscricoes_competicoes', 'inscricoes_atletas', 'competicoes', 'equipes', 'modalidades'];
foreach ($tabelas as $tabela) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela '$tabela' existe<br>";
        } else {
            echo "❌ Tabela '$tabela' NÃO existe<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao verificar tabela '$tabela': " . $e->getMessage() . "<br>";
    }
}

// Teste 5: Contar inscrições
echo "<h3>5. Testando queries de inscrições</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Pendente'");
    $total = $stmt->fetch()['total'];
    echo "✅ Inscrições Pendentes: $total<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
    $total = $stmt->fetch()['total'];
    echo "✅ Inscrições Confirmadas: $total<br>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Cancelada'");
    $total = $stmt->fetch()['total'];
    echo "✅ Inscrições Canceladas: $total<br>";
} catch (Exception $e) {
    echo "❌ Erro ao contar inscrições: " . $e->getMessage() . "<br>";
}

// Teste 6: Query principal de inscrições
echo "<h3>6. Testando query principal</h3>";
try {
    $sql = "
        SELECT
            i.*,
            c.nome as competicao_nome,
            c.status as competicao_status,
            e.nome as equipe_nome,
            e.municipio as equipe_municipio,
            m.nome as modalidade_nome,
            (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = i.id) as total_atletas
        FROM inscricoes_competicoes i
        INNER JOIN competicoes c ON i.competicao_id = c.id
        INNER JOIN equipes e ON i.equipe_id = e.id
        LEFT JOIN modalidades m ON c.modalidade_id = m.id
        ORDER BY i.data_inscricao DESC
        LIMIT 5
    ";

    $stmt = $pdo->query($sql);
    $inscricoes = $stmt->fetchAll();
    echo "✅ Query executada com sucesso<br>";
    echo "📊 Total de inscrições encontradas: " . count($inscricoes) . "<br>";

    if (count($inscricoes) > 0) {
        echo "<br><strong>Primeiras inscrições:</strong><br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Protocolo</th><th>Competição</th><th>Equipe</th><th>Status</th><th>Atletas</th></tr>";
        foreach ($inscricoes as $insc) {
            echo "<tr>";
            echo "<td>" . $insc['protocolo'] . "</td>";
            echo "<td>" . $insc['competicao_nome'] . "</td>";
            echo "<td>" . $insc['equipe_nome'] . "</td>";
            echo "<td>" . $insc['status'] . "</td>";
            echo "<td>" . $insc['total_atletas'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erro na query principal: " . $e->getMessage() . "<br>";
}

// Teste 7: Buscar competições para filtro
echo "<h3>7. Testando busca de competições</h3>";
try {
    $competicoes = $pdo->query("SELECT id, nome FROM competicoes ORDER BY created_at DESC")->fetchAll();
    echo "✅ Competições encontradas: " . count($competicoes) . "<br>";
} catch (Exception $e) {
    echo "❌ Erro ao buscar competições: " . $e->getMessage() . "<br>";
}

echo "<br><h3>✅ Diagnóstico concluído!</h3>";
echo "<p>Se todos os testes passaram, o problema pode estar no navegador ou no caminho do arquivo.</p>";
echo "<p><a href='inscricoes.php'>Tentar acessar inscricoes.php novamente</a></p>";
