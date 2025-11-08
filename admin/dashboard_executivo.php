<?php
/**
 * Dashboard Executivo Avançado
 *
 * Dashboard com KPIs avançados e analytics para gestão estratégica
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes_auxiliares.php';
require_once __DIR__ . '/../includes/multi_tenancy_helper.php';

requireAdminLogin();

$pdo = getDBConnection();
$org_id = getCurrentOrganizationId() ?? 1;
setCurrentOrganization($org_id);

// Período de análise (padrão: últimos 30 dias)
$periodo = $_GET['periodo'] ?? '30';
$data_inicio = date('Y-m-d', strtotime("-$periodo days"));
$data_fim = date('Y-m-d');

// ===== KPIs PRINCIPAIS =====

// 1. Total de Receitas
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor_liquido), 0) as total_receita,
           COUNT(*) as total_transacoes
    FROM payment_transactions
    WHERE organizacao_id = ? AND status = 'approved'
      AND DATE(data_aprovacao) BETWEEN ? AND ?
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$receita = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Crescimento vs período anterior
$data_inicio_anterior = date('Y-m-d', strtotime("-" . ($periodo * 2) . " days"));
$data_fim_anterior = $data_inicio;

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor_liquido), 0) as total_receita_anterior
    FROM payment_transactions
    WHERE organizacao_id = ? AND status = 'approved'
      AND DATE(data_aprovacao) BETWEEN ? AND ?
");
$stmt->execute([$org_id, $data_inicio_anterior, $data_fim_anterior]);
$receita_anterior = $stmt->fetchColumn();
$crescimento_receita = $receita_anterior > 0
    ? (($receita['total_receita'] - $receita_anterior) / $receita_anterior) * 100
    : 0;

// 3. Taxa de Conversão de Inscrições
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_inscricoes,
        SUM(CASE WHEN status = 'Confirmada' THEN 1 ELSE 0 END) as inscricoes_confirmadas,
        SUM(CASE WHEN status = 'Cancelada' THEN 1 ELSE 0 END) as inscricoes_canceladas
    FROM inscricoes_competicoes
    WHERE organizacao_id = ? AND DATE(data_inscricao) BETWEEN ? AND ?
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$inscricoes_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$taxa_conversao = $inscricoes_stats['total_inscricoes'] > 0
    ? ($inscricoes_stats['inscricoes_confirmadas'] / $inscricoes_stats['total_inscricoes']) * 100
    : 0;

// 4. Ticket Médio
$ticket_medio = $receita['total_transacoes'] > 0
    ? $receita['total_receita'] / $receita['total_transacoes']
    : 0;

// 5. Novos Atletas e Equipes
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM atletas
    WHERE organizacao_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$novos_atletas = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM equipes
    WHERE organizacao_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$novas_equipes = $stmt->fetchColumn();

// 6. Eventos Ativos
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM competicoes
    WHERE organizacao_id = ? AND status IN ('Aberta', 'Em Andamento')
");
$stmt->execute([$org_id]);
$eventos_ativos = $stmt->fetchColumn();

// ===== GRÁFICOS =====

// Receitas por dia (últimos 30 dias)
$stmt = $pdo->prepare("
    SELECT DATE(data_aprovacao) as data, SUM(valor_liquido) as receita
    FROM payment_transactions
    WHERE organizacao_id = ? AND status = 'approved'
      AND DATE(data_aprovacao) BETWEEN ? AND ?
    GROUP BY DATE(data_aprovacao)
    ORDER BY data
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$receitas_diarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inscrições por modalidade
$stmt = $pdo->prepare("
    SELECT m.nome as modalidade, COUNT(*) as total
    FROM inscricoes_competicoes ic
    INNER JOIN competicoes c ON ic.competicao_id = c.id
    INNER JOIN modalidades m ON c.modalidade_id = m.id
    WHERE ic.organizacao_id = ? AND ic.status = 'Confirmada'
      AND DATE(ic.data_inscricao) BETWEEN ? AND ?
    GROUP BY m.id, m.nome
    ORDER BY total DESC
    LIMIT 10
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$inscricoes_modalidade = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 Cidades
$stmt = $pdo->prepare("
    SELECT e.cidade, COUNT(*) as total
    FROM inscricoes_competicoes ic
    INNER JOIN equipes e ON ic.equipe_id = e.id
    WHERE ic.organizacao_id = ? AND ic.status = 'Confirmada'
      AND DATE(ic.data_inscricao) BETWEEN ? AND ?
      AND e.cidade IS NOT NULL AND e.cidade != ''
    GROUP BY e.cidade
    ORDER BY total DESC
    LIMIT 10
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$top_cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métodos de Pagamento
$stmt = $pdo->prepare("
    SELECT metodo_pagamento, COUNT(*) as total, SUM(valor_liquido) as receita
    FROM payment_transactions
    WHERE organizacao_id = ? AND status = 'approved'
      AND DATE(data_aprovacao) BETWEEN ? AND ?
    GROUP BY metodo_pagamento
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$metodos_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Performance de Eventos
$stmt = $pdo->prepare("
    SELECT
        c.nome as evento,
        COUNT(ic.id) as total_inscricoes,
        SUM(CASE WHEN ic.status = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
        c.taxa_inscricao,
        (SUM(CASE WHEN ic.status = 'Confirmada' THEN 1 ELSE 0 END) * c.taxa_inscricao) as receita_estimada
    FROM competicoes c
    LEFT JOIN inscricoes_competicoes ic ON c.id = ic.competicao_id
    WHERE c.organizacao_id = ?
      AND DATE(c.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_inscricoes DESC
    LIMIT 5
");
$stmt->execute([$org_id, $data_inicio, $data_fim]);
$performance_eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Executivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }

        body {
            background: #f8f9fc;
        }

        .kpi-card {
            border-left: 4px solid;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .kpi-card.primary { border-color: var(--primary-color); }
        .kpi-card.success { border-color: var(--success-color); }
        .kpi-card.danger { border-color: var(--danger-color); }
        .kpi-card.warning { border-color: var(--warning-color); }
        .kpi-card.info { border-color: var(--info-color); }

        .kpi-icon {
            font-size: 2.5em;
            opacity: 0.2;
        }

        .kpi-value {
            font-size: 2em;
            font-weight: bold;
        }

        .kpi-change {
            font-size: 0.9em;
        }

        .kpi-change.positive {
            color: var(--success-color);
        }

        .kpi-change.negative {
            color: var(--danger-color);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table-performance th {
            background: #f8f9fc;
            font-weight: 600;
        }

        .badge-custom {
            padding: 0.5em 1em;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-chart-line"></i> Dashboard Executivo</h2>
                <p class="text-muted">Análise estratégica e indicadores de performance</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <a href="?periodo=7" class="btn btn-sm btn-outline-primary <?= $periodo == '7' ? 'active' : '' ?>">7 dias</a>
                    <a href="?periodo=30" class="btn btn-sm btn-outline-primary <?= $periodo == '30' ? 'active' : '' ?>">30 dias</a>
                    <a href="?periodo=90" class="btn btn-sm btn-outline-primary <?= $periodo == '90' ? 'active' : '' ?>">90 dias</a>
                    <a href="?periodo=365" class="btn btn-sm btn-outline-primary <?= $periodo == '365' ? 'active' : '' ?>">1 ano</a>
                </div>
            </div>
        </div>

        <!-- KPIs Principais -->
        <div class="row g-3 mb-4">
            <!-- Receita Total -->
            <div class="col-md-3">
                <div class="card kpi-card primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small mb-1">RECEITA TOTAL</div>
                                <div class="kpi-value">R$ <?= number_format($receita['total_receita'], 2, ',', '.') ?></div>
                                <div class="kpi-change <?= $crescimento_receita >= 0 ? 'positive' : 'negative' ?>">
                                    <i class="fas fa-arrow-<?= $crescimento_receita >= 0 ? 'up' : 'down' ?>"></i>
                                    <?= abs(number_format($crescimento_receita, 1)) ?>% vs período anterior
                                </div>
                            </div>
                            <div>
                                <i class="fas fa-dollar-sign kpi-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Taxa de Conversão -->
            <div class="col-md-3">
                <div class="card kpi-card success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small mb-1">TAXA DE CONVERSÃO</div>
                                <div class="kpi-value"><?= number_format($taxa_conversao, 1) ?>%</div>
                                <div class="text-muted small">
                                    <?= $inscricoes_stats['inscricoes_confirmadas'] ?> / <?= $inscricoes_stats['total_inscricoes'] ?> inscrições
                                </div>
                            </div>
                            <div>
                                <i class="fas fa-chart-line kpi-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Médio -->
            <div class="col-md-3">
                <div class="card kpi-card warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small mb-1">TICKET MÉDIO</div>
                                <div class="kpi-value">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></div>
                                <div class="text-muted small">
                                    <?= $receita['total_transacoes'] ?> transações
                                </div>
                            </div>
                            <div>
                                <i class="fas fa-receipt kpi-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Crescimento Base -->
            <div class="col-md-3">
                <div class="card kpi-card info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted small mb-1">CRESCIMENTO</div>
                                <div class="kpi-value"><?= $novos_atletas + $novas_equipes ?></div>
                                <div class="text-muted small">
                                    <?= $novos_atletas ?> atletas • <?= $novas_equipes ?> equipes
                                </div>
                            </div>
                            <div>
                                <i class="fas fa-users kpi-icon text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row g-3 mb-4">
            <!-- Receita Diária -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-area"></i> Evolução de Receita</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="receitaDiariaChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métodos de Pagamento -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Métodos de Pagamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="metodosPagamentoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Inscrições por Modalidade -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Modalidades</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="modalidadesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Cidades -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Top 10 Cidades</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="cidadesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Performance de Eventos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Performance de Eventos</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-performance mb-0">
                                <thead>
                                    <tr>
                                        <th>Evento</th>
                                        <th class="text-center">Inscrições</th>
                                        <th class="text-center">Confirmadas</th>
                                        <th class="text-center">Taxa</th>
                                        <th class="text-end">Taxa Inscrição</th>
                                        <th class="text-end">Receita Estimada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performance_eventos as $evento): ?>
                                        <?php
                                        $taxa_evento = $evento['total_inscricoes'] > 0
                                            ? ($evento['confirmadas'] / $evento['total_inscricoes']) * 100
                                            : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($evento['evento']) ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge badge-custom bg-primary"><?= $evento['total_inscricoes'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-custom bg-success"><?= $evento['confirmadas'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= number_format($taxa_evento, 1) ?>%</strong>
                                            </td>
                                            <td class="text-end">R$ <?= number_format($evento['taxa_inscricao'], 2, ',', '.') ?></td>
                                            <td class="text-end">
                                                <strong>R$ <?= number_format($evento['receita_estimada'], 2, ',', '.') ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="relatorios.php" class="btn btn-primary">
                    <i class="fas fa-file-export"></i> Exportar Relatório Completo
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuração global dos gráficos
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = '#858796';

        // Gráfico de Receita Diária
        const receitaDiariaCtx = document.getElementById('receitaDiariaChart').getContext('2d');
        new Chart(receitaDiariaCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($receitas_diarias, 'data')) ?>,
                datasets: [{
                    label: 'Receita (R$)',
                    data: <?= json_encode(array_column($receitas_diarias, 'receita')) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'R$ ' + value.toLocaleString('pt-BR')
                        }
                    }
                }
            }
        });

        // Gráfico de Modalidades
        const modalidadesCtx = document.getElementById('modalidadesChart').getContext('2d');
        new Chart(modalidadesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($inscricoes_modalidade, 'modalidade')) ?>,
                datasets: [{
                    label: 'Inscrições',
                    data: <?= json_encode(array_column($inscricoes_modalidade, 'total')) ?>,
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Gráfico de Cidades
        const cidadesCtx = document.getElementById('cidadesChart').getContext('2d');
        new Chart(cidadesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($top_cidades, 'cidade')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($top_cidades, 'total')) ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Gráfico de Métodos de Pagamento
        const metodosCtx = document.getElementById('metodosPagamentoChart').getContext('2d');
        new Chart(metodosCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($metodos_pagamento, 'metodo_pagamento')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($metodos_pagamento, 'total')) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
