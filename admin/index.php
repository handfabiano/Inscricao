<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes");
$totalCompeticoes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Aprovada'");
$totalEquipes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 1");
$totalAtletas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes WHERE status = 'Aberta'");
$competicoesAbertas = $stmt->fetch()['total'];

// Competições recentes
$stmt = $pdo->query("
    SELECT c.*, m.nome as modalidade_nome
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    ORDER BY c.created_at DESC
    LIMIT 10
");
$competicoesRecentes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1>🏆 Sistema v3.0 - Admin</h1>
                <nav>
                    <a href="index.php">Dashboard</a>
                    <a href="competicoes.php">Competições</a>
                    <a href="logout.php">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 20px;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>!</h2>
            <p style="color: #64748b;">Nível de acesso: <strong><?php echo $_SESSION['admin_nivel']; ?></strong></p>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $totalCompeticoes; ?></h3>
                <p>Total de Competições</p>
            </div>

            <div class="stat-card" style="border-left-color: #10b981;">
                <h3 style="color: #10b981;"><?php echo $competicoesAbertas; ?></h3>
                <p>Competições Abertas</p>
            </div>

            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h3 style="color: #f59e0b;"><?php echo $totalEquipes; ?></h3>
                <p>Equipes Aprovadas</p>
            </div>

            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <h3 style="color: #8b5cf6;"><?php echo $totalAtletas; ?></h3>
                <p>Atletas Cadastrados</p>
            </div>
        </div>

        <!-- Competições Recentes -->
        <div class="card">
            <div class="card-header">
                Competições Recentes
            </div>

            <?php if (empty($competicoesRecentes)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    <p>Nenhuma competição cadastrada.</p>
                    <a href="competicoes.php" class="btn btn-primary" style="margin-top: 1rem;">Criar Primeira Competição</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Modalidade</th>
                            <th>Inscrições</th>
                            <th>Status</th>
                            <th>Criada em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competicoesRecentes as $comp): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($comp['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($comp['modalidade_nome'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo formatarData($comp['data_inicio_inscricoes']); ?> até
                                    <?php echo formatarData($comp['data_fim_inscricoes']); ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = [
                                        'Rascunho' => 'badge-warning',
                                        'Aberta' => 'badge-success',
                                        'Fechada' => 'badge-info',
                                        'Em Andamento' => 'badge-info',
                                        'Encerrada' => 'badge-danger',
                                        'Cancelada' => 'badge-danger'
                                    ];
                                    $class = $badgeClass[$comp['status']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo $comp['status']; ?></span>
                                </td>
                                <td><?php echo formatarDataHora($comp['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="competicoes.php" class="btn btn-primary">Gerenciar Competições</a>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.9rem;">
        <p>&copy; 2025 Sistema de Gestão de Competições Esportivas v3.0</p>
    </footer>
</body>
</html>
