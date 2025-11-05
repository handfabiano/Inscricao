<?php
require_once '../config/config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
    FROM inscricoes i
    LEFT JOIN modalidades m ON i.modalidade_id = m.id
    LEFT JOIN categorias c ON i.categoria_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inscricao = $stmt->fetch();

if (!$inscricao) {
    header('Location: index.php');
    exit;
}

// Processar atualização de observações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['observacoes'])) {
    $obs = sanitize($_POST['observacoes']);
    $stmt = $pdo->prepare("UPDATE inscricoes SET observacoes = ? WHERE id = ?");
    $stmt->execute([$obs, $id]);

    header('Location: visualizar.php?id=' . $id . '&msg=atualizado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Inscrição</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Dados atualizados com sucesso!
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fas fa-file-alt"></i> Detalhes da Inscrição
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <div style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <strong style="color: var(--text-light); font-size: 0.9rem;">Protocolo:</strong>
                        <h3 style="color: var(--primary-color); margin: 5px 0;"><?php echo htmlspecialchars($inscricao['protocolo']); ?></h3>
                    </div>
                    <div>
                        <?php
                        $statusClass = [
                            'Pendente' => 'badge-warning',
                            'Aprovada' => 'badge-success',
                            'Rejeitada' => 'badge-danger',
                            'Cancelada' => 'badge-info'
                        ];
                        $class = $statusClass[$inscricao['status']] ?? 'badge-info';
                        ?>
                        <span class="badge <?php echo $class; ?>" style="font-size: 1.1rem; padding: 8px 20px;">
                            <?php echo $inscricao['status']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dados Pessoais -->
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-user"></i> Dados Pessoais
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Nome Completo:</strong>
                    <span><?php echo htmlspecialchars($inscricao['nome_completo']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">CPF:</strong>
                    <span><?php echo formatCPF($inscricao['cpf']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">RG:</strong>
                    <span><?php echo htmlspecialchars($inscricao['rg']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Data de Nascimento:</strong>
                    <span><?php echo date('d/m/Y', strtotime($inscricao['data_nascimento'])); ?> (<?php echo calcularIdade($inscricao['data_nascimento']); ?> anos)</span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Gênero:</strong>
                    <span><?php echo htmlspecialchars($inscricao['genero']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Email:</strong>
                    <span><?php echo htmlspecialchars($inscricao['email']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Telefone:</strong>
                    <span><?php echo formatPhone($inscricao['telefone']); ?></span>
                </div>
                <?php if ($inscricao['celular']): ?>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Celular:</strong>
                    <span><?php echo formatPhone($inscricao['celular']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Endereço -->
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-map-marker-alt"></i> Endereço
            </h4>
            <div style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <p>
                    <?php
                    echo htmlspecialchars($inscricao['endereco']) . ', ' .
                         htmlspecialchars($inscricao['numero']);
                    if ($inscricao['complemento']) {
                        echo ' - ' . htmlspecialchars($inscricao['complemento']);
                    }
                    echo '<br>' . htmlspecialchars($inscricao['bairro']) . '<br>' .
                         htmlspecialchars($inscricao['cidade']) . ' - ' .
                         htmlspecialchars($inscricao['estado']) . '<br>' .
                         'CEP: ' . preg_replace('/(\d{5})(\d{3})/', '$1-$2', $inscricao['cep']);
                    ?>
                </p>
            </div>

            <!-- Dados Esportivos -->
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-medal"></i> Dados Esportivos
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Modalidade:</strong>
                    <span><?php echo htmlspecialchars($inscricao['modalidade_nome']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Categoria:</strong>
                    <span><?php echo htmlspecialchars($inscricao['categoria_nome']); ?></span>
                </div>
                <?php if ($inscricao['experiencia_anos']): ?>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Experiência:</strong>
                    <span><?php echo $inscricao['experiencia_anos']; ?> anos</span>
                </div>
                <?php endif; ?>
                <?php if ($inscricao['clube_anterior']): ?>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Clube Anterior:</strong>
                    <span><?php echo htmlspecialchars($inscricao['clube_anterior']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Responsável -->
            <?php if ($inscricao['responsavel_nome']): ?>
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-user-shield"></i> Responsável
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Nome:</strong>
                    <span><?php echo htmlspecialchars($inscricao['responsavel_nome']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">CPF:</strong>
                    <span><?php echo formatCPF($inscricao['responsavel_cpf']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Telefone:</strong>
                    <span><?php echo formatPhone($inscricao['responsavel_telefone']); ?></span>
                </div>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
                    <strong style="display: block; color: var(--text-light); font-size: 0.85rem;">Parentesco:</strong>
                    <span><?php echo htmlspecialchars($inscricao['responsavel_parentesco']); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documentos -->
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-file-upload"></i> Documentos Enviados
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <?php if ($inscricao['foto_path']): ?>
                <a href="../public/uploads/<?php echo $inscricao['foto_path']; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-image"></i> Foto 3x4
                </a>
                <?php endif; ?>
                <?php if ($inscricao['documento_identidade_path']): ?>
                <a href="../public/uploads/<?php echo $inscricao['documento_identidade_path']; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-id-card"></i> Doc. Identidade
                </a>
                <?php endif; ?>
                <?php if ($inscricao['comprovante_residencia_path']): ?>
                <a href="../public/uploads/<?php echo $inscricao['comprovante_residencia_path']; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-home"></i> Comp. Residência
                </a>
                <?php endif; ?>
                <?php if ($inscricao['atestado_medico_path']): ?>
                <a href="../public/uploads/<?php echo $inscricao['atestado_medico_path']; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-medical"></i> Atestado Médico
                </a>
                <?php endif; ?>
            </div>

            <!-- Observações -->
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">
                <i class="fas fa-sticky-note"></i> Observações
            </h4>
            <form method="POST" action="">
                <div class="form-group">
                    <textarea name="observacoes" rows="4" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;"><?php echo htmlspecialchars($inscricao['observacoes'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Observações
                </button>
            </form>

            <!-- Ações -->
            <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--border-color); display: flex; gap: 10px; flex-wrap: wrap; justify-content: space-between;">
                <div style="display: flex; gap: 10px;">
                    <?php if ($inscricao['status'] === 'Pendente'): ?>
                        <a href="aprovar.php?id=<?php echo $id; ?>" class="btn btn-success" onclick="return confirm('Aprovar esta inscrição?')">
                            <i class="fas fa-check"></i> Aprovar
                        </a>
                        <a href="rejeitar.php?id=<?php echo $id; ?>" class="btn btn-danger" onclick="return confirm('Rejeitar esta inscrição?')">
                            <i class="fas fa-times"></i> Rejeitar
                        </a>
                    <?php endif; ?>
                </div>
                <a href="../comprovante.php?protocolo=<?php echo $inscricao['protocolo']; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i> Gerar Comprovante
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas - Painel Administrativo</p>
        </div>
    </footer>
</body>
</html>
