<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipe_id = $_SESSION['equipe_id'];

// Buscar dados da equipe
$stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
$stmt->execute([$equipe_id]);
$equipe = $stmt->fetch();

// Estatísticas
$stats = [
    'atletas' => $pdo->prepare("SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = ? AND ativo = 1"),
    'inscricoes' => $pdo->prepare("SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = ?"),
    'competicoes_abertas' => $pdo->query("SELECT COUNT(*) FROM competicoes WHERE status = 'Aberta' AND data_fim_inscricoes >= CURDATE()")
];

$stats['atletas']->execute([$equipe_id]);
$total_atletas = $stats['atletas']->fetchColumn();

$stats['inscricoes']->execute([$equipe_id]);
$total_inscricoes = $stats['inscricoes']->fetchColumn();

$competicoes_abertas = $stats['competicoes_abertas']->fetchColumn();

// Últimos atletas
$stmt = $pdo->prepare("
    SELECT * FROM atletas
    WHERE equipe_atual_id = ? AND ativo = 1
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$equipe_id]);
$ultimos_atletas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($equipe['nome']); ?></title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($equipe['nome']); ?></h1>
            <nav>
                <a href="index.php" class="active">Dashboard</a>
                <a href="atletas.php">Atletas</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="competicoes.php">Competições Abertas</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin: 0;">Bem-vindo, <?php echo htmlspecialchars($equipe['responsavel_nome']); ?>!</h2>
            <p style="color: var(--text-light); margin: 5px 0 0 0;">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($equipe['cidade']); ?> - <?php echo $equipe['estado']; ?>
            </p>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3><?php echo $total_atletas; ?></h3>
                <p>Atletas Ativos</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #2563eb, #1e40af);">
                <h3><?php echo $total_inscricoes; ?></h3>
                <p>Inscrições Realizadas</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3><?php echo $competicoes_abertas; ?></h3>
                <p>Competições Abertas</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <h3><?php echo $equipe['status']; ?></h3>
                <p>Status da Equipe</p>
            </div>
        </div>

        <!-- Grid com 2 colunas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; margin-top: 30px;">
            <!-- Últimos Atletas -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-friends"></i> Últimos Atletas Cadastrados
                </div>
                <?php if (empty($ultimos_atletas)): ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-light);">
                        <i class="fas fa-user-plus" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>Nenhum atleta cadastrado ainda</p>
                        <a href="cadastrar_atleta.php" class="btn btn-success" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Cadastrar Primeiro Atleta
                        </a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Posição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_atletas as $atleta): ?>
                            <tr>
                                <td>
                                    <?php if ($atleta['foto_path']): ?>
                                        <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($atleta['nome_completo']); ?></td>
                                <td><?php echo htmlspecialchars($atleta['posicao'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; padding: 15px;">
                        <a href="atletas.php" class="btn btn-primary">Ver Todos os Atletas</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ações Rápidas -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Ações Rápidas
                </div>
                <div style="padding: 20px;">
                    <a href="cadastrar_atleta.php" class="btn btn-success" style="width: 100%; margin-bottom: 15px;">
                        <i class="fas fa-user-plus"></i> Cadastrar Novo Atleta
                    </a>
                    <a href="competicoes.php" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">
                        <i class="fas fa-trophy"></i> Ver Competições Abertas
                    </a>
                    <a href="inscricoes.php" class="btn btn-warning" style="width: 100%; margin-bottom: 15px;">
                        <i class="fas fa-clipboard-list"></i> Minhas Inscrições
                    </a>
                    <a href="perfil.php" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-cog"></i> Editar Perfil da Equipe
                    </a>
                </div>
            </div>
        </div>

        <!-- Informações Importantes -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Informações Importantes
            </div>
            <div style="padding: 20px;">
                <ul style="line-height: 2;">
                    <li>📸 <strong>Foto dos Atletas:</strong> Todas as fotos devem ser 3x4 (JPG ou PNG, máx 2MB)</li>
                    <li>📄 <strong>Documentos:</strong> RG, CPF e Atestado Médico são obrigatórios</li>
                    <li>👥 <strong>Inscrições:</strong> Cada competição tem regras específicas de quantidade de atletas</li>
                    <li>✅ <strong>Aprovação:</strong> Inscrições precisam ser aprovadas pela organização</li>
                    <li>💰 <strong>Pagamento:</strong> Envie o comprovante após realizar o pagamento</li>
                </ul>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0 - <?php echo htmlspecialchars($equipe['nome']); ?></p>
        </div>
    </footer>
</body>
</html>
