<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Processar registro de resultados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    try {
        if ($_POST['acao'] === 'salvar_resultados') {
            $competicaoId = (int)$_POST['competicao_id'];
            $colocacoes = $_POST['colocacao'] ?? [];
            $pontuacoes = $_POST['pontuacao'] ?? [];

            $pdo->beginTransaction();

            foreach ($colocacoes as $inscricaoId => $colocacao) {
                $pontuacao = $pontuacoes[$inscricaoId] ?? null;

                // Atualizar resultado na inscrição
                $stmt = $pdo->prepare("
                    UPDATE inscricoes_competicoes
                    SET colocacao = ?, pontuacao = ?, updated_at = NOW()
                    WHERE id = ? AND competicao_id = ?
                ");
                $stmt->execute([
                    $colocacao ?: null,
                    $pontuacao ?: null,
                    $inscricaoId,
                    $competicaoId
                ]);
            }

            $pdo->commit();
            redirect('resultados.php?competicao_id=' . $competicaoId, 'Resultados salvos com sucesso!', 'success');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirect('resultados.php', 'Erro ao salvar resultados: ' . $e->getMessage(), 'error');
    }
}

// Filtros
$filtroCompeticao = $_GET['competicao_id'] ?? '';
$filtroStatus = $_GET['status'] ?? 'Encerrada';

// Buscar competições
$sql = "SELECT id, nome, status, data_inicio_evento, data_fim_evento FROM competicoes WHERE 1=1";
$params = [];

if (!empty($filtroStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY data_inicio_evento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$competicoes = $stmt->fetchAll();

// Se uma competição específica foi selecionada, buscar resultados
$competicaoSelecionada = null;
$inscricoes = [];

if ($filtroCompeticao) {
    $stmt = $pdo->prepare("SELECT * FROM competicoes WHERE id = ?");
    $stmt->execute([$filtroCompeticao]);
    $competicaoSelecionada = $stmt->fetch();

    if ($competicaoSelecionada) {
        // Buscar inscrições confirmadas
        $stmt = $pdo->prepare("
            SELECT
                ic.*,
                e.nome as equipe_nome,
                (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = ic.id) as total_atletas
            FROM inscricoes_competicoes ic
            INNER JOIN equipes e ON ic.equipe_id = e.id
            WHERE ic.competicao_id = ? AND ic.status = 'Confirmada'
            ORDER BY
                CASE WHEN ic.colocacao IS NULL THEN 1 ELSE 0 END,
                ic.colocacao ASC,
                ic.pontuacao DESC
        ");
        $stmt->execute([$filtroCompeticao]);
        $inscricoes = $stmt->fetchAll();
    }
}

$pageTitle = 'Gerenciar Resultados';
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
        .podio-card {
            transition: transform 0.2s;
        }

        .podio-card:hover {
            transform: translateY(-5px);
        }

        .ouro {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: white;
        }

        .prata {
            background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
            color: white;
        }

        .bronze {
            background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
            color: white;
        }

        .posicao-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
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
                        <a class="nav-link active" href="resultados.php">
                            <i class="fas fa-medal"></i> Resultados
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
                <i class="fas fa-medal text-warning"></i>
                Gerenciar Resultados
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

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-trophy"></i> Selecionar Competição
                        </label>
                        <select name="competicao_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Selecione uma competição...</option>
                            <?php foreach ($competicoes as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>"
                                        <?php echo $filtroCompeticao == $comp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comp['nome']); ?>
                                    (<?php echo htmlspecialchars($comp['status']); ?> -
                                    <?php echo formatarData($comp['data_inicio_evento']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Status da Competição
                        </label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="Encerrada" <?php echo $filtroStatus === 'Encerrada' ? 'selected' : ''; ?>>Encerrada</option>
                            <option value="Em Andamento" <?php echo $filtroStatus === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="Fechada" <?php echo $filtroStatus === 'Fechada' ? 'selected' : ''; ?>>Fechada</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($competicaoSelecionada): ?>
            <!-- Pódio (Top 3) -->
            <?php
            $top3 = array_filter($inscricoes, function($insc) {
                return $insc['colocacao'] && $insc['colocacao'] <= 3;
            });
            ?>

            <?php if (!empty($top3)): ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($top3 as $insc):
                        if ($insc['colocacao'] == 1) {
                            $classe = 'ouro';
                            $icone = 'fa-trophy';
                        } elseif ($insc['colocacao'] == 2) {
                            $classe = 'prata';
                            $icone = 'fa-medal';
                        } else {
                            $classe = 'bronze';
                            $icone = 'fa-award';
                        }
                    ?>
                        <div class="col-md-4">
                            <div class="card podio-card <?php echo $classe; ?> shadow">
                                <div class="card-body text-center">
                                    <i class="fas <?php echo $icone; ?> fa-3x mb-3"></i>
                                    <h3><?php echo $insc['colocacao']; ?>º Lugar</h3>
                                    <h5><?php echo htmlspecialchars($insc['equipe_nome']); ?></h5>
                                    <?php if ($insc['pontuacao']): ?>
                                        <p class="mb-0"><strong><?php echo $insc['pontuacao']; ?></strong> pontos</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Resultados -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Registrar/Editar Resultados
                        <span class="badge bg-secondary"><?php echo count($inscricoes); ?> equipes</span>
                    </h5>
                </div>

                <?php if (empty($inscricoes)): ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 3rem;"></i>
                        <h4 class="text-muted">Nenhuma equipe inscrita confirmada</h4>
                        <p class="text-muted">Não há equipes com inscrição confirmada nesta competição.</p>
                    </div>
                <?php else: ?>
                    <div class="card-body">
                        <form method="POST" action="" onsubmit="return confirm('Confirmar salvamento dos resultados?');">
                            <input type="hidden" name="acao" value="salvar_resultados">
                            <input type="hidden" name="competicao_id" value="<?php echo $competicaoSelecionada['id']; ?>">

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 120px;">Colocação</th>
                                            <th>Equipe</th>
                                            <th class="text-center">Atletas</th>
                                            <th style="width: 150px;">Pontuação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inscricoes as $insc): ?>
                                            <tr>
                                                <td>
                                                    <input type="number"
                                                           name="colocacao[<?php echo $insc['id']; ?>]"
                                                           class="form-control"
                                                           placeholder="Ex: 1"
                                                           min="1"
                                                           value="<?php echo htmlspecialchars($insc['colocacao'] ?? ''); ?>">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($insc['equipe_nome']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Protocolo: <?php echo htmlspecialchars($insc['protocolo']); ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo $insc['total_atletas']; ?> atletas</span>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="pontuacao[<?php echo $insc['id']; ?>]"
                                                           class="form-control"
                                                           placeholder="Pontos"
                                                           step="0.01"
                                                           value="<?php echo htmlspecialchars($insc['pontuacao'] ?? ''); ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Salvar Resultados
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ranking Completo -->
            <?php
            $comResultado = array_filter($inscricoes, function($insc) {
                return $insc['colocacao'] !== null;
            });
            ?>

            <?php if (!empty($comResultado)): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-list-ol"></i> Ranking Completo
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;" class="text-center">Posição</th>
                                    <th>Equipe</th>
                                    <th class="text-center">Atletas</th>
                                    <th class="text-center">Pontuação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comResultado as $insc):
                                    if ($insc['colocacao'] == 1) {
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif ($insc['colocacao'] == 2) {
                                        $badgeClass = 'bg-secondary';
                                    } elseif ($insc['colocacao'] == 3) {
                                        $badgeClass = 'bg-brown';
                                        $badgeClass = 'text-white';
                                        $badgeClass = 'bg-warning';
                                    } else {
                                        $badgeClass = 'bg-primary';
                                    }
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="posicao-badge <?php echo $badgeClass; ?>">
                                                <?php echo $insc['colocacao']; ?>º
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($insc['equipe_nome']); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $insc['total_atletas']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($insc['pontuacao']): ?>
                                                <strong><?php echo number_format($insc['pontuacao'], 2, ',', '.'); ?></strong> pts
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-trophy text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">Selecione uma competição</h4>
                    <p class="text-muted">Escolha uma competição acima para gerenciar seus resultados.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
