<?php
/**
 * Dashboard de Integrações
 *
 * Interface para gerenciar integrações externas, webhooks e notificações
 *
 * @version 1.0
 * @date 2025-11-08
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/integrations_helper.php';
require_once __DIR__ . '/../includes/notifications_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}

$pdo = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];
$organizacao_id = $_SESSION['organizacao_id'] ?? 1;

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF('integrations_actions');

    $acao = $_POST['acao'] ?? '';

    switch ($acao) {
        case 'processar_fila':
            $resultado = processarFilaNotificacoes(100);
            $mensagem = "Processadas: {$resultado['processadas']}, Sucesso: {$resultado['sucesso']}, Erro: {$resultado['erro']}";
            $tipo_mensagem = 'success';
            break;
    }
}

// Buscar dados
// 1. Integrações ativas
$stmt = $pdo->prepare("SELECT * FROM active_integrations WHERE organizacao_id = ?");
$stmt->execute([$organizacao_id]);
$integracoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Providers disponíveis
$providers = listAvailableProviders();

// 3. Webhooks
$stmt = $pdo->prepare("SELECT * FROM webhook_stats WHERE organizacao_id = ? LIMIT 10");
$stmt->execute([$organizacao_id]);
$webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Notificações pendentes
$stmt = $pdo->query("SELECT COUNT(*) FROM pending_notifications");
$notif_pendentes = $stmt->fetchColumn();

// 5. Estatísticas gerais
$stats = [
    'integracoes_ativas' => count($integracoes),
    'webhooks_configurados' => count($webhooks),
    'notificacoes_pendentes' => $notif_pendentes
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrações - Sistema Esportivo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        .integration-card {
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .integration-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .integration-card.inactive {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
        .stat-badge {
            font-size: 2rem;
            font-weight: bold;
        }
        .provider-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 24px;
        }
        .provider-icon.federation { background: #28a745; color: white; }
        .provider-icon.payment { background: #ffc107; color: #000; }
        .provider-icon.communication { background: #17a2b8; color: white; }
        .provider-icon.streaming { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">
                <i class="bi bi-plugin"></i> Integrações
            </a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Integrações Ativas</h6>
                        <div class="stat-badge text-primary"><?php echo $stats['integracoes_ativas']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Webhooks Configurados</h6>
                        <div class="stat-badge text-info"><?php echo $stats['webhooks_configurados']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Notificações Pendentes</h6>
                        <div class="stat-badge text-warning"><?php echo $stats['notificacoes_pendentes']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Integrações Ativas -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plugin"></i> Integrações Ativas</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($integracoes)): ?>
                            <p class="text-muted text-center py-4">Nenhuma integração configurada</p>
                        <?php else: ?>
                            <?php foreach ($integracoes as $int): ?>
                                <div class="integration-card card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <div class="provider-icon <?php echo $int['categoria']; ?> me-3">
                                                <i class="bi bi-<?php
                                                    echo $int['categoria'] === 'communication' ? 'chat-dots' :
                                                        ($int['categoria'] === 'payment' ? 'credit-card' :
                                                            ($int['categoria'] === 'streaming' ? 'camera-video' : 'shield-check'));
                                                ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($int['provider_nome']); ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($int['categoria']); ?></small>
                                                <div class="mt-2">
                                                    <span class="badge bg-<?php echo $int['status'] === 'ativo' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($int['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="mt-2 small">
                                                    <strong>Requests:</strong> <?php echo number_format($int['total_requests']); ?> |
                                                    <strong>Taxa sucesso:</strong> <?php echo $int['taxa_sucesso']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Providers Disponíveis -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Providers Disponíveis</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php
                        $categorias = array_unique(array_column($providers, 'categoria'));
                        foreach ($categorias as $cat):
                            $providers_cat = array_filter($providers, fn($p) => $p['categoria'] === $cat);
                        ?>
                            <h6 class="border-bottom pb-2 mb-3"><?php echo ucfirst($cat); ?></h6>
                            <?php foreach ($providers_cat as $provider): ?>
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($provider['nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($provider['descricao']); ?></small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-plus"></i> Configurar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhooks -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Webhooks</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($webhooks)): ?>
                            <p class="text-muted text-center py-4">Nenhum webhook configurado</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>URL</th>
                                            <th>Status</th>
                                            <th>Disparos</th>
                                            <th>Taxa Sucesso</th>
                                            <th>Último Disparo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($webhooks as $wh): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($wh['nome']); ?></td>
                                                <td><code><?php echo htmlspecialchars(substr($wh['url'], 0, 50)) . '...'; ?></code></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $wh['ativo'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $wh['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($wh['total_dispatched']); ?></td>
                                                <td><?php echo $wh['taxa_sucesso']; ?>%</td>
                                                <td><?php echo $wh['last_dispatch_at'] ? date('d/m/Y H:i', strtotime($wh['last_dispatch_at'])) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="d-inline">
                            <?php echo generateCSRFField('integrations_actions'); ?>
                            <input type="hidden" name="acao" value="processar_fila">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Processar Fila de Notificações
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/csrf.js"></script>
</body>
</html>
