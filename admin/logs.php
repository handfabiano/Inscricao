<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();

// Filtros
$usuario = $_GET['usuario'] ?? '';
$acao = $_GET['acao'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

// Construir query
$sql = "SELECT l.*, u.nome as usuario_nome, u.username
        FROM logs l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE 1=1";

$params = [];

if ($usuario) {
    $sql .= " AND l.usuario_id = ?";
    $params[] = $usuario;
}

if ($acao) {
    $sql .= " AND l.acao LIKE ?";
    $params[] = "%$acao%";
}

if ($dataInicio) {
    $sql .= " AND DATE(l.created_at) >= ?";
    $params[] = $dataInicio;
}

if ($dataFim) {
    $sql .= " AND DATE(l.created_at) <= ?";
    $params[] = $dataFim;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Buscar usuários para filtro
$usuarios = $pdo->query("SELECT id, nome, username FROM usuarios ORDER BY nome")->fetchAll();

// Estatísticas
$totalLogs = $pdo->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$logsHoje = $pdo->query("SELECT COUNT(*) FROM logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$logsSemana = $pdo->query("SELECT COUNT(*) FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        .log-aprovacao { color: var(--success-color); }
        .log-rejeicao { color: var(--danger-color); }
        .log-login { color: var(--primary-color); }
        .log-default { color: var(--text-light); }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="relatorios.php">Relatórios</a>
                <a href="modalidades.php">Modalidades</a>
                <a href="logs.php" class="active">Logs</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2 style="margin-bottom: 20px;">Logs do Sistema</h2>

        <!-- Estatísticas -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
            <div class="stat-card" style="background: linear-gradient(135deg, #2563eb, #1e40af);">
                <h3><?php echo $totalLogs; ?></h3>
                <p>Total de Logs</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3><?php echo $logsHoje; ?></h3>
                <p>Hoje</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3><?php echo $logsSemana; ?></h3>
                <p>Últimos 7 dias</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filtros
            </div>

            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label>Usuário</label>
                    <select name="usuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $usuario == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ação</label>
                    <select name="acao">
                        <option value="">Todas</option>
                        <option value="Aprovação" <?php echo $acao === 'Aprovação' ? 'selected' : ''; ?>>Aprovação</option>
                        <option value="Rejeição" <?php echo $acao === 'Rejeição' ? 'selected' : ''; ?>>Rejeição</option>
                        <option value="Login" <?php echo $acao === 'Login' ? 'selected' : ''; ?>>Login</option>
                        <option value="Visualização" <?php echo $acao === 'Visualização' ? 'selected' : ''; ?>>Visualização</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Data Início</label>
                    <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>">
                </div>

                <div class="form-group">
                    <label>Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo $dataFim; ?>">
                </div>

                <div class="form-group" style="display: flex; gap: 10px; align-items: end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de Logs -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <i class="fas fa-list"></i> Registro de Ações (últimos 500)
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-light); margin-bottom: 10px;"></i>
                                    <p style="color: var(--text-light);">Nenhum log encontrado</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="white-space: nowrap;">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['usuario_nome'] ?? 'Sistema'); ?>
                                        <br><small style="color: var(--text-light);">@<?php echo htmlspecialchars($log['username'] ?? 'sistema'); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $iconClass = 'log-default';
                                        $icon = 'fa-circle';

                                        if (strpos($log['acao'], 'Aprovação') !== false) {
                                            $iconClass = 'log-aprovacao';
                                            $icon = 'fa-check-circle';
                                        } elseif (strpos($log['acao'], 'Rejeição') !== false) {
                                            $iconClass = 'log-rejeicao';
                                            $icon = 'fa-times-circle';
                                        } elseif (strpos($log['acao'], 'Login') !== false) {
                                            $iconClass = 'log-login';
                                            $icon = 'fa-sign-in-alt';
                                        }
                                        ?>
                                        <i class="fas <?php echo $icon; ?> log-icon <?php echo $iconClass; ?>"></i>
                                        <?php echo htmlspecialchars($log['acao']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['descricao']); ?></td>
                                    <td style="font-family: monospace; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($log['ip']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (count($logs) >= 500): ?>
            <div class="alert alert-info" style="margin-top: 20px;">
                <i class="fas fa-info-circle"></i>
                Mostrando os últimos 500 registros. Use os filtros para refinar sua busca.
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas - Painel Administrativo</p>
        </div>
    </footer>
</body>
</html>
