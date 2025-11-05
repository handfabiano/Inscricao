<?php
require_once '../config/config.php';
require_once '../includes/email.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();

// Buscar dados da inscrição
$stmt = $pdo->prepare("
    SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
    FROM inscricoes i
    LEFT JOIN modalidades m ON i.modalidade_id = m.id
    LEFT JOIN categorias c ON i.categoria_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inscricao = $stmt->fetch();

if (!$inscricao || $inscricao['status'] !== 'Pendente') {
    header('Location: index.php');
    exit;
}

// Atualizar status
$stmt = $pdo->prepare("UPDATE inscricoes SET status = 'Rejeitada' WHERE id = ?");
$stmt->execute([$id]);

// Log da ação
$stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
$stmt->execute([
    $_SESSION['user_id'],
    'Rejeição de Inscrição',
    "Inscrição ID: $id rejeitada - Protocolo: {$inscricao['protocolo']}",
    $_SERVER['REMOTE_ADDR']
]);

// Enviar email de rejeição
try {
    $emailSystem = new EmailSystem();
    $emailSystem->enviarRejeicao($inscricao);
} catch (Exception $e) {
    error_log("Erro ao enviar email de rejeição: " . $e->getMessage());
}

header('Location: visualizar.php?id=' . $id . '&msg=rejeitado');
exit;
?>
