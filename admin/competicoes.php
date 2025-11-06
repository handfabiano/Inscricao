<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$mensagem = '';
$mensagemTipo = '';

// Processar criação de competição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['nome', 'data_inicio_inscricoes', 'data_fim_inscricoes', 'data_inicio_evento', 'data_fim_evento', 'modalidade_id', 'genero_permitido', 'min_atletas', 'max_atletas'];

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
                data_inicio_inscricoes, data_fim_inscricoes,
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
            $_POST['data_inicio_inscricoes'],
            $_POST['data_fim_inscricoes'],
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

        $mensagem = 'Competição criada com sucesso!';
        $mensagemTipo = 'success';

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $mensagemTipo = 'error';
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Competições - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1>🏆 Sistema v3.0 - Admin</h1>
                <nav>
                    <a href="index.php">Dashboard</a>
                    <a href="competicoes.php">Competições</a>
                    <a href="logout.php">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 20px;">
        <div class="d-flex justify-between align-center mb-3">
            <h2>Gerenciar Competições</h2>
            <button class="btn btn-primary" onclick="abrirModal()">
                + Nova Competição
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $mensagemTipo; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (empty($competicoes)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">Nenhuma competição cadastrada</p>
                    <button class="btn btn-primary" onclick="abrirModal()">Criar Primeira Competição</button>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">Banner</th>
                            <th>Nome</th>
                            <th>Modalidade</th>
                            <th>Período Inscrições</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competicoes as $comp): ?>
                            <tr>
                                <td>
                                    <?php if ($comp['banner_path']): ?>
                                        <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>"
                                             alt="Banner"
                                             style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 40px; background: #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #64748b;">
                                            Sem banner
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($comp['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($comp['modalidade_nome']); ?></td>
                                <td style="font-size: 0.9rem;">
                                    <?php echo formatarData($comp['data_inicio_inscricoes']); ?> até
                                    <?php echo formatarData($comp['data_fim_inscricoes']); ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = [
                                        'Rascunho' => 'badge-warning',
                                        'Aberta' => 'badge-success',
                                        'Fechada' => 'badge-info',
                                        'Em Andamento' => 'badge-info',
                                        'Encerrada' => 'badge-danger',
                                        'Cancelada' => 'badge-danger'
                                    ];
                                    $class = $badgeClass[$comp['status']] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo $comp['status']; ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">Ver</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Criar Competição -->
    <div id="modalCompeticao" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Nova Competição</h2>
                <button class="close" onclick="fecharModal()">&times;</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="criar">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Nome da Competição *</label>
                        <input type="text" name="nome" required placeholder="Ex: Copa Roraima de Futsal 2025">
                    </div>

                    <div class="form-group">
                        <label>Modalidade *</label>
                        <select name="modalidade_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($modalidades as $mod): ?>
                                <option value="<?php echo $mod['id']; ?>"><?php echo htmlspecialchars($mod['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="3" placeholder="Breve descrição da competição..."></textarea>
                </div>

                <div class="form-group">
                    <label>Banner da Competição (JPG, PNG - máx 5MB)</label>
                    <input type="file" name="banner" accept="image/*" onchange="previewBanner(event)">
                    <div id="previewBanner" class="image-preview"></div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Período</h3>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Início das Inscrições *</label>
                        <input type="date" name="data_inicio_inscricoes" required>
                    </div>

                    <div class="form-group">
                        <label>Fim das Inscrições *</label>
                        <input type="date" name="data_fim_inscricoes" required>
                    </div>

                    <div class="form-group">
                        <label>Início do Evento *</label>
                        <input type="date" name="data_inicio_evento" required>
                    </div>

                    <div class="form-group">
                        <label>Fim do Evento *</label>
                        <input type="date" name="data_fim_evento" required>
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Regras</h3>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Gênero *</label>
                        <select name="genero_permitido" required>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Misto">Misto</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mínimo de Atletas *</label>
                        <input type="number" name="min_atletas" value="1" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Máximo de Atletas *</label>
                        <input type="number" name="max_atletas" value="20" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Categorias Permitidas</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;">
                        <?php foreach ($categorias as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                                <input type="checkbox" name="categorias_permitidas[]" value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small>Deixe em branco para permitir todas</small>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Taxa de Inscrição (R$)</label>
                        <input type="number" name="taxa_inscricao" step="0.01" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="Rascunho">Rascunho</option>
                            <option value="Aberta">Aberta</option>
                            <option value="Fechada">Fechada</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Local</h3>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Local do Evento</label>
                        <input type="text" name="local_evento" placeholder="Ex: Ginásio Municipal">
                    </div>

                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" placeholder="Ex: Boa Vista">
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="">Selecione...</option>
                            <option value="RR">Roraima</option>
                            <option value="AM">Amazonas</option>
                            <option value="AC">Acre</option>
                            <option value="RO">Rondônia</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Regulamento</label>
                    <textarea name="regulamento" rows="4" placeholder="Descreva as regras da competição..."></textarea>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Competição</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalCompeticao').classList.add('show');
        }

        function fecharModal() {
            document.getElementById('modalCompeticao').classList.remove('show');
        }

        function previewBanner(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('previewBanner');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    preview.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.remove('show');
                preview.innerHTML = '';
            }
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalCompeticao');
            if (event.target === modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
