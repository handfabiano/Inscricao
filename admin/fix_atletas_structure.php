<?php
/**
 * Fix Atletas Structure - Web Interface
 *
 * Adiciona colunas faltantes na tabela atletas
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
    <title>Fix Atletas Structure - Migration</title>
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

        .column-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .column-item {
            padding: 8px 12px;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #28a745;
            font-size: 14px;
        }

        .column-item.missing {
            border-left-color: #dc3545;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Fix Atletas Structure - Migration</h1>
            <p>Adiciona colunas faltantes na tabela atletas</p>
        </div>

        <div class="content">
            <?php
            $execute = isset($_GET['execute']) && $_GET['execute'] === 'yes';

            try {
                $pdo = getDBConnection();

                // Verificar estrutura atual
                echo '<div class="section">';
                echo '<h2>📋 Estrutura Atual da Tabela Atletas</h2>';

                $stmt = $pdo->query("DESCRIBE atletas");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $existing_columns = [];
                foreach ($columns as $col) {
                    $existing_columns[] = $col['Field'];
                }

                // Colunas que deveriam existir
                $required_columns = [
                    'nome_completo' => 'Nome completo do atleta',
                    'genero' => 'Gênero do atleta',
                    'cep' => 'CEP do endereço',
                    'numero' => 'Número do endereço',
                    'complemento' => 'Complemento do endereço',
                    'bairro' => 'Bairro do endereço',
                    'foto_path' => 'Caminho da foto',
                    'documento_identidade_path' => 'Caminho do documento',
                    'comprovante_residencia_path' => 'Caminho do comprovante',
                    'atestado_medico_path' => 'Caminho do atestado',
                    'observacoes' => 'Observações adicionais'
                ];

                $missing_columns = [];
                foreach ($required_columns as $col => $desc) {
                    if (!in_array($col, $existing_columns)) {
                        $missing_columns[] = $col;
                    }
                }

                echo '<div class="status-box">';
                echo '<h3>Status das Colunas</h3>';
                echo '<p><strong>Total de colunas na tabela:</strong> ' . count($existing_columns) . '</p>';
                echo '<p><strong>Colunas faltando:</strong> ' . count($missing_columns) . '</p>';
                echo '</div>';

                if (count($missing_columns) > 0) {
                    echo '<div class="status-box warning">';
                    echo '<h3>⚠️ Colunas Faltando</h3>';
                    echo '<div class="column-list">';
                    foreach ($missing_columns as $col) {
                        echo '<div class="column-item missing">❌ ' . $col . '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                echo '<div class="status-box">';
                echo '<h3>✅ Colunas Existentes</h3>';
                echo '<div class="column-list">';
                foreach ($required_columns as $col => $desc) {
                    if (in_array($col, $existing_columns)) {
                        echo '<div class="column-item">✅ ' . $col . '</div>';
                    }
                }
                echo '</div>';
                echo '</div>';

                echo '</div>';

                // Executar migration se solicitado
                if ($execute) {
                    echo '<div class="section">';
                    echo '<h2>⚙️ Executando Migration</h2>';

                    if (count($missing_columns) > 0) {
                        echo '<div class="log-output">';

                        $logs = [];

                        // Adicionar cada coluna faltante
                        foreach ($required_columns as $col => $desc) {
                            if (in_array($col, $missing_columns)) {
                                try {
                                    switch ($col) {
                                        case 'nome_completo':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN nome_completo VARCHAR(255) AFTER id");
                                            $pdo->exec("UPDATE atletas SET nome_completo = nome WHERE nome_completo IS NULL OR nome_completo = ''");
                                            break;
                                        case 'genero':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN genero VARCHAR(20) AFTER data_nascimento");
                                            $pdo->exec("UPDATE atletas SET genero = sexo WHERE genero IS NULL OR genero = ''");
                                            break;
                                        case 'cep':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN cep VARCHAR(10) AFTER telefone_responsavel");
                                            break;
                                        case 'numero':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN numero VARCHAR(10) AFTER endereco");
                                            break;
                                        case 'complemento':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN complemento VARCHAR(100) AFTER numero");
                                            break;
                                        case 'bairro':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN bairro VARCHAR(100) AFTER complemento");
                                            break;
                                        case 'foto_path':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN foto_path VARCHAR(255) AFTER estado");
                                            $pdo->exec("UPDATE atletas SET foto_path = foto WHERE foto_path IS NULL OR foto_path = ''");
                                            break;
                                        case 'documento_identidade_path':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN documento_identidade_path VARCHAR(255) AFTER foto_path");
                                            $pdo->exec("UPDATE atletas SET documento_identidade_path = documento_identidade WHERE documento_identidade_path IS NULL OR documento_identidade_path = ''");
                                            break;
                                        case 'comprovante_residencia_path':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN comprovante_residencia_path VARCHAR(255) AFTER documento_identidade_path");
                                            break;
                                        case 'atestado_medico_path':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN atestado_medico_path VARCHAR(255) AFTER comprovante_residencia_path");
                                            break;
                                        case 'observacoes':
                                            $pdo->exec("ALTER TABLE atletas ADD COLUMN observacoes TEXT AFTER atestado_medico_path");
                                            break;
                                    }
                                    $logs[] = "✅ Coluna '$col' adicionada com sucesso";
                                } catch (PDOException $e) {
                                    $logs[] = "⚠️ Aviso para '$col': " . $e->getMessage();
                                }
                            }
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
                        echo '<p>A tabela atletas foi atualizada corretamente.</p>';
                        echo '</div>';

                        echo '<a href="fix_atletas_structure.php" class="btn">🔄 Verificar Resultado</a>';

                    } else {
                        echo '<div class="status-box success">';
                        echo '<h3>✅ Nada a fazer!</h3>';
                        echo '<p>Todas as colunas necessárias já existem.</p>';
                        echo '</div>';
                    }

                    echo '</div>';

                } else {
                    // Mostrar botão para executar
                    echo '<div class="section">';
                    echo '<h2>🚀 Executar Migration</h2>';

                    if (count($missing_columns) > 0) {
                        echo '<div class="status-box warning">';
                        echo '<h3>⚠️ Migration Necessária</h3>';
                        echo '<p>As seguintes colunas precisam ser adicionadas:</p>';
                        echo '<ul style="margin-left: 20px; margin-top: 10px;">';
                        foreach ($missing_columns as $col) {
                            echo '<li><strong>' . $col . '</strong> - ' . $required_columns[$col] . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';

                        echo '<a href="fix_atletas_structure.php?execute=yes" class="btn">▶️ Executar Migration Agora</a>';

                    } else {
                        echo '<div class="status-box success">';
                        echo '<h3>✅ Tudo Certo!</h3>';
                        echo '<p>Todas as colunas necessárias já existem. Nenhuma ação necessária.</p>';
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
                    <p><strong>Arquivo de Migration:</strong> migrations/002_fix_atletas_structure.sql</p>
                    <p><strong>Tabela Afetada:</strong> atletas</p>
                    <p><strong>Operações:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Adiciona colunas de endereço detalhado (cep, numero, complemento, bairro)</li>
                        <li>Adiciona colunas de documentos com sufixo _path</li>
                        <li>Adiciona coluna observacoes para dados adicionais</li>
                        <li>Adiciona coluna nome_completo (copia de nome)</li>
                        <li>Adiciona coluna genero (copia de sexo)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
