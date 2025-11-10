<?php
/**
 * Página Pública Principal
 * Sistema de Gestão Esportiva Enterprise
 */

// Configurações de erro (desativar em produção)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Tentar conectar ao banco
$db_connected = false;
$pdo = null;

try {
    require_once '../config/database.php';
    $pdo = getDBConnection();
    $db_connected = ($pdo !== null);
} catch (Exception $e) {
    $db_connected = false;
    error_log("Erro na página pública: " . $e->getMessage());
}

// Inicializar variáveis
$totalCompeticoes = 0;
$totalEquipes = 0;
$totalAtletas = 0;
$totalInscricoes = 0;
$competicoesAbertas = [];
$proximasCompeticoes = [];

// Se conectado, buscar dados
if ($db_connected) {
    try {
        // Verificar se tabelas existem
        $stmt = $pdo->query("SHOW TABLES LIKE 'competicoes'");
        $table_exists = $stmt->rowCount() > 0;

        if ($table_exists) {
            // Buscar competições abertas (usando nome CORRETO: PLURAL)
            $stmt = $pdo->query("
                SELECT * FROM competicoes
                WHERE status = 'Aberta'
                ORDER BY data_inicio_inscricoes DESC
                LIMIT 6
            ");
            $competicoesAbertas = $stmt->fetchAll();

            // Buscar próximas competições
            $stmt = $pdo->query("
                SELECT * FROM competicoes
                WHERE data_inicio_evento >= CURDATE()
                ORDER BY data_inicio_evento ASC
                LIMIT 6
            ");
            $proximasCompeticoes = $stmt->fetchAll();

            // Estatísticas
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes");
            $totalCompeticoes = $stmt->fetch()['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Aprovada'");
            $totalEquipes = $stmt->fetch()['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 1");
            $totalAtletas = $stmt->fetch()['total'];

            $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
            $totalInscricoes = $stmt->fetch()['total'];
        }
    } catch (Exception $e) {
        // Silenciar erros e usar valores padrão
        error_log("Erro ao buscar dados: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão Esportiva Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .feature-card {
            transition: all 0.3s;
            border: none;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 28px;
            margin: 0 auto 20px;
        }
        .competicao-card {
            transition: all 0.3s;
            height: 100%;
        }
        .competicao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .alert-setup {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
        }
        .btn-glow {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
            transition: all 0.3s;
        }
        .btn-glow:hover {
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.8);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-trophy me-2"></i>
                Sistema Esportivo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../equipe/login.php">
                            <i class="fas fa-users"></i> Equipes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/login.php">
                            <i class="fas fa-shield-alt"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!$db_connected): ?>
    <!-- Alert de Configuração -->
    <div class="container mt-4">
        <div class="alert alert-setup shadow-lg" role="alert">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <div class="flex-grow-1">
                    <h4 class="alert-heading mb-2">⚙️ Sistema em Configuração</h4>
                    <p class="mb-2">O banco de dados ainda não está configurado. Siga os passos:</p>
                    <ol class="mb-2">
                        <li>Configure as credenciais em <code>config/database.php</code></li>
                        <li>Execute as migrations: <code>/migrations/run_migrations.php</code></li>
                        <li>Consulte o guia: <code>CONFIGURAR_HOSTINGER.md</code></li>
                    </ol>
                    <a href="/test_error.php" class="btn btn-light btn-sm">
                        <i class="fas fa-bug"></i> Ver Diagnóstico Completo
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-3 fw-bold mb-4">
                        <i class="fas fa-trophy"></i>
                        Gestão Esportiva Enterprise
                    </h1>
                    <p class="lead mb-4 fs-4">
                        Sistema completo com <strong>Multi-Tenancy</strong>, <strong>Analytics BI</strong>,
                        <strong>Machine Learning</strong> e <strong>Integrações Externas</strong>
                    </p>
                    <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                        <a href="../admin/dashboard_analytics.php" class="btn btn-light btn-lg px-5 btn-glow">
                            <i class="fas fa-chart-line"></i> Dashboard Analytics
                        </a>
                        <a href="../admin/dashboard_integracoes.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-plug"></i> Integrações
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-2">Estatísticas do Sistema</h2>
                    <p class="text-muted">Dados em tempo real</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #667eea;">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($totalCompeticoes); ?></h2>
                            <p class="text-muted mb-0">Competições</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #f093fb;">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-users fa-3x text-danger mb-3"></i>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($totalEquipes); ?></h2>
                            <p class="text-muted mb-0">Equipes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #4facfe;">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-running fa-3x text-info mb-3"></i>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($totalAtletas); ?></h2>
                            <p class="text-muted mb-0">Atletas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #43e97b;">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($totalInscricoes); ?></h2>
                            <p class="text-muted mb-0">Inscrições</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Competições Abertas -->
    <?php if (!empty($competicoesAbertas)): ?>
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold">
                        <i class="fas fa-door-open text-success"></i>
                        Inscrições Abertas
                    </h2>
                    <p class="text-muted">Confira as competições com inscrições abertas no momento</p>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($competicoesAbertas as $comp): ?>
                <div class="col-md-4">
                    <div class="card competicao-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-success">Aberta</span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($comp['modalidade'] ?? 'N/A'); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($comp['nome']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($comp['descricao'] ?? '', 0, 100)); ?><?php echo strlen($comp['descricao'] ?? '') > 100 ? '...' : ''; ?>
                            </p>
                            <hr>
                            <div class="small">
                                <p class="mb-2">
                                    <i class="fas fa-calendar text-primary"></i>
                                    <strong>Evento:</strong>
                                    <?php echo date('d/m/Y', strtotime($comp['data_inicio_evento'])); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    <strong>Local:</strong> <?php echo htmlspecialchars($comp['local_evento'] ?? 'A definir'); ?>
                                </p>
                                <p class="mb-0 text-success">
                                    <i class="fas fa-clock"></i>
                                    <strong>Inscrições até:</strong>
                                    <?php echo date('d/m/Y', strtotime($comp['data_fim_inscricoes'])); ?>
                                </p>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="../equipe/login.php" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-sign-in-alt"></i> Fazer Login para Inscrever
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Próximas Competições -->
    <?php if (!empty($proximasCompeticoes)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        Próximas Competições
                    </h2>
                    <p class="text-muted">Eventos que acontecerão em breve</p>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach (array_slice($proximasCompeticoes, 0, 3) as $comp): ?>
                <div class="col-md-4">
                    <div class="card competicao-card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary">Em Breve</span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($comp['modalidade'] ?? 'N/A'); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($comp['nome']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($comp['descricao'] ?? '', 0, 100)); ?><?php echo strlen($comp['descricao'] ?? '') > 100 ? '...' : ''; ?>
                            </p>
                            <hr>
                            <div class="small">
                                <p class="mb-2">
                                    <i class="fas fa-calendar text-primary"></i>
                                    <strong>Data:</strong>
                                    <?php echo date('d/m/Y', strtotime($comp['data_inicio_evento'])); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    <strong>Local:</strong> <?php echo htmlspecialchars($comp['local_evento'] ?? 'A definir'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Funcionalidades Enterprise -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="fw-bold mb-2">🚀 Funcionalidades Enterprise</h2>
                    <p class="text-muted">Sistema completo implementado em 4 fases</p>
                </div>
            </div>
            <div class="row g-4">
                <!-- FASE 0 -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-building"></i>
                            </div>
                            <h5 class="fw-bold">Multi-Tenancy</h5>
                            <p class="text-muted small mb-0">Sistema multi-organização com isolamento de dados e 3 planos de assinatura</p>
                        </div>
                    </div>
                </div>

                <!-- FASE 1 -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="fw-bold">Segurança 2FA</h5>
                            <p class="text-muted small mb-0">Autenticação de dois fatores, CSRF Protection e sistema de cache avançado</p>
                        </div>
                    </div>
                </div>

                <!-- FASE 2 -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h5 class="fw-bold">Chaveamento</h5>
                            <p class="text-muted small mb-0">Geração automática de torneios, rankings ELO e estatísticas avançadas</p>
                        </div>
                    </div>
                </div>

                <!-- FASE 3 -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-brain"></i>
                            </div>
                            <h5 class="fw-bold">Analytics & BI</h5>
                            <p class="text-muted small mb-0">Machine Learning, previsão de partidas e identificação de talentos</p>
                        </div>
                    </div>
                </div>

                <!-- FASE 4 -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-plug"></i>
                            </div>
                            <h5 class="fw-bold">Integrações</h5>
                            <p class="text-muted small mb-0">WhatsApp, CBF/COB, YouTube Live e notificações multi-canal</p>
                        </div>
                    </div>
                </div>

                <!-- API -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-secondary bg-opacity-10 text-secondary">
                                <i class="fas fa-code"></i>
                            </div>
                            <h5 class="fw-bold">API RESTful</h5>
                            <p class="text-muted small mb-0">API v1 com autenticação JWT e rate limiting configurável</p>
                        </div>
                    </div>
                </div>

                <!-- Pagamentos -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h5 class="fw-bold">Pagamentos</h5>
                            <p class="text-muted small mb-0">PIX, Cartão, Split de pagamento e webhooks de confirmação</p>
                        </div>
                    </div>
                </div>

                <!-- Streaming -->
                <div class="col-md-3">
                    <div class="card feature-card shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-video"></i>
                            </div>
                            <h5 class="fw-bold">Streaming</h5>
                            <p class="text-muted small mb-0">Integração com YouTube Live e Facebook Live para transmissões</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container text-center text-white">
            <h2 class="mb-4 fw-bold">Acesse os Dashboards</h2>
            <p class="lead mb-4">Sistema enterprise completo com 14.436 linhas de código implementadas</p>
            <div class="row g-3 justify-content-center">
                <div class="col-md-3">
                    <a href="../admin/dashboard_executivo.php" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-chart-pie"></i> Dashboard Executivo
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="../admin/dashboard_analytics.php" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-brain"></i> Analytics & BI
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="../admin/dashboard_integracoes.php" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-plug"></i> Integrações
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="../admin/gerar_chaveamento.php" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-sitemap"></i> Chaveamento
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-trophy me-2"></i>Sistema Esportivo</h5>
                    <p class="text-muted">Plataforma enterprise completa para gestão esportiva</p>
                    <div class="mt-3">
                        <span class="badge bg-primary me-2">55 Tabelas</span>
                        <span class="badge bg-success me-2">100+ Funções</span>
                        <span class="badge bg-info">9 Migrations</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Acesso Rápido</h6>
                    <ul class="list-unstyled">
                        <li><a href="../equipe/login.php" class="text-muted text-decoration-none"><i class="fas fa-users"></i> Área da Equipe</a></li>
                        <li><a href="../admin/login.php" class="text-muted text-decoration-none"><i class="fas fa-shield-alt"></i> Área Administrativa</a></li>
                        <li><a href="/test_error.php" class="text-muted text-decoration-none"><i class="fas fa-bug"></i> Diagnóstico</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Tecnologias</h6>
                    <p class="text-muted small">
                        PHP 8.2+ | MySQL 5.7+ | Bootstrap 5 | Chart.js<br>
                        Machine Learning | Multi-Tenancy | RESTful API
                    </p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <small class="text-muted">
                    &copy; 2025 Sistema de Gestão Esportiva Enterprise.
                    <strong>14.436 linhas de código</strong> | <strong>4 Fases Completas</strong>
                </small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
