<?php
/**
 * Dashboard de Analytics e Business Intelligence
 *
 * Interface completa para análises preditivas, talentos e insights
 *
 * @version 1.0
 * @date 2025-11-08
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/analytics_helper.php';
require_once __DIR__ . '/../includes/report_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}

$pdo = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];

// Obter organização
$organizacao_id = $_SESSION['organizacao_id'] ?? 1;

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF('analytics_actions');

    $acao = $_POST['acao'] ?? '';

    switch ($acao) {
        case 'executar_scout':
            $modalidade_id = $_POST['modalidade_id'] ?? null;
            if ($modalidade_id) {
                $resultado = executarScoutTalentos(1, $modalidade_id); // Scout ID 1 (padrão)
                if ($resultado['success']) {
                    $mensagem = "Scout executado! {$resultado['total']} talentos identificados.";
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = $resultado['message'];
                    $tipo_mensagem = 'error';
                }
            }
            break;

        case 'gerar_insights':
            $tipo = $_POST['tipo_insight'] ?? 'team';
            $entidade_id = $_POST['entidade_id'] ?? null;
            if ($entidade_id) {
                $resultado = gerarInsights($tipo, $entidade_id);
                if ($resultado['success']) {
                    $mensagem = "{$resultado['total']} insights gerados!";
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = $resultado['message'];
                    $tipo_mensagem = 'error';
                }
            }
            break;
    }
}

// Buscar dados para dashboard
// 1. Previsões recentes
$stmt = $pdo->prepare("
    SELECT
        mp.*,
        m.data_hora,
        ec.nome as equipe_casa,
        ev.nome as equipe_visitante,
        pm.nome as modelo
    FROM match_predictions mp
    INNER JOIN matches m ON mp.match_id = m.id
    INNER JOIN equipes ec ON m.equipe_casa_id = ec.id
    INNER JOIN equipes ev ON m.equipe_visitante_id = ev.id
    INNER JOIN prediction_models pm ON mp.model_id = pm.id
    ORDER BY mp.created_at DESC
    LIMIT 10
");
$stmt->execute();
$previsoes_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Top talentos
$stmt = $pdo->query("
    SELECT * FROM top_talents
    LIMIT 10
");
$top_talentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Insights pendentes
$stmt = $pdo->query("
    SELECT * FROM pending_insights
    LIMIT 10
");
$insights_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Performance dos modelos
$stmt = $pdo->query("SELECT * FROM model_performance");
$modelos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Estatísticas gerais
$stmt = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM match_predictions) as total_previsoes,
        (SELECT COUNT(*) FROM match_predictions WHERE previsao_correta = TRUE) as previsoes_corretas,
        (SELECT COUNT(*) FROM identified_talents WHERE status = 'ativo') as talentos_ativos,
        (SELECT COUNT(*) FROM performance_insights WHERE visualizado = FALSE) as insights_nao_visualizados,
        (SELECT COUNT(*) FROM generated_reports WHERE status = 'concluido') as relatorios_gerados
");
$stats_gerais = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular taxa de acerto
$stats_gerais['taxa_acerto'] = $stats_gerais['total_previsoes'] > 0 ?
    round(($stats_gerais['previsoes_corretas'] / $stats_gerais['total_previsoes']) * 100, 2) : 0;

// Buscar modalidades para filtros
$stmt = $pdo->query("SELECT id, nome FROM modalidades ORDER BY nome");
$modalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar equipes para filtros
$stmt = $pdo->prepare("
    SELECT id, nome FROM equipes
    WHERE organizacao_id = ?
    ORDER BY nome
    LIMIT 100
");
$stmt->execute([$organizacao_id]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRF('analytics_actions'); ?>">
    <title>Analytics & BI - Sistema Esportivo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .insight-card {
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }
        .insight-card.critica {
            border-left-color: #dc3545;
        }
        .insight-card.alta {
            border-left-color: #fd7e14;
        }
        .insight-card.media {
            border-left-color: #ffc107;
        }

        .talent-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .talent-badge.elite {
            background: #ffd700;
            color: #000;
        }
        .talent-badge.destaque {
            background: #c0c0c0;
            color: #000;
        }
        .talent-badge.promissor {
            background: #cd7f32;
            color: #fff;
        }

        .prediction-bar {
            height: 30px;
            border-radius: 5px;
            display: flex;
            overflow: hidden;
        }
        .prob-casa {
            background: #28a745;
        }
        .prob-empate {
            background: #ffc107;
        }
        .prob-visitante {
            background: #dc3545;
        }

        .model-accuracy {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">
                <i class="bi bi-graph-up-arrow"></i> Analytics & BI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/relatorios.php">Relatórios</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas Gerais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h6><i class="bi bi-graph-up"></i> Previsões Realizadas</h6>
                    <h2><?php echo number_format($stats_gerais['total_previsoes']); ?></h2>
                    <small>Taxa de acerto: <?php echo $stats_gerais['taxa_acerto']; ?>%</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <h6><i class="bi bi-star-fill"></i> Talentos Identificados</h6>
                    <h2><?php echo number_format($stats_gerais['talentos_ativos']); ?></h2>
                    <small>Atletas em destaque</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <h6><i class="bi bi-lightbulb"></i> Insights Pendentes</h6>
                    <h2><?php echo number_format($stats_gerais['insights_nao_visualizados']); ?></h2>
                    <small>Aguardando análise</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <h6><i class="bi bi-file-earmark-text"></i> Relatórios Gerados</h6>
                    <h2><?php echo number_format($stats_gerais['relatorios_gerados']); ?></h2>
                    <small>Disponíveis</small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-md-6">
                <!-- Performance dos Modelos -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-cpu"></i> Performance dos Modelos de IA</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($modelos as $modelo): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="model-accuracy me-3" style="background: <?php
                                    echo $modelo['acuracia'] >= 70 ? '#28a745' :
                                        ($modelo['acuracia'] >= 50 ? '#ffc107' : '#dc3545');
                                ?>;">
                                    <?php echo $modelo['acuracia'] ?? '0'; ?>%
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($modelo['nome']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $modelo['algoritmo']; ?> |
                                        <?php echo number_format($modelo['total_predicoes']); ?> previsões
                                    </small>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar" style="width: <?php echo $modelo['acuracia']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Previsões Recentes -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Previsões Recentes</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($previsoes_recentes as $prev): ?>
                            <div class="mb-3 p-2 border-bottom">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?php echo htmlspecialchars($prev['equipe_casa']); ?></strong>
                                    <span class="text-muted">vs</span>
                                    <strong><?php echo htmlspecialchars($prev['equipe_visitante']); ?></strong>
                                </div>

                                <div class="prediction-bar mb-1">
                                    <div class="prob-casa" style="width: <?php echo $prev['vitoria_casa_prob']; ?>%">
                                        <small class="text-white px-2"><?php echo $prev['vitoria_casa_prob']; ?>%</small>
                                    </div>
                                    <div class="prob-empate" style="width: <?php echo $prev['empate_prob']; ?>%">
                                        <small class="px-2"><?php echo $prev['empate_prob']; ?>%</small>
                                    </div>
                                    <div class="prob-visitante" style="width: <?php echo $prev['vitoria_visitante_prob']; ?>%">
                                        <small class="text-white px-2"><?php echo $prev['vitoria_visitante_prob']; ?>%</small>
                                    </div>
                                </div>

                                <small class="text-muted">
                                    Confiança: <?php echo $prev['confianca']; ?>% |
                                    Gols esperados: <?php echo $prev['gols_esperados_casa']; ?> x <?php echo $prev['gols_esperados_visitante']; ?>
                                    <?php if ($prev['resultado_real']): ?>
                                        | <span class="badge bg-<?php echo $prev['previsao_correta'] ? 'success' : 'danger'; ?>">
                                            <?php echo $prev['previsao_correta'] ? 'Acertou!' : 'Errou'; ?>
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-lightning-fill"></i> Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <?php echo generateCSRFField('analytics_actions'); ?>
                            <input type="hidden" name="acao" value="executar_scout">
                            <div class="mb-2">
                                <label>Executar Scout de Talentos</label>
                                <select name="modalidade_id" class="form-select" required>
                                    <option value="">Selecione modalidade...</option>
                                    <?php foreach ($modalidades as $mod): ?>
                                        <option value="<?php echo $mod['id']; ?>">
                                            <?php echo htmlspecialchars($mod['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm w-100">
                                <i class="bi bi-search"></i> Executar Scout
                            </button>
                        </form>

                        <form method="POST">
                            <?php echo generateCSRFField('analytics_actions'); ?>
                            <input type="hidden" name="acao" value="gerar_insights">
                            <input type="hidden" name="tipo_insight" value="team">
                            <div class="mb-2">
                                <label>Gerar Insights para Equipe</label>
                                <select name="entidade_id" class="form-select" required>
                                    <option value="">Selecione equipe...</option>
                                    <?php foreach ($equipes as $eq): ?>
                                        <option value="<?php echo $eq['id']; ?>">
                                            <?php echo htmlspecialchars($eq['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info btn-sm w-100">
                                <i class="bi bi-lightbulb"></i> Gerar Insights
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita -->
            <div class="col-md-6">
                <!-- Top Talentos -->
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-star-fill"></i> Top Talentos Identificados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Atleta</th>
                                        <th>Idade</th>
                                        <th>Score</th>
                                        <th>Potencial</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_talentos as $talento): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($talento['nome_completo']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($talento['equipe_atual'] ?? 'Sem equipe'); ?></small>
                                            </td>
                                            <td><?php echo $talento['idade']; ?> anos</td>
                                            <td>
                                                <strong class="text-primary"><?php echo $talento['talent_score']; ?></strong>
                                            </td>
                                            <td>
                                                <span class="talent-badge <?php echo $talento['potencial']; ?>">
                                                    <?php echo ucfirst($talento['potencial']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Insights Pendentes -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Insights Prioritários</h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($insights_pendentes as $insight): ?>
                            <div class="card insight-card <?php echo $insight['prioridade']; ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1">
                                            <i class="bi bi-<?php
                                                echo $insight['categoria'] === 'performance' ? 'graph-up' :
                                                    ($insight['categoria'] === 'risco' ? 'exclamation-triangle' :
                                                        ($insight['categoria'] === 'tendencia' ? 'arrow-up-right' : 'lightbulb'));
                                            ?>"></i>
                                            <?php echo htmlspecialchars($insight['titulo']); ?>
                                        </h6>
                                        <span class="badge bg-<?php
                                            echo $insight['prioridade'] === 'critica' ? 'danger' :
                                                ($insight['prioridade'] === 'alta' ? 'warning' : 'secondary');
                                        ?>">
                                            <?php echo ucfirst($insight['prioridade']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-2 small"><?php echo htmlspecialchars($insight['descricao']); ?></p>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($insight['entidade_nome'] ?? 'Geral'); ?> |
                                        Confiança: <?php echo $insight['confianca']; ?>%
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($insights_pendentes)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-check-circle" style="font-size: 48px;"></i>
                                <p class="mt-2">Nenhum insight pendente no momento!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/csrf.js"></script>
</body>
</html>
