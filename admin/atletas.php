<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Filtros
$filtroNome = $_GET['nome'] ?? '';
$filtroCPF = $_GET['cpf'] ?? '';
$filtroEquipe = $_GET['equipe'] ?? '';
$filtroGenero = $_GET['genero'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

// Buscar estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 1");
$totalAtivos = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE ativo = 0");
$totalInativos = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE genero = 'M' AND ativo = 1");
$totalMasculino = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas WHERE genero = 'F' AND ativo = 1");
$totalFeminino = $stmt->fetch()['total'];

// Construir query com filtros
$sql = "
    SELECT
        a.*,
        e.nome as equipe_nome,
        TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
        (SELECT COUNT(DISTINCT ia.inscricao_competicao_id)
         FROM inscricoes_atletas ia
         INNER JOIN inscricoes_competicoes ic ON ia.inscricao_competicao_id = ic.id
         WHERE ia.atleta_id = a.id AND ic.status = 'Confirmada') as total_competicoes
    FROM atletas a
    LEFT JOIN equipes e ON a.equipe_atual_id = e.id
    WHERE 1=1
";

$params = [];

if (!empty($filtroNome)) {
    $sql .= " AND a.nome_completo LIKE ?";
    $params[] = "%$filtroNome%";
}

if (!empty($filtroCPF)) {
    $cpfLimpo = preg_replace('/[^0-9]/', '', $filtroCPF);
    $sql .= " AND a.cpf LIKE ?";
    $params[] = "%$cpfLimpo%";
}

if (!empty($filtroEquipe)) {
    $sql .= " AND e.nome LIKE ?";
    $params[] = "%$filtroEquipe%";
}

if (!empty($filtroGenero)) {
    $sql .= " AND a.genero = ?";
    $params[] = $filtroGenero;
}

if ($filtroStatus !== '') {
    $sql .= " AND a.ativo = ?";
    $params[] = (int)$filtroStatus;
}

$sql .= " ORDER BY a.nome_completo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$atletas = $stmt->fetchAll();

$pageTitle = 'Gerenciar Atletas';
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

        .foto-circular {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .foto-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
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
                        <a class="nav-link" href="equipes.php">
                            <i class="fas fa-users"></i> Equipes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="atletas.php">
                            <i class="fas fa-running"></i> Atletas
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

    <div class="container-fluid mt-4 mb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-running text-info"></i>
                Gerenciar Atletas
            </h2>
        </div>

        <!-- Estatísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                        <h3 class="display-4 mb-1 text-success"><?php echo $totalAtivos; ?></h3>
                        <p class="text-muted mb-0 small">Atletas Ativos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-user-slash fa-2x text-danger mb-2"></i>
                        <h3 class="display-4 mb-1 text-danger"><?php echo $totalInativos; ?></h3>
                        <p class="text-muted mb-0 small">Atletas Inativos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-mars fa-2x text-primary mb-2"></i>
                        <h3 class="display-4 mb-1 text-primary"><?php echo $totalMasculino; ?></h3>
                        <p class="text-muted mb-0 small">Masculino</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-venus fa-2x text-pink mb-2" style="color: #e83e8c;"></i>
                        <h3 class="display-4 mb-1" style="color: #e83e8c;"><?php echo $totalFeminino; ?></h3>
                        <p class="text-muted mb-0 small">Feminino</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-search"></i> Nome
                        </label>
                        <input type="text" name="nome" class="form-control"
                               placeholder="Digite o nome"
                               value="<?php echo htmlspecialchars($filtroNome); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> CPF
                        </label>
                        <input type="text" name="cpf" class="form-control"
                               placeholder="000.000.000-00"
                               value="<?php echo htmlspecialchars($filtroCPF); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-users"></i> Equipe
                        </label>
                        <input type="text" name="equipe" class="form-control"
                               placeholder="Nome da equipe"
                               value="<?php echo htmlspecialchars($filtroEquipe); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-venus-mars"></i> Gênero
                        </label>
                        <select name="genero" class="form-select">
                            <option value="">Todos</option>
                            <option value="M" <?php echo $filtroGenero === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo $filtroGenero === 'F' ? 'selected' : ''; ?>>Feminino</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $filtroStatus === '1' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo $filtroStatus === '0' ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="atletas.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Limpar Filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Atletas -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Atletas Cadastrados
                    <span class="badge bg-secondary"><?php echo count($atletas); ?></span>
                </h5>
            </div>

            <?php if (empty($atletas)): ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-running text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted">Nenhum atleta encontrado</h4>
                    <p class="text-muted">Não há atletas que correspondam aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Foto</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Equipe</th>
                                <th class="text-center">Gênero</th>
                                <th class="text-center">Idade</th>
                                <th class="text-center">Competições</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atletas as $atleta):
                                $primeiraLetra = strtoupper(substr($atleta['nome_completo'], 0, 1));
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if ($atleta['foto_path']): ?>
                                            <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                                 class="foto-circular"
                                                 alt="<?php echo htmlspecialchars($atleta['nome_completo']); ?>">
                                        <?php else: ?>
                                            <div class="foto-placeholder"><?php echo $primeiraLetra; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($atleta['nome_completo']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="font-monospace"><?php echo formatarCPF($atleta['cpf']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($atleta['equipe_nome']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($atleta['equipe_nome']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sem equipe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($atleta['genero'] === 'M'): ?>
                                            <span class="badge bg-primary"><i class="fas fa-mars"></i> Masculino</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: #e83e8c;"><i class="fas fa-venus"></i> Feminino</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo $atleta['idade']; ?></strong> anos
                                    </td>
                                    <td class="text-center">
                                        <?php if ($atleta['total_competicoes'] > 0): ?>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="verHistorico(<?php echo $atleta['id']; ?>, '<?php echo htmlspecialchars($atleta['nome_completo']); ?>')">
                                                <i class="fas fa-trophy"></i> <?php echo $atleta['total_competicoes']; ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($atleta['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info"
                                                onclick="verDetalhes(<?php echo $atleta['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal - Histórico de Competições -->
    <div class="modal fade" id="modalHistorico" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-history"></i> Histórico de Competições
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalHistoricoConteudo">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Detalhes do Atleta -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user"></i> Detalhes do Atleta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetalhesConteudo">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verHistorico(atletaId, atletaNome) {
            const modal = new bootstrap.Modal(document.getElementById('modalHistorico'));
            const conteudo = document.getElementById('modalHistoricoConteudo');

            // Mostrar loading
            conteudo.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;

            modal.show();

            // Buscar histórico via AJAX
            fetch('get_historico_atleta.php?atleta_id=' + atletaId)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        conteudo.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> ${data.erro}
                            </div>
                        `;
                        return;
                    }

                    let html = `
                        <h6 class="mb-3"><strong>${atletaNome}</strong></h6>
                    `;

                    if (data.competicoes.length === 0) {
                        html += `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Atleta ainda não participou de nenhuma competição.
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Competição</th>
                                            <th>Equipe</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        data.competicoes.forEach(comp => {
                            const badgeClass = {
                                'Pendente': 'bg-warning',
                                'Confirmada': 'bg-success',
                                'Cancelada': 'bg-danger'
                            }[comp.status] || 'bg-secondary';

                            html += `
                                <tr>
                                    <td><strong>${comp.competicao_nome}</strong></td>
                                    <td>${comp.equipe_nome}</td>
                                    <td><span class="badge ${badgeClass}">${comp.status}</span></td>
                                    <td><small class="text-muted">${comp.data_inscricao}</small></td>
                                </tr>
                            `;
                        });

                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    conteudo.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Erro ao carregar histórico
                        </div>
                    `;
                });
        }

        function verDetalhes(atletaId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
            const conteudo = document.getElementById('modalDetalhesConteudo');

            // Mostrar loading
            conteudo.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;

            modal.show();

            // Buscar detalhes via AJAX
            fetch('get_detalhes_atleta.php?atleta_id=' + atletaId)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        conteudo.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> ${data.erro}
                            </div>
                        `;
                        return;
                    }

                    const atleta = data.atleta;
                    const primeiraLetra = atleta.nome_completo.charAt(0).toUpperCase();

                    let html = `
                        <div class="row">
                            <div class="col-md-4 text-center">
                    `;

                    if (atleta.foto_path) {
                        html += `<img src="../${atleta.foto_path}" class="img-fluid rounded" style="max-width: 200px;">`;
                    } else {
                        html += `
                            <div class="foto-placeholder mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                ${primeiraLetra}
                            </div>
                        `;
                    }

                    html += `
                            </div>
                            <div class="col-md-8">
                                <h5>${atleta.nome_completo}</h5>
                                <hr>
                                <p><strong><i class="fas fa-id-card"></i> CPF:</strong> ${atleta.cpf}</p>
                                <p><strong><i class="fas fa-birthday-cake"></i> Data de Nascimento:</strong> ${atleta.data_nascimento}</p>
                                <p><strong><i class="fas fa-calendar"></i> Idade:</strong> ${atleta.idade} anos</p>
                                <p><strong><i class="fas fa-venus-mars"></i> Gênero:</strong> ${atleta.genero === 'M' ? 'Masculino' : 'Feminino'}</p>
                    `;

                    if (atleta.equipe_nome) {
                        html += `<p><strong><i class="fas fa-users"></i> Equipe:</strong> ${atleta.equipe_nome}</p>`;
                    }

                    html += `
                                <p><strong><i class="fas fa-flag"></i> Status:</strong>
                                    <span class="badge ${atleta.ativo ? 'bg-success' : 'bg-danger'}">
                                        ${atleta.ativo ? 'Ativo' : 'Inativo'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    `;

                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    conteudo.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Erro ao carregar detalhes
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>
