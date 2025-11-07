<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../config/config.php';
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Buscar competições abertas
try {
    $stmt = $pdo->query("
        SELECT * FROM competicoes
        WHERE status = 'Aberta'
        ORDER BY data_inicio_inscricao DESC
        LIMIT 6
    ");
    $competicoesAbertas = $stmt->fetchAll();
} catch (Exception $e) {
    $competicoesAbertas = [];
}

// Buscar próximas competições
try {
    $stmt = $pdo->query("
        SELECT * FROM competicoes
        WHERE data_inicio_evento >= CURDATE()
        ORDER BY data_inicio_evento ASC
        LIMIT 6
    ");
    $proximasCompeticoes = $stmt->fetchAll();
} catch (Exception $e) {
    $proximasCompeticoes = [];
}

// Buscar resultados recentes (competições com resultados)
try {
    $stmt = $pdo->query("
        SELECT DISTINCT c.*, COUNT(DISTINCT i.id) as total_inscritos
        FROM competicoes c
        INNER JOIN inscricoes_competicoes i ON c.id = i.competicao_id
        WHERE i.colocacao IS NOT NULL
        GROUP BY c.id
        ORDER BY c.data_inicio_evento DESC
        LIMIT 6
    ");
    $resultadosRecentes = $stmt->fetchAll();
} catch (Exception $e) {
    $resultadosRecentes = [];
}

// Estatísticas gerais
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes");
    $totalCompeticoes = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes WHERE status = 'Aprovada'");
    $totalEquipes = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 1");
    $totalAtletas = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
    $totalInscricoes = $stmt->fetch()['total'];
} catch (Exception $e) {
    $totalCompeticoes = 0;
    $totalEquipes = 0;
    $totalAtletas = 0;
    $totalInscricoes = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Competições Esportivas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .competicao-card {
            transition: all 0.3s;
            height: 100%;
        }
        .competicao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
            margin: 0 auto 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-trophy"></i> Competições Esportivas
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
                            <i class="fas fa-sign-in-alt"></i> Login Equipe
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/login.php">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">
                        <i class="fas fa-trophy"></i>
                        Sistema de Gestão de Competições Esportivas
                    </h1>
                    <p class="lead mb-4">
                        Plataforma completa para gerenciamento de competições, inscrições de equipes e publicação de resultados
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="../equipe/login.php" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-users"></i> Área da Equipe
                        </a>
                        <a href="../admin/login.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-shield-alt"></i> Área Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold">Estatísticas do Sistema</h2>
                    <p class="text-muted">Números atualizados em tempo real</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #667eea;">
                        <div class="card-body text-center">
                            <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                            <h2 class="mb-0"><?php echo $totalCompeticoes; ?></h2>
                            <p class="text-muted mb-0">Competições</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #f093fb;">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-warning mb-3"></i>
                            <h2 class="mb-0"><?php echo $totalEquipes; ?></h2>
                            <p class="text-muted mb-0">Equipes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #4facfe;">
                        <div class="card-body text-center">
                            <i class="fas fa-running fa-3x text-info mb-3"></i>
                            <h2 class="mb-0"><?php echo $totalAtletas; ?></h2>
                            <p class="text-muted mb-0">Atletas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0" style="border-left-color: #43e97b;">
                        <div class="card-body text-center">
                            <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
                            <h2 class="mb-0"><?php echo $totalInscricoes; ?></h2>
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
                                <span class="badge bg-info"><?php echo htmlspecialchars($comp['modalidade']); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($comp['nome']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($comp['descricao'], 0, 100)); ?>...
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
                                    <strong>Local:</strong> <?php echo htmlspecialchars($comp['local']); ?>
                                </p>
                                <p class="mb-0 text-success">
                                    <i class="fas fa-clock"></i>
                                    <strong>Inscrições até:</strong>
                                    <?php echo date('d/m/Y', strtotime($comp['data_fim_inscricao'])); ?>
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
    <?php else: ?>
    <section class="py-5">
        <div class="container">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h5>Nenhuma competição com inscrições abertas no momento</h5>
                <p class="mb-0">Em breve teremos novas competições disponíveis!</p>
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
                                <span class="badge bg-info"><?php echo htmlspecialchars($comp['modalidade']); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($comp['nome']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($comp['descricao'], 0, 100)); ?>...
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
                                    <strong>Local:</strong> <?php echo htmlspecialchars($comp['local']); ?>
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

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="fw-bold">Por que usar nossa plataforma?</h2>
                    <p class="text-muted">Sistema completo para gestão de eventos esportivos</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h5>Inscrições Online</h5>
                    <p class="text-muted">Sistema completo de inscrições com validação automática e protocolo de acompanhamento</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Gestão Completa</h5>
                    <p class="text-muted">Gerencie competições, equipes, atletas e resultados em um único lugar</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h5>Resultados em Tempo Real</h5>
                    <p class="text-muted">Publique resultados, rankings e estatísticas instantaneamente</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Pronto para Participar?</h2>
            <p class="lead mb-4">Faça login ou cadastre sua equipe para começar a participar das competições</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="../equipe/login.php" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-user-plus"></i> Área da Equipe
                </a>
                <a href="../admin/login.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="fas fa-shield-alt"></i> Acesso Admin
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-trophy"></i> Sistema de Competições Esportivas</h5>
                    <p class="text-muted">Plataforma completa para gestão de eventos esportivos</p>
                </div>
                <div class="col-md-3">
                    <h6>Acesso Rápido</h6>
                    <ul class="list-unstyled">
                        <li><a href="../equipe/login.php" class="text-muted text-decoration-none">Login Equipe</a></li>
                        <li><a href="../admin/login.php" class="text-muted text-decoration-none">Login Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Informações</h6>
                    <p class="text-muted small">Sistema desenvolvido para facilitar a gestão de competições esportivas</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center text-muted">
                <small>&copy; 2025 Sistema de Gestão de Competições Esportivas. Todos os direitos reservados.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
