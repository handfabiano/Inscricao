<?php
/**
 * Gerador de Chaveamento Automático
 *
 * Interface para gerar chaveamentos automáticos de competições
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes_auxiliares.php';
require_once __DIR__ . '/../includes/championship_helper.php';

requireAdminLogin();

$pdo = getDBConnection();
$competicao_id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$competicao_id) {
    header('Location: competicoes.php');
    exit;
}

// Buscar competição
$stmt = $pdo->prepare("SELECT * FROM competicoes WHERE id = ?");
$stmt->execute([$competicao_id]);
$competicao = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar equipes inscritas
$stmt = $pdo->prepare("
    SELECT e.*, ic.id as inscricao_id
    FROM equipes e
    INNER JOIN inscricoes_competicoes ic ON e.id = ic.equipe_id
    WHERE ic.competicao_id = ? AND ic.status = 'Confirmada'
    ORDER BY e.nome
");
$stmt->execute([$competicao_id]);
$equipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar geração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formato = $_POST['formato'];
    $equipes_ids = array_column($equipes, 'id');

    $opcoes = [
        'data_inicio' => $_POST['data_inicio'],
        'intervalo_dias' => (int)$_POST['intervalo_dias'],
        'sortear' => isset($_POST['sortear'])
    ];

    switch ($formato) {
        case 'eliminatoria_simples':
            $resultado = gerarChaveamentoEliminatoriaSimples($competicao_id, $equipes_ids, $opcoes);
            break;

        case 'pontos_corridos':
            $opcoes['ida_volta'] = isset($_POST['ida_volta']);
            $resultado = gerarChaveamentoPontosCorridos($competicao_id, $equipes_ids, $opcoes);
            break;

        case 'grupos':
            $opcoes['num_grupos'] = (int)$_POST['num_grupos'];
            $opcoes['classificados_por_grupo'] = (int)$_POST['classificados_por_grupo'];
            $resultado = gerarChaveamentoGrupos($competicao_id, $equipes_ids, $opcoes);
            break;
    }

    if ($resultado['success']) {
        $mensagem = $resultado['message'];
        $tipo_mensagem = 'success';
    } else {
        $mensagem = $resultado['message'];
        $tipo_mensagem = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Chaveamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-project-diagram"></i> Gerar Chaveamento</h2>
                        <p class="text-muted"><?= htmlspecialchars($competicao['nome']) ?></p>
                    </div>
                    <a href="competicoes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>

                <!-- Mensagens -->
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?= $tipo_mensagem === 'error' ? 'danger' : $tipo_mensagem ?> alert-dismissible fade show">
                        <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Equipes Inscritas -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Equipes Inscritas (<?= count($equipes) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($equipes as $equipe): ?>
                                <div class="col-md-4 mb-2">
                                    <i class="fas fa-check-circle text-success"></i> <?= htmlspecialchars($equipe['nome']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configurar Chaveamento</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Formato -->
                            <div class="mb-4">
                                <label class="form-label"><strong>Formato da Competição</strong></label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card h-100 formato-card" onclick="selecionarFormato('eliminatoria_simples')">
                                            <div class="card-body text-center">
                                                <input type="radio" name="formato" value="eliminatoria_simples" id="fmt1" required>
                                                <label for="fmt1" class="w-100">
                                                    <i class="fas fa-trophy fa-3x text-warning mb-2"></i>
                                                    <h6>Eliminatória Simples</h6>
                                                    <small class="text-muted">Mata-mata em jogo único</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100 formato-card" onclick="selecionarFormato('pontos_corridos')">
                                            <div class="card-body text-center">
                                                <input type="radio" name="formato" value="pontos_corridos" id="fmt2">
                                                <label for="fmt2" class="w-100">
                                                    <i class="fas fa-list-ol fa-3x text-success mb-2"></i>
                                                    <h6>Pontos Corridos</h6>
                                                    <small class="text-muted">Todos contra todos</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100 formato-card" onclick="selecionarFormato('grupos')">
                                            <div class="card-body text-center">
                                                <input type="radio" name="formato" value="grupos" id="fmt3">
                                                <label for="fmt3" class="w-100">
                                                    <i class="fas fa-layer-group fa-3x text-primary mb-2"></i>
                                                    <h6>Grupos + Mata-mata</h6>
                                                    <small class="text-muted">Fase de grupos + eliminatórias</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações Comuns -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Data de Início dos Jogos</label>
                                    <input type="date" name="data_inicio" class="form-control" required
                                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Intervalo entre Jogos (dias)</label>
                                    <input type="number" name="intervalo_dias" class="form-control" value="7" min="1" required>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="sortear" id="sortear" checked>
                                <label class="form-check-label" for="sortear">
                                    Sortear equipes aleatoriamente
                                </label>
                            </div>

                            <!-- Configurações Específicas -->
                            <div id="config-pontos-corridos" style="display:none;">
                                <hr>
                                <h6>Configurações de Pontos Corridos</h6>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="ida_volta" id="ida_volta" checked>
                                    <label class="form-check-label" for="ida_volta">
                                        Jogos de ida e volta
                                    </label>
                                </div>
                            </div>

                            <div id="config-grupos" style="display:none;">
                                <hr>
                                <h6>Configurações de Grupos</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Número de Grupos</label>
                                        <select name="num_grupos" class="form-select">
                                            <option value="2">2 grupos</option>
                                            <option value="4" selected>4 grupos</option>
                                            <option value="6">6 grupos</option>
                                            <option value="8">8 grupos</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Classificados por Grupo</label>
                                        <select name="classificados_por_grupo" class="form-select">
                                            <option value="1">1 classificado</option>
                                            <option value="2" selected>2 classificados</option>
                                            <option value="3">3 classificados</option>
                                            <option value="4">4 classificados</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-magic"></i> Gerar Chaveamento Automaticamente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Informações -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informações</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li><strong>Eliminatória Simples:</strong> Ideal para copas e torneios rápidos. Jogo único, quem perde está eliminado.</li>
                            <li><strong>Pontos Corridos:</strong> Todos jogam contra todos. Vence quem tiver mais pontos ao final. Pode ser turno único ou ida e volta.</li>
                            <li><strong>Grupos + Mata-mata:</strong> Combina fase de grupos (pontos corridos) com fase eliminatória. Formato usado em Copa do Mundo.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selecionarFormato(formato) {
            // Ocultar todas as configs
            document.getElementById('config-pontos-corridos').style.display = 'none';
            document.getElementById('config-grupos').style.display = 'none';

            // Mostrar config específica
            if (formato === 'pontos_corridos') {
                document.getElementById('config-pontos-corridos').style.display = 'block';
            } else if (formato === 'grupos') {
                document.getElementById('config-grupos').style.display = 'block';
            }

            // Selecionar radio
            document.querySelector(`input[value="${formato}"]`).checked = true;
        }

        // Adicionar estilo hover aos cards
        document.querySelectorAll('.formato-card').forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('mouseenter', () => card.classList.add('border-primary'));
            card.addEventListener('mouseleave', () => {
                if (!card.querySelector('input').checked) {
                    card.classList.remove('border-primary');
                }
            });
        });

        // Mostrar config ao carregar se houver formato selecionado
        document.querySelectorAll('input[name="formato"]').forEach(input => {
            input.addEventListener('change', () => selecionarFormato(input.value));
        });
    </script>
</body>
</html>
