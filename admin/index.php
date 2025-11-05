<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();

// Estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes");
$totalInscricoes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes WHERE status = 'Pendente'");
$pendentes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes WHERE status = 'Aprovada'");
$aprovadas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes WHERE DATE(created_at) = CURDATE()");
$hoje = $stmt->fetch()['total'];

// Listar inscrições recentes
$filtroStatus = $_GET['status'] ?? '';
$filtroModalidade = $_GET['modalidade'] ?? '';
$busca = $_GET['busca'] ?? '';

$sql = "SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
        FROM inscricoes i
        LEFT JOIN modalidades m ON i.modalidade_id = m.id
        LEFT JOIN categorias c ON i.categoria_id = c.id
        WHERE 1=1";

$params = [];

if ($filtroStatus) {
    $sql .= " AND i.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroModalidade) {
    $sql .= " AND i.modalidade_id = ?";
    $params[] = $filtroModalidade;
}

if ($busca) {
    $sql .= " AND (i.nome_completo LIKE ? OR i.cpf LIKE ? OR i.protocolo LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY i.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inscricoes = $stmt->fetchAll();

// Buscar modalidades para filtro
$modalidades = $pdo->query("SELECT id, nome FROM modalidades WHERE ativo = 1 ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header {
            background: white;
            padding: 15px 0;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons a, .action-buttons button {
            padding: 6px 12px;
            font-size: 0.85rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }

        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php" class="active">Dashboard</a>
                <a href="busca.php">Busca</a>
                <a href="relatorios.php">Relatórios</a>
                <a href="modalidades.php">Modalidades</a>
                <a href="logs.php">Logs</a>
                <a href="perfil.php">Perfil</a>
                <a href="../index.php" target="_blank">Ver Site</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?></h2>
            <div class="user-info">
                <span class="badge badge-info"><?php echo $_SESSION['nivel']; ?></span>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $totalInscricoes; ?></h3>
                <p>Total de Inscrições</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3><?php echo $pendentes; ?></h3>
                <p>Pendentes</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3><?php echo $aprovadas; ?></h3>
                <p>Aprovadas</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <h3><?php echo $hoje; ?></h3>
                <p>Hoje</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="busca">Buscar</label>
                    <input type="text" id="busca" name="busca" placeholder="Nome, CPF ou Protocolo" value="<?php echo htmlspecialchars($busca); ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="Pendente" <?php echo $filtroStatus === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="Aprovada" <?php echo $filtroStatus === 'Aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="Rejeitada" <?php echo $filtroStatus === 'Rejeitada' ? 'selected' : ''; ?>>Rejeitada</option>
                        <option value="Cancelada" <?php echo $filtroStatus === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modalidade">Modalidade</label>
                    <select id="modalidade" name="modalidade">
                        <option value="">Todas</option>
                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?php echo $mod['id']; ?>" <?php echo $filtroModalidade == $mod['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mod['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Inscrições -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Inscrições Recentes
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Protocolo</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Modalidade</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inscricoes)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-light); margin-bottom: 10px;"></i>
                                    <p style="color: var(--text-light);">Nenhuma inscrição encontrada</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inscricoes as $insc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($insc['protocolo']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['nome_completo']); ?></td>
                                    <td><?php echo formatCPF($insc['cpf']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['modalidade_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($insc['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'Pendente' => 'badge-warning',
                                            'Aprovada' => 'badge-success',
                                            'Rejeitada' => 'badge-danger',
                                            'Cancelada' => 'badge-info'
                                        ];
                                        $class = $statusClass[$insc['status']] ?? 'badge-info';
                                        ?>
                                        <span class="badge <?php echo $class; ?>"><?php echo $insc['status']; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="visualizar.php?id=<?php echo $insc['id']; ?>" class="btn-view" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($insc['status'] === 'Pendente'): ?>
                                                <a href="aprovar.php?id=<?php echo $insc['id']; ?>" class="btn-approve" title="Aprovar" onclick="return confirm('Aprovar esta inscrição?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="rejeitar.php?id=<?php echo $insc['id']; ?>" class="btn-reject" title="Rejeitar" onclick="return confirm('Rejeitar esta inscrição?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
