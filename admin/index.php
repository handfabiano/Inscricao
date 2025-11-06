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
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt"></i> Admin - Sistema de Competições
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
                        <a class="nav-link" href="competicoes.php">
                            <i class="fas fa-trophy"></i> Competições
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

    <div class="container mt-4 mb-5">
        <!-- Welcome Header -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h2 class="mb-2">
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>!
                </h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-shield-alt"></i>
                    Nível de acesso: <span class="badge bg-success"><?php echo htmlspecialchars($_SESSION['admin_nivel']); ?></span>
                </p>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-2x text-primary mb-2"></i>
                        <h3 class="display-4 mb-1"><?php echo $totalCompeticoes; ?></h3>
                        <p class="text-muted mb-0 small">Total de Competições</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-door-open fa-2x text-success mb-2"></i>
                        <h3 class="display-4 mb-1 text-success"><?php echo $competicoesAbertas; ?></h3>
                        <p class="text-muted mb-0 small">Competições Abertas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h3 class="display-4 mb-1 text-warning"><?php echo $totalEquipes; ?></h3>
                        <p class="text-muted mb-0 small">Equipes Aprovadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <i class="fas fa-running fa-2x text-info mb-2"></i>
                        <h3 class="display-4 mb-1 text-info"><?php echo $totalAtletas; ?></h3>
                        <p class="text-muted mb-0 small">Atletas Cadastrados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Competições Recentes -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Competições Recentes
                </h5>
            </div>

            <?php if (empty($competicoesRecentes)): ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-trophy text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted mb-3">Nenhuma competição cadastrada</h4>
                    <a href="competicoes.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Criar Primeira Competição
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>Modalidade</th>
                                <th>Período de Inscrições</th>
                                <th class="text-center">Status</th>
                                <th>Criada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competicoesRecentes as $comp):
                                $badgeClass = [
                                    'Rascunho' => 'bg-secondary',
                                    'Aberta' => 'bg-success',
                                    'Fechada' => 'bg-primary',
                                    'Em Andamento' => 'bg-info',
                                    'Encerrada' => 'bg-dark',
                                    'Cancelada' => 'bg-danger'
                                ];
                                $class = $badgeClass[$comp['status']] ?? 'bg-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($comp['nome']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo htmlspecialchars($comp['modalidade_nome'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo formatarData($comp['data_inicio_inscricoes']); ?> até
                                            <?php echo formatarData($comp['data_fim_inscricoes']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo htmlspecialchars($comp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo formatarDataHora($comp['created_at']); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light text-center">
                    <a href="competicoes.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Gerenciar Todas as Competições
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
