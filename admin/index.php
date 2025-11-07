<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Estatísticas Gerais
$stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes");
$totalCompeticoes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Aprovada'");
$totalEquipes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 1");
$totalAtletas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes WHERE status = 'Aberta'");
$competicoesAbertas = $stmt->fetch()['total'];

// Estatísticas de Inscrições
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Pendente'");
$inscricoesPendentes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
$inscricoesConfirmadas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Pendente'");
$equipesPendentes = $stmt->fetch()['total'];

// Inscrições Recentes Pendentes
$stmt = $pdo->query("
    SELECT
        i.*,
        c.nome as competicao_nome,
        e.nome as equipe_nome,
        (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = i.id) as total_atletas
    FROM inscricoes_competicoes i
    INNER JOIN competicoes c ON i.competicao_id = c.id
    INNER JOIN equipes e ON i.equipe_id = e.id
    WHERE i.status = 'Pendente'
    ORDER BY i.created_at DESC
    LIMIT 5
");
$inscricoesRecentesPendentes = $stmt->fetchAll();

$pageTitle = 'Dashboard Administrativo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .quick-access-card {
            border-left: 4px solid;
            height: 100%;
        }

        .quick-access-card.primary {
            border-left-color: #0d6efd;
        }

        .quick-access-card.success {
            border-left-color: #198754;
        }

        .quick-access-card.warning {
            border-left-color: #ffc107;
        }

        .quick-access-card.danger {
            border-left-color: #dc3545;
        }

        .quick-access-card.info {
            border-left-color: #0dcaf0;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            background: #dc3545;
            color: white;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt"></i> Admin Master - Sistema de Competições
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                            <?php if ($inscricoesPendentes > 0): ?>
                                <span class="notification-badge"><?php echo $inscricoesPendentes; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 mb-5">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="mb-2">
                            <i class="fas fa-tachometer-alt text-primary"></i>
                            Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>!
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-shield-alt"></i>
                            Nível de acesso: <span class="badge bg-success"><?php echo htmlspecialchars($_SESSION['admin_nivel']); ?></span>
                            <span class="ms-3"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas Importantes -->
        <?php if ($inscricoesPendentes > 0 || $equipesPendentes > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Atenção!</h5>
                    <ul class="mb-0">
                        <?php if ($inscricoesPendentes > 0): ?>
                            <li>
                                <strong><?php echo $inscricoesPendentes; ?></strong>
                                <?php echo $inscricoesPendentes == 1 ? 'inscrição pendente' : 'inscrições pendentes'; ?>
                                aguardando aprovação.
                                <a href="inscricoes.php?status=Pendente" class="alert-link">Ver agora →</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($equipesPendentes > 0): ?>
                            <li>
                                <strong><?php echo $equipesPendentes; ?></strong>
                                <?php echo $equipesPendentes == 1 ? 'equipe pendente' : 'equipes pendentes'; ?>
                                aguardando aprovação.
                            </li>
                        <?php endif; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estatísticas Principais -->
        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Visão Geral do Sistema</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $totalCompeticoes; ?></h3>
                            <p class="text-muted mb-0 small">Competições</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $competicoesAbertas; ?></h3>
                            <p class="text-muted mb-0 small">Abertas para Inscrição</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $totalEquipes; ?></h3>
                            <p class="text-muted mb-0 small">Equipes Aprovadas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="fas fa-running"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $totalAtletas; ?></h3>
                            <p class="text-muted mb-0 small">Atletas Ativos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas de Inscrições -->
        <h5 class="mb-3"><i class="fas fa-clipboard-check"></i> Status das Inscrições</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $inscricoesPendentes; ?></h3>
                            <p class="text-muted mb-0 small">Pendentes</p>
                        </div>
                        <?php if ($inscricoesPendentes > 0): ?>
                            <a href="inscricoes.php?status=Pendente" class="btn btn-sm btn-warning">
                                <i class="fas fa-eye"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $inscricoesConfirmadas; ?></h3>
                            <p class="text-muted mb-0 small">Confirmadas</p>
                        </div>
                        <a href="inscricoes.php?status=Confirmada" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-list-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo $inscricoesPendentes + $inscricoesConfirmadas; ?></h3>
                            <p class="text-muted mb-0 small">Total de Inscrições</p>
                        </div>
                        <a href="inscricoes.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acesso Rápido -->
        <h5 class="mb-3"><i class="fas fa-bolt"></i> Acesso Rápido</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-lg-3">
                <a href="competicoes.php" class="text-decoration-none">
                    <div class="card quick-access-card primary shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-trophy fa-2x text-primary me-3"></i>
                                <h6 class="mb-0">Competições</h6>
                            </div>
                            <p class="text-muted small mb-0">Gerenciar competições e modalidades</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="inscricoes.php" class="text-decoration-none position-relative">
                    <div class="card quick-access-card success shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clipboard-list fa-2x text-success me-3"></i>
                                <h6 class="mb-0">Inscrições</h6>
                                <?php if ($inscricoesPendentes > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?php echo $inscricoesPendentes; ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-0">Aprovar e gerenciar inscrições</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="equipes.php" class="text-decoration-none">
                    <div class="card quick-access-card warning shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-users fa-2x text-warning me-3"></i>
                                <h6 class="mb-0">Equipes</h6>
                                <?php if ($equipesPendentes > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?php echo $equipesPendentes; ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-0">Gerenciar equipes cadastradas</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="atletas.php" class="text-decoration-none">
                    <div class="card quick-access-card info shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-running fa-2x text-info me-3"></i>
                                <h6 class="mb-0">Atletas</h6>
                            </div>
                            <p class="text-muted small mb-0">Ver todos os atletas cadastrados</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="relatorios.php" class="text-decoration-none">
                    <div class="card quick-access-card danger shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-chart-bar fa-2x text-danger me-3"></i>
                                <h6 class="mb-0">Relatórios</h6>
                            </div>
                            <p class="text-muted small mb-0">Gerar relatórios e estatísticas</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="calendario.php" class="text-decoration-none">
                    <div class="card quick-access-card primary shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-alt fa-2x text-primary me-3"></i>
                                <h6 class="mb-0">Calendário</h6>
                            </div>
                            <p class="text-muted small mb-0">Ver calendário de eventos</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="resultados.php" class="text-decoration-none">
                    <div class="card quick-access-card success shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-medal fa-2x text-success me-3"></i>
                                <h6 class="mb-0">Resultados</h6>
                            </div>
                            <p class="text-muted small mb-0">Registrar resultados e rankings</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="configuracoes.php" class="text-decoration-none">
                    <div class="card quick-access-card warning shadow-sm card-hover">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-cog fa-2x text-warning me-3"></i>
                                <h6 class="mb-0">Configurações</h6>
                            </div>
                            <p class="text-muted small mb-0">Configurar sistema e usuários</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Inscrições Pendentes Recentes -->
        <?php if (!empty($inscricoesRecentesPendentes)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning bg-opacity-10 border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-clock text-warning"></i>
                            Inscrições Pendentes Recentes
                            <span class="badge bg-warning text-dark"><?php echo $inscricoesPendentes; ?></span>
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Protocolo</th>
                                    <th>Competição</th>
                                    <th>Equipe</th>
                                    <th class="text-center">Atletas</th>
                                    <th>Data</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoesRecentesPendentes as $insc): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($insc['protocolo']); ?></span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($insc['competicao_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($insc['equipe_nome']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $insc['total_atletas']; ?> atletas</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo formatarDataHora($insc['created_at']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <a href="inscricoes.php#inscricao-<?php echo $insc['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light text-center border-0">
                        <a href="inscricoes.php?status=Pendente" class="btn btn-warning">
                            <i class="fas fa-clipboard-check"></i> Ver Todas as Inscrições Pendentes
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas - Dashboard Master Admin</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
