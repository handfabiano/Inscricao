<?php
/**
 * Validação Completa do Sistema - Web Version
 *
 * Testa: configurações, relatórios, convites e estrutura do banco
 *
 * @version 2.1
 * @date 2025-11-10
 */

// Iniciar output buffering para evitar problemas com headers
ob_start();

require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação do Sistema</title>
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
            max-width: 1000px;
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
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #667eea;
        }

        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section h2::before {
            content: attr(data-icon);
            font-size: 24px;
            margin-right: 10px;
        }

        .test-item {
            padding: 10px 15px;
            margin: 8px 0;
            background: white;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 3px solid transparent;
        }

        .test-item.success {
            border-left-color: #28a745;
        }

        .test-item.error {
            border-left-color: #dc3545;
        }

        .test-item.info {
            border-left-color: #17a2b8;
        }

        .test-label {
            font-weight: 500;
            color: #333;
        }

        .test-status {
            font-size: 20px;
        }

        .test-message {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .status-box {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .status-box.alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }

        .status-box.success {
            background: #d4edda;
            border: 1px solid #28a745;
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

        .links-uteis {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .links-uteis h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .links-uteis ul {
            list-style: none;
        }

        .links-uteis li {
            padding: 8px 0;
        }

        .links-uteis a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .links-uteis a:hover {
            text-decoration: underline;
        }

        .timestamp {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Validação Completa do Sistema</h1>
            <p>Verificação de estrutura, tabelas e configurações</p>
        </div>

        <div class="content">
            <?php
            try {
                $pdo = getDBConnection();

                // ========================================
                // 1. TESTE DE CONFIGURAÇÕES
                // ========================================
                echo '<div class="section">';
                echo '<h2 data-icon="1️⃣">Configurações do Sistema</h2>';

                // Verificar tabela administradores
                $stmt = $pdo->query("SHOW TABLES LIKE 'administradores'");
                $admin_exists = $stmt->rowCount() > 0;

                echo '<div class="test-item ' . ($admin_exists ? 'success' : 'error') . '">';
                echo '<div>';
                echo '<div class="test-label">Tabela administradores</div>';
                if ($admin_exists) {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo '<div class="test-message">Total de admins cadastrados: ' . $result['total'] . '</div>';
                }
                echo '</div>';
                echo '<div class="test-status">' . ($admin_exists ? '✅' : '❌') . '</div>';
                echo '</div>';

                // Verificar arquivo configuracoes.php
                $file_exists = file_exists(__DIR__ . '/configuracoes.php');
                echo '<div class="test-item ' . ($file_exists ? 'success' : 'error') . '">';
                echo '<div class="test-label">Arquivo configuracoes.php</div>';
                echo '<div class="test-status">' . ($file_exists ? '✅' : '❌') . '</div>';
                echo '</div>';

                echo '</div>';

                // ========================================
                // 2. TESTE DE RELATÓRIOS
                // ========================================
                echo '<div class="section">';
                echo '<h2 data-icon="2️⃣">Sistema de Relatórios</h2>';

                $file_exists = file_exists(__DIR__ . '/relatorios.php');
                echo '<div class="test-item ' . ($file_exists ? 'success' : 'error') . '">';
                echo '<div class="test-label">Arquivo relatorios.php</div>';
                echo '<div class="test-status">' . ($file_exists ? '✅' : '❌') . '</div>';
                echo '</div>';

                // Verificar tabelas essenciais
                $essential_tables = [
                    'equipes' => 'Gerenciamento de equipes',
                    'competicoes' => 'Gerenciamento de competições',
                    'atletas' => 'Cadastro de atletas',
                    'inscricoes_competicoes' => 'Inscrições em competições'
                ];

                foreach ($essential_tables as $table => $description) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    $exists = $stmt->rowCount() > 0;

                    echo '<div class="test-item ' . ($exists ? 'success' : 'error') . '">';
                    echo '<div>';
                    echo '<div class="test-label">Tabela ' . $table . '</div>';
                    echo '<div class="test-message">' . $description . '</div>';
                    echo '</div>';
                    echo '<div class="test-status">' . ($exists ? '✅' : '❌') . '</div>';
                    echo '</div>';
                }

                echo '</div>';

                // ========================================
                // 3. TESTE DE CONVITES DE ATLETAS
                // ========================================
                echo '<div class="section">';
                echo '<h2 data-icon="3️⃣">Sistema de Convites de Atletas</h2>';

                // Verificar tabela
                $stmt = $pdo->query("SHOW TABLES LIKE 'convites_atletas'");
                $table_exists = $stmt->rowCount() > 0;

                echo '<div class="test-item ' . ($table_exists ? 'success' : 'error') . '">';
                echo '<div class="test-label">Tabela convites_atletas</div>';
                echo '<div class="test-status">' . ($table_exists ? '✅' : '❌') . '</div>';
                echo '</div>';

                if ($table_exists) {
                    // Verificar estrutura das colunas
                    $stmt = $pdo->query("DESCRIBE convites_atletas");
                    $existing_columns = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $existing_columns[$row['Field']] = $row;
                    }

                    // Verificar colunas CORRETAS (novas)
                    $correct_columns = [
                        'data_expiracao' => 'Data de expiração do convite',
                        'data_aceite' => 'Data em que o convite foi aceito',
                        'token' => 'Token único do convite',
                        'equipe_id' => 'ID da equipe que criou o convite'
                    ];

                    $all_correct = true;
                    foreach ($correct_columns as $column => $description) {
                        $exists = isset($existing_columns[$column]);
                        if (!$exists) $all_correct = false;

                        echo '<div class="test-item ' . ($exists ? 'success' : 'error') . '">';
                        echo '<div>';
                        echo '<div class="test-label">Coluna ' . $column . '</div>';
                        echo '<div class="test-message">' . $description . '</div>';
                        echo '</div>';
                        echo '<div class="test-status">' . ($exists ? '✅' : '❌') . '</div>';
                        echo '</div>';
                    }

                    // Verificar colunas ANTIGAS (devem NÃO existir)
                    $old_columns = [
                        'validade_ate' => 'Coluna antiga (deve ser data_expiracao)',
                        'usado_em' => 'Coluna antiga (deve ser data_aceite)'
                    ];

                    $has_old = false;
                    foreach ($old_columns as $column => $description) {
                        $exists = isset($existing_columns[$column]);
                        if ($exists) {
                            $has_old = true;
                            $all_correct = false;
                        }

                        echo '<div class="test-item ' . (!$exists ? 'success' : 'error') . '">';
                        echo '<div>';
                        echo '<div class="test-label">Coluna ' . $column . ' (antiga)</div>';
                        echo '<div class="test-message">' . ($exists ? 'Ainda existe - precisa migration' : 'Não existe - correto!') . '</div>';
                        echo '</div>';
                        echo '<div class="test-status">' . (!$exists ? '✅' : '❌') . '</div>';
                        echo '</div>';
                    }

                    // Total de convites
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM convites_atletas");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    echo '<div class="test-item info">';
                    echo '<div class="test-label">Total de convites no banco</div>';
                    echo '<div class="test-status">' . $result['total'] . '</div>';
                    echo '</div>';

                    // Status geral da estrutura
                    if ($has_old) {
                        echo '<div class="status-box alert">';
                        echo '<h3>⚠️ Migration Necessária</h3>';
                        echo '<p>A tabela possui colunas antigas que precisam ser renomeadas.</p>';
                        echo '<p><strong>Ação necessária:</strong> Execute a migration em <a href="fix_convites_columns.php">fix_convites_columns.php</a></p>';
                        echo '</div>';
                    } else if ($all_correct) {
                        echo '<div class="status-box success">';
                        echo '<h3>✅ Estrutura Correta!</h3>';
                        echo '<p>Todas as colunas estão com os nomes corretos. Sistema pronto para uso!</p>';
                        echo '</div>';
                    }
                }

                echo '</div>';

                // ========================================
                // 4. RESUMO DE TABELAS
                // ========================================
                echo '<div class="section">';
                echo '<h2 data-icon="4️⃣">Resumo do Banco de Dados</h2>';

                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                echo '<div class="test-item info">';
                echo '<div class="test-label">Total de tabelas no banco</div>';
                echo '<div class="test-status">' . count($tables) . '</div>';
                echo '</div>';

                // Verificar tabelas por categoria
                $categories = [
                    'Sistema' => ['administradores', 'usuarios', 'logs', 'migrations'],
                    'Competições' => ['competicoes', 'modalidades', 'categorias', 'inscricoes_competicoes'],
                    'Equipes/Atletas' => ['equipes', 'atletas', 'inscricoes_atletas', 'convites_atletas'],
                    'Organizações' => ['organizacoes', 'planos_assinatura', 'historico_assinaturas']
                ];

                foreach ($categories as $category => $category_tables) {
                    $found = array_intersect($category_tables, $tables);
                    if (count($found) > 0) {
                        echo '<div class="status-box">';
                        echo '<strong>' . $category . ':</strong> ';
                        echo implode(', ', $found);
                        echo '</div>';
                    }
                }

                echo '</div>';

                // ========================================
                // 5. LINKS ÚTEIS
                // ========================================
                echo '<div class="links-uteis">';
                echo '<h3>🔗 Links Úteis</h3>';
                echo '<ul>';
                echo '<li>🏠 <a href="index.php">Dashboard Admin</a></li>';
                echo '<li>⚙️ <a href="configuracoes.php">Configurações</a></li>';
                echo '<li>📊 <a href="relatorios.php">Relatórios</a></li>';
                echo '<li>✉️ <a href="convites_atletas.php">Convites de Atletas</a></li>';

                // Link para fix apenas se necessário
                if (isset($has_old) && $has_old) {
                    echo '<li>🔧 <a href="fix_convites_columns.php" style="color: #dc3545; font-weight: bold;">Corrigir Colunas (NECESSÁRIO)</a></li>';
                }

                echo '</ul>';
                echo '</div>';

            } catch (PDOException $e) {
                echo '<div class="section">';
                echo '<div class="status-box alert">';
                echo '<h3>❌ Erro de Conexão com o Banco</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
                echo '</div>';
            }
            ?>

            <div class="timestamp">
                Validação executada em: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>
</body>
</html>
