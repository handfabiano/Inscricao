<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();

// Buscar informações da equipe
$stmt = $pdo->prepare("SELECT * FROM equipes WHERE id = ?");
$stmt->execute([$_SESSION['equipe_id']]);
$equipe = $stmt->fetch();

// Estatísticas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM atletas WHERE equipe_atual_id = ? AND ativo = 1");
$stmt->execute([$_SESSION['equipe_id']]);
$totalAtletas = $stmt->fetch()['total'];

// Buscar atletas recentes
$stmt = $pdo->prepare("
    SELECT * FROM atletas
    WHERE equipe_atual_id = ? AND ativo = 1
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['equipe_id']]);
$atletasRecentes = $stmt->fetchAll();

// Buscar competições abertas
$stmt = $pdo->query("
    SELECT c.*, m.nome as modalidade_nome
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE c.status = 'Aberta'
    ORDER BY c.data_fim_inscricao ASC
    LIMIT 5
");
$competicoesAbertas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Equipe - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <div class="header-content">
                <h1>👥 Sistema v3.0 - Equipe</h1>
                <nav>
                    <a href="index.php">Dashboard</a>
                    <a href="cadastrar_atleta.php">Cadastrar Atleta</a>
                    <a href="convites_atletas.php">Convidar Atletas</a>
                    <a href="atletas.php">Meus Atletas</a>
                    <a href="inscricoes.php">Inscrições</a>
                    <a href="minhas_inscricoes.php">Histórico</a>
                    <a href="logout.php">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 20px;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <h2>Bem-vindo, <?php echo htmlspecialchars($equipe['nome']); ?>!</h2>
            <p style="color: #64748b;">
                Responsável: <strong><?php echo htmlspecialchars($equipe['responsavel_nome']); ?></strong>
                <?php if ($equipe['status'] !== 'Aprovada'): ?>
                    <span class="badge badge-warning" style="margin-left: 1rem;">Status: <?php echo $equipe['status']; ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: #10b981;">
                <h3 style="color: #10b981;"><?php echo $totalAtletas; ?></h3>
                <p>Atletas Cadastrados</p>
            </div>

            <div class="stat-card" style="border-left-color: #3b82f6;">
                <h3 style="color: #3b82f6;">0</h3>
                <p>Inscrições em Competições</p>
            </div>

            <div class="stat-card" style="border-left-color: #f59e0b;">
                <h3 style="color: #f59e0b;"><?php echo count($competicoesAbertas); ?></h3>
                <p>Competições Abertas</p>
            </div>

            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <h3 style="color: #8b5cf6;">-</h3>
                <p>Última Atividade</p>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Atletas Recentes -->
            <div class="card">
                <div class="card-header">
                    Atletas Recentes
                </div>

                <?php if (empty($atletasRecentes)): ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">
                        <p>Nenhum atleta cadastrado.</p>
                        <a href="cadastrar_atleta.php" class="btn btn-success" style="margin-top: 1rem;">Cadastrar Primeiro Atleta</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Gênero</th>
                                <th>Cadastrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atletasRecentes as $atleta): ?>
                                <tr>
                                    <td>
                                        <?php if ($atleta['foto_path']): ?>
                                            <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                                 class="foto-circular" alt="Foto">
                                        <?php else: ?>
                                            <div class="foto-circular" style="background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                                ?
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($atleta['nome_completo']); ?></strong></td>
                                    <td><?php echo $atleta['genero']; ?></td>
                                    <td><?php echo formatarData($atleta['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="text-align: center; padding: 1rem;">
                        <a href="atletas.php" class="btn btn-primary">Ver Todos os Atletas</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Competições Abertas -->
            <div class="card">
                <div class="card-header">
                    Competições Abertas
                </div>

                <?php if (empty($competicoesAbertas)): ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">
                        <p>Nenhuma competição aberta no momento.</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 1rem;">
                        <?php foreach ($competicoesAbertas as $comp): ?>
                            <div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem;">
                                <div style="display: flex; gap: 1rem; align-items: start;">
                                    <?php if ($comp['banner_path']): ?>
                                        <img src="<?php echo BANNER_URL . $comp['banner_path']; ?>"
                                             alt="Banner"
                                             style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px;">
                                    <?php endif; ?>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 0.5rem;"><?php echo htmlspecialchars($comp['nome']); ?></h4>
                                        <p style="margin: 0; font-size: 0.9rem; color: #64748b;">
                                            <strong><?php echo htmlspecialchars($comp['modalidade_nome']); ?></strong><br>
                                            Inscrições até: <?php echo formatarData($comp['data_fim_inscricao']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="cadastrar_atleta.php" class="btn btn-success">+ Cadastrar Novo Atleta</a>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.9rem;">
        <p>&copy; 2025 Sistema de Gestão de Competições Esportivas v3.0</p>
    </footer>
</body>
</html>
