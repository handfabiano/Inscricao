<?php
/**
 * Versão de debug de inscricoes.php
 * Com exibição de erros ativada
 */

// Ativar TODOS os erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DEBUG MODE ATIVADO -->\n";

require_once '../config/config.php';
requireAdminLogin();

echo "<!-- Admin autenticado: " . $_SESSION['admin_nome'] . " -->\n";

$pdo = getDBConnection();

echo "<!-- Conexão com banco OK -->\n";

// Processar aprovação/rejeição de inscrição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $inscricaoId = (int)$_POST['inscricao_id'];
    $acao = $_POST['acao'];

    try {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare("UPDATE inscricoes_competicoes SET status = 'Confirmada' WHERE id = ?");
            $stmt->execute([$inscricaoId]);
            redirect('inscricoes_debug.php', 'Inscrição aprovada com sucesso!', 'success');
        } elseif ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE inscricoes_competicoes SET status = 'Cancelada' WHERE id = ?");
            $stmt->execute([$inscricaoId]);
            redirect('inscricoes_debug.php', 'Inscrição rejeitada com sucesso!', 'success');
        }
    } catch (Exception $e) {
        redirect('inscricoes_debug.php', 'Erro ao processar inscrição: ' . $e->getMessage(), 'error');
    }
}

// Filtros
$filtroCompeticao = $_GET['competicao'] ?? '';
$filtroEquipe = $_GET['equipe'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

echo "<!-- Filtros carregados -->\n";

// Buscar estatísticas
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Pendente'");
    $totalPendentes = $stmt->fetch()['total'];
    echo "<!-- Pendentes: $totalPendentes -->\n";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Confirmada'");
    $totalConfirmadas = $stmt->fetch()['total'];
    echo "<!-- Confirmadas: $totalConfirmadas -->\n";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_competicoes WHERE status = 'Cancelada'");
    $totalCanceladas = $stmt->fetch()['total'];
    echo "<!-- Canceladas: $totalCanceladas -->\n";
} catch (Exception $e) {
    die("ERRO ao buscar estatísticas: " . $e->getMessage());
}

// Construir query de inscrições com filtros
$sql = "
    SELECT
        i.*,
        c.nome as competicao_nome,
        c.status as competicao_status,
        e.nome as equipe_nome,
        e.municipio as equipe_municipio,
        m.nome as modalidade_nome,
        (SELECT COUNT(*) FROM inscricoes_atletas WHERE inscricao_competicao_id = i.id) as total_atletas
    FROM inscricoes_competicoes i
    INNER JOIN competicoes c ON i.competicao_id = c.id
    INNER JOIN equipes e ON i.equipe_id = e.id
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE 1=1
";

$params = [];

if (!empty($filtroCompeticao)) {
    $sql .= " AND i.competicao_id = ?";
    $params[] = $filtroCompeticao;
}

if (!empty($filtroEquipe)) {
    $sql .= " AND e.nome LIKE ?";
    $params[] = "%$filtroEquipe%";
}

if (!empty($filtroStatus)) {
    $sql .= " AND i.status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY i.data_inscricao DESC";

echo "<!-- Query construída -->\n";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inscricoes = $stmt->fetchAll();
    echo "<!-- Inscrições encontradas: " . count($inscricoes) . " -->\n";
} catch (Exception $e) {
    die("ERRO na query principal: " . $e->getMessage() . "<br><br>SQL: $sql");
}

// Buscar competições para filtro
try {
    $competicoes = $pdo->query("SELECT id, nome FROM competicoes ORDER BY created_at DESC")->fetchAll();
    echo "<!-- Competições encontradas: " . count($competicoes) . " -->\n";
} catch (Exception $e) {
    die("ERRO ao buscar competições: " . $e->getMessage());
}

$pageTitle = 'Gerenciar Inscrições';

echo "<!-- Todas as queries executadas com sucesso! -->\n";
echo "<!-- Iniciando HTML -->\n";
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
        .foto-circular {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .foto-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .card-stat {
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-info">
            <strong>DEBUG MODE:</strong> Esta é uma versão de diagnóstico do inscricoes.php
        </div>

        <h2>Gerenciar Inscrições</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?php echo $totalPendentes; ?></h3>
                        <p>Pendentes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo $totalConfirmadas; ?></h3>
                        <p>Confirmadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger"><?php echo $totalCanceladas; ?></h3>
                        <p>Canceladas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Inscrições (<?php echo count($inscricoes); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($inscricoes)): ?>
                    <p class="text-muted text-center">Nenhuma inscrição encontrada.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Protocolo</th>
                                <th>Competição</th>
                                <th>Equipe</th>
                                <th>Status</th>
                                <th>Atletas</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $insc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($insc['protocolo']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['competicao_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['equipe_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($insc['status']); ?></td>
                                    <td><?php echo $insc['total_atletas']; ?></td>
                                    <td><?php echo formatarDataHora($insc['data_inscricao']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="alert alert-success mt-4">
            ✅ Página carregada com sucesso! Se você vê esta mensagem, significa que o código PHP está funcionando.
        </div>
    </div>
</body>
</html>
