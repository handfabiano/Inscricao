<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Estatísticas
$stats = [
    'competicoes' => $pdo->query("SELECT COUNT(*) FROM competicoes")->fetchColumn(),
    'equipes' => $pdo->query("SELECT COUNT(*) FROM equipes WHERE status = 'Aprovada'")->fetchColumn(),
    'atletas' => $pdo->query("SELECT COUNT(*) FROM atletas WHERE ativo = 1")->fetchColumn(),
    'inscricoes' => $pdo->query("SELECT COUNT(*) FROM inscricoes_competicoes")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-trophy"></i> Sistema v3.0 - Admin</h1>
            <nav>
                <a href="index.php" class="active">Dashboard</a>
                <a href="competicoes.php">Competições</a>
                <a href="equipes.php">Equipes</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>!</h2>

        <div class="stats-grid" style="margin-top: 30px;">
            <div class="stat-card">
                <h3><?php echo $stats['competicoes']; ?></h3>
                <p>Competições</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3><?php echo $stats['equipes']; ?></h3>
                <p>Equipes Aprovadas</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3><?php echo $stats['atletas']; ?></h3>
                <p>Atletas Ativos</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <h3><?php echo $stats['inscricoes']; ?></h3>
                <p>Inscrições</p>
            </div>
        </div>

        <div class="card" style="margin-top: 40px;">
            <div class="card-header">🚀 Sistema v3.0 - Competições Esportivas</div>
            <div style="padding: 30px; text-align: center;">
                <h3>Sistema Totalmente Novo!</h3>
                <p style="margin: 20px 0; color: var(--text-light);">
                    Modelo: <strong>Competições → Equipes → Atletas → Inscrições</strong>
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; text-align: left;">
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                        <i class="fas fa-trophy" style="font-size: 2rem; color: var(--primary-color);"></i>
                        <h4 style="margin: 10px 0;">Competições</h4>
                        <p style="font-size: 0.9rem; color: var(--text-light);">Banner, regras, períodos</p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                        <i class="fas fa-users" style="font-size: 2rem; color: var(--success-color);"></i>
                        <h4 style="margin: 10px 0;">Equipes</h4>
                        <p style="font-size: 0.9rem; color: var(--text-light);">Gestão por responsável</p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                        <i class="fas fa-user-friends" style="font-size: 2rem; color: var(--warning-color);"></i>
                        <h4 style="margin: 10px 0;">Atletas</h4>
                        <p style="font-size: 0.9rem; color: var(--text-light);">Com foto e histórico</p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                        <i class="fas fa-clipboard-check" style="font-size: 2rem; color: var(--danger-color);"></i>
                        <h4 style="margin: 10px 0;">Validações</h4>
                        <p style="font-size: 0.9rem; color: var(--text-light);">Regras automáticas</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0 - Gestão de Competições Esportivas</p>
        </div>
    </footer>
</body>
</html>
