<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar_competicao') {
        try {
            // Validar dados básicos
            $nome = sanitize($_POST['nome']);
            $descricao = sanitize($_POST['descricao']);
            $modalidade_id = (int)$_POST['modalidade_id'];

            // Datas
            $data_inicio_inscricoes = $_POST['data_inicio_inscricoes'];
            $data_fim_inscricoes = $_POST['data_fim_inscricoes'];
            $data_inicio_evento = $_POST['data_inicio_evento'];
            $data_fim_evento = $_POST['data_fim_evento'];

            // Regras
            $categorias = $_POST['categorias'] ?? [];
            $genero = $_POST['genero_permitido'];
            $min_atletas = (int)$_POST['min_atletas'];
            $max_atletas = (int)$_POST['max_atletas'];
            $taxa_inscricao = (float)$_POST['taxa_inscricao'];

            // Upload de banner
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

            // Inserir competição
            $sql = "INSERT INTO competicoes (
                nome, descricao, banner_path,
                data_inicio_inscricoes, data_fim_inscricoes,
                data_inicio_evento, data_fim_evento,
                modalidade_id, categorias_permitidas, genero_permitido,
                min_atletas, max_atletas, taxa_inscricao,
                local_evento, cidade, estado, regulamento,
                status, criado_por
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nome, $descricao, $bannerFileName,
                $data_inicio_inscricoes, $data_fim_inscricoes,
                $data_inicio_evento, $data_fim_evento,
                $modalidade_id, json_encode($categorias), $genero,
                $min_atletas, $max_atletas, $taxa_inscricao,
                sanitize($_POST['local_evento'] ?? ''),
                sanitize($_POST['cidade'] ?? ''),
                $_POST['estado'] ?? '',
                sanitize($_POST['regulamento'] ?? ''),
                'Rascunho',
                $_SESSION['admin_id']
            ]);

            $mensagem = "Competição criada com sucesso!";

        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }

    if ($acao === 'alterar_status') {
        $id = (int)$_POST['id'];
        $novoStatus = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE competicoes SET status = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $id]);

        $mensagem = "Status alterado para: $novoStatus";
    }
}

// Buscar competições
$competicoes = $pdo->query("
    SELECT c.*, m.nome as modalidade_nome,
           (SELECT COUNT(*) FROM inscricoes_competicoes WHERE competicao_id = c.id) as total_inscricoes
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    ORDER BY c.created_at DESC
")->fetchAll();

// Buscar modalidades para o form
$modalidades = $pdo->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY idade_minima")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Competições</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-trophy"></i> Sistema v3.0 - Competições</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="competicoes.php" class="active">Competições</a>
                <a href="equipes.php">Equipes</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($mensagem): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Gestão de Competições</h2>
            <button onclick="document.getElementById('modalNovaCompeticao').style.display='block'" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Competição
            </button>
        </div>

        <!-- Lista de Competições -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Competições Cadastradas
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Banner</th>
                        <th>Nome</th>
                        <th>Modalidade</th>
                        <th>Período Inscrições</th>
                        <th>Inscrições</th>
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
                                     style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 50px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                    <i class="fas fa-image" style="color: #999;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($comp['nome']); ?></strong>
                            <br><small style="color: var(--text-light);">
                                <?php echo formatarData($comp['data_inicio_evento']); ?> a
                                <?php echo formatarData($comp['data_fim_evento']); ?>
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($comp['modalidade_nome']); ?></td>
                        <td>
                            <?php echo formatarData($comp['data_inicio_inscricoes']); ?><br>
                            <small>até <?php echo formatarData($comp['data_fim_inscricoes']); ?></small>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-info"><?php echo $comp['total_inscricoes']; ?></span>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'Rascunho' => 'badge-info',
                                'Aberta' => 'badge-success',
                                'Fechada' => 'badge-warning',
                                'Em Andamento' => 'badge-warning',
                                'Encerrada' => 'badge-danger'
                            ];
                            $color = $statusColors[$comp['status']] ?? 'badge-info';
                            ?>
                            <span class="badge <?php echo $color; ?>"><?php echo $comp['status']; ?></span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="competicao_detalhes.php?id=<?php echo $comp['id']; ?>"
                                   class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="alterar_status">
                                    <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 6px; font-size: 0.85rem;">
                                        <option value="">Alterar</option>
                                        <option value="Aberta">Abrir</option>
                                        <option value="Fechada">Fechar</option>
                                        <option value="Em Andamento">Iniciar</option>
                                        <option value="Encerrada">Encerrar</option>
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Nova Competição -->
    <div id="modalNovaCompeticao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="background: white; max-width: 900px; margin: 50px auto; border-radius: 12px; padding: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2><i class="fas fa-trophy"></i> Nova Competição</h2>
                <button onclick="document.getElementById('modalNovaCompeticao').style.display='none'"
                        style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="criar_competicao">

                <div class="form-section">
                    <h3>Informações Básicas</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label>Nome da Competição *</label>
                            <input type="text" name="nome" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Banner da Competição (JPG/PNG, máx 5MB)</label>
                        <input type="file" name="banner" accept=".jpg,.jpeg,.png">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Períodos</h3>
                    <div class="form-row">
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
                </div>

                <div class="form-section">
                    <h3>Configurações Esportivas</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Modalidade *</label>
                            <select name="modalidade_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($modalidades as $mod): ?>
                                    <option value="<?php echo $mod['id']; ?>">
                                        <?php echo htmlspecialchars($mod['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Gênero Permitido *</label>
                            <select name="genero_permitido" required>
                                <option value="Masculino">Masculino</option>
                                <option value="Feminino">Feminino</option>
                                <option value="Misto">Misto</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Categorias Permitidas (selecione múltiplas)</label>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <?php foreach ($categorias as $cat): ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="categorias[]" value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                    (<?php echo $cat['idade_minima']; ?>-<?php echo $cat['idade_maxima']; ?> anos)
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Regras de Equipe</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mínimo de Atletas *</label>
                            <input type="number" name="min_atletas" min="1" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Máximo de Atletas *</label>
                            <input type="number" name="max_atletas" min="1" value="20" required>
                        </div>
                        <div class="form-group">
                            <label>Taxa de Inscrição (R$)</label>
                            <input type="number" name="taxa_inscricao" step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Local do Evento</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label>Nome do Local</label>
                            <input type="text" name="local_evento">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade">
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado">
                                <option value="">Selecione</option>
                                <option value="RR">Roraima</option>
                                <!-- Outros estados... -->
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Regulamento</h3>
                    <div class="form-group">
                        <textarea name="regulamento" rows="5" placeholder="Descreva o regulamento da competição..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modalNovaCompeticao').style.display='none'" class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Competição
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0 - Gestão de Competições Esportivas</p>
        </div>
    </footer>
</body>
</html>
