<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Corrigir Dados da Tabela Equipes</h1>";

try {
    require_once 'config/database.php';
    $pdo = getDBConnection();

    // Verificar se é POST (confirmação)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
        $acao = $_POST['acao'];

        if ($acao === 'deletar_todos') {
            $stmt = $pdo->query("DELETE FROM equipes");
            $deleted = $stmt->rowCount();
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ {$deleted} registro(s) deletado(s) com sucesso!</strong>";
            echo "</div>";
        }

        if ($acao === 'deletar_nulls') {
            $stmt = $pdo->query("DELETE FROM equipes WHERE nome IS NULL OR email IS NULL OR senha IS NULL");
            $deleted = $stmt->rowCount();
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ {$deleted} registro(s) com campos NULL deletado(s)!</strong>";
            echo "</div>";
        }

        if ($acao === 'corrigir_organizacao') {
            // Verificar se existe alguma organização
            $stmt = $pdo->query("SELECT id FROM organizacoes LIMIT 1");
            $org = $stmt->fetch();

            if ($org) {
                $orgId = $org['id'];
                $stmt = $pdo->prepare("UPDATE equipes SET organizacao_id = ? WHERE organizacao_id IS NULL");
                $stmt->execute([$orgId]);
                $updated = $stmt->rowCount();
                echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
                echo "<strong>✅ {$updated} equipe(s) atualizada(s) com organizacao_id = {$orgId}!</strong>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
                echo "<strong>❌ Nenhuma organização encontrada no banco!</strong>";
                echo "</div>";
            }
        }

        if ($acao === 'corrigir_status') {
            $stmt = $pdo->query("UPDATE equipes SET status = 'Pendente' WHERE status IS NULL OR status = ''");
            $updated = $stmt->rowCount();
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ {$updated} equipe(s) com status corrigido para 'Pendente'!</strong>";
            echo "</div>";
        }

        if ($acao === 'corrigir_datas') {
            $stmt = $pdo->query("UPDATE equipes SET created_at = NOW() WHERE created_at IS NULL");
            $updated = $stmt->rowCount();
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ {$updated} equipe(s) com data de criação corrigida!</strong>";
            echo "</div>";
        }

        echo "<br><a href='diagnostico_equipes.php' class='btn'>Ver Diagnóstico Atualizado</a>";
        echo " | <a href='corrigir_equipes.php' class='btn'>Voltar</a>";
        echo "<hr>";
    }

    // Mostrar contadores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes");
    $total = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE nome IS NULL OR email IS NULL OR senha IS NULL");
    $nulls = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE organizacao_id IS NULL");
    $semOrg = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status IS NULL OR status = ''");
    $semStatus = $stmt->fetch()['total'];

    echo "<h2>Status Atual:</h2>";
    echo "<ul>";
    echo "<li><strong>Total de equipes:</strong> {$total}</li>";
    echo "<li><strong>Com campos NULL:</strong> <span style='color: " . ($nulls > 0 ? 'red' : 'green') . "'>{$nulls}</span></li>";
    echo "<li><strong>Sem organizacao_id:</strong> <span style='color: " . ($semOrg > 0 ? 'orange' : 'green') . "'>{$semOrg}</span></li>";
    echo "<li><strong>Sem status:</strong> <span style='color: " . ($semStatus > 0 ? 'orange' : 'green') . "'>{$semStatus}</span></li>";
    echo "</ul>";

    echo "<h2>Ações de Correção:</h2>";

    ?>
    <style>
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-success { background: #28a745; }
        .btn:hover { opacity: 0.8; }
        form { display: inline; }
    </style>

    <?php if ($nulls > 0): ?>
        <form method="POST" onsubmit="return confirm('Deletar equipes com campos NULL obrigatórios vazios?');">
            <input type="hidden" name="acao" value="deletar_nulls">
            <button type="submit" class="btn btn-warning">🗑️ Deletar Equipes com Campos NULL (<?php echo $nulls; ?>)</button>
        </form>
    <?php endif; ?>

    <?php if ($semOrg > 0): ?>
        <form method="POST" onsubmit="return confirm('Corrigir organizacao_id para todas as equipes sem organização?');">
            <input type="hidden" name="acao" value="corrigir_organizacao">
            <button type="submit" class="btn btn-success">🔧 Corrigir organizacao_id (<?php echo $semOrg; ?>)</button>
        </form>
    <?php endif; ?>

    <?php if ($semStatus > 0): ?>
        <form method="POST" onsubmit="return confirm('Definir status como Pendente para equipes sem status?');">
            <input type="hidden" name="acao" value="corrigir_status">
            <button type="submit" class="btn btn-success">🔧 Corrigir Status (<?php echo $semStatus; ?>)</button>
        </form>
    <?php endif; ?>

    <form method="POST" onsubmit="return confirm('Corrigir datas de criação NULL?');">
        <input type="hidden" name="acao" value="corrigir_datas">
        <button type="submit" class="btn btn-success">📅 Corrigir Datas NULL</button>
    </form>

    <br><br>

    <form method="POST" onsubmit="return confirm('⚠️ ATENÇÃO: Isso irá DELETAR TODAS AS EQUIPES! Tem certeza?');">
        <input type="hidden" name="acao" value="deletar_todos">
        <button type="submit" class="btn btn-danger">🗑️ DELETAR TODAS AS EQUIPES (<?php echo $total; ?>)</button>
    </form>

    <br><br>
    <a href="diagnostico_equipes.php" class="btn">📊 Ver Diagnóstico Completo</a>
    <a href="admin/equipes.php" class="btn">← Voltar para Equipes</a>

    <?php

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERRO:</h2>";
    echo "<pre style='color: red;'>" . $e->getMessage() . "</pre>";
}
?>
