<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Processar aprovação/rejeição de inscrição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $inscricaoId = (int)$_POST['inscricao_id'];
    $acao = $_POST['acao'];

    try {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare("UPDATE inscricoes_competicoes SET status = 'Confirmada' WHERE id = ?");
            $stmt->execute([$inscricaoId]);
            redirect('inscricoes.php', 'Inscrição aprovada com sucesso!', 'success');
        } elseif ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE inscricoes_competicoes SET status = 'Cancelada' WHERE id = ?");
            $stmt->execute([$inscricaoId]);
            redirect('inscricoes.php', 'Inscrição rejeitada com sucesso!', 'success');
        }
    } catch (Exception $e) {
        redirect('inscricoes.php', 'Erro ao processar inscrição: ' . $e->getMessage(), 'error');
    }
}

// Filtros
$filtroCompeticao = $_GET['competicao'] ?? '';
$filtroEquipe = $_GET['equipe'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

// Buscar estatísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Pendente'");
$totalPendentes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
$totalConfirmadas = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Cancelada'");
$totalCanceladas = $stmt->fetch()['total'];

// Construir query de inscrições com filtros
$sql = "
    SELECT
        i.*,
        c.nome as competicao_nome,
        c.status as competicao_status,
        e.nome as equipe_nome,
        e.responsavel_nome as equipe_responsavel,
        m.nome as modalidade_nome,
        (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = i.id) as total_atletas
    FROM inscricoes_competicoes i
    INNER JOIN competicoes c ON i.competicao_id = c.id
    INNER JOIN equipes e ON i.equipe_id = e.id
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE 1=1
";

$params = [];

if (!empty($filtroCompeticao)) {
    $sql .= " AND i.competicao_id = ?";
    $params[] = $filtroCompeticao;
}

if (!empty($filtroEquipe)) {
    $sql .= " AND e.nome LIKE ?";
    $params[] = "%$filtroEquipe%";
}

if (!empty($filtroStatus)) {
    $sql .= " AND i.status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inscricoes = $stmt->fetchAll();

// Buscar competições para filtro
$competicoes = $pdo->query("SELECT id, nome FROM competicoes ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Gerenciar Inscrições';
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

        .card-stat {
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
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
                        <a class="nav-link active" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
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

    <div class="container mt-4 mb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-clipboard-list text-primary"></i>
                Gerenciar Inscrições
            </h2>
        </div>

        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert alert-<?php echo $_SESSION['mensagem_tipo']; ?> alert-dismissible fade show">
                <?php
                echo htmlspecialchars($_SESSION['mensagem']);
                unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h3 class="display-4 mb-1 text-warning"><?php echo $totalPendentes; ?></h3>
                        <p class="text-muted mb-0 small">Inscrições Pendentes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h3 class="display-4 mb-1 text-success"><?php echo $totalConfirmadas; ?></h3>
                        <p class="text-muted mb-0 small">Inscrições Confirmadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100 card-stat">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h3 class="display-4 mb-1 text-danger"><?php echo $totalCanceladas; ?></h3>
                        <p class="text-muted mb-0 small">Inscrições Canceladas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-trophy"></i> Competição
                        </label>
                        <select name="competicao" class="form-select">
                            <option value="">Todas as competições</option>
                            <?php foreach ($competicoes as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>"
                                        <?php echo $filtroCompeticao == $comp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comp['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-users"></i> Equipe
                        </label>
                        <input type="text" name="equipe" class="form-control"
                               placeholder="Nome da equipe"
                               value="<?php echo htmlspecialchars($filtroEquipe); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">Todos os status</option>
                            <option value="Pendente" <?php echo $filtroStatus === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="Confirmada" <?php echo $filtroStatus === 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="Cancelada" <?php echo $filtroStatus === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="inscricoes.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Limpar Filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Inscrições -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Inscrições
                    <span class="badge bg-secondary"><?php echo count($inscricoes); ?></span>
                </h5>
            </div>

            <?php if (empty($inscricoes)): ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted">Nenhuma inscrição encontrada</h4>
                    <p class="text-muted">Não há inscrições que correspondam aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Protocolo</th>
                                <th>Competição</th>
                                <th>Equipe</th>
                                <th>Modalidade</th>
                                <th class="text-center">Atletas</th>
                                <th class="text-center">Status</th>
                                <th>Data Inscrição</th>
                                <th class="text-center" style="width: 200px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $insc):
                                $badgeClass = [
                                    'Pendente' => 'bg-warning',
                                    'Confirmada' => 'bg-success',
                                    'Cancelada' => 'bg-danger'
                                ];
                                $class = $badgeClass[$insc['status']] ?? 'bg-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <strong class="font-monospace"><?php echo htmlspecialchars($insc['protocolo']); ?></strong>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($insc['competicao_nome']); ?></div>
                                        <small class="text-muted">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($insc['competicao_status']); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($insc['equipe_nome']); ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($insc['equipe_responsavel']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo htmlspecialchars($insc['modalidade_nome'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="verAtletas(<?php echo $insc['id']; ?>)">
                                            <i class="fas fa-users"></i> <?php echo $insc['total_atletas']; ?>
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo htmlspecialchars($insc['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo formatarDataHora($insc['created_at']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($insc['status'] === 'Pendente'): ?>
                                            <form method="POST" style="display: inline-block;"
                                                  onsubmit="return confirm('Confirmar aprovação desta inscrição?');">
                                                <input type="hidden" name="inscricao_id" value="<?php echo $insc['id']; ?>">
                                                <input type="hidden" name="acao" value="aprovar">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Aprovar
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline-block;"
                                                  onsubmit="return confirm('Confirmar rejeição desta inscrição?');">
                                                <input type="hidden" name="inscricao_id" value="<?php echo $insc['id']; ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i> Rejeitar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal - Ver Atletas -->
    <div class="modal fade" id="modalAtletas" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-users"></i> Atletas Inscritos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalAtletasConteudo">
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
        function verAtletas(inscricaoId) {
            const modal = new bootstrap.Modal(document.getElementById('modalAtletas'));
            const conteudo = document.getElementById('modalAtletasConteudo');

            // Mostrar loading
            conteudo.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            `;

            modal.show();

            // Buscar atletas via AJAX
            fetch('get_atletas_inscricao.php?id=' + inscricaoId)
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
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="text-muted mb-1">Competição</h6>
                            <p class="mb-0"><strong>${data.competicao.nome}</strong></p>
                        </div>
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="text-muted mb-1">Equipe</h6>
                            <p class="mb-0"><strong>${data.equipe.nome}</strong></p>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> Responsável: ${data.equipe.responsavel}
                            </small>
                        </div>
                        <h6 class="text-muted mb-3">Atletas (${data.atletas.length})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">Foto</th>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th class="text-center">Gênero</th>
                                        <th class="text-center">Idade</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.atletas.forEach(atleta => {
                        const primeiraLetra = atleta.nome.charAt(0).toUpperCase();
                        const fotoHtml = atleta.foto_path
                            ? '<img src="../' + atleta.foto_path + '" class="foto-circular">'
                            : '<div class="foto-placeholder">' + primeiraLetra + '</div>';

                        html += `
                            <tr>
                                <td>${fotoHtml}</td>
                                <td>${atleta.nome}</td>
                                <td><span class="font-monospace small">${atleta.cpf}</span></td>
                                <td class="text-center">
                                    <span class="badge ${atleta.genero === 'M' ? 'bg-primary' : 'bg-pink'}">
                                        ${atleta.genero === 'M' ? 'Masculino' : 'Feminino'}
                                    </span>
                                </td>
                                <td class="text-center">${atleta.idade} anos</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    conteudo.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Erro ao carregar atletas: ${error.message}
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>
