<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$pageTitle = 'Configurações do Sistema';

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

        } elseif ($acao === 'editar_admin') {
            $id = $_POST['admin_id'];
            $nome = $_POST['nome'];
            $nivel = $_POST['nivel'];
            $ativo = $_POST['ativo'];

            $stmt = $pdo->prepare("UPDATE administradores SET nome = ?, nivel = ?, ativo = ? WHERE id = ?");
            $stmt->execute([$nome, $nivel, $ativo, $id]);

            $mensagem = "Administrador atualizado com sucesso!";
            $tipoMensagem = "success";

        } elseif ($acao === 'alterar_senha') {
            $id = $_POST['admin_id'];
            $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE administradores SET senha = ? WHERE id = ?");
            $stmt->execute([$novaSenha, $id]);

            $mensagem = "Senha alterada com sucesso!";
            $tipoMensagem = "success";

        } elseif ($acao === 'limpar_logs') {
            $diasAnteriores = (int)$_POST['dias'];
            $dataLimite = date('Y-m-d', strtotime("-$diasAnteriores days"));

            // Criar tabela de logs se não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS logs_sistema (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_tipo VARCHAR(20),
                    usuario_id INT,
                    acao VARCHAR(100),
                    descricao TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_created_at (created_at)
                )
            ");

            $stmt = $pdo->prepare("DELETE FROM logs_sistema WHERE DATE(created_at) < ?");
            $stmt->execute([$dataLimite]);
            $linhasApagadas = $stmt->rowCount();

            $mensagem = "$linhasApagadas registros de log foram removidos.";
            $tipoMensagem = "info";

        } elseif ($acao === 'configurar_email') {
            // Criar tabela se não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS config_email (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    smtp_host VARCHAR(255) NOT NULL,
                    smtp_port INT NOT NULL DEFAULT 587,
                    smtp_username VARCHAR(255) NOT NULL,
                    smtp_password VARCHAR(255) NOT NULL,
                    smtp_secure VARCHAR(10) DEFAULT 'tls',
                    email_remetente VARCHAR(255) NOT NULL,
                    nome_remetente VARCHAR(255) NOT NULL,
                    ativo BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Verificar se já existe configuração
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM config_email");
            $existe = $stmt->fetch()['total'] > 0;

            if ($existe) {
                // Atualizar configuração existente
                $stmt = $pdo->prepare("
                    UPDATE config_email SET
                        smtp_host = ?,
                        smtp_port = ?,
                        smtp_username = ?,
                        smtp_password = ?,
                        smtp_secure = ?,
                        email_remetente = ?,
                        nome_remetente = ?,
                        ativo = ?
                    WHERE id = 1
                ");
            } else {
                // Inserir nova configuração
                $stmt = $pdo->prepare("
                    INSERT INTO config_email (
                        smtp_host, smtp_port, smtp_username, smtp_password,
                        smtp_secure, email_remetente, nome_remetente, ativo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
            }

            $stmt->execute([
                $_POST['smtp_host'],
                (int)$_POST['smtp_port'],
                $_POST['smtp_username'],
                $_POST['smtp_password'],
                $_POST['smtp_secure'],
                $_POST['email_remetente'],
                $_POST['nome_remetente'],
                isset($_POST['ativo']) ? 1 : 0
            ]);

            $mensagem = "Configurações de e-mail salvas com sucesso!";
            $tipoMensagem = "success";

        } elseif ($acao === 'testar_email') {
            require_once '../includes/email_helper.php';

            $emailTeste = $_POST['email_teste'];
            $resultado = enviarEmail(
                $emailTeste,
                'Teste de Configuração SMTP',
                '<h2>Teste de E-mail</h2><p>Se você recebeu este e-mail, as configurações SMTP estão funcionando corretamente!</p><p><strong>Data/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>',
                'Usuário Teste'
            );

            if ($resultado) {
                $mensagem = "E-mail de teste enviado com sucesso para " . htmlspecialchars($emailTeste);
                $tipoMensagem = "success";
            } else {
                $mensagem = "Erro ao enviar e-mail de teste. Verifique as configurações SMTP.";
                $tipoMensagem = "danger";
            }
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
}

// Buscar configuração de e-mail
$configEmail = null;
try {
    $stmt = $pdo->query("SELECT * FROM config_email WHERE id = 1 LIMIT 1");
    $configEmail = $stmt->fetch();
} catch (Exception $e) {
    // Tabela ainda não existe
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

// Verificar se tabela de logs existe e buscar logs recentes
$tabelaLogsExiste = false;
$logsRecentes = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'logs_sistema'");
    $tabelaLogsExiste = $stmt->rowCount() > 0;

    if ($tabelaLogsExiste) {
        $stmt = $pdo->query("
            SELECT * FROM logs_sistema
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $logsRecentes = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Ignora erro se tabela não existe
}

// Informações do banco de dados
try {
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbInfo = $stmt->fetch();
    $dbName = $dbInfo['db_name'];

    $stmt = $pdo->prepare("
        SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = ?
        ORDER BY size_mb DESC
    ");
    $stmt->execute([$dbName]);
    $tabelasSizes = $stmt->fetchAll();
} catch (Exception $e) {
    $dbName = 'N/A';
    $tabelasSizes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .log-entry {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt"></i> Admin - Sistema de Competições
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="configuracoes.php">
                            <i class="fas fa-cog"></i> Configurações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 mb-5">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="fas fa-cog text-warning"></i>
                            Configurações do Sistema
                        </h2>
                        <p class="text-muted mb-0">Gerencie administradores, monitore o sistema e ajuste configurações</p>
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

        <!-- Estatísticas do Sistema -->
        <h5 class="mb-3"><i class="fas fa-database"></i> Estatísticas do Banco de Dados</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #0d6efd;">
                    <div class="card-body">
                        <h4 class="text-primary mb-1"><?php echo $stats['competicoes']; ?></h4>
                        <p class="text-muted mb-0 small">Competições</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <h4 class="text-warning mb-1"><?php echo $stats['equipes']; ?></h4>
                        <p class="text-muted mb-0 small">Equipes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #0dcaf0;">
                    <div class="card-body">
                        <h4 class="text-info mb-1"><?php echo $stats['atletas']; ?></h4>
                        <p class="text-muted mb-0 small">Atletas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #198754;">
                    <div class="card-body">
                        <h4 class="text-success mb-1"><?php echo $stats['inscricoes']; ?></h4>
                        <p class="text-muted mb-0 small">Inscrições</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gerenciamento de Administradores -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-shield text-primary"></i>
                            Administradores do Sistema
                        </h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoAdmin">
                            <i class="fas fa-plus"></i> Novo Admin
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Nível</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($administradores as $admin): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($admin['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($admin['nivel']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($admin['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo date('d/m/Y', strtotime($admin['created_at'])); ?></small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning" onclick="editarAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" onclick="alterarSenha(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['nome']); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Logs do Sistema -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt text-info"></i>
                            Logs do Sistema (Últimos 20 registros)
                        </h5>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalLimparLogs">
                            <i class="fas fa-trash"></i> Limpar Logs
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!$tabelaLogsExiste): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Tabela de logs ainda não foi criada. Será criada automaticamente quando houver registros.
                            </div>
                        <?php elseif (empty($logsRecentes)): ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-inbox"></i> Nenhum log registrado ainda.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Usuário</th>
                                            <th>Ação</th>
                                            <th>Descrição</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logsRecentes as $log): ?>
                                        <tr class="log-entry">
                                            <td><small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($log['usuario_tipo'] ?? 'N/A'); ?> #<?php echo $log['usuario_id'] ?? 'N/A'; ?></small></td>
                                            <td><small><strong><?php echo htmlspecialchars($log['acao'] ?? 'N/A'); ?></strong></small></td>
                                            <td><small><?php echo htmlspecialchars($log['descricao'] ?? 'N/A'); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Painel de Informações -->
            <div class="col-lg-4">
                <!-- Informações do Banco -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-database text-success"></i>
                            Informações do Banco
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nome do Banco:</strong><br><?php echo htmlspecialchars($dbName); ?></p>
                        <hr>
                        <p class="mb-2"><strong>Tamanho das Tabelas:</strong></p>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <?php foreach ($tabelasSizes as $tabela): ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($tabela['table_name']); ?></small></td>
                                    <td class="text-end"><small><strong><?php echo $tabela['size_mb']; ?> MB</strong></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Configurações de E-mail -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope-open-text text-primary"></i>
                            Configurações de E-mail (SMTP)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($configEmail): ?>
                            <div class="alert alert-success alert-sm">
                                <i class="fas fa-check-circle"></i>
                                <strong>Status:</strong> <?php echo $configEmail['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </div>
                            <p class="small mb-2"><strong>Servidor:</strong> <?php echo htmlspecialchars($configEmail['smtp_host']); ?>:<?php echo $configEmail['smtp_port']; ?></p>
                            <p class="small mb-2"><strong>Remetente:</strong> <?php echo htmlspecialchars($configEmail['email_remetente']); ?></p>
                        <?php else: ?>
                            <div class="alert alert-warning alert-sm">
                                <i class="fas fa-exclamation-triangle"></i>
                                E-mail ainda não configurado
                            </div>
                        <?php endif; ?>
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalConfigurarEmail">
                                <i class="fas fa-cog"></i> <?php echo $configEmail ? 'Editar' : 'Configurar'; ?> SMTP
                            </button>
                            <?php if ($configEmail && $configEmail['ativo']): ?>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalTestarEmail">
                                <i class="fas fa-paper-plane"></i> Enviar E-mail de Teste
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ações do Sistema -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-tools text-warning"></i>
                            Ações do Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="alert('Funcionalidade de backup será implementada em breve!')">
                                <i class="fas fa-download"></i> Backup do Banco
                            </button>
                            <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-sync"></i> Recarregar Página
                            </button>
                            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalSobre">
                                <i class="fas fa-info-circle"></i> Sobre o Sistema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Novo Admin -->
    <div class="modal fade" id="modalNovoAdmin" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_admin">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Novo Administrador</h5>
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

    <!-- Modal: Editar Admin -->
    <div class="modal fade" id="modalEditarAdmin" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="editar_admin">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Administrador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso</label>
                            <select name="nivel" id="edit_nivel" class="form-select" required>
                                <option value="Master">Master</option>
                                <option value="Admin">Admin</option>
                                <option value="Moderador">Moderador</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="ativo" id="edit_ativo" class="form-select" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Alterar Senha -->
    <div class="modal fade" id="modalAlterarSenha" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="alterar_senha">
                    <input type="hidden" name="admin_id" id="senha_admin_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-key"></i> Alterar Senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Alterando senha do administrador: <strong id="senha_admin_nome"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="nova_senha" class="form-control" required minlength="6">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">Alterar Senha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Limpar Logs -->
    <div class="modal fade" id="modalLimparLogs" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="limpar_logs">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-trash"></i> Limpar Logs do Sistema</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atenção:</strong> Esta ação irá remover permanentemente os logs antigos.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remover logs anteriores a:</label>
                            <select name="dias" class="form-select">
                                <option value="7">7 dias atrás</option>
                                <option value="30">30 dias atrás</option>
                                <option value="60">60 dias atrás</option>
                                <option value="90">90 dias atrás</option>
                                <option value="180">180 dias atrás</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Limpeza</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Sobre -->
    <div class="modal fade" id="modalSobre" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Sobre o Sistema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-shield-alt fa-4x text-primary mb-3"></i>
                    <h4>Sistema de Gestão de Competições Esportivas</h4>
                    <p class="text-muted">Versão 1.0.0</p>
                    <hr>
                    <p><strong>Desenvolvido em:</strong> 2025</p>
                    <p><strong>Tecnologias:</strong> PHP, MySQL, Bootstrap 5</p>
                    <p class="mb-0"><strong>Módulos:</strong> 9 módulos completos</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Configurar E-mail -->
    <div class="modal fade" id="modalConfigurarEmail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="configurar_email">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-envelope-open-text"></i> Configurações de E-mail SMTP</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Atenção:</strong> Configure o servidor SMTP para enviar e-mails de confirmação aos atletas e equipes.
                            Para Gmail, use: smtp.gmail.com (porta 587) e ative "Acesso a apps menos seguros" ou use senha de app.
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Servidor SMTP</label>
                                <input type="text" name="smtp_host" class="form-control"
                                    value="<?php echo $configEmail['smtp_host'] ?? 'smtp.gmail.com'; ?>"
                                    placeholder="smtp.gmail.com" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Porta</label>
                                <input type="number" name="smtp_port" class="form-control"
                                    value="<?php echo $configEmail['smtp_port'] ?? 587; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Usuário/E-mail SMTP</label>
                            <input type="text" name="smtp_username" class="form-control"
                                value="<?php echo $configEmail['smtp_username'] ?? ''; ?>"
                                placeholder="seu-email@gmail.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha SMTP</label>
                            <input type="password" name="smtp_password" class="form-control"
                                value="<?php echo $configEmail['smtp_password'] ?? ''; ?>"
                                placeholder="Senha ou senha de app" required>
                            <small class="text-muted">Para Gmail, recomenda-se usar senha de app</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Criptografia</label>
                            <select name="smtp_secure" class="form-select" required>
                                <option value="tls" <?php echo (!$configEmail || $configEmail['smtp_secure'] === 'tls') ? 'selected' : ''; ?>>TLS (Recomendado - Porta 587)</option>
                                <option value="ssl" <?php echo ($configEmail && $configEmail['smtp_secure'] === 'ssl') ? 'selected' : ''; ?>>SSL (Porta 465)</option>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">E-mail Remetente</label>
                            <input type="email" name="email_remetente" class="form-control"
                                value="<?php echo $configEmail['email_remetente'] ?? ''; ?>"
                                placeholder="noreply@exemplo.com" required>
                            <small class="text-muted">E-mail que aparecerá como remetente nas mensagens</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nome do Remetente</label>
                            <input type="text" name="nome_remetente" class="form-control"
                                value="<?php echo $configEmail['nome_remetente'] ?? 'Sistema de Competições'; ?>"
                                placeholder="Sistema de Competições" required>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ativo" id="emailAtivo"
                                <?php echo (!$configEmail || $configEmail['ativo']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="emailAtivo">
                                Sistema de e-mail ativo
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Testar E-mail -->
    <div class="modal fade" id="modalTestarEmail" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="testar_email">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Enviar E-mail de Teste</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Um e-mail de teste será enviado para verificar se as configurações estão corretas.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail de Destino</label>
                            <input type="email" name="email_teste" class="form-control"
                                placeholder="seu-email@exemplo.com" required>
                            <small class="text-muted">Digite o e-mail onde deseja receber o teste</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane"></i> Enviar E-mail de Teste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarAdmin(admin) {
            document.getElementById('edit_admin_id').value = admin.id;
            document.getElementById('edit_nome').value = admin.nome;
            document.getElementById('edit_nivel').value = admin.nivel;
            document.getElementById('edit_ativo').value = admin.ativo ? '1' : '0';

            new bootstrap.Modal(document.getElementById('modalEditarAdmin')).show();
        }

        function alterarSenha(adminId, adminNome) {
            document.getElementById('senha_admin_id').value = adminId;
            document.getElementById('senha_admin_nome').textContent = adminNome;

            new bootstrap.Modal(document.getElementById('modalAlterarSenha')).show();
        }
    </script>
</body>
</html>
