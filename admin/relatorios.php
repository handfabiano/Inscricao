<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$pageTitle = 'Relatórios e Estatísticas';

// Período padrão: último mês
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$tipoRelatorio = $_GET['tipo'] ?? 'geral';

// Validar datas
if (strtotime($dataInicio) > strtotime($dataFim)) {
    $temp = $dataInicio;
    $dataInicio = $dataFim;
    $dataFim = $temp;
}

// Função para gerar relatório geral
function getRelatorioGeral($pdo, $dataInicio, $dataFim) {
    $stats = [];

    // Total de inscrições no período
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
               SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
               SUM(CASE WHEN status = 'Cancelada' THEN 1 ELSE 0 END) as canceladas
        FROM inscricoes_competicoes
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$dataInicio, $dataFim]);
    $stats['inscricoes'] = $stmt->fetch();

    // Competições no período
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'Aberta' THEN 1 ELSE 0 END) as abertas,
               SUM(CASE WHEN status = 'Encerrada' THEN 1 ELSE 0 END) as encerradas
        FROM competicoes
        WHERE DATE(data_inicio_inscricao) BETWEEN ? AND ?
    ");
    $stmt->execute([$dataInicio, $dataFim]);
    $stats['competicoes'] = $stmt->fetch();

    // Novas equipes no período
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'Aprovada' THEN 1 ELSE 0 END) as aprovadas,
               SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes
        FROM equipes
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$dataInicio, $dataFim]);
    $stats['equipes'] = $stmt->fetch();

    // Novos atletas no período
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
               SUM(CASE WHEN genero = 'Feminino' THEN 1 ELSE 0 END) as feminino
        FROM atletas
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$dataInicio, $dataFim]);
    $stats['atletas'] = $stmt->fetch();

    return $stats;
}

// Relatório de inscrições por competição
function getInscricoesPorCompeticao($pdo, $dataInicio, $dataFim) {
    $stmt = $pdo->prepare("
        SELECT c.nome, c.modalidade,
               COUNT(i.id) as total_inscricoes,
               SUM(CASE WHEN i.status = 'Confirmada' THEN 1 ELSE 0 END) as confirmadas,
               SUM(CASE WHEN i.status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
               c.data_inicio_evento, c.data_fim_evento
        FROM competicoes c
        LEFT JOIN inscricoes_competicoes i ON c.id = i.competicao_id
            AND DATE(i.created_at) BETWEEN ? AND ?
        WHERE DATE(c.data_inicio_inscricao) BETWEEN ? AND ?
        GROUP BY c.id
        ORDER BY total_inscricoes DESC
    ");
    $stmt->execute([$dataInicio, $dataFim, $dataInicio, $dataFim]);
    return $stmt->fetchAll();
}

// Relatório de equipes por município
function getEquipesPorMunicipio($pdo) {
    $stmt = $pdo->query("
        SELECT municipio,
               COUNT(*) as total,
               SUM(CASE WHEN status = 'Aprovada' THEN 1 ELSE 0 END) as aprovadas,
               SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes
        FROM equipes
        GROUP BY municipio
        ORDER BY total DESC
        LIMIT 20
    ");
    return $stmt->fetchAll();
}

// Relatório de atletas por faixa etária
function getAtletasPorFaixaEtaria($pdo) {
    $stmt = $pdo->query("
        SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 12 THEN 'Infantil (0-11)'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 12 AND 17 THEN 'Juvenil (12-17)'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 34 THEN 'Adulto (18-34)'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 35 AND 59 THEN 'Master (35-59)'
                ELSE 'Senior (60+)'
            END as faixa_etaria,
            COUNT(*) as total,
            SUM(CASE WHEN genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
            SUM(CASE WHEN genero = 'Feminino' THEN 1 ELSE 0 END) as feminino
        FROM atletas
        WHERE ativo = 1
        GROUP BY faixa_etaria
        ORDER BY
            CASE
                WHEN faixa_etaria LIKE 'Infantil%' THEN 1
                WHEN faixa_etaria LIKE 'Juvenil%' THEN 2
                WHEN faixa_etaria LIKE 'Adulto%' THEN 3
                WHEN faixa_etaria LIKE 'Master%' THEN 4
                ELSE 5
            END
    ");
    return $stmt->fetchAll();
}

// Buscar dados baseado no tipo de relatório
try {
    $relatorioGeral = getRelatorioGeral($pdo, $dataInicio, $dataFim);
    $inscricoesPorCompeticao = getInscricoesPorCompeticao($pdo, $dataInicio, $dataFim);
    $equipesPorMunicipio = getEquipesPorMunicipio($pdo);
    $atletasPorFaixaEtaria = getAtletasPorFaixaEtaria($pdo);
} catch (Exception $e) {
    die("Erro ao gerar relatório: " . $e->getMessage());
}
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
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
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
                        <a class="nav-link active" href="relatorios.php">
                            <i class="fas fa-chart-bar"></i> Relatórios
                        </a>
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
        <!-- Cabeçalho -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="mb-0">
                                    <i class="fas fa-chart-bar text-danger"></i>
                                    Relatórios e Estatísticas
                                </h2>
                            </div>
                            <div class="col-md-6 text-end">
                                <button onclick="window.print()" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                                <button onclick="exportarCSV()" class="btn btn-success">
                                    <i class="fas fa-file-csv"></i> Exportar CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-calendar"></i> Data Início</label>
                                <input type="date" name="data_inicio" class="form-control" value="<?php echo $dataInicio; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-calendar"></i> Data Fim</label>
                                <input type="date" name="data_fim" class="form-control" value="<?php echo $dataFim; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-filter"></i> Tipo de Relatório</label>
                                <select name="tipo" class="form-select">
                                    <option value="geral" <?php echo $tipoRelatorio == 'geral' ? 'selected' : ''; ?>>Relatório Geral</option>
                                    <option value="inscricoes" <?php echo $tipoRelatorio == 'inscricoes' ? 'selected' : ''; ?>>Inscrições</option>
                                    <option value="equipes" <?php echo $tipoRelatorio == 'equipes' ? 'selected' : ''; ?>>Equipes</option>
                                    <option value="atletas" <?php echo $tipoRelatorio == 'atletas' ? 'selected' : ''; ?>>Atletas</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Gerar Relatório
                                </button>
                                <a href="relatorios.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info do Período -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($dataInicio)); ?> até <?php echo date('d/m/Y', strtotime($dataFim)); ?>
                </div>
            </div>
        </div>

        <!-- Estatísticas Gerais do Período -->
        <h5 class="mb-3"><i class="fas fa-chart-line"></i> Resumo do Período</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100 border-0" style="border-left-color: #0d6efd;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo $relatorioGeral['inscricoes']['total']; ?></h4>
                                <p class="text-muted mb-0 small">Inscrições</p>
                                <small class="text-success">
                                    <i class="fas fa-check"></i> <?php echo $relatorioGeral['inscricoes']['confirmadas']; ?> confirmadas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100 border-0" style="border-left-color: #198754;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo $relatorioGeral['competicoes']['total']; ?></h4>
                                <p class="text-muted mb-0 small">Competições</p>
                                <small class="text-info">
                                    <i class="fas fa-door-open"></i> <?php echo $relatorioGeral['competicoes']['abertas']; ?> abertas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100 border-0" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo $relatorioGeral['equipes']['total']; ?></h4>
                                <p class="text-muted mb-0 small">Novas Equipes</p>
                                <small class="text-success">
                                    <i class="fas fa-check"></i> <?php echo $relatorioGeral['equipes']['aprovadas']; ?> aprovadas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm h-100 border-0" style="border-left-color: #0dcaf0;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                <i class="fas fa-running"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?php echo $relatorioGeral['atletas']['total']; ?></h4>
                                <p class="text-muted mb-0 small">Novos Atletas</p>
                                <small class="text-primary">
                                    <i class="fas fa-male"></i> <?php echo $relatorioGeral['atletas']['masculino']; ?> /
                                    <i class="fas fa-female"></i> <?php echo $relatorioGeral['atletas']['feminino']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inscrições por Competição -->
        <?php if ($tipoRelatorio == 'geral' || $tipoRelatorio == 'inscricoes'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie text-primary"></i>
                            Inscrições por Competição
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Competição</th>
                                    <th>Modalidade</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Confirmadas</th>
                                    <th class="text-center">Pendentes</th>
                                    <th>Período do Evento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoesPorCompeticao as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['nome']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($item['modalidade']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $item['total_inscricoes']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $item['confirmadas']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark"><?php echo $item['pendentes']; ?></span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($item['data_inicio_evento'])); ?>
                                            até
                                            <?php echo date('d/m/Y', strtotime($item['data_fim_evento'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Equipes por Município -->
        <?php if ($tipoRelatorio == 'geral' || $tipoRelatorio == 'equipes'): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt text-warning"></i>
                            Top 20 Municípios com Mais Equipes
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Município</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Aprovadas</th>
                                    <th class="text-center">Pendentes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipesPorMunicipio as $mun): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($mun['municipio']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $mun['total']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $mun['aprovadas']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark"><?php echo $mun['pendentes']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Atletas por Faixa Etária -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-info"></i>
                            Atletas por Faixa Etária
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Faixa Etária</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Masc.</th>
                                    <th class="text-center">Fem.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($atletasPorFaixaEtaria as $faixa): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($faixa['faixa_etaria']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $faixa['total']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $faixa['masculino']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?php echo $faixa['feminino']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-3 mt-5 no-print">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportarCSV() {
            alert('Funcionalidade de exportação CSV será implementada em breve!');
            // Implementar exportação CSV no futuro
        }
    </script>
</body>
</html>
