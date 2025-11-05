<?php
require_once 'config/config.php';

$inscricao = null;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $protocolo = sanitize($_POST['protocolo'] ?? '');

    if (empty($cpf) && empty($protocolo)) {
        $erro = 'Informe o CPF ou o Protocolo para consultar';
    } else {
        $pdo = getDBConnection();

        if (!empty($protocolo)) {
            $stmt = $pdo->prepare("
                SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
                FROM inscricoes i
                LEFT JOIN modalidades m ON i.modalidade_id = m.id
                LEFT JOIN categorias c ON i.categoria_id = c.id
                WHERE i.protocolo = ?
            ");
            $stmt->execute([$protocolo]);
        } else {
            $stmt = $pdo->prepare("
                SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
                FROM inscricoes i
                LEFT JOIN modalidades m ON i.modalidade_id = m.id
                LEFT JOIN categorias c ON i.categoria_id = c.id
                WHERE i.cpf = ?
                ORDER BY i.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$cpf]);
        }

        $inscricao = $stmt->fetch();

        if (!$inscricao) {
            $erro = 'Inscrição não encontrada';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Inscrição</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-running"></i> Inscrição de Atletas</h1>
            <nav>
                <a href="index.php">Inscrição</a>
                <a href="consulta.php" class="active">Consultar Inscrição</a>
                <a href="admin/login.php">Área Administrativa</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-wrapper">
            <div class="form-header">
                <h2>Consultar Inscrição</h2>
                <p>Informe seu CPF ou Protocolo para consultar sua inscrição</p>
            </div>

            <form method="POST" action="">
                <div class="form-section">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00">
                        </div>

                        <div class="form-group" style="text-align: center; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 1.2rem; color: var(--text-light);">OU</span>
                        </div>

                        <div class="form-group">
                            <label for="protocolo">Protocolo</label>
                            <input type="text" id="protocolo" name="protocolo" placeholder="INSC20250101XXXXXX">
                        </div>
                    </div>

                    <?php if ($erro): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($inscricao): ?>
                <div class="card" style="margin-top: 40px;">
                    <div class="card-header">
                        <i class="fas fa-file-alt"></i> Dados da Inscrição
                    </div>

                    <div style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <strong style="color: var(--text-light); font-size: 0.9rem;">Protocolo:</strong>
                                <h3 style="color: var(--primary-color); margin: 5px 0;"><?php echo htmlspecialchars($inscricao['protocolo']); ?></h3>
                            </div>
                            <div>
                                <strong style="color: var(--text-light); font-size: 0.9rem;">Status:</strong>
                                <div style="margin-top: 5px;">
                                    <?php
                                    $statusClass = [
                                        'Pendente' => 'badge-warning',
                                        'Aprovada' => 'badge-success',
                                        'Rejeitada' => 'badge-danger',
                                        'Cancelada' => 'badge-info'
                                    ];
                                    $class = $statusClass[$inscricao['status']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo $inscricao['status']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 style="margin-bottom: 15px;"><i class="fas fa-user"></i> Dados Pessoais</h4>
                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Nome Completo:</strong>
                            <?php echo htmlspecialchars($inscricao['nome_completo']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">CPF:</strong>
                            <?php echo formatCPF($inscricao['cpf']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">RG:</strong>
                            <?php echo htmlspecialchars($inscricao['rg']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Data de Nascimento:</strong>
                            <?php echo date('d/m/Y', strtotime($inscricao['data_nascimento'])); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Gênero:</strong>
                            <?php echo htmlspecialchars($inscricao['genero']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Email:</strong>
                            <?php echo htmlspecialchars($inscricao['email']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Telefone:</strong>
                            <?php echo formatPhone($inscricao['telefone']); ?>
                        </div>
                    </div>

                    <h4 style="margin-bottom: 15px;"><i class="fas fa-medal"></i> Dados Esportivos</h4>
                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Modalidade:</strong>
                            <?php echo htmlspecialchars($inscricao['modalidade_nome']); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Categoria:</strong>
                            <?php echo htmlspecialchars($inscricao['categoria_nome']); ?>
                        </div>

                        <?php if ($inscricao['experiencia_anos']): ?>
                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Experiência:</strong>
                            <?php echo $inscricao['experiencia_anos']; ?> anos
                        </div>
                        <?php endif; ?>

                        <?php if ($inscricao['clube_anterior']): ?>
                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Clube Anterior:</strong>
                            <?php echo htmlspecialchars($inscricao['clube_anterior']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h4 style="margin-bottom: 15px;"><i class="fas fa-clock"></i> Informações da Inscrição</h4>
                    <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Data de Inscrição:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($inscricao['created_at'])); ?>
                        </div>

                        <div class="info-item" style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                            <strong style="display: block; color: var(--primary-color); margin-bottom: 5px;">Última Atualização:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($inscricao['updated_at'])); ?>
                        </div>
                    </div>

                    <?php if ($inscricao['observacoes']): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Observações:</strong><br>
                                <?php echo nl2br(htmlspecialchars($inscricao['observacoes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions" style="margin-top: 30px;">
                        <a href="comprovante.php?protocolo=<?php echo $inscricao['protocolo']; ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Baixar Comprovante
                        </a>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="public/js/script.js"></script>
</body>
</html>
