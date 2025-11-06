<?php
require_once '../config/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($usuario) && !empty($senha)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM equipes WHERE usuario = ? AND ativo = 1 AND status = 'Aprovada'");
        $stmt->execute([$usuario]);
        $equipe = $stmt->fetch();

        if ($equipe && password_verify($senha, $equipe['senha'])) {
            $_SESSION['equipe_id'] = $equipe['id'];
            $_SESSION['equipe_nome'] = $equipe['nome'];
            $_SESSION['responsavel_nome'] = $equipe['responsavel_nome'];

            // Atualizar último acesso
            $stmt = $pdo->prepare("UPDATE equipes SET ultimo_acesso = NOW() WHERE id = ?");
            $stmt->execute([$equipe['id']]);

            header('Location: index.php');
            exit;
        } else {
            $erro = 'Usuário ou senha inválidos, ou equipe não aprovada';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login Equipe - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #10b981, #059669); }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-users" style="font-size: 3rem; color: #10b981;"></i>
            <h2 style="margin: 10px 0;">Área da Equipe</h2>
            <p style="color: var(--text-light);">Sistema v3.0</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="usuario" required autofocus>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required>
            </div>
            <button type="submit" class="btn btn-success" style="width: 100%; background: #10b981;">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="../cadastro_equipe.php" style="color: #10b981;">
                <i class="fas fa-user-plus"></i> Cadastrar Nova Equipe
            </a>
        </div>

        <div style="text-align: center; margin-top: 15px;">
            <a href="../index.php" style="color: var(--text-light); font-size: 0.9rem;">
                <i class="fas fa-home"></i> Voltar ao Site
            </a>
        </div>
    </div>
</body>
</html>
