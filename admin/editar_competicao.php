<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    redirect('competicoes.php', 'ID inválido', 'error');
}

// Buscar competição
$stmt = $pdo->prepare("SELECT * FROM competicoes WHERE id = ?");
$stmt->execute([$id]);
$comp = $stmt->fetch();

if (!$comp) {
    redirect('competicoes.php', 'Competição não encontrada', 'error');
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $bannerFileName = $comp['banner_path'];
        
        if (!empty($_FILES['banner']['name'])) {
            // Excluir banner antigo
            if (!empty($bannerFileName) && file_exists(BANNER_PATH . $bannerFileName)) {
                unlink(BANNER_PATH . $bannerFileName);
            }
            
            $bannerFileName = uploadFile($_FILES['banner'], 'banner', BANNER_PATH, ALLOWED_IMAGE_EXTENSIONS, MAX_FILE_SIZE);
        }
        
        $categorias = isset($_POST['categorias_permitidas']) ? $_POST['categorias_permitidas'] : [];
        
        $stmt = $pdo->prepare("
            UPDATE competicoes SET
                nome = ?, descricao = ?, banner_path = ?,
                data_inicio_inscricao = ?, data_fim_inscricao = ?,
                data_inicio_evento = ?, data_fim_evento = ?,
                modalidade_id = ?, categorias_permitidas = ?, genero_permitido = ?,
                min_atletas = ?, max_atletas = ?, taxa_inscricao = ?,
                local_evento = ?, cidade = ?, estado = ?,
                regulamento = ?, status = ?, updated_at = NOW()
            WHERE id = ?
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
            json_encode($categorias),
            $_POST['genero_permitido'],
            (int)$_POST['min_atletas'],
            (int)$_POST['max_atletas'],
            floatval($_POST['taxa_inscricao'] ?? 0),
            sanitize($_POST['local_evento'] ?? ''),
            sanitize($_POST['cidade'] ?? ''),
            sanitize($_POST['estado'] ?? ''),
            sanitize($_POST['regulamento'] ?? ''),
            $_POST['status'],
            $id
        ]);
        
        redirect('competicoes.php', 'Competição atualizada com sucesso!', 'success');
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$modalidades = $pdo->query("SELECT * FROM modalidades WHERE ativo = 1")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1")->fetchAll();
$categoriasComp = json_decode($comp['categorias_permitidas'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Competição</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="competicoes.php">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-edit"></i> Editar Competição</h2>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($comp['nome']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Modalidade *</label>
                    <select class="form-select" name="modalidade_id" required>
                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?php echo $mod['id']; ?>" <?php echo $mod['id'] == $comp['modalidade_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mod['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-control" name="descricao" rows="3"><?php echo htmlspecialchars($comp['descricao'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Banner</label>
                    <?php if ($comp['banner_path']): ?>
                        <div class="mb-2">
                            <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="banner" accept="image/*">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Início Inscrições *</label>
                    <input type="date" class="form-control" name="data_inicio_inscricao" value="<?php echo $comp['data_inicio_inscricao']; ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fim Inscrições *</label>
                    <input type="date" class="form-control" name="data_fim_inscricao" value="<?php echo $comp['data_fim_inscricao']; ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Início Evento *</label>
                    <input type="date" class="form-control" name="data_inicio_evento" value="<?php echo $comp['data_inicio_evento']; ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fim Evento *</label>
                    <input type="date" class="form-control" name="data_fim_evento" value="<?php echo $comp['data_fim_evento']; ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Gênero *</label>
                    <select class="form-select" name="genero_permitido" required>
                        <option value="Masculino" <?php echo $comp['genero_permitido'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="Feminino" <?php echo $comp['genero_permitido'] == 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="Ambos" <?php echo $comp['genero_permitido'] == 'Ambos' ? 'selected' : ''; ?>>Ambos</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Min Atletas *</label>
                    <input type="number" class="form-control" name="min_atletas" value="<?php echo $comp['min_atletas']; ?>" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Max Atletas *</label>
                    <input type="number" class="form-control" name="max_atletas" value="<?php echo $comp['max_atletas']; ?>" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Taxa (R$)</label>
                    <input type="number" class="form-control" name="taxa_inscricao" step="0.01" value="<?php echo $comp['taxa_inscricao']; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="Rascunho" <?php echo $comp['status'] == 'Rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="Aberta" <?php echo $comp['status'] == 'Aberta' ? 'selected' : ''; ?>>Aberta</option>
                        <option value="Fechada" <?php echo $comp['status'] == 'Fechada' ? 'selected' : ''; ?>>Fechada</option>
                        <option value="Encerrada" <?php echo $comp['status'] == 'Encerrada' ? 'selected' : ''; ?>>Encerrada</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Categorias</label>
                    <div class="row">
                        <?php foreach ($categorias as $cat): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categorias_permitidas[]" 
                                           value="<?php echo $cat['nome']; ?>" 
                                           id="cat<?php echo $cat['id']; ?>"
                                           <?php echo in_array($cat['nome'], $categoriasComp) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Local</label>
                    <input type="text" class="form-control" name="local_evento" value="<?php echo htmlspecialchars($comp['local_evento'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Cidade</label>
                    <input type="text" class="form-control" name="cidade" value="<?php echo htmlspecialchars($comp['cidade'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Selecione...</option>
                        <option value="RR" <?php echo $comp['estado'] == 'RR' ? 'selected' : ''; ?>>Roraima</option>
                        <option value="AM" <?php echo $comp['estado'] == 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Regulamento</label>
                    <textarea class="form-control" name="regulamento" rows="4"><?php echo htmlspecialchars($comp['regulamento'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="competicoes.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
