<?php
/**
 * Página de Histórico de Inscrições
 * Lista todas as inscrições da equipe com detalhes
 */

require_once __DIR__ . '/../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];

// Buscar todas as inscrições da equipe
$stmt = $pdo->prepare("
    SELECT
        ic.*,
        c.nome as competicao_nome,
        c.banner_path,
        c.data_inicio,
        c.data_fim,
        m.nome as modalidade_nome,
        m.icone as modalidade_icone,
        (SELECT COUNT(*) FROM inscricoes_atletas
         WHERE inscricao_competicao_id = ic.id) as qtd_atletas
    FROM inscricoes_competicoes ic
    INNER JOIN competicoes c ON ic.competicao_id = c.id
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE ic.equipe_id = ?
    ORDER BY ic.data_inscricao DESC
");
$stmt->execute([$equipeId]);
$inscricoes = $stmt->fetchAll();

$pageTitle = 'Minhas Inscrições';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-trophy"></i> Sistema de Competições
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
                        <a class="nav-link" href="atletas.php">
                            <i class="fas fa-users"></i> Atletas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="minhas_inscricoes.php">
                            <i class="fas fa-history"></i> Histórico
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['equipe_nome']); ?>
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

    <div class="container mt-4">
        <!-- Mensagens -->
        <?php exibirMensagem(); ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-history"></i> Histórico de Inscrições</h2>
                <p class="text-muted">Veja todas as suas inscrições em competições</p>
            </div>
            <a href="inscricoes.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Inscrição
            </a>
        </div>

        <!-- Estatísticas -->
        <?php
        $totalInscricoes = count($inscricoes);
        $pendentes = 0;
        $confirmadas = 0;
        $canceladas = 0;

        foreach ($inscricoes as $insc) {
            if ($insc['status'] === 'Pendente') $pendentes++;
            if ($insc['status'] === 'Confirmada') $confirmadas++;
            if ($insc['status'] === 'Cancelada') $canceladas++;
        }
        ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $totalInscricoes; ?></h3>
                    <p>Total de Inscrições</p>
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3 class="text-warning"><?php echo $pendentes; ?></h3>
                    <p>Pendentes</p>
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3 class="text-success"><?php echo $confirmadas; ?></h3>
                    <p>Confirmadas</p>
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3 class="text-danger"><?php echo $canceladas; ?></h3>
                    <p>Canceladas</p>
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>

        <!-- Lista de Inscrições -->
        <?php if (count($inscricoes) === 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Você ainda não possui inscrições em competições.
                <a href="inscricoes.php" class="alert-link">Inscrever agora</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($inscricoes as $insc):
                    // Definir cor do status
                    $statusClass = 'secondary';
                    $statusIcon = 'fa-question';

                    if ($insc['status'] === 'Pendente') {
                        $statusClass = 'warning';
                        $statusIcon = 'fa-clock';
                    } elseif ($insc['status'] === 'Confirmada') {
                        $statusClass = 'success';
                        $statusIcon = 'fa-check-circle';
                    } elseif ($insc['status'] === 'Cancelada') {
                        $statusClass = 'danger';
                        $statusIcon = 'fa-times-circle';
                    }
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-<?php echo $statusClass; ?>">
                            <?php if (!empty($insc['banner_path'])): ?>
                                <img src="<?php echo BANNER_URL . $insc['banner_path']; ?>"
                                     class="card-img-top"
                                     style="height: 150px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($insc['competicao_nome']); ?>">
                            <?php endif; ?>

                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0">
                                        <?php if (!empty($insc['modalidade_icone'])): ?>
                                            <i class="fas <?php echo $insc['modalidade_icone']; ?>"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($insc['competicao_nome']); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?>"></i>
                                        <?php echo $insc['status']; ?>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <ul class="list-unstyled small">
                                        <li>
                                            <i class="fas fa-barcode"></i>
                                            <strong>Protocolo:</strong>
                                            <code><?php echo $insc['protocolo']; ?></code>
                                        </li>
                                        <li>
                                            <i class="fas fa-calendar"></i>
                                            <strong>Data da Inscrição:</strong>
                                            <?php echo formatarDataHora($insc['data_inscricao']); ?>
                                        </li>
                                        <li>
                                            <i class="fas fa-users"></i>
                                            <strong>Atletas Inscritos:</strong>
                                            <?php echo $insc['qtd_atletas']; ?>
                                        </li>
                                        <?php if ($insc['data_inicio']): ?>
                                            <li>
                                                <i class="fas fa-play-circle"></i>
                                                <strong>Início:</strong>
                                                <?php echo formatarData($insc['data_inicio']); ?>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <button type="button"
                                        class="btn btn-outline-primary btn-sm w-100"
                                        onclick="verDetalhesInscricao(<?php echo $insc['id']; ?>)">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Detalhes da Inscrição -->
    <div class="modal fade" id="modalDetalhesInscricao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-list"></i> Detalhes da Inscrição
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="conteudoDetalhes">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalhesInscricao(inscricaoId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalhesInscricao'));
            modal.show();

            // Carregar detalhes via AJAX
            fetch(`get_detalhes_inscricao.php?id=${inscricaoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        document.getElementById('conteudoDetalhes').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.erro}
                            </div>
                        `;
                        return;
                    }

                    let html = '<div class="row">';

                    // Informações da Competição
                    html += '<div class="col-md-6 mb-3">';
                    html += '<h6><i class="fas fa-trophy"></i> Competição</h6>';
                    html += '<ul class="list-unstyled">';
                    html += `<li><strong>Nome:</strong> ${data.competicao.nome}</li>`;
                    html += `<li><strong>Modalidade:</strong> ${data.competicao.modalidade || 'N/A'}</li>`;
                    if (data.competicao.data_inicio) {
                        html += `<li><strong>Início:</strong> ${data.competicao.data_inicio}</li>`;
                    }
                    html += '</ul>';
                    html += '</div>';

                    // Informações da Inscrição
                    html += '<div class="col-md-6 mb-3">';
                    html += '<h6><i class="fas fa-clipboard-check"></i> Inscrição</h6>';
                    html += '<ul class="list-unstyled">';
                    html += `<li><strong>Protocolo:</strong> <code>${data.inscricao.protocolo}</code></li>`;
                    html += `<li><strong>Status:</strong> <span class="badge bg-${data.inscricao.status_class}">${data.inscricao.status}</span></li>`;
                    html += `<li><strong>Data:</strong> ${data.inscricao.data_inscricao}</li>`;
                    html += '</ul>';
                    html += '</div>';

                    html += '</div>';

                    // Atletas Inscritos
                    html += '<hr>';
                    html += '<h6><i class="fas fa-users"></i> Atletas Inscritos</h6>';

                    if (data.atletas.length === 0) {
                        html += '<p class="text-muted">Nenhum atleta inscrito.</p>';
                    } else {
                        html += '<div class="row">';
                        data.atletas.forEach(atleta => {
                            html += '<div class="col-md-6 mb-2">';
                            html += '<div class="d-flex align-items-center p-2 border rounded">';
                            if (atleta.foto_path) {
                                html += `<img src="${atleta.foto_path}" class="foto-circular me-2" alt="${atleta.nome}">`;
                            }
                            html += '<div>';
                            html += `<strong>${atleta.nome}</strong><br>`;
                            html += `<small class="text-muted">${atleta.idade} anos • ${atleta.genero}</small>`;
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }

                    document.getElementById('conteudoDetalhes').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('conteudoDetalhes').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Erro ao carregar detalhes.
                        </div>
                    `;
                    console.error('Erro:', error);
                });
        }
    </script>
</body>
</html>
