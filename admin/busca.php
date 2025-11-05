<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();
$resultados = [];
$totalResultados = 0;
$termoBusca = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['q'])) {
    $termoBusca = sanitize($_POST['busca'] ?? $_GET['q'] ?? '');

    if (!empty($termoBusca)) {
        $sql = "SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
                FROM inscricoes i
                LEFT JOIN modalidades m ON i.modalidade_id = m.id
                LEFT JOIN categorias c ON i.categoria_id = c.id
                WHERE (
                    i.nome_completo LIKE ? OR
                    i.cpf LIKE ? OR
                    i.email LIKE ? OR
                    i.protocolo LIKE ? OR
                    i.telefone LIKE ? OR
                    i.responsavel_nome LIKE ? OR
                    i.cidade LIKE ? OR
                    m.nome LIKE ? OR
                    c.nome LIKE ?
                )
                ORDER BY i.created_at DESC
                LIMIT 100";

        $searchTerm = "%$termoBusca%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
            $searchTerm, $searchTerm, $searchTerm, $searchTerm
        ]);

        $resultados = $stmt->fetchAll();
        $totalResultados = count($resultados);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca Avançada</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-box {
            max-width: 800px;
            margin: 0 auto 40px;
        }

        .search-box input {
            font-size: 1.2rem;
            padding: 20px;
        }

        .highlight {
            background-color: yellow;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt"></i> Painel Administrativo</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="busca.php" class="active">Busca Avançada</a>
                <a href="relatorios.php">Relatórios</a>
                <a href="perfil.php">Perfil</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2 style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-search"></i> Busca Avançada
        </h2>

        <div class="search-box">
            <form method="POST" action="">
                <div class="form-group">
                    <input
                        type="text"
                        name="busca"
                        placeholder="Digite nome, CPF, email, protocolo, telefone, cidade..."
                        value="<?php echo htmlspecialchars($termoBusca); ?>"
                        autofocus
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>

            <?php if ($termoBusca): ?>
                <div style="margin-top: 20px; text-align: center; color: var(--text-light);">
                    <strong><?php echo $totalResultados; ?></strong> resultado(s) encontrado(s) para
                    "<strong><?php echo htmlspecialchars($termoBusca); ?></strong>"
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($resultados)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Resultados da Busca
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Protocolo</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Modalidade</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados as $insc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($insc['protocolo']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($insc['nome_completo']); ?></strong>
                                        <?php if ($insc['cidade']): ?>
                                            <br><small style="color: var(--text-light);">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($insc['cidade']); ?> - <?php echo $insc['estado']; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatCPF($insc['cpf']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['email']); ?></td>
                                    <td><?php echo formatPhone($insc['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['modalidade_nome']); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'Pendente' => 'badge-warning',
                                            'Aprovada' => 'badge-success',
                                            'Rejeitada' => 'badge-danger',
                                            'Cancelada' => 'badge-info'
                                        ];
                                        $class = $statusClass[$insc['status']] ?? 'badge-info';
                                        ?>
                                        <span class="badge <?php echo $class; ?>"><?php echo $insc['status']; ?></span>
                                    </td>
                                    <td>
                                        <a href="visualizar.php?id=<?php echo $insc['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalResultados >= 100): ?>
                    <div class="alert alert-warning" style="margin-top: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Mostrando apenas os primeiros 100 resultados. Refine sua busca para resultados mais precisos.
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($termoBusca): ?>
            <div class="card">
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--text-light); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-dark);">Nenhum resultado encontrado</h3>
                    <p style="color: var(--text-light);">
                        Não encontramos nenhuma inscrição correspondente à sua busca.
                    </p>
                    <p style="color: var(--text-light); margin-top: 20px;">
                        <strong>Dicas:</strong>
                    </p>
                    <ul style="color: var(--text-light); text-align: left; display: inline-block; margin-top: 10px;">
                        <li>Verifique se digitou corretamente</li>
                        <li>Tente usar menos palavras</li>
                        <li>Use apenas números do CPF (sem pontos ou traços)</li>
                        <li>Experimente buscar por protocolo, nome ou cidade</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-dark);">Busca Inteligente</h3>
                    <p style="color: var(--text-light); max-width: 600px; margin: 20px auto;">
                        Use o campo acima para buscar inscrições por diversos critérios simultaneamente.
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 40px; text-align: left;">
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-user" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Nome</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o nome completo ou parte dele
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-id-card" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por CPF</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite apenas os números do CPF
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-barcode" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Protocolo</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o protocolo completo ou parcial
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Email</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o email ou parte dele
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-phone" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Telefone</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o número de telefone
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-city" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Cidade</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o nome da cidade
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-medal" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Modalidade</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o nome da modalidade
                            </p>
                        </div>

                        <div style="background: var(--light-bg); padding: 20px; border-radius: 8px;">
                            <i class="fas fa-layer-group" style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4>Por Categoria</h4>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Digite o nome da categoria
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas - Painel Administrativo</p>
        </div>
    </footer>
</body>
</html>
