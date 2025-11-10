<?php
/**
 * Gerenciamento de Competições
 * CRUD completo de competições esportivas
 */

require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Processar criação de competição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['nome', 'data_inicio_inscricao', 'data_fim_inscricao', 'data_inicio_evento', 'data_fim_evento', 'modalidade_id', 'genero_permitido', 'min_atletas', 'max_atletas'];

        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("Campo obrigatório: " . str_replace('_', ' ', $campo));
            }
        }

        // Upload do banner (opcional)
        $bannerFileName = null;
        if (!empty($_FILES['banner']['name'])) {
            $bannerFileName = uploadFile(
                $_FILES['banner'],
                'banner',
                BANNER_PATH,
                ALLOWED_IMAGE_EXTENSIONS,
                MAX_FILE_SIZE
            );
        }

        // Processar categorias permitidas
        $categorias = isset($_POST['categorias_permitidas']) ? $_POST['categorias_permitidas'] : [];
        $categoriasJson = json_encode($categorias);

        // Inserir competição
        $stmt = $pdo->prepare("
            INSERT INTO competicoes (
                nome, descricao, banner_path,
                data_inicio_inscricao, data_fim_inscricao,
                data_inicio_evento, data_fim_evento,
                modalidade_id, categorias_permitidas, genero_permitido,
                min_atletas, max_atletas, taxa_inscricao,
                local_evento, cidade, estado,
                regulamento, status, criado_por, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            sanitize($_POST['nome']),
            sanitize($_POST['descricao'] ?? ''),
            $bannerFileName,
            $_POST['data_inicio_inscricao'],
            $_POST['data_fim_inscricao'],
            $_POST['data_inicio_evento'],
            $_POST['data_fim_evento'],
            (int)$_POST['modalidade_id'],
            $categoriasJson,
            $_POST['genero_permitido'],
            (int)$_POST['min_atletas'],
            (int)$_POST['max_atletas'],
            floatval($_POST['taxa_inscricao'] ?? 0),
            sanitize($_POST['local_evento'] ?? ''),
            sanitize($_POST['cidade'] ?? ''),
            sanitize($_POST['estado'] ?? ''),
            sanitize($_POST['regulamento'] ?? ''),
            $_POST['status'] ?? 'Rascunho',
            $_SESSION['admin_id']
        ]);

        redirect('competicoes.php', 'Competição criada com sucesso!', 'success');

    } catch (Exception $e) {
        redirect('competicoes.php', 'Erro: ' . $e->getMessage(), 'error');
    }
}

// Buscar competições
$stmt = $pdo->query("
    SELECT c.*, m.nome as modalidade_nome
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    ORDER BY c.created_at DESC
");
$competicoes = $stmt->fetchAll();

// Buscar modalidades para o formulário
$modalidades = $pdo->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar categorias para o formulário
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY idade_minima")->fetchAll();

$pageTitle = 'Gerenciar Competições';
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
                        <a class="nav-link active" href="competicoes.php">
                            <i class="fas fa-trophy"></i> Competições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscricoes.php">
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
        <!-- Mensagens -->
        <?php exibirMensagem(); ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-trophy"></i> Gerenciar Competições</h2>
                <p class="text-muted mb-0">Total: <?php echo count($competicoes); ?> competição(ões) cadastrada(s)</p>
            </div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCompeticao">
                <i class="fas fa-plus"></i> Nova Competição
            </button>
        </div>
        <!-- Lista de Competições -->
        <?php if (empty($competicoes)): ?>
            <div class="card text-center p-5 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-trophy text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mb-3">Nenhuma competição cadastrada</h4>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalCompeticao">
                        <i class="fas fa-plus"></i> Criar Primeira Competição
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($competicoes as $comp):
                    $badgeClass = [
                        'Rascunho' => 'bg-secondary',
                        'Aberta' => 'bg-success',
                        'Fechada' => 'bg-primary',
                        'Em Andamento' => 'bg-info',
                        'Encerrada' => 'bg-dark',
                        'Cancelada' => 'bg-danger'
                    ];
                    $class = $badgeClass[$comp['status']] ?? 'bg-secondary';
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($comp['banner_path'])): ?>
                                <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>"
                                     class="card-img-top"
                                     style="height: 180px; object-fit: cover;"
                                     alt="<?php echo htmlspecialchars($comp['nome']); ?>">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted"
                                     style="height: 180px;">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">
                                        <?php echo htmlspecialchars($comp['nome']); ?>
                                    </h5>
                                    <span class="badge <?php echo $class; ?>">
                                        <?php echo $comp['status']; ?>
                                    </span>
                                </div>

                                <p class="text-muted small mb-2">
                                    <i class="fas fa-running"></i>
                                    <?php echo htmlspecialchars($comp['modalidade_nome'] ?? 'N/A'); ?>
                                </p>

                                <?php if (!empty($comp['descricao'])): ?>
                                    <p class="card-text text-muted small">
                                        <?php echo nl2br(htmlspecialchars(substr($comp['descricao'], 0, 100))); ?>
                                        <?php echo strlen($comp['descricao']) > 100 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-auto">
                                    <hr>
                                    <div class="small text-muted">
                                        <div class="mb-1">
                                            <i class="fas fa-calendar-alt"></i>
                                            <strong>Inscrições:</strong><br>
                                            <?php echo formatarData($comp['data_inicio_inscricao']); ?> até
                                            <?php echo formatarData($comp['data_fim_inscricao']); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-users"></i>
                                            <strong>Atletas:</strong> <?php echo $comp['min_atletas']; ?> a <?php echo $comp['max_atletas']; ?>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 mt-3">
                                        <a href="editar_competicao.php?id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmarExclusao(<?php echo $comp['id']; ?>, '<?php echo addslashes($comp['nome']); ?>')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- Modal Criar Competição -->
    <div class="modal fade" id="modalCompeticao" tabindex="-1" aria-labelledby="modalCompeticaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalCompeticaoLabel">
                        <i class="fas fa-plus-circle"></i> Nova Competição
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="criar">

                    <div class="modal-body">
                        <!-- Informações Básicas -->
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-info-circle text-primary"></i> Informações Básicas
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome da Competição *</label>
                                <input type="text"
                                       class="form-control"
                                       id="nome"
                                       name="nome"
                                       placeholder="Ex: Copa Roraima de Futsal 2025"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label for="modalidade_id" class="form-label">Modalidade *</label>
                                <select class="form-select" id="modalidade_id" name="modalidade_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($modalidades as $mod): ?>
                                        <option value="<?php echo $mod['id']; ?>">
                                            <?php echo htmlspecialchars($mod['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control"
                                          id="descricao"
                                          name="descricao"
                                          rows="3"
                                          placeholder="Breve descrição da competição..."></textarea>
                            </div>

                            <div class="col-12">
                                <label for="banner" class="form-label">Banner da Competição (JPG, PNG - máx 5MB)</label>
                                <input type="file"
                                       class="form-control"
                                       id="banner"
                                       name="banner"
                                       accept="image/*"
                                       onchange="previewBanner(event)">
                                <div id="previewBanner" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Período -->
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-calendar text-primary"></i> Período
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="data_inicio_inscricao" class="form-label">Início das Inscrições *</label>
                                <input type="date"
                                       class="form-control"
                                       id="data_inicio_inscricao"
                                       name="data_inicio_inscricao"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label for="data_fim_inscricao" class="form-label">Fim das Inscrições *</label>
                                <input type="date"
                                       class="form-control"
                                       id="data_fim_inscricao"
                                       name="data_fim_inscricao"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label for="data_inicio_evento" class="form-label">Início do Evento *</label>
                                <input type="date"
                                       class="form-control"
                                       id="data_inicio_evento"
                                       name="data_inicio_evento"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label for="data_fim_evento" class="form-label">Fim do Evento *</label>
                                <input type="date"
                                       class="form-control"
                                       id="data_fim_evento"
                                       name="data_fim_evento"
                                       required>
                            </div>
                        </div>

                        <!-- Regras -->
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-rules text-primary"></i> Regras de Inscrição
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="genero_permitido" class="form-label">Gênero Permitido *</label>
                                <select class="form-select" id="genero_permitido" name="genero_permitido" required>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Feminino">Feminino</option>
                                    <option value="Ambos">Ambos</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="min_atletas" class="form-label">Mínimo de Atletas *</label>
                                <input type="number"
                                       class="form-control"
                                       id="min_atletas"
                                       name="min_atletas"
                                       value="1"
                                       min="1"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label for="max_atletas" class="form-label">Máximo de Atletas *</label>
                                <input type="number"
                                       class="form-control"
                                       id="max_atletas"
                                       name="max_atletas"
                                       value="20"
                                       min="1"
                                       required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Categorias Permitidas (deixe em branco para todas)</label>
                                <div class="row g-2">
                                    <?php foreach ($categorias as $cat): ?>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="categorias_permitidas[]"
                                                       value="<?php echo $cat['nome']; ?>"
                                                       id="cat<?php echo $cat['id']; ?>">
                                                <label class="form-check-label" for="cat<?php echo $cat['id']; ?>">
                                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                                    <small class="text-muted">(<?php echo $cat['idade_minima']; ?>-<?php echo $cat['idade_maxima']; ?> anos)</small>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="taxa_inscricao" class="form-label">Taxa de Inscrição (R$)</label>
                                <input type="number"
                                       class="form-control"
                                       id="taxa_inscricao"
                                       name="taxa_inscricao"
                                       step="0.01"
                                       value="0"
                                       min="0">
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Rascunho">Rascunho</option>
                                    <option value="Aberta" selected>Aberta</option>
                                    <option value="Fechada">Fechada</option>
                                </select>
                            </div>
                        </div>

                        <!-- Local -->
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-map-marker-alt text-primary"></i> Local do Evento
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="local_evento" class="form-label">Local</label>
                                <input type="text"
                                       class="form-control"
                                       id="local_evento"
                                       name="local_evento"
                                       placeholder="Ex: Ginásio Municipal">
                            </div>

                            <div class="col-md-4">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text"
                                       class="form-control"
                                       id="cidade"
                                       name="cidade"
                                       placeholder="Ex: Boa Vista">
                            </div>

                            <div class="col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <option value="RR">Roraima</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="AC">Acre</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="PA">Pará</option>
                                    <option value="AP">Amapá</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="regulamento" class="form-label">Regulamento</label>
                                <textarea class="form-control"
                                          id="regulamento"
                                          name="regulamento"
                                          rows="4"
                                          placeholder="Descreva as regras e regulamento da competição..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Criar Competição
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao(id, nome) {
            if (confirm(`Tem certeza que deseja excluir a competição "${nome}"?\n\nEsta ação não pode ser desfeita!`)) {
                window.location.href = `excluir_competicao.php?id=${id}`;
            }
        }
    </script>
    <script>
        function previewBanner(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('previewBanner');

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}"
                             class="img-thumbnail"
                             style="max-width: 300px; max-height: 200px; object-fit: cover;"
                             alt="Preview do banner">
                    `;
                };

                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Validação de datas
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');

            if (form) {
                form.addEventListener('submit', function(e) {
                    const dataInicioInscricoes = document.getElementById('data_inicio_inscricao').value;
                    const dataFimInscricoes = document.getElementById('data_fim_inscricao').value;
                    const dataInicioEvento = document.getElementById('data_inicio_evento').value;
                    const dataFimEvento = document.getElementById('data_fim_evento').value;

                    // Validar que fim das inscrições é depois do início
                    if (dataFimInscricoes < dataInicioInscricoes) {
                        e.preventDefault();
                        alert('A data de fim das inscrições deve ser posterior à data de início');
                        return false;
                    }

                    // Validar que evento começa depois das inscrições
                    if (dataInicioEvento < dataFimInscricoes) {
                        e.preventDefault();
                        alert('O evento deve começar após o fim das inscrições');
                        return false;
                    }

                    // Validar que fim do evento é depois do início
                    if (dataFimEvento < dataInicioEvento) {
                        e.preventDefault();
                        alert('A data de fim do evento deve ser posterior à data de início');
                        return false;
                    }

                    // Validar min/max atletas
                    const minAtletas = parseInt(document.getElementById('min_atletas').value);
                    const maxAtletas = parseInt(document.getElementById('max_atletas').value);

                    if (maxAtletas < minAtletas) {
                        e.preventDefault();
                        alert('O máximo de atletas deve ser maior ou igual ao mínimo');
                        return false;
                    }

                    return true;
                });
            }
        });
    </script>
</body>
</html>
