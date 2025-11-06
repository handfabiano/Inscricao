<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipe_id = $_SESSION['equipe_id'];

$atletas = $pdo->prepare("SELECT * FROM atletas WHERE equipe_atual_id = ? AND ativo = 1 ORDER BY nome_completo");
$atletas->execute([$equipe_id]);
$atletas = $atletas->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus Atletas</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($_SESSION['equipe_nome']); ?></h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="atletas.php" class="active">Atletas</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="competicoes.php">Competições</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Meus Atletas (<?php echo count($atletas); ?>)</h2>
            <a href="cadastrar_atleta.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Cadastrar Atleta
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cadastrado'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Atleta cadastrado com sucesso!</div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Idade</th>
                        <th>Gênero</th>
                        <th>Posição</th>
                        <th>N°</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atletas as $atleta): ?>
                    <tr>
                        <td>
                            <?php if ($atleta['foto_path']): ?>
                                <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                     style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($atleta['nome_completo']); ?></strong></td>
                        <td><?php echo formatCPF($atleta['cpf']); ?></td>
                        <td><?php echo calcularIdade($atleta['data_nascimento']); ?> anos</td>
                        <td><?php echo $atleta['genero']; ?></td>
                        <td><?php echo htmlspecialchars($atleta['posicao'] ?? '-'); ?></td>
                        <td><?php echo $atleta['numero_camisa'] ?? '-'; ?></td>
                        <td>
                            <a href="atleta_detalhes.php?id=<?php echo $atleta['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($atletas)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 60px;">
                            <i class="fas fa-user-plus" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                            <p>Nenhum atleta cadastrado</p>
                            <a href="cadastrar_atleta.php" class="btn btn-success" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Cadastrar Primeiro Atleta
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0</p>
        </div>
    </footer>
</body>
</html>
