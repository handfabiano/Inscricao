<?php
require_once '../config/config.php';
requireLogin();

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar_perfil') {
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);

        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $_SESSION['user_id']]);

        $_SESSION['nome'] = $nome;
        $mensagem = "Perfil atualizado com sucesso!";

        // Recarregar dados
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch();
    }

    if ($acao === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'];
        $novaSenha = $_POST['nova_senha'];
        $confirmarSenha = $_POST['confirmar_senha'];

        if (!password_verify($senhaAtual, $usuario['password'])) {
            $erro = "Senha atual incorreta!";
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = "As senhas não coincidem!";
        } elseif (strlen($novaSenha) < 6) {
            $erro = "A senha deve ter no mínimo 6 caracteres!";
        } else {
            $hashNovaSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$hashNovaSenha, $_SESSION['user_id']]);

            // Log da ação
            $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'Alteração de Senha',
                "Usuário alterou sua senha",
                $_SERVER['REMOTE_ADDR']
            ]);

            $mensagem = "Senha alterada com sucesso!";
        }
    }
}

// Estatísticas do usuário
$statsUsuario = [];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$statsUsuario['total_acoes'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM logs
    WHERE usuario_id = ? AND acao LIKE '%Aprovação%'
");
$stmt->execute([$_SESSION['user_id']]);
$statsUsuario['aprovacoes'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM logs
    WHERE usuario_id = ? AND acao LIKE '%Rejeição%'
");
$stmt->execute([$_SESSION['user_id']]);
$statsUsuario['rejeicoes'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM logs
    WHERE usuario_id = ? AND DATE(created_at) = CURDATE()
");
$stmt->execute([$_SESSION['user_id']]);
$statsUsuario['acoes_hoje'] = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
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
                <a href="modalidades.php">Modalidades</a>
                <a href="logs.php">Logs</a>
                <a href="perfil.php" class="active">Perfil</a>
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

        <h2 style="margin-bottom: 30px;">Meu Perfil</h2>

        <!-- Estatísticas do Usuário -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <h3><?php echo $statsUsuario['total_acoes']; ?></h3>
                <p>Total de Ações</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <h3><?php echo $statsUsuario['aprovacoes']; ?></h3>
                <p>Aprovações</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <h3><?php echo $statsUsuario['rejeicoes']; ?></h3>
                <p>Rejeições</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3><?php echo $statsUsuario['acoes_hoje']; ?></h3>
                <p>Ações Hoje</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
            <!-- Dados do Perfil -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user"></i> Dados do Perfil
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="acao" value="atualizar_perfil">

                    <div class="form-group">
                        <label>Usuário</label>
                        <input type="text" value="<?php echo htmlspecialchars($usuario['username']); ?>" disabled>
                        <small style="color: var(--text-light);">O nome de usuário não pode ser alterado</small>
                    </div>

                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nível de Acesso</label>
                        <input type="text" value="<?php echo htmlspecialchars($usuario['nivel']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Último Acesso</label>
                        <input type="text" value="<?php echo $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Conta Criada em</label>
                        <input type="text" value="<?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>" disabled>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </form>
            </div>

            <!-- Alterar Senha -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lock"></i> Alterar Senha
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="acao" value="alterar_senha">

                    <div class="form-group">
                        <label for="senha_atual">Senha Atual *</label>
                        <input type="password" id="senha_atual" name="senha_atual" required>
                    </div>

                    <div class="form-group">
                        <label for="nova_senha">Nova Senha *</label>
                        <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
                        <small style="color: var(--text-light);">Mínimo de 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha *</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Dicas de Segurança:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Use no mínimo 8 caracteres</li>
                            <li>Combine letras maiúsculas e minúsculas</li>
                            <li>Adicione números e caracteres especiais</li>
                            <li>Não use informações pessoais</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>

        <!-- Atividades Recentes -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <i class="fas fa-history"></i> Minhas Atividades Recentes
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Ação</th>
                        <th>Descrição</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT * FROM logs
                        WHERE usuario_id = ?
                        ORDER BY created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $atividadesRecentes = $stmt->fetchAll();

                    if (empty($atividadesRecentes)):
                    ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light);">
                                Nenhuma atividade registrada
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($atividadesRecentes as $ativ): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <?php echo date('d/m/Y H:i', strtotime($ativ['created_at'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($ativ['acao']); ?></td>
                                <td><?php echo htmlspecialchars($ativ['descricao']); ?></td>
                                <td style="font-family: monospace; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($ativ['ip']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="text-align: center; margin-top: 15px;">
                <a href="logs.php?usuario=<?php echo $_SESSION['user_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-list"></i> Ver Todas as Atividades
                </a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas - Painel Administrativo</p>
        </div>
    </footer>

    <script>
        // Validar senhas iguais
        document.querySelector('form[action=""][method="POST"]').addEventListener('submit', function(e) {
            const novaSenha = document.getElementById('nova_senha');
            const confirmarSenha = document.getElementById('confirmar_senha');

            if (novaSenha && confirmarSenha && novaSenha.value !== confirmarSenha.value) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                confirmarSenha.focus();
            }
        });
    </script>
</body>
</html>
