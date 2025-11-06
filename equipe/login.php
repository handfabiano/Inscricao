<?php
require_once '../config/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitize($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($usuario) && !empty($senha)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM equipes WHERE usuario = ? AND ativo = 1");
            $stmt->execute([$usuario]);
            $equipe = $stmt->fetch();

            if ($equipe && password_verify($senha, $equipe['senha'])) {
                $_SESSION['equipe_id'] = $equipe['id'];
                $_SESSION['equipe_nome'] = $equipe['nome'];
                $_SESSION['equipe_responsavel'] = $equipe['responsavel_nome'];
                $_SESSION['equipe_status'] = $equipe['status'];

                // Atualizar último acesso
                $stmt = $pdo->prepare("UPDATE equipes SET ultimo_acesso = NOW() WHERE id = ?");
                $stmt->execute([$equipe['id']]);

                header('Location: index.php');
                exit;
            } else {
                $erro = 'Usuário ou senha inválidos';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao processar login';
        }
    } else {
        $erro = 'Preencha todos os campos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Equipe - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #10b981;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>👥 Sistema v3.0</h1>
            <p>Área da Equipe</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="usuario" required autofocus placeholder="Digite seu usuário">
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required placeholder="Digite sua senha">
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%;">
                Entrar
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; color: #64748b; font-size: 0.85rem;">
            <p>Primeira vez? Entre em contato com o administrador.</p>
        </div>
    </div>
</body>
</html>
