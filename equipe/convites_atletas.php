<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];

// Processar ações
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'gerar_convite') {
            // Gerar token único
            $token = bin2hex(random_bytes(32));
            $emailAtleta = $_POST['email_atleta'] ?? null;
            $nomeAtleta = $_POST['nome_atleta'] ?? null;
            $diasValidade = (int)($_POST['dias_validade'] ?? 7);

            $validadeAte = date('Y-m-d H:i:s', strtotime("+$diasValidade days"));

            $stmt = $pdo->prepare("
                INSERT INTO convites_atletas (equipe_id, token, email_atleta, nome_atleta, data_expiracao)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$equipeId, $token, $emailAtleta, $nomeAtleta, $validadeAte]);

            $mensagem = "Convite gerado com sucesso! Copie o link abaixo e envie para o atleta.";
            $tipoMensagem = "success";
            $ultimoToken = $token;

        } elseif ($acao === 'cancelar_convite') {
            $conviteId = $_POST['convite_id'];

            $stmt = $pdo->prepare("
                UPDATE convites_atletas
                SET status = 'Expirado'
                WHERE id = ? AND equipe_id = ?
            ");
            $stmt->execute([$conviteId, $equipeId]);

            $mensagem = "Convite cancelado com sucesso!";
            $tipoMensagem = "info";
        }

    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// Buscar convites da equipe
$stmt = $pdo->prepare("
    SELECT * FROM convites_atletas
    WHERE equipe_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$equipeId]);
$convites = $stmt->fetchAll();

// Buscar informações da equipe
$stmt = $pdo->prepare("SELECT nome FROM equipes WHERE id = ?");
$stmt->execute([$equipeId]);
$equipe = $stmt->fetch();

$pageTitle = 'Convites para Atletas';
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
        .convite-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .convite-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .link-convite {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-users"></i> Painel da Equipe
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
                        <a class="nav-link active" href="convites_atletas.php">
                            <i class="fas fa-user-plus"></i> Convidar Atletas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="meus_atletas.php">
                            <i class="fas fa-running"></i> Meus Atletas
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

    <div class="container mt-4 mb-5">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="mb-2">
                            <i class="fas fa-user-plus text-primary"></i>
                            Convidar Atletas para <?php echo htmlspecialchars($equipe['nome']); ?>
                        </h2>
                        <p class="text-muted mb-0">Gere links de convite para que atletas possam se cadastrar em sua equipe</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensagem); ?>
            <?php if (isset($ultimoToken)): ?>
                <hr>
                <p class="mb-2"><strong>Link do convite:</strong></p>
                <div class="link-convite mb-2" id="linkConvite">
                    <?php
                    // Obter o protocolo
                    $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

                    // Obter o host
                    $host = $_SERVER['HTTP_HOST'];

                    // Obter o caminho base (remove /equipe/convites_atletas.php)
                    $scriptName = $_SERVER['SCRIPT_NAME']; // Ex: /Inscricao/equipe/convites_atletas.php
                    $basePath = str_replace('/equipe/convites_atletas.php', '', $scriptName);

                    // Construir URL completa
                    $linkConvite = $protocolo . "://" . $host . $basePath . "/publico/cadastro_atleta.php?token=" . $ultimoToken;
                    echo htmlspecialchars($linkConvite);
                    ?>
                </div>
                <button class="btn btn-sm btn-success" onclick="copiarLink()">
                    <i class="fas fa-copy"></i> Copiar Link
                </button>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Formulário de Novo Convite -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle"></i>
                            Gerar Novo Convite
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="acao" value="gerar_convite">

                            <div class="mb-3">
                                <label class="form-label">Nome do Atleta (Opcional)</label>
                                <input type="text" name="nome_atleta" class="form-control" placeholder="Ex: João Silva">
                                <small class="text-muted">Ajuda a identificar o convite</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email do Atleta (Opcional)</label>
                                <input type="email" name="email_atleta" class="form-control" placeholder="atleta@email.com">
                                <small class="text-muted">Para referência futura</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Validade do Convite</label>
                                <select name="dias_validade" class="form-select">
                                    <option value="1">1 dia</option>
                                    <option value="3">3 dias</option>
                                    <option value="7" selected>7 dias (1 semana)</option>
                                    <option value="15">15 dias</option>
                                    <option value="30">30 dias (1 mês)</option>
                                    <option value="90">90 dias (3 meses)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-link"></i> Gerar Link de Convite
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Instruções -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            Como Funciona?
                        </h6>
                    </div>
                    <div class="card-body">
                        <ol class="small mb-0">
                            <li>Gere um link de convite</li>
                            <li>Envie o link para o atleta (WhatsApp, Email, etc)</li>
                            <li>O atleta preenche o formulário de cadastro</li>
                            <li>Ao finalizar, o atleta fica vinculado à sua equipe automaticamente</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Lista de Convites -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Convites Gerados
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($convites)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Nenhum convite gerado ainda. Crie seu primeiro convite ao lado!
                            </div>
                        <?php else: ?>
                            <?php foreach ($convites as $convite): ?>
                            <div class="convite-card card mb-3" style="border-left-color: <?php
                                echo $convite['status'] === 'Aceito' ? '#198754' :
                                    ($convite['status'] === 'Expirado' || strtotime($convite['data_expiracao']) < time() ? '#dc3545' : '#ffc107');
                            ?>;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <?php if ($convite['nome_atleta']): ?>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($convite['nome_atleta']); ?>
                                                </h6>
                                            <?php else: ?>
                                                <h6 class="mb-1 text-muted">
                                                    <i class="fas fa-user"></i>
                                                    Atleta não especificado
                                                </h6>
                                            <?php endif; ?>

                                            <?php if ($convite['email_atleta']): ?>
                                                <p class="mb-1 small text-muted">
                                                    <i class="fas fa-envelope"></i>
                                                    <?php echo htmlspecialchars($convite['email_atleta']); ?>
                                                </p>
                                            <?php endif; ?>

                                            <p class="mb-1 small">
                                                <i class="fas fa-calendar"></i>
                                                Criado: <?php echo date('d/m/Y H:i', strtotime($convite['created_at'])); ?>
                                            </p>
                                            <p class="mb-1 small">
                                                <i class="fas fa-clock"></i>
                                                Válido até: <?php echo date('d/m/Y H:i', strtotime($convite['data_expiracao'])); ?>
                                            </p>

                                            <?php
                                            $statusClass = match($convite['status']) {
                                                'Aceito' => 'success',
                                                'Expirado' => 'danger',
                                                default => (strtotime($convite['data_expiracao']) < time() ? 'danger' : 'warning')
                                            };
                                            $statusText = $convite['status'];
                                            if ($convite['status'] === 'Pendente' && strtotime($convite['data_expiracao']) < time()) {
                                                $statusText = 'Expirado';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>

                                            <?php if ($convite['status'] === 'Aceito' && !empty($convite['data_aceite'])): ?>
                                                <p class="mb-0 small text-success mt-1">
                                                    <i class="fas fa-check-circle"></i>
                                                    Usado em: <?php echo date('d/m/Y H:i', strtotime($convite['data_aceite'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php if ($convite['status'] === 'Pendente' && strtotime($convite['data_expiracao']) > time()): ?>
                                                <?php
                                                // Obter o protocolo
                                                $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                                                // Obter o host
                                                $host = $_SERVER['HTTP_HOST'];
                                                // Obter o caminho base
                                                $scriptName = $_SERVER['SCRIPT_NAME'];
                                                $basePath = str_replace('/equipe/convites_atletas.php', '', $scriptName);
                                                // Construir URL completa
                                                $linkConvite = $protocolo . "://" . $host . $basePath . "/publico/cadastro_atleta.php?token=" . $convite['token'];
                                                ?>
                                                <button class="btn btn-sm btn-success mb-1 w-100" onclick="copiarLinkParam('<?php echo htmlspecialchars($linkConvite); ?>')">
                                                    <i class="fas fa-copy"></i> Copiar Link
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="acao" value="cancelar_convite">
                                                    <input type="hidden" name="convite_id" value="<?php echo $convite['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger w-100" onclick="return confirm('Deseja realmente cancelar este convite?')">
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
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
        // Função para copiar link do elemento #linkConvite
        function copiarLink() {
            const linkElement = document.getElementById('linkConvite');
            if (linkElement) {
                const link = linkElement.textContent.trim();
                copiarTexto(link);
            }
        }

        // Função para copiar link recebido como parâmetro
        function copiarLinkParam(link) {
            copiarTexto(link);
        }

        // Função auxiliar para copiar texto
        function copiarTexto(texto) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(texto).then(function() {
                    alert('Link copiado para a área de transferência!');
                }, function(err) {
                    // Fallback para método antigo
                    usarFallback(texto);
                });
            } else {
                usarFallback(texto);
            }
        }

        // Fallback para navegadores que não suportam clipboard API
        function usarFallback(texto) {
            const textArea = document.createElement('textarea');
            textArea.value = texto;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Link copiado para a área de transferência!');
            } catch (err) {
                prompt('Copie o link abaixo:', texto);
            }
            document.body.removeChild(textArea);
        }
    </script>
</body>
</html>
