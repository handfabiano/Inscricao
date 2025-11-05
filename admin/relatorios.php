<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();

// Estatísticas gerais
$stats = [];

// Total por status
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM inscricoes GROUP BY status");
$stats['por_status'] = $stmt->fetchAll();

// Total por modalidade
$stmt = $pdo->query("
    SELECT m.nome, COUNT(i.id) as total
    FROM modalidades m
    LEFT JOIN inscricoes i ON m.id = i.modalidade_id
    GROUP BY m.id, m.nome
    ORDER BY total DESC
");
$stats['por_modalidade'] = $stmt->fetchAll();

// Total por categoria
$stmt = $pdo->query("
    SELECT c.nome, COUNT(i.id) as total
    FROM categorias c
    LEFT JOIN inscricoes i ON c.id = i.categoria_id
    GROUP BY c.id, c.nome
    ORDER BY total DESC
");
$stats['por_categoria'] = $stmt->fetchAll();

// Total por gênero
$stmt = $pdo->query("SELECT genero, COUNT(*) as total FROM inscricoes GROUP BY genero");
$stats['por_genero'] = $stmt->fetchAll();

// Inscrições por mês (últimos 12 meses)
$stmt = $pdo->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as mes,
        COUNT(*) as total
    FROM inscricoes
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mes
    ORDER BY mes
");
$stats['por_mes'] = $stmt->fetchAll();

// Top 5 cidades
$stmt = $pdo->query("
    SELECT cidade, COUNT(*) as total
    FROM inscricoes
    GROUP BY cidade
    ORDER BY total DESC
    LIMIT 5
");
$stats['top_cidades'] = $stmt->fetchAll();

// Média de idade por modalidade
$stmt = $pdo->query("
    SELECT
        m.nome as modalidade,
        ROUND(AVG(TIMESTAMPDIFF(YEAR, i.data_nascimento, CURDATE()))) as media_idade
    FROM inscricoes i
    JOIN modalidades m ON i.modalidade_id = m.id
    GROUP BY m.id, m.nome
    ORDER BY media_idade DESC
");
$stats['media_idade'] = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Estatísticas</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin: 30px 0;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .export-section {
            background: var(--white);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="relatorios.php" class="active">Relatórios</a>
                <a href="modalidades.php">Modalidades</a>
                <a href="logs.php">Logs</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2 style="margin-bottom: 20px;">Relatórios e Estatísticas</h2>

        <!-- Exportação -->
        <div class="export-section">
            <h3><i class="fas fa-file-download"></i> Exportar Dados</h3>
            <p style="color: var(--text-light); margin-bottom: 15px;">
                Exporte os dados das inscrições em diferentes formatos
            </p>

            <form method="GET" action="exportar.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="Pendente">Pendente</option>
                        <option value="Aprovada">Aprovada</option>
                        <option value="Rejeitada">Rejeitada</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Modalidade</label>
                    <select name="modalidade">
                        <option value="">Todas</option>
                        <?php
                        $modalidades = $pdo->query("SELECT id, nome FROM modalidades ORDER BY nome")->fetchAll();
                        foreach ($modalidades as $mod) {
                            echo "<option value='{$mod['id']}'>{$mod['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Data Início</label>
                    <input type="date" name="data_inicio">
                </div>

                <div class="form-group">
                    <label>Data Fim</label>
                    <input type="date" name="data_fim">
                </div>

                <div class="form-group" style="display: flex; gap: 10px; align-items: end;">
                    <button type="submit" name="formato" value="csv" class="btn btn-primary">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                    <button type="submit" name="formato" value="excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>

        <!-- Gráficos -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Inscrições por Status
            </div>
            <div class="chart-container">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>

        <div class="stats-cards">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Por Modalidade
                </div>
                <div class="chart-container">
                    <canvas id="chartModalidade"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Por Gênero
                </div>
                <div class="chart-container">
                    <canvas id="chartGenero"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Inscrições por Mês (Últimos 12 meses)
            </div>
            <div class="chart-container">
                <canvas id="chartMensal"></canvas>
            </div>
        </div>

        <!-- Tabelas de estatísticas -->
        <div class="stats-cards">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-city"></i> Top 5 Cidades
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Cidade</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_cidades'] as $cidade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cidade['cidade']); ?></td>
                            <td><strong><?php echo $cidade['total']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-birthday-cake"></i> Média de Idade por Modalidade
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Modalidade</th>
                            <th>Média de Idade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['media_idade'] as $media): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($media['modalidade']); ?></td>
                            <td><strong><?php echo $media['media_idade']; ?> anos</strong></td>
                        </tr>
                        <?php endforeach; ?>
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

    <script>
        // Gráfico de Status
        const ctxStatus = document.getElementById('chartStatus').getContext('2d');
        new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($stats['por_status'], 'status')); ?>,
                datasets: [{
                    label: 'Inscrições',
                    data: <?php echo json_encode(array_column($stats['por_status'], 'total')); ?>,
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráfico de Modalidade
        const ctxModalidade = document.getElementById('chartModalidade').getContext('2d');
        new Chart(ctxModalidade, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($stats['por_modalidade'], 'nome')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['por_modalidade'], 'total')); ?>,
                    backgroundColor: [
                        '#2563eb', '#7c3aed', '#db2777', '#dc2626', '#ea580c',
                        '#ca8a04', '#16a34a', '#0891b2', '#4f46e5', '#9333ea'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Gênero
        const ctxGenero = document.getElementById('chartGenero').getContext('2d');
        new Chart(ctxGenero, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($stats['por_genero'], 'genero')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['por_genero'], 'total')); ?>,
                    backgroundColor: ['#2563eb', '#db2777', '#16a34a']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico Mensal
        const ctxMensal = document.getElementById('chartMensal').getContext('2d');
        new Chart(ctxMensal, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($stats['por_mes'], 'mes')); ?>,
                datasets: [{
                    label: 'Inscrições',
                    data: <?php echo json_encode(array_column($stats['por_mes'], 'total')); ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
