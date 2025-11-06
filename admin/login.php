<?php
/**
 * Login Administrativo
 * Autenticação de administradores do sistema
 */

require_once '../config/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $erro = 'Preencha todos os campos';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? AND ativo = 1");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['admin_id'] = $usuario['id'];
            $_SESSION['admin_nome'] = $usuario['nome'];
            $_SESSION['admin_nivel'] = $usuario['nivel'];

            // Atualizar último acesso
            $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
            $stmt->execute([$usuario['id']]);

            header('Location: index.php');
            exit;
        } else {
            $erro = 'Usuário ou senha inválidos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Área Administrativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .login-header i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .login-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .login-body {
            padding: 2.5rem 2rem;
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1.05rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h2>Área Administrativa</h2>
            <p>Sistema de Gestão de Competições</p>
        </div>

        <div class="login-body">
            <?php if ($erro): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating mb-3">
                    <input type="text"
                           class="form-control"
                           id="username"
                           name="username"
                           placeholder="Usuário"
                           required
                           autofocus>
                    <label for="username">
                        <i class="fas fa-user me-2"></i>Usuário
                    </label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           placeholder="Senha"
                           required>
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Senha
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar no Sistema
                </button>
            </form>

            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao site
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
