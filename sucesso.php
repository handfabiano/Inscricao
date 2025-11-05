<?php
require_once 'config/config.php';

$protocolo = isset($_GET['protocolo']) ? $_GET['protocolo'] : '';

if (empty($protocolo)) {
    header('Location: index.php');
    exit;
}

// Buscar dados da inscrição
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
    FROM inscricoes i
    LEFT JOIN modalidades m ON i.modalidade_id = m.id
    LEFT JOIN categorias c ON i.categoria_id = c.id
    WHERE i.protocolo = ?
");
$stmt->execute([$protocolo]);
$inscricao = $stmt->fetch();

if (!$inscricao) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição Realizada com Sucesso</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 50px auto;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .protocolo-box {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
        }

        .protocolo-box h2 {
            font-size: 2.5rem;
            margin: 10px 0;
            letter-spacing: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            text-align: left;
            margin: 30px 0;
        }

        .info-item {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
        }

        .info-item strong {
            display: block;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        @media print {
            .actions, header, footer {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-running"></i> Inscrição de Atletas</h1>
            <nav>
                <a href="index.php">Inscrição</a>
                <a href="consulta.php">Consultar Inscrição</a>
                <a href="admin/login.php">Área Administrativa</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-wrapper success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1>Inscrição Realizada com Sucesso!</h1>
            <p>Sua inscrição foi registrada em nosso sistema.</p>

            <div class="protocolo-box">
                <p>Protocolo de Inscrição:</p>
                <h2><?php echo htmlspecialchars($protocolo); ?></h2>
                <p style="font-size: 0.9rem; margin-top: 10px; opacity: 0.9;">
                    Guarde este número para consultar sua inscrição
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Dados da Inscrição
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <strong>Nome:</strong>
                        <?php echo htmlspecialchars($inscricao['nome_completo']); ?>
                    </div>

                    <div class="info-item">
                        <strong>CPF:</strong>
                        <?php echo formatCPF($inscricao['cpf']); ?>
                    </div>

                    <div class="info-item">
                        <strong>Data de Nascimento:</strong>
                        <?php echo date('d/m/Y', strtotime($inscricao['data_nascimento'])); ?>
                    </div>

                    <div class="info-item">
                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($inscricao['email']); ?>
                    </div>

                    <div class="info-item">
                        <strong>Telefone:</strong>
                        <?php echo formatPhone($inscricao['telefone']); ?>
                    </div>

                    <div class="info-item">
                        <strong>Modalidade:</strong>
                        <?php echo htmlspecialchars($inscricao['modalidade_nome']); ?>
                    </div>

                    <div class="info-item">
                        <strong>Categoria:</strong>
                        <?php echo htmlspecialchars($inscricao['categoria_nome']); ?>
                    </div>

                    <div class="info-item">
                        <strong>Status:</strong>
                        <span class="badge badge-warning"><?php echo $inscricao['status']; ?></span>
                    </div>

                    <div class="info-item">
                        <strong>Data de Inscrição:</strong>
                        <?php echo date('d/m/Y H:i', strtotime($inscricao['created_at'])); ?>
                    </div>
                </div>

                <div class="alert alert-info" style="margin-top: 20px;">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Próximos Passos:</strong>
                        <ul style="margin: 10px 0 0 20px; text-align: left;">
                            <li>Sua inscrição será analisada pela equipe responsável</li>
                            <li>Você receberá um email com a confirmação ou solicitação de ajustes</li>
                            <li>O prazo de análise é de até 5 dias úteis</li>
                            <li>Utilize o protocolo para acompanhar o status da sua inscrição</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Imprimir Comprovante
                </button>
                <a href="comprovante.php?protocolo=<?php echo $protocolo; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf"></i> Baixar PDF
                </a>
                <a href="consulta.php" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Consultar Inscrição
                </a>
                <a href="index.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nova Inscrição
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
