<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();
$mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar_modalidade') {
        $nome = sanitize($_POST['nome']);
        $descricao = sanitize($_POST['descricao']);

        $stmt = $pdo->prepare("INSERT INTO modalidades (nome, descricao) VALUES (?, ?)");
        $stmt->execute([$nome, $descricao]);

        $mensagem = "Modalidade adicionada com sucesso!";
    }

    if ($acao === 'adicionar_categoria') {
        $nome = sanitize($_POST['nome']);
        $idade_min = (int)$_POST['idade_minima'];
        $idade_max = (int)$_POST['idade_maxima'];
        $descricao = sanitize($_POST['descricao']);

        $stmt = $pdo->prepare("INSERT INTO categorias (nome, idade_minima, idade_maxima, descricao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $idade_min, $idade_max, $descricao]);

        $mensagem = "Categoria adicionada com sucesso!";
    }

    if ($acao === 'desativar_modalidade') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE modalidades SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $mensagem = "Modalidade desativada!";
    }

    if ($acao === 'ativar_modalidade') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE modalidades SET ativo = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $mensagem = "Modalidade ativada!";
    }

    if ($acao === 'desativar_categoria') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $mensagem = "Categoria desativada!";
    }

    if ($acao === 'ativar_categoria') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $mensagem = "Categoria ativada!";
    }
}

// Buscar modalidades e categorias
$modalidades = $pdo->query("
    SELECT m.*, COUNT(i.id) as total_inscricoes
    FROM modalidades m
    LEFT JOIN inscricoes i ON m.id = i.modalidade_id
    GROUP BY m.id
    ORDER BY m.nome
")->fetchAll();

$categorias = $pdo->query("
    SELECT c.*, COUNT(i.id) as total_inscricoes
    FROM categorias c
    LEFT JOIN inscricoes i ON c.id = i.categoria_id
    GROUP BY c.id
    ORDER BY c.idade_minima
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Modalidades e Categorias</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="relatorios.php">Relatórios</a>
                <a href="modalidades.php" class="active">Modalidades</a>
                <a href="logs.php">Logs</a>
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

        <h2 style="margin-bottom: 30px;">Gestão de Modalidades e Categorias</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 30px;">
            <!-- MODALIDADES -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-medal"></i> Adicionar Nova Modalidade
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="adicionar_modalidade">

                        <div class="form-group">
                            <label for="nome_modalidade">Nome da Modalidade *</label>
                            <input type="text" id="nome_modalidade" name="nome" required>
                        </div>

                        <div class="form-group">
                            <label for="descricao_modalidade">Descrição</label>
                            <textarea id="descricao_modalidade" name="descricao" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Adicionar Modalidade
                        </button>
                    </form>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Modalidades Cadastradas
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Inscrições</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modalidades as $mod): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($mod['nome']); ?></strong>
                                    <?php if ($mod['descricao']): ?>
                                        <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($mod['descricao']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $mod['total_inscricoes']; ?></td>
                                <td>
                                    <?php if ($mod['ativo']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $mod['id']; ?>">
                                        <?php if ($mod['ativo']): ?>
                                            <button type="submit" name="acao" value="desativar_modalidade" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;" onclick="return confirm('Desativar esta modalidade?')">
                                                <i class="fas fa-times"></i> Desativar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="acao" value="ativar_modalidade" class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;">
                                                <i class="fas fa-check"></i> Ativar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CATEGORIAS -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-layer-group"></i> Adicionar Nova Categoria
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="adicionar_categoria">

                        <div class="form-group">
                            <label for="nome_categoria">Nome da Categoria *</label>
                            <input type="text" id="nome_categoria" name="nome" required placeholder="Ex: Sub-11">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="idade_minima">Idade Mínima *</label>
                                <input type="number" id="idade_minima" name="idade_minima" required min="5" max="100">
                            </div>

                            <div class="form-group">
                                <label for="idade_maxima">Idade Máxima *</label>
                                <input type="number" id="idade_maxima" name="idade_maxima" required min="5" max="100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descricao_categoria">Descrição</label>
                            <textarea id="descricao_categoria" name="descricao" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Adicionar Categoria
                        </button>
                    </form>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <i class="fas fa-list"></i> Categorias Cadastradas
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Faixa Etária</th>
                                <th>Inscrições</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cat['nome']); ?></strong>
                                    <?php if ($cat['descricao']): ?>
                                        <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($cat['descricao']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $cat['idade_minima']; ?>-<?php echo $cat['idade_maxima']; ?> anos</td>
                                <td><?php echo $cat['total_inscricoes']; ?></td>
                                <td>
                                    <?php if ($cat['ativo']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                        <?php if ($cat['ativo']): ?>
                                            <button type="submit" name="acao" value="desativar_categoria" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;" onclick="return confirm('Desativar esta categoria?')">
                                                <i class="fas fa-times"></i> Desativar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="acao" value="ativar_categoria" class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;">
                                                <i class="fas fa-check"></i> Ativar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas - Painel Administrativo</p>
        </div>
    </footer>
</body>
</html>
