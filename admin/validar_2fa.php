<?php
/**
 * Validação de 2FA no Login
 *
 * Página para validar código de dois fatores após login
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/two_factor_helper.php';

// Verificar se está no processo de 2FA
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_user_type'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['2fa_user_id'];
$user_type = $_SESSION['2fa_user_type'];
$tentativas = $_SESSION['2fa_tentativas'] ?? 0;
$max_tentativas = 5;

$erro = '';
$mostrar_recovery = isset($_GET['recovery']) && $_GET['recovery'] === '1';

// Verificar dispositivo confiável
$remember_device = false;
if (isset($_COOKIE['trusted_device_' . $user_type])) {
    $device = verifyTrustedDevice($_COOKIE['trusted_device_' . $user_type]);
    if ($device && $device['user_id'] == $user_id && $device['user_type'] == $user_type) {
        // Dispositivo confiável, permitir acesso
        completeTwoFactorLogin();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $remember = isset($_POST['remember_device']);

    if ($tentativas >= $max_tentativas) {
        $erro = 'Número máximo de tentativas excedido. Por favor, faça login novamente.';
        session_destroy();
    } else {
        $valid = false;

        if ($mostrar_recovery) {
            // Verificar código de recuperação
            $valid = verifyRecoveryCode($user_type, $user_id, $code);
        } else {
            // Verificar código TOTP
            $pdo = getDBConnection();
            $table = $user_type === 'admin' ? 'administradores' : 'equipes';
            $stmt = $pdo->prepare("SELECT two_factor_secret FROM $table WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['two_factor_secret']) {
                $valid = verifyTOTP($user['two_factor_secret'], $code);
            }
        }

        if ($valid) {
            // Log sucesso
            logTwoFactorEvent($user_type, $user_id, 'login_success', $mostrar_recovery ? 'recovery_code' : 'totp');

            // Criar dispositivo confiável se solicitado
            if ($remember) {
                $settings = getSecuritySettings();
                if ($settings['allow_trusted_devices'] ?? true) {
                    $device_name = getBrowserName() . ' em ' . getOperatingSystem();
                    $token = createTrustedDevice($user_type, $user_id, $device_name, $settings['trusted_device_duration_days'] ?? 30);

                    if ($token) {
                        setcookie('trusted_device_' . $user_type, $token, [
                            'expires' => strtotime('+30 days'),
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    }
                }
            }

            // Completar login
            completeTwoFactorLogin();

        } else {
            $tentativas++;
            $_SESSION['2fa_tentativas'] = $tentativas;
            logTwoFactorEvent($user_type, $user_id, 'login_failed', $mostrar_recovery ? 'recovery_code' : 'totp');
            $erro = 'Código inválido. ' . ($max_tentativas - $tentativas) . ' tentativas restantes.';
        }
    }
}

function completeTwoFactorLogin() {
    global $user_id, $user_type;

    if ($user_type === 'admin') {
        $_SESSION['admin_id'] = $user_id;
        unset($_SESSION['2fa_user_id'], $_SESSION['2fa_user_type'], $_SESSION['2fa_tentativas']);
        header('Location: index.php');
    } else {
        $_SESSION['equipe_id'] = $user_id;
        unset($_SESSION['2fa_user_id'], $_SESSION['2fa_user_type'], $_SESSION['2fa_tentativas']);
        header('Location: ../equipe/index.php');
    }
    exit;
}

function getBrowserName() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
    if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
    if (strpos($user_agent, 'Safari') !== false) return 'Safari';
    if (strpos($user_agent, 'Edge') !== false) return 'Edge';
    return 'Navegador';
}

function getOperatingSystem() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strpos($user_agent, 'Windows') !== false) return 'Windows';
    if (strpos($user_agent, 'Mac') !== false) return 'Mac';
    if (strpos($user_agent, 'Linux') !== false) return 'Linux';
    if (strpos($user_agent, 'Android') !== false) return 'Android';
    if (strpos($user_agent, 'iOS') !== false) return 'iOS';
    return 'Outro';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação em Duas Etapas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-2fa {
            max-width: 450px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .code-input {
            font-size: 2em;
            letter-spacing: 15px;
            text-align: center;
            font-family: monospace;
            padding: 20px;
        }
        .lock-icon {
            font-size: 4em;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-2fa mx-auto">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fas fa-lock lock-icon"></i>
                    <h3 class="mt-3">Verificação em Duas Etapas</h3>
                    <p class="text-muted">
                        <?php if ($mostrar_recovery): ?>
                            Digite um código de recuperação
                        <?php else: ?>
                            Digite o código de 6 dígitos do seu autenticador
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <input type="text"
                               name="code"
                               class="form-control code-input"
                               maxlength="<?php echo $mostrar_recovery ? 9 : 6; ?>"
                               pattern="<?php echo $mostrar_recovery ? '[0-9]{4}-?[0-9]{4}' : '[0-9]{6}'; ?>"
                               placeholder="<?php echo $mostrar_recovery ? '____-____' : '______'; ?>"
                               required
                               autofocus>
                        <?php if (!$mostrar_recovery): ?>
                            <small class="form-text text-muted">O código muda a cada 30 segundos</small>
                        <?php endif; ?>
                    </div>

                    <?php
                    $settings = getSecuritySettings();
                    if ($settings['allow_trusted_devices'] ?? true):
                    ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="remember_device" id="remember">
                        <label class="form-check-label" for="remember">
                            <small>Confiar neste dispositivo por 30 dias</small>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Verificar
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <?php if (!$mostrar_recovery): ?>
                    <div class="text-center">
                        <p class="small mb-2">Perdeu acesso ao autenticador?</p>
                        <a href="?recovery=1" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-key"></i> Usar Código de Recuperação
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <a href="validar_2fa.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-mobile-alt"></i> Usar Autenticador
                        </a>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="logout.php" class="btn btn-sm btn-link text-muted">
                        <i class="fas fa-sign-out-alt"></i> Cancelar e Sair
                    </a>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        Tentativas: <?php echo $tentativas; ?>/<?php echo $max_tentativas; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit quando digitar código completo
        document.querySelector('input[name="code"]').addEventListener('input', function(e) {
            const maxLength = <?php echo $mostrar_recovery ? 9 : 6; ?>;
            const value = this.value.replace('-', '');

            if (value.length === (maxLength - (<?php echo $mostrar_recovery ? 1 : 0; ?>))) {
                this.form.submit();
            }
        });

        // Formatar código de recuperação
        <?php if ($mostrar_recovery): ?>
        document.querySelector('input[name="code"]').addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            this.value = value;
        });
        <?php endif; ?>
    </script>
</body>
</html>
