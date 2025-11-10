<?php
/**
 * VERSÃO DEBUG - admin/configuracoes.php
 * Esta versão mostra erros e não requer login
 * USE APENAS PARA DEBUG - DELETAR APÓS CORRIGIR
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- MODO DEBUG ATIVADO -->\n";
echo "<!-- Esta página NÃO requer login para facilitar debug -->\n";

require_once '../config/config.php';

// NÃO FAZER requireAdminLogin() em modo debug
// requireAdminLogin();

// Simular admin logado para debug
if (!isset($_SESSION['admin_id'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 2px solid #ffc107; margin-bottom: 20px;'>\n";
    echo "<strong>⚠️ MODO DEBUG:</strong> Você não está logado, mas pode ver a página para debug.\n";
    echo "<br><a href='login.php'>Fazer login normalmente</a> | <a href='criar_admin.php'>Criar admin</a>\n";
    echo "</div>\n";
    // Simular sessão para debug
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_nome'] = 'DEBUG MODE';
}

try {
    $pdo = getDBConnection();
    $pageTitle = 'Configurações do Sistema (DEBUG)';

    // Processar ação
    $mensagem = '';
    $tipoMensagem = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';

        try {
            if ($acao === 'criar_admin') {
                $nome = $_POST['nome'];
                $email = $_POST['email'];
                $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $nivel = $_POST['nivel'];

                $stmt = $pdo->prepare("INSERT INTO administradores (nome, email, senha, nivel) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $senha, $nivel]);

                $mensagem = "Administrador criado com sucesso!";
                $tipoMensagem = "success";
            }
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }

    // Buscar administradores
    try {
        $stmt = $pdo->query("SELECT * FROM administradores ORDER BY created_at DESC");
        $administradores = $stmt->fetchAll();
    } catch (Exception $e) {
        $administradores = [];
        echo "<div style='background: #f8d7da; padding: 15px; border: 2px solid #dc3545; margin: 20px 0;'>\n";
        echo "<strong>Erro ao buscar administradores:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
        echo "<br><br><strong>Possível solução:</strong> Execute <a href='/migrations/run_migrations_BASICO.php'>/migrations/run_migrations_BASICO.php</a>\n";
        echo "</div>\n";
    }

    // Buscar estatísticas do sistema
    $stats = [];
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM competicoes");
        $stats['competicoes'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipes");
        $stats['equipes'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM atletas");
        $stats['atletas'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes");
        $stats['inscricoes'] = $stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats = ['competicoes' => 0, 'equipes' => 0, 'atletas' => 0, 'inscricoes' => 0];
    }

} catch (Exception $e) {
    die("<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; margin: 20px;'>\n" .
        "<h2>Erro Fatal</h2>\n" .
        "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n" .
        "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n" .
        "<p><strong>Linha:</strong> " . $e->getLine() . "</p>\n" .
        "</div>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - DEBUG MODE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Alert DEBUG -->
    <div class="alert alert-warning sticky-top mb-0" role="alert">
        <div class="container">
            <strong><i class="fas fa-exclamation-triangle"></i> MODO DEBUG ATIVADO</strong> -
            Esta é uma versão de debug de configuracoes.php.
            <a href="configuracoes.php" class="alert-link">Ir para versão normal</a> |
            <a href="test_configuracoes.php" class="alert-link">Ver diagnóstico completo</a>
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="fas fa-cog text-warning"></i>
                            Configurações do Sistema (DEBUG MODE)
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <h5 class="mb-3"><i class="fas fa-database"></i> Estatísticas do Sistema</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-primary"><?php echo $stats['competicoes']; ?></h4>
                        <p class="mb-0 small">Competições</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-warning"><?php echo $stats['equipes']; ?></h4>
                        <p class="mb-0 small">Equipes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-info"><?php echo $stats['atletas']; ?></h4>
                        <p class="mb-0 small">Atletas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="text-success"><?php echo $stats['inscricoes']; ?></h4>
                        <p class="mb-0 small">Inscrições</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Administradores -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-shield text-primary"></i>
                    Administradores (<?php echo count($administradores); ?>)
                </h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoAdmin">
                    <i class="fas fa-plus"></i> Novo Admin
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Criado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($administradores)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum administrador cadastrado</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoAdmin">
                                    Criar Primeiro Admin
                                </button>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($administradores as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($admin['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($admin['nivel']); ?></span></td>
                                <td>
                                    <?php if ($admin['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Instruções -->
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Como sair do modo DEBUG</h5>
            <ol>
                <li>Certifique-se de que há pelo menos 1 administrador cadastrado acima</li>
                <li><a href="login.php">Faça login normalmente</a> com o email e senha do admin</li>
                <li>Acesse <a href="configuracoes.php">configuracoes.php</a> (versão normal)</li>
                <li>Delete este arquivo (configuracoes_DEBUG.php) e test_configuracoes.php por segurança</li>
            </ol>
        </div>
    </div>

    <!-- Modal: Novo Admin -->
    <div class="modal fade" id="modalNovoAdmin" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_admin">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Administrador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso</label>
                            <select name="nivel" class="form-select" required>
                                <option value="Master">Master</option>
                                <option value="Admin">Admin</option>
                                <option value="Moderador">Moderador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Administrador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
