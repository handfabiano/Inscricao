<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $equipeId = (int)$_POST['equipe_id'];
    $acao = $_POST['acao'];

    try {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare("UPDATE equipes SET status = 'Aprovada' WHERE id = ?");
            $stmt->execute([$equipeId]);
            redirect('equipes.php', 'Equipe aprovada com sucesso!', 'success');
        } elseif ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE equipes SET status = 'Rejeitada' WHERE id = ?");
            $stmt->execute([$equipeId]);
            redirect('equipes.php', 'Equipe rejeitada com sucesso!', 'success');
        } elseif ($acao === 'ativar') {
            $stmt = $pdo->prepare("UPDATE equipes SET status = 'Aprovada' WHERE id = ?");
            $stmt->execute([$equipeId]);
            redirect('equipes.php', 'Equipe ativada com sucesso!', 'success');
        }
    } catch (Exception $e) {
        redirect('equipes.php', 'Erro ao processar equipe: ' . $e->getMessage(), 'error');
    }
}

// Filtros
$filtroNome = $_GET['nome'] ?? '';
$filtroStatus = $_GET['status'] ?? '';
$filtroCidade = $_GET['cidade'] ?? '';

// Buscar estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Pendente'");
$totalPendentes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Aprovada'");
$totalAprovadas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Rejeitada'");
$totalRejeitadas = $stmt->fetch()['total'];

// Construir query com filtros
$sql = "
    SELECT
        e.*,
        (SELECT COUNT(*) FROM atletas WHERE equipe_atual_id = e.id AND ativo = 1) as total_atletas,
        (SELECT COUNT(*) FROM inscricoes_competicoes WHERE equipe_id = e.id) as total_inscricoes
    FROM equipes e
    WHERE 1=1
";

$params = [];

if (!empty($filtroNome)) {
    $sql .= " AND e.nome LIKE ?";
    $params[] = "%$filtroNome%";
}

if (!empty($filtroStatus)) {
    $sql .= " AND e.status = ?";
    $params[] = $filtroStatus;
}

if (!empty($filtroCidade)) {
    $sql .= " AND e.cidade LIKE ?";
    $params[] = "%$filtroCidade%";
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipes = $stmt->fetchAll();

$pageTitle = 'Gerenciar Equipes';
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
        .card-stat {
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }
    </style>
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="competicoes.php">
                            <i class="fas fa-trophy"></i> Competições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="equipes.php">
                            <i class="fas fa-users"></i> Equipes
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-users text-primary"></i>
                Gerenciar Equipes
            </h2>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensagem_tipo']; ?> alert-dismissible fade show">
                <?php
                echo htmlspecialchars($_SESSION['mensagem']);
                unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h3 class="display-4 mb-1 text-warning"><?php echo $totalPendentes; ?></h3>
                        <p class="text-muted mb-0 small">Equipes Pendentes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h3 class="display-4 mb-1 text-success"><?php echo $totalAprovadas; ?></h3>
                        <p class="text-muted mb-0 small">Equipes Aprovadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h3 class="display-4 mb-1 text-danger"><?php echo $totalRejeitadas; ?></h3>
                        <p class="text-muted mb-0 small">Equipes Rejeitadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-search"></i> Nome da Equipe
                        </label>
                        <input type="text" name="nome" class="form-control"
                               placeholder="Digite o nome"
                               value="<?php echo htmlspecialchars($filtroNome); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Cidade
                        </label>
                        <input type="text" name="cidade" class="form-control"
                               placeholder="Digite a cidade"
                               value="<?php echo htmlspecialchars($filtroCidade); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="Pendente" <?php echo $filtroStatus === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="Aprovada" <?php echo $filtroStatus === 'Aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                            <option value="Rejeitada" <?php echo $filtroStatus === 'Rejeitada' ? 'selected' : ''; ?>>Rejeitada</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="equipes.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Limpar Filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Equipes -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Equipes Cadastradas
                    <span class="badge bg-secondary"><?php echo count($equipes); ?></span>
                </h5>
            </div>

            <?php if (empty($equipes)): ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted">Nenhuma equipe encontrada</h4>
                    <p class="text-muted">Não há equipes que correspondam aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nome da Equipe</th>
                                <th>Responsável</th>
                                <th>Cidade</th>
                                <th class="text-center">Atletas</th>
                                <th class="text-center">Inscrições</th>
                                <th class="text-center">Status</th>
                                <th>Cadastro</th>
                                <th class="text-center" style="width: 180px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipes as $equipe):
                                $badgeClass = [
                                    'Pendente' => 'bg-warning',
                                    'Aprovada' => 'bg-success',
                                    'Rejeitada' => 'bg-danger'
                                ];
                                $class = $badgeClass[$equipe['status']] ?? 'bg-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($equipe['nome']); ?></strong>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($equipe['responsavel_nome']); ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($equipe['responsavel_email']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($equipe['cidade'] ?? ''); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $equipe['total_atletas']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $equipe['total_inscricoes']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo htmlspecialchars($equipe['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo formatarData($equipe['created_at']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($equipe['status'] === 'Pendente'): ?>
                                            <form method="POST" style="display: inline-block;"
                                                  onsubmit="return confirm('Confirmar aprovação desta equipe?');">
                                                <input type="hidden" name="equipe_id" value="<?php echo $equipe['id']; ?>">
                                                <input type="hidden" name="acao" value="aprovar">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline-block;"
                                                  onsubmit="return confirm('Confirmar rejeição desta equipe?');">
                                                <input type="hidden" name="equipe_id" value="<?php echo $equipe['id']; ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($equipe['status'] === 'Rejeitada'): ?>
                                            <form method="POST" style="display: inline-block;"
                                                  onsubmit="return confirm('Reativar esta equipe?');">
                                                <input type="hidden" name="equipe_id" value="<?php echo $equipe['id']; ?>">
                                                <input type="hidden" name="acao" value="ativar">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-redo"></i> Reativar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
