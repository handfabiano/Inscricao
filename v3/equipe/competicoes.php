<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();

// Competições abertas
$competicoes = $pdo->query("
    SELECT c.*, m.nome as modalidade_nome,
           (SELECT COUNT(*) FROM inscricoes_competicoes WHERE competicao_id = c.id) as total_inscritos
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE c.status = 'Aberta'
      AND c.data_fim_inscricoes >= CURDATE()
    ORDER BY c.data_inicio_inscricoes
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Competições Abertas</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($_SESSION['equipe_nome']); ?></h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="atletas.php">Atletas</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="competicoes.php" class="active">Competições</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Competições Abertas (<?php echo count($competicoes); ?>)</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; margin-top: 30px;">
            <?php foreach ($competicoes as $comp): ?>
            <div class="card">
                <?php if ($comp['banner_path']): ?>
                    <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>"
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px 12px 0 0;">
                <?php endif; ?>

                <div style="padding: 20px;">
                    <h3 style="margin: 0 0 15px 0;"><?php echo htmlspecialchars($comp['nome']); ?></h3>

                    <div style="margin-bottom: 15px;">
                        <p style="margin: 5px 0;"><i class="fas fa-medal"></i> <?php echo htmlspecialchars($comp['modalidade_nome']); ?></p>
                        <p style="margin: 5px 0;"><i class="fas fa-calendar"></i> <?php echo formatarData($comp['data_inicio_evento']); ?> a <?php echo formatarData($comp['data_fim_evento']); ?></p>
                        <p style="margin: 5px 0;"><i class="fas fa-users"></i> Min: <?php echo $comp['min_atletas']; ?> | Máx: <?php echo $comp['max_atletas']; ?> atletas</p>
                        <p style="margin: 5px 0;"><i class="fas fa-money-bill"></i> <?php echo formatarMoeda($comp['taxa_inscricao']); ?></p>
                    </div>

                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                        <strong style="font-size: 0.9rem;">Inscrições até:</strong>
                        <br><?php echo formatarData($comp['data_fim_inscricoes']); ?>
                    </div>

                    <a href="inscrever.php?competicao_id=<?php echo $comp['id']; ?>" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-clipboard-check"></i> Inscrever Minha Equipe
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($competicoes)): ?>
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 60px;">
                <i class="fas fa-trophy" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                <h3>Nenhuma competição aberta no momento</h3>
                <p style="color: var(--text-light);">Volte mais tarde para ver novas competições</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0</p>
        </div>
    </footer>
</body>
</html>
