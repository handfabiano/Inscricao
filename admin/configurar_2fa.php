<?php
/**
 * Configuração de Autenticação de Dois Fatores (2FA)
 *
 * Página para configurar, ativar e desativar 2FA
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes_auxiliares.php';
require_once __DIR__ . '/../includes/two_factor_helper.php';

requireAdminLogin();

$admin_id = $_SESSION['admin_id'];
$mensagem = '';
$tipo_mensagem = '';

// Verificar status atual do 2FA
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT two_factor_enabled, two_factor_confirmed_at FROM administradores WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$has_2fa = $admin['two_factor_enabled'] ?? false;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'setup') {
        // Iniciar configuração
        $result = setupTwoFactor('admin', $admin_id);

        if ($result['success']) {
            $_SESSION['2fa_setup'] = $result;
            $mensagem = '2FA iniciado. Escaneie o QR Code com seu aplicativo autenticador.';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = $result['message'];
            $tipo_mensagem = 'error';
        }

    } elseif ($action === 'confirm') {
        // Confirmar com código
        $code = $_POST['code'] ?? '';

        $result = confirmTwoFactor('admin', $admin_id, $code);

        if ($result['success']) {
            $mensagem = '2FA ativado com sucesso! Guarde seus códigos de recuperação em local seguro.';
            $tipo_mensagem = 'success';
            unset($_SESSION['2fa_setup']);
            $has_2fa = true;
        } else {
            $mensagem = $result['message'];
            $tipo_mensagem = 'error';
        }

    } elseif ($action === 'disable') {
        // Desativar 2FA
        $senha = $_POST['senha'] ?? '';

        // Verificar senha
        $stmt = $pdo->prepare("SELECT senha FROM administradores WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($senha, $admin_data['senha'])) {
            if (disableTwoFactor('admin', $admin_id)) {
                $mensagem = '2FA desativado com sucesso.';
                $tipo_mensagem = 'success';
                $has_2fa = false;
            } else {
                $mensagem = 'Erro ao desativar 2FA.';
                $tipo_mensagem = 'error';
            }
        } else {
            $mensagem = 'Senha incorreta.';
            $tipo_mensagem = 'error';
        }

    } elseif ($action === 'cancel') {
        // Cancelar configuração
        unset($_SESSION['2fa_setup']);
        $mensagem = 'Configuração cancelada.';
        $tipo_mensagem = 'info';
    }
}

$setup_data = $_SESSION['2fa_setup'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Autenticação de Dois Fatores (2FA)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .qr-code-container {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .recovery-codes {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #ffc107;
        }
        .recovery-code {
            font-family: monospace;
            font-size: 1.1em;
            padding: 5px;
            display: inline-block;
            margin: 5px;
        }
        .secret-key {
            font-family: monospace;
            font-size: 1.2em;
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
        }
        .security-icon {
            font-size: 4em;
            color: #28a745;
        }
        .warning-icon {
            font-size: 4em;
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <!-- Cabeçalho -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shield-alt"></i> Autenticação de Dois Fatores (2FA)</h2>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>

                <!-- Mensagens -->
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem === 'error' ? 'danger' : $tipo_mensagem; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($mensagem); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($has_2fa && !$setup_data): ?>
                    <!-- 2FA ATIVADO -->
                    <div class="card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-check-circle security-icon"></i>
                            <h3 class="mt-3">2FA Ativado</h3>
                            <p class="text-muted">Sua conta está protegida com autenticação de dois fatores.</p>
                            <p><small>Ativado em: <?php echo date('d/m/Y H:i', strtotime($admin['two_factor_confirmed_at'])); ?></small></p>

                            <hr class="my-4">

                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disableModal">
                                <i class="fas fa-times"></i> Desativar 2FA
                            </button>
                        </div>
                    </div>

                <?php elseif ($setup_data): ?>
                    <!-- CONFIGURAÇÃO EM ANDAMENTO -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-qrcode"></i> Configurar Autenticador</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Escaneie o QR Code abaixo com seu aplicativo autenticador
                                (Google Authenticator, Microsoft Authenticator, Authy, etc.)
                            </div>

                            <!-- QR Code -->
                            <div class="qr-code-container">
                                <img src="<?php echo htmlspecialchars($setup_data['qr_code_url']); ?>" alt="QR Code">
                                <p class="mt-3"><strong>Ou digite manualmente:</strong></p>
                                <div class="secret-key"><?php echo htmlspecialchars($setup_data['secret']); ?></div>
                            </div>

                            <!-- Códigos de Recuperação -->
                            <div class="recovery-codes mt-4">
                                <h5><i class="fas fa-key"></i> Códigos de Recuperação</h5>
                                <p class="mb-3">Guarde estes códigos em local seguro. Você pode usá-los caso perca acesso ao seu autenticador.</p>
                                <div class="row">
                                    <?php foreach ($setup_data['recovery_codes'] as $code): ?>
                                        <div class="col-md-6">
                                            <div class="recovery-code"><?php echo htmlspecialchars($code); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning mt-3" onclick="printRecoveryCodes()">
                                    <i class="fas fa-print"></i> Imprimir Códigos
                                </button>
                            </div>

                            <!-- Verificação -->
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="action" value="confirm">
                                <div class="mb-3">
                                    <label class="form-label">Digite o código de 6 dígitos do seu autenticador:</label>
                                    <input type="text" name="code" class="form-control form-control-lg text-center"
                                           maxlength="6" pattern="[0-9]{6}" required autofocus
                                           style="letter-spacing: 10px; font-size: 2em;">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check"></i> Verificar e Ativar 2FA
                                    </button>
                                    <button type="submit" name="action" value="cancel" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- 2FA DESATIVADO -->
                    <div class="card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-exclamation-triangle warning-icon"></i>
                            <h3 class="mt-3">2FA Desativado</h3>
                            <p class="text-muted">Proteja sua conta ativando a autenticação de dois fatores.</p>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <i class="fas fa-shield-alt text-success" style="font-size: 2em;"></i>
                                            <h5 class="mt-2">Mais Segurança</h5>
                                            <p class="small">Proteja sua conta contra acessos não autorizados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <i class="fas fa-mobile-alt text-primary" style="font-size: 2em;"></i>
                                            <h5 class="mt-2">Fácil de Usar</h5>
                                            <p class="small">Use seu celular como segundo fator de autenticação</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" class="mt-4">
                                <input type="hidden" name="action" value="setup">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-lock"></i> Ativar 2FA Agora
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Informações -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-question-circle"></i> Como funciona?</h5>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>Instale um aplicativo autenticador no seu celular (Google Authenticator, Microsoft Authenticator, Authy)</li>
                                <li>Clique em "Ativar 2FA Agora" e escaneie o QR Code com o aplicativo</li>
                                <li>Digite o código de 6 dígitos gerado pelo aplicativo para confirmar</li>
                                <li>Guarde os códigos de recuperação em local seguro</li>
                                <li>A partir de agora, você precisará do código do aplicativo para fazer login</li>
                            </ol>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Desativar 2FA -->
    <div class="modal fade" id="disableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Desativar 2FA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="disable">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Desativar o 2FA tornará sua conta menos segura!
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirme sua senha:</label>
                            <input type="password" name="senha" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Desativar 2FA</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printRecoveryCodes() {
            window.print();
        }

        // Auto-submit quando digitar 6 dígitos
        document.querySelector('input[name="code"]')?.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
