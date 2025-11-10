<?php
/**
 * Fix Convites Atletas Columns - Web Interface
 *
 * Renomeia colunas validade_ate e usado_em para data_expiracao e data_aceite
 *
 * @version 1.0
 * @date 2025-11-10
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_admin.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Convites Columns - Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .status-box.success {
            background: #d4edda;
            border-left-color: #28a745;
        }

        .status-box.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .status-box.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }

        .status-box h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }

        .status-item {
            padding: 8px 0;
            font-size: 14px;
        }

        .status-item.good::before {
            content: "✅ ";
        }

        .status-item.bad::before {
            content: "❌ ";
        }

        .status-item.info::before {
            content: "ℹ️ ";
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn.disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .log-output {
            background: #2d2d2d;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .table-structure {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table-structure th,
        .table-structure td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }

        .table-structure th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .table-structure tr:hover {
            background: #f8f9fa;
        }

        .highlight {
            background: #fff3cd;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix Convites Columns - Migration</h1>
            <p>Correção de nomes de colunas na tabela convites_atletas</p>
        </div>

        <div class="content">
            <?php
            $execute = isset($_GET['execute']) && $_GET['execute'] === 'yes';

            try {
                $pdo = getDBConnection();

                // Verificar estrutura atual
                echo '<div class="section">';
                echo '<h2>📋 Estrutura Atual da Tabela</h2>';

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

                echo '<div class="status-box">';
                echo '<h3>Status das Colunas Críticas</h3>';
                echo '<div class="status-item ' . ($has_validade_ate ? 'bad' : 'good') . '">';
                echo 'validade_ate: ' . ($has_validade_ate ? 'Existe (precisa ser renomeada)' : 'Não existe');
                echo '</div>';
                echo '<div class="status-item ' . ($has_usado_em ? 'bad' : 'good') . '">';
                echo 'usado_em: ' . ($has_usado_em ? 'Existe (precisa ser renomeada)' : 'Não existe');
                echo '</div>';
                echo '<div class="status-item ' . ($has_data_expiracao ? 'good' : 'bad') . '">';
                echo 'data_expiracao: ' . ($has_data_expiracao ? 'Existe (correto!)' : 'Não existe');
                echo '</div>';
                echo '<div class="status-item ' . ($has_data_aceite ? 'good' : 'bad') . '">';
                echo 'data_aceite: ' . ($has_data_aceite ? 'Existe (correto!)' : 'Não existe');
                echo '</div>';
                echo '</div>';

                // Mostrar tabela completa
                echo '<table class="table-structure">';
                echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>';
                foreach ($columns as $col) {
                    $highlight = in_array($col['Field'], ['validade_ate', 'usado_em', 'data_expiracao', 'data_aceite']) ? 'highlight' : '';
                    echo '<tr class="' . $highlight . '">';
                    echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';

                // Verificar convites existentes
                echo '<div class="section">';
                echo '<h2>📊 Convites Existentes</h2>';
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM convites_atletas");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '<div class="status-box info">';
                echo '<div class="status-item info">Total de convites no banco: ' . $result['total'] . '</div>';
                echo '</div>';
                echo '</div>';

                // Executar migration se solicitado
                if ($execute) {
                    echo '<div class="section">';
                    echo '<h2>⚙️ Executando Migration</h2>';

                    if (($has_validade_ate || $has_usado_em) && !$has_data_expiracao && !$has_data_aceite) {
                        echo '<div class="log-output">';

                        $logs = [];

                        // 1. Renomear validade_ate para data_expiracao
                        if ($has_validade_ate) {
                            try {
                                $pdo->exec("ALTER TABLE convites_atletas CHANGE COLUMN validade_ate data_expiracao DATETIME NOT NULL");
                                $logs[] = "✅ Coluna 'validade_ate' renomeada para 'data_expiracao'";
                            } catch (PDOException $e) {
                                $logs[] = "❌ Erro ao renomear validade_ate: " . $e->getMessage();
                            }
                        }

                        // 2. Renomear usado_em para data_aceite
                        if ($has_usado_em) {
                            try {
                                $pdo->exec("ALTER TABLE convites_atletas CHANGE COLUMN usado_em data_aceite DATETIME NULL");
                                $logs[] = "✅ Coluna 'usado_em' renomeada para 'data_aceite'";
                            } catch (PDOException $e) {
                                $logs[] = "❌ Erro ao renomear usado_em: " . $e->getMessage();
                            }
                        }

                        // 3. Adicionar telefone_atleta se não existir
                        $stmt = $pdo->query("SHOW COLUMNS FROM convites_atletas LIKE 'telefone_atleta'");
                        if ($stmt->rowCount() == 0) {
                            try {
                                $pdo->exec("ALTER TABLE convites_atletas ADD COLUMN telefone_atleta VARCHAR(20) AFTER email_atleta");
                                $logs[] = "✅ Coluna 'telefone_atleta' adicionada";
                            } catch (PDOException $e) {
                                $logs[] = "⚠️ Aviso telefone_atleta: " . $e->getMessage();
                            }
                        }

                        // 4. Adicionar data_criacao se não existir
                        $stmt = $pdo->query("SHOW COLUMNS FROM convites_atletas LIKE 'data_criacao'");
                        if ($stmt->rowCount() == 0) {
                            try {
                                $pdo->exec("ALTER TABLE convites_atletas ADD COLUMN data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status");
                                $logs[] = "✅ Coluna 'data_criacao' adicionada";
                            } catch (PDOException $e) {
                                $logs[] = "⚠️ Aviso data_criacao: " . $e->getMessage();
                            }
                        }

                        // 5. Atualizar ENUM do status
                        try {
                            $pdo->exec("ALTER TABLE convites_atletas MODIFY COLUMN status ENUM('Pendente', 'Aceito', 'Recusado', 'Expirado') DEFAULT 'Pendente'");
                            $logs[] = "✅ ENUM 'status' atualizado (adicionado 'Recusado')";
                        } catch (PDOException $e) {
                            $logs[] = "⚠️ Aviso status: " . $e->getMessage();
                        }

                        foreach ($logs as $log) {
                            echo $log . "\n";
                        }

                        echo "\n" . str_repeat("=", 50) . "\n";
                        echo "✅ MIGRATION CONCLUÍDA COM SUCESSO!\n";
                        echo str_repeat("=", 50) . "\n";

                        echo '</div>';

                        echo '<div class="status-box success">';
                        echo '<h3>Migration aplicada com sucesso!</h3>';
                        echo '<p>A tabela convites_atletas foi atualizada corretamente.</p>';
                        echo '</div>';

                        echo '<a href="fix_convites_columns.php" class="btn">🔄 Verificar Resultado</a>';

                    } else if ($has_data_expiracao && $has_data_aceite) {
                        echo '<div class="status-box success">';
                        echo '<h3>✅ Nada a fazer!</h3>';
                        echo '<p>As colunas já estão com os nomes corretos.</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="status-box warning">';
                        echo '<h3>⚠️ Estado Inconsistente</h3>';
                        echo '<p>A estrutura da tabela está em um estado inesperado. Verifique manualmente.</p>';
                        echo '</div>';
                    }

                    echo '</div>';

                } else {
                    // Mostrar botão para executar
                    echo '<div class="section">';
                    echo '<h2>🚀 Executar Migration</h2>';

                    if (($has_validade_ate || $has_usado_em) && !$has_data_expiracao && !$has_data_aceite) {
                        echo '<div class="status-box warning">';
                        echo '<h3>⚠️ Migration Necessária</h3>';
                        echo '<p>As colunas precisam ser renomeadas para o padrão correto:</p>';
                        echo '<ul style="margin-left: 20px; margin-top: 10px;">';
                        echo '<li><strong>validade_ate</strong> → <strong>data_expiracao</strong></li>';
                        echo '<li><strong>usado_em</strong> → <strong>data_aceite</strong></li>';
                        echo '</ul>';
                        echo '</div>';

                        echo '<a href="fix_convites_columns.php?execute=yes" class="btn">▶️ Executar Migration Agora</a>';

                    } else if ($has_data_expiracao && $has_data_aceite) {
                        echo '<div class="status-box success">';
                        echo '<h3>✅ Tudo Certo!</h3>';
                        echo '<p>As colunas já estão com os nomes corretos. Nenhuma ação necessária.</p>';
                        echo '</div>';

                        echo '<a href="index.php" class="btn">← Voltar ao Dashboard</a>';
                    }

                    echo '</div>';
                }

            } catch (PDOException $e) {
                echo '<div class="status-box error">';
                echo '<h3>❌ Erro de Conexão</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            ?>

            <div class="section">
                <h2>📚 Informações Técnicas</h2>
                <div class="status-box">
                    <p><strong>Arquivo de Migration:</strong> migrations/001_fix_convites_atletas_columns.sql</p>
                    <p><strong>Tabela Afetada:</strong> convites_atletas</p>
                    <p><strong>Operações:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>ALTER TABLE ... CHANGE COLUMN validade_ate data_expiracao</li>
                        <li>ALTER TABLE ... CHANGE COLUMN usado_em data_aceite</li>
                        <li>ALTER TABLE ... ADD COLUMN telefone_atleta (se não existir)</li>
                        <li>ALTER TABLE ... ADD COLUMN data_criacao (se não existir)</li>
                        <li>ALTER TABLE ... MODIFY status ENUM (adiciona opção 'Recusado')</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
