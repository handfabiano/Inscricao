<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();
$pageTitle = 'Central de Notificações';

// Processar envio de notificação
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'enviar_notificacao') {
            $destinatarios = $_POST['destinatarios'] ?? '';
            $assunto = $_POST['assunto'];
            $mensagemEmail = $_POST['mensagem'];
            $tipoDestinatario = $_POST['tipo_destinatario'];

            // Criar tabela de notificações se não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS notificacoes_email (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tipo_destinatario VARCHAR(50),
                    destinatarios TEXT,
                    assunto VARCHAR(255),
                    mensagem TEXT,
                    enviado_por INT,
                    total_enviados INT DEFAULT 0,
                    total_erros INT DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'Pendente',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (enviado_por) REFERENCES administradores(id)
                )
            ");

            // Registrar notificação
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes_email
                (tipo_destinatario, destinatarios, assunto, mensagem, enviado_por, status)
                VALUES (?, ?, ?, ?, ?, 'Processando')
            ");
            $stmt->execute([
                $tipoDestinatario,
                $destinatarios,
                $assunto,
                $mensagemEmail,
                $_SESSION['admin_id']
            ]);
            $notificacaoId = $pdo->lastInsertId();

            // Determinar lista de e-mails para envio
            $emailsEnviar = [];

            if ($tipoDestinatario === 'Todas as Equipes') {
                $stmt = $pdo->query("SELECT nome, email, responsavel_nome FROM equipes WHERE status = 'Aprovada' AND email != ''");
                $emailsEnviar = $stmt->fetchAll();
            } elseif ($tipoDestinatario === 'Equipe Específica') {
                $stmt = $pdo->prepare("SELECT nome, email, responsavel_nome FROM equipes WHERE email = ?");
                $stmt->execute([$destinatarios]);
                $resultado = $stmt->fetch();
                if ($resultado) {
                    $emailsEnviar = [$resultado];
                }
            } elseif ($tipoDestinatario === 'Equipes com Inscrição Pendente') {
                $stmt = $pdo->query("
                    SELECT DISTINCT e.nome, e.email, e.responsavel_nome
                    FROM equipes e
                    INNER JOIN inscricoes_competicoes i ON e.id = i.equipe_id
                    WHERE i.status = 'Pendente' AND e.email != ''
                ");
                $emailsEnviar = $stmt->fetchAll();
            }

            // Enviar e-mails
            require_once '../includes/email_helper.php';
            $totalEnviados = 0;
            $totalErros = 0;

            // Converter quebras de linha para HTML
            $corpoHtml = nl2br(htmlspecialchars($mensagemEmail));
            $corpoHtml = "
                <div style='font-family: Arial, sans-serif;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>Sistema de Competições Esportivas</h2>
                    </div>
                    <div style='padding: 30px; background-color: #f8f9fa;'>
                        <div style='background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                            {$corpoHtml}
                        </div>
                    </div>
                    <div style='background-color: #343a40; color: #adb5bd; padding: 20px; text-align: center; font-size: 12px;'>
                        <p style='margin: 0;'>&copy; 2025 Sistema de Gestão de Competições Esportivas</p>
                    </div>
                </div>
            ";

            foreach ($emailsEnviar as $equipe) {
                try {
                    $resultado = enviarEmail(
                        $equipe['email'],
                        $assunto,
                        $corpoHtml,
                        $equipe['responsavel_nome'] ?? $equipe['nome']
                    );

                    if ($resultado) {
                        $totalEnviados++;
                    } else {
                        $totalErros++;
                    }
                } catch (Exception $e) {
                    $totalErros++;
                    error_log("Erro ao enviar e-mail para {$equipe['email']}: " . $e->getMessage());
                }
            }

            // Atualizar status da notificação
            $statusFinal = $totalErros > 0 ? 'Enviado com Erros' : 'Enviado';
            if ($totalEnviados === 0 && $totalErros > 0) {
                $statusFinal = 'Erro';
            }

            $stmt = $pdo->prepare("
                UPDATE notificacoes_email
                SET status = ?, total_enviados = ?, total_erros = ?
                WHERE id = ?
            ");
            $stmt->execute([$statusFinal, $totalEnviados, $totalErros, $notificacaoId]);

            if ($totalEnviados > 0) {
                $mensagem = "Notificação enviada! Total: {$totalEnviados} e-mail(s) enviado(s)";
                if ($totalErros > 0) {
                    $mensagem .= " ({$totalErros} erro(s))";
                }
                $tipoMensagem = "success";
            } else {
                $mensagem = "Erro ao enviar notificações. Verifique as configurações de e-mail.";
                $tipoMensagem = "danger";
            }

        } elseif ($acao === 'marcar_lida') {
            $notificacaoId = $_POST['notificacao_id'];

            $stmt = $pdo->prepare("UPDATE notificacoes_email SET status = 'Enviado' WHERE id = ?");
            $stmt->execute([$notificacaoId]);

            $mensagem = "Status atualizado com sucesso!";
            $tipoMensagem = "info";
        }

    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Criar tabela de notificações se não existir
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notificacoes_email (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_destinatario VARCHAR(50),
            destinatarios TEXT,
            assunto VARCHAR(255),
            mensagem TEXT,
            enviado_por INT,
            status VARCHAR(20) DEFAULT 'Pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (Exception $e) {
    // Tabela já existe
}

// Buscar notificações recentes
$stmt = $pdo->query("
    SELECT n.*, a.nome as admin_nome
    FROM notificacoes_email n
    LEFT JOIN administradores a ON n.enviado_por = a.id
    ORDER BY n.created_at DESC
    LIMIT 50
");
$notificacoesRecentes = $stmt->fetchAll();

// Buscar equipes para notificação
$stmt = $pdo->query("SELECT id, nome, email FROM equipes WHERE status = 'Aprovada' ORDER BY nome");
$equipes = $stmt->fetchAll();

// Estatísticas
$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Processando', 'Agendado') THEN 1 ELSE 0 END) as agendadas,
        SUM(CASE WHEN status IN ('Enviado', 'Enviado com Erros') THEN 1 ELSE 0 END) as enviadas,
        SUM(CASE WHEN status = 'Erro' THEN 1 ELSE 0 END) as erros,
        COALESCE(SUM(total_enviados), 0) as total_emails_enviados,
        COALESCE(SUM(total_erros), 0) as total_emails_erros
    FROM notificacoes_email
");
$stats = $stmt->fetch();
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
        .notificacao-item {
            border-left: 3px solid #dee2e6;
            transition: all 0.2s;
        }
        .notificacao-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .template-btn {
            cursor: pointer;
            transition: all 0.2s;
        }
        .template-btn:hover {
            transform: translateX(5px);
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
                        <a class="nav-link active" href="notificacoes.php">
                            <i class="fas fa-bell"></i> Notificações
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
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="mb-0">
                                    <i class="fas fa-bell text-primary"></i>
                                    Central de Notificações
                                </h2>
                                <p class="text-muted mb-0">Envie notificações por e-mail para equipes e atletas</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaNotificacao">
                                    <i class="fas fa-plus"></i> Nova Notificação
                                </button>
                            </div>
                        </div>
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
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #0d6efd;">
                    <div class="card-body">
                        <h4 class="text-primary mb-1"><?php echo $stats['total'] ?? 0; ?></h4>
                        <p class="text-muted mb-0 small">Total de Notificações</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <h4 class="text-warning mb-1"><?php echo $stats['agendadas'] ?? 0; ?></h4>
                        <p class="text-muted mb-0 small">Agendadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #198754;">
                    <div class="card-body">
                        <h4 class="text-success mb-1"><?php echo $stats['enviadas'] ?? 0; ?></h4>
                        <p class="text-muted mb-0 small">Enviadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color: #dc3545;">
                    <div class="card-body">
                        <h4 class="text-danger mb-1"><?php echo $stats['erros'] ?? 0; ?></h4>
                        <p class="text-muted mb-0 small">Com Erro</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Histórico de Notificações -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-primary"></i>
                            Histórico de Notificações
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($notificacoesRecentes)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Nenhuma notificação enviada ainda. Clique em "Nova Notificação" para começar.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notificacoesRecentes as $notif): ?>
                            <div class="notificacao-item p-3 mb-3 border rounded">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-envelope text-primary"></i>
                                            <?php echo htmlspecialchars($notif['assunto']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> Enviado por: <?php echo htmlspecialchars($notif['admin_nome'] ?? 'N/A'); ?>
                                            <span class="ms-2">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                            </span>
                                        </small>
                                    </div>
                                    <div>
                                        <?php
                                        $badgeClass = match($notif['status']) {
                                            'Agendado', 'Processando' => 'bg-warning text-dark',
                                            'Enviado' => 'bg-success',
                                            'Enviado com Erros' => 'bg-info',
                                            'Erro' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($notif['status']); ?>
                                        </span>
                                        <?php if (isset($notif['total_enviados']) && $notif['total_enviados'] > 0): ?>
                                            <br><small class="text-muted"><?php echo $notif['total_enviados']; ?> enviado(s)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="mb-2"><strong>Tipo:</strong> <?php echo htmlspecialchars($notif['tipo_destinatario']); ?></p>
                                <p class="mb-2 text-muted small">
                                    <?php echo nl2br(htmlspecialchars(substr($notif['mensagem'], 0, 200))); ?>
                                    <?php if (strlen($notif['mensagem']) > 200) echo '...'; ?>
                                </p>
                                <button class="btn btn-sm btn-outline-primary" onclick="verDetalhes(<?php echo htmlspecialchars(json_encode($notif)); ?>)">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Templates e Atalhos -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group text-success"></i>
                            Templates Rápidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <div class="list-group-item template-btn" onclick="usarTemplate('inscricao_aprovada')">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Inscrição Aprovada</strong>
                                <p class="mb-0 small text-muted">Notificar equipe sobre aprovação</p>
                            </div>
                            <div class="list-group-item template-btn" onclick="usarTemplate('inscricao_rejeitada')">
                                <i class="fas fa-times-circle text-danger"></i>
                                <strong>Inscrição Rejeitada</strong>
                                <p class="mb-0 small text-muted">Notificar equipe sobre rejeição</p>
                            </div>
                            <div class="list-group-item template-btn" onclick="usarTemplate('nova_competicao')">
                                <i class="fas fa-trophy text-warning"></i>
                                <strong>Nova Competição</strong>
                                <p class="mb-0 small text-muted">Anunciar nova competição</p>
                            </div>
                            <div class="list-group-item template-btn" onclick="usarTemplate('lembrete')">
                                <i class="fas fa-bell text-info"></i>
                                <strong>Lembrete</strong>
                                <p class="mb-0 small text-muted">Lembrete de prazo ou evento</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-info bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-info"></i>
                            Informações
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">
                            <i class="fas fa-lightbulb text-warning"></i>
                            <strong>Dica:</strong> Use templates para economizar tempo!
                        </p>
                        <p class="small mb-2">
                            <i class="fas fa-users text-primary"></i>
                            <strong>Equipes cadastradas:</strong> <?php echo count($equipes); ?>
                        </p>
                        <p class="small mb-2">
                            <i class="fas fa-envelope text-success"></i>
                            <strong>E-mails enviados:</strong> <?php echo $stats['total_emails_enviados'] ?? 0; ?>
                        </p>
                        <p class="small mb-0">
                            <i class="fas fa-info-circle text-info"></i>
                            <strong>Nota:</strong> Configure SMTP em "Configurações" para ativar envio de e-mails.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nova Notificação -->
    <div class="modal fade" id="modalNovaNotificacao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="enviar_notificacao">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nova Notificação</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo de Destinatário</label>
                            <select name="tipo_destinatario" id="tipoDestinatario" class="form-select" required onchange="atualizarDestinatarios()">
                                <option value="">Selecione...</option>
                                <option value="Todas as Equipes">Todas as Equipes</option>
                                <option value="Equipe Específica">Equipe Específica</option>
                                <option value="Equipes com Inscrição Pendente">Equipes com Inscrição Pendente</option>
                            </select>
                        </div>
                        <div class="mb-3" id="divEquipeEspecifica" style="display: none;">
                            <label class="form-label">Selecionar Equipe</label>
                            <select name="destinatarios" id="selectEquipe" class="form-select">
                                <option value="">Selecione uma equipe...</option>
                                <?php foreach ($equipes as $equipe): ?>
                                    <option value="<?php echo htmlspecialchars($equipe['email']); ?>">
                                        <?php echo htmlspecialchars($equipe['nome']); ?> - <?php echo htmlspecialchars($equipe['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assunto</label>
                            <input type="text" name="assunto" id="assuntoNotificacao" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensagem</label>
                            <textarea name="mensagem" id="mensagemNotificacao" class="form-control" rows="8" required></textarea>
                            <small class="text-muted">Dica: Use templates rápidos para economizar tempo</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar Notificação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Detalhes da Notificação -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detalhes da Notificação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="conteudoDetalhes"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
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
        function atualizarDestinatarios() {
            const tipo = document.getElementById('tipoDestinatario').value;
            const divEquipe = document.getElementById('divEquipeEspecifica');

            if (tipo === 'Equipe Específica') {
                divEquipe.style.display = 'block';
                document.getElementById('selectEquipe').required = true;
            } else {
                divEquipe.style.display = 'none';
                document.getElementById('selectEquipe').required = false;
            }
        }

        function usarTemplate(tipo) {
            const templates = {
                'inscricao_aprovada': {
                    assunto: 'Inscrição Aprovada - Sistema de Competições',
                    mensagem: `Prezada Equipe,\n\nTemos o prazer de informar que sua inscrição foi APROVADA!\n\nVocê já pode acessar o sistema e verificar os detalhes da competição.\n\nBoa sorte!\n\nAtenciosamente,\nEquipe Administrativa`
                },
                'inscricao_rejeitada': {
                    assunto: 'Inscrição Não Aprovada - Sistema de Competições',
                    mensagem: `Prezada Equipe,\n\nInfelizmente sua inscrição não foi aprovada.\n\nMotivo: [INFORMAR MOTIVO]\n\nVocê pode entrar em contato conosco para mais informações.\n\nAtenciosamente,\nEquipe Administrativa`
                },
                'nova_competicao': {
                    assunto: 'Nova Competição Disponível!',
                    mensagem: `Prezada Equipe,\n\nUma nova competição está disponível para inscrições!\n\nCompetição: [NOME DA COMPETIÇÃO]\nData: [DATA]\nModalidade: [MODALIDADE]\n\nNão perca essa oportunidade!\n\nAtenciosamente,\nEquipe Administrativa`
                },
                'lembrete': {
                    assunto: 'Lembrete - Sistema de Competições',
                    mensagem: `Prezada Equipe,\n\nEste é um lembrete sobre [ASSUNTO].\n\nPrazo: [DATA/HORA]\n\nNão se esqueça!\n\nAtenciosamente,\nEquipe Administrativa`
                }
            };

            const template = templates[tipo];
            if (template) {
                document.getElementById('assuntoNotificacao').value = template.assunto;
                document.getElementById('mensagemNotificacao').value = template.mensagem;
                new bootstrap.Modal(document.getElementById('modalNovaNotificacao')).show();
            }
        }

        function verDetalhes(notif) {
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Assunto:</strong><br>${notif.assunto}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong><br><span class="badge bg-info">${notif.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tipo:</strong><br>${notif.tipo_destinatario}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Criado em:</strong><br>${new Date(notif.created_at).toLocaleString('pt-BR')}</p>
                    </div>
                    <div class="col-12">
                        <p><strong>Mensagem:</strong></p>
                        <div class="alert alert-light">
                            ${notif.mensagem.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('conteudoDetalhes').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        }
    </script>
</body>
</html>
