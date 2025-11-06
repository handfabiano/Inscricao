<?php
/**
 * Página de Inscrições em Competições
 * Equipe pode se inscrever em competições abertas
 */

require_once __DIR__ . '/../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];
$erro = null;
$competicoesAbertas = [];
$atletasDisponiveis = [];

try {
    // Buscar informações da equipe
    $stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
    $stmt->execute([$equipeId]);
    $equipe = $stmt->fetch();

    if (!$equipe) {
        throw new Exception('Equipe não encontrada');
    }

    // Buscar competições abertas (inscrições abertas)
    $hoje = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT
            c.*,
            m.nome as modalidade_nome,
            m.icone as modalidade_icone,
            (SELECT COUNT(*) FROM inscricoes_competicoes
             WHERE competicao_id = c.id AND equipe_id = ? AND status IN ('Pendente', 'Confirmada')) as ja_inscrito
        FROM competicoes c
        LEFT JOIN modalidades m ON c.modalidade_id = m.id
        WHERE c.status = 'Aberta'
        AND c.data_inicio_inscricoes <= ?
        AND c.data_fim_inscricoes >= ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$equipeId, $hoje, $hoje]);
    $competicoesAbertas = $stmt->fetchAll();

    // Buscar atletas ativos da equipe
    $stmt = $pdo->prepare("
        SELECT id, nome_completo, data_nascimento, genero, foto_path
        FROM atletas
        WHERE equipe_atual_id = ? AND ativo = 1
        ORDER BY nome_completo
    ");
    $stmt->execute([$equipeId]);
    $atletasDisponiveis = $stmt->fetchAll();

} catch (Exception $e) {
    $erro = $e->getMessage();
}

$pageTitle = 'Inscrições em Competições';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Competições</title>
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
                        <a class="nav-link active" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="minhas_inscricoes.php">
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

    <div class="container mt-4 mb-5">
        <!-- Mensagens -->
        <?php exibirMensagem(); ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php else: ?>
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-clipboard-list"></i> Competições Abertas</h2>
                    <p class="text-muted mb-0">Inscreva sua equipe nas competições disponíveis</p>
                </div>
            </div>

            <?php if (count($competicoesAbertas) === 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Não há competições com inscrições abertas no momento.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($competicoesAbertas as $comp):
                        $categorias = json_decode($comp['categorias_permitidas'], true);
                        $jaInscrito = $comp['ja_inscrito'] > 0;
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 <?php echo $jaInscrito ? 'border-success' : ''; ?> shadow-sm">
                                <?php if (!empty($comp['banner_path'])): ?>
                                    <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>"
                                         class="card-img-top"
                                         style="height: 180px; object-fit: cover;"
                                         alt="<?php echo htmlspecialchars($comp['nome']); ?>">
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">
                                            <?php if (!empty($comp['modalidade_icone'])): ?>
                                                <i class="fas <?php echo $comp['modalidade_icone']; ?> text-primary"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($comp['nome']); ?>
                                        </h5>
                                        <?php if ($jaInscrito): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Inscrito
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($comp['descricao'])): ?>
                                        <p class="card-text text-muted small">
                                            <?php echo nl2br(htmlspecialchars(substr($comp['descricao'], 0, 100))); ?>
                                            <?php echo strlen($comp['descricao']) > 100 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <h6 class="mb-2"><i class="fas fa-info-circle text-primary"></i> Requisitos:</h6>
                                        <ul class="list-unstyled small mb-0">
                                            <li class="mb-1">
                                                <i class="fas fa-calendar text-muted"></i>
                                                <strong>Inscrições até:</strong> <?php echo formatarData($comp['data_fim_inscricoes']); ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="fas fa-list text-muted"></i>
                                                <strong>Categorias:</strong> <?php echo !empty($categorias) ? implode(', ', $categorias) : 'Todas'; ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="fas fa-venus-mars text-muted"></i>
                                                <strong>Gênero:</strong> <?php echo $comp['genero_permitido'] ?: 'Todos'; ?>
                                            </li>
                                            <li class="mb-1">
                                                <i class="fas fa-users text-muted"></i>
                                                <strong>Atletas:</strong> <?php echo $comp['min_atletas']; ?> a <?php echo $comp['max_atletas']; ?> por equipe
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mt-auto">
                                        <?php if (!$jaInscrito): ?>
                                            <button type="button"
                                                    class="btn btn-primary w-100"
                                                    onclick="abrirModalInscricao(<?php echo $comp['id']; ?>, <?php echo htmlspecialchars(json_encode($comp['nome'])); ?>, <?php echo $comp['min_atletas']; ?>, <?php echo $comp['max_atletas']; ?>, <?php echo htmlspecialchars(json_encode($comp['genero_permitido'])); ?>, <?php echo htmlspecialchars(json_encode($categorias)); ?>)">
                                                <i class="fas fa-clipboard-check"></i> Inscrever Equipe
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-success w-100" disabled>
                                                <i class="fas fa-check-circle"></i> Equipe já inscrita
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Inscrição -->
    <div class="modal fade" id="modalInscricao" tabindex="-1" aria-labelledby="modalInscricaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInscricaoLabel">
                        <i class="fas fa-clipboard-check"></i> Inscrever em Competição
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formInscricao" method="POST" action="processar_inscricao.php">
                    <div class="modal-body">
                        <input type="hidden" name="competicao_id" id="competicao_id">

                        <div class="alert alert-info">
                            <h6 class="mb-2" id="nomeCompeticao"></h6>
                            <div id="requisitos" class="small"></div>
                        </div>

                        <h6 class="mb-3"><i class="fas fa-users"></i> Selecione os Atletas</h6>
                        <p class="text-muted small mb-3">
                            Escolha <span id="rangeAtletas" class="fw-bold"></span> atleta(s) para esta competição
                        </p>

                        <?php if (count($atletasDisponiveis) === 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Você não possui atletas cadastrados.
                                <a href="cadastrar_atleta.php" class="alert-link">Cadastrar atleta agora</a>
                            </div>
                        <?php else: ?>
                            <div id="listaAtletas" class="row">
                                <?php foreach ($atletasDisponiveis as $atleta):
                                    $idade = calcularIdade($atleta['data_nascimento']);
                                ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="card atleta-item h-100"
                                             data-genero="<?php echo htmlspecialchars($atleta['genero']); ?>"
                                             data-idade="<?php echo $idade; ?>">
                                            <div class="card-body p-2">
                                                <div class="form-check d-flex align-items-center">
                                                    <input class="form-check-input me-2 flex-shrink-0"
                                                           type="checkbox"
                                                           name="atletas[]"
                                                           value="<?php echo $atleta['id']; ?>"
                                                           id="atleta<?php echo $atleta['id']; ?>"
                                                           onchange="atualizarContador()">
                                                    <label class="form-check-label d-flex align-items-center w-100" for="atleta<?php echo $atleta['id']; ?>">
                                                        <?php if ($atleta['foto_path']): ?>
                                                            <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                                                 class="foto-circular me-2 flex-shrink-0"
                                                                 alt="<?php echo htmlspecialchars($atleta['nome_completo']); ?>">
                                                        <?php else: ?>
                                                            <div class="foto-circular me-2 flex-shrink-0 bg-secondary text-white d-flex align-items-center justify-content-center">
                                                                <?php echo strtoupper(substr($atleta['nome_completo'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold small"><?php echo htmlspecialchars($atleta['nome_completo']); ?></div>
                                                            <small class="text-muted">
                                                                <?php echo $idade; ?> anos • <?php echo htmlspecialchars($atleta['genero']); ?>
                                                            </small>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div id="contadorSelecionados" class="alert alert-secondary mt-3 mb-0">
                                <i class="fas fa-users"></i> <span id="numSelecionados">0</span> atleta(s) selecionado(s)
                            </div>

                            <div id="msgErro" class="alert alert-danger mt-3 mb-0 d-none"></div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <?php if (count($atletasDisponiveis) > 0): ?>
                            <button type="submit" class="btn btn-primary" id="btnConfirmarInscricao">
                                <i class="fas fa-check"></i> Confirmar Inscrição
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let minAtletas = 1;
        let maxAtletas = 99;
        let generoPermitido = '';
        let categoriasPermitidas = [];
        let modalInstance = null;

        function abrirModalInscricao(id, nome, min, max, genero, categorias) {
            minAtletas = min;
            maxAtletas = max;
            generoPermitido = genero || '';
            categoriasPermitidas = categorias || [];

            document.getElementById('competicao_id').value = id;
            document.getElementById('nomeCompeticao').textContent = nome;
            document.getElementById('rangeAtletas').textContent = min === max ? min : `${min} a ${max}`;

            // Montar requisitos
            let requisitos = '<ul class="mb-0 small">';
            requisitos += `<li><strong>Atletas necessários:</strong> ${min === max ? min : `${min} a ${max}`}</li>`;
            if (genero) {
                requisitos += `<li><strong>Gênero permitido:</strong> ${genero}</li>`;
            }
            if (categorias && categorias.length > 0) {
                requisitos += `<li><strong>Categorias:</strong> ${categorias.join(', ')}</li>`;
            }
            requisitos += '</ul>';
            document.getElementById('requisitos').innerHTML = requisitos;

            // Resetar seleções
            document.querySelectorAll('input[name="atletas[]"]').forEach(cb => {
                cb.checked = false;
                cb.disabled = false;
            });

            // Filtrar atletas por gênero
            filtrarAtletasPorGenero();

            // Resetar mensagens
            document.getElementById('msgErro').classList.add('d-none');
            atualizarContador();

            // Abrir modal
            const modalEl = document.getElementById('modalInscricao');
            modalInstance = new bootstrap.Modal(modalEl);
            modalInstance.show();
        }

        function filtrarAtletasPorGenero() {
            const atletasItems = document.querySelectorAll('.atleta-item');

            atletasItems.forEach(item => {
                const atletaGenero = item.dataset.genero;
                const checkbox = item.querySelector('input[type="checkbox"]');
                let mostrar = true;

                // Filtrar por gênero se especificado e diferente de "Ambos"
                if (generoPermitido && generoPermitido !== 'Ambos' && generoPermitido !== '') {
                    if (atletaGenero !== generoPermitido) {
                        mostrar = false;
                    }
                }

                if (mostrar) {
                    item.classList.remove('d-none');
                    item.style.opacity = '1';
                } else {
                    item.classList.add('d-none');
                    item.style.opacity = '0.5';
                    checkbox.disabled = true;
                    checkbox.checked = false;
                }
            });
        }

        function atualizarContador() {
            const selecionados = document.querySelectorAll('input[name="atletas[]"]:checked:not(:disabled)').length;
            document.getElementById('numSelecionados').textContent = selecionados;

            const contador = document.getElementById('contadorSelecionados');
            contador.classList.remove('alert-secondary', 'alert-warning', 'alert-success', 'alert-danger');

            if (selecionados === 0) {
                contador.classList.add('alert-secondary');
            } else if (selecionados < minAtletas) {
                contador.classList.add('alert-warning');
            } else if (selecionados >= minAtletas && selecionados <= maxAtletas) {
                contador.classList.add('alert-success');
            } else {
                contador.classList.add('alert-danger');
            }
        }

        // Validar seleção de atletas ao submeter
        document.getElementById('formInscricao').addEventListener('submit', function(e) {
            const selecionados = document.querySelectorAll('input[name="atletas[]"]:checked:not(:disabled)').length;
            const msgErro = document.getElementById('msgErro');

            if (selecionados < minAtletas) {
                e.preventDefault();
                msgErro.textContent = `Você precisa selecionar pelo menos ${minAtletas} atleta(s). Atualmente: ${selecionados}`;
                msgErro.classList.remove('d-none');
                msgErro.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return false;
            }

            if (selecionados > maxAtletas) {
                e.preventDefault();
                msgErro.textContent = `Você selecionou ${selecionados} atleta(s), mas o máximo permitido é ${maxAtletas}`;
                msgErro.classList.remove('d-none');
                msgErro.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return false;
            }

            msgErro.classList.add('d-none');
            return true;
        });
    </script>
</body>
</html>
