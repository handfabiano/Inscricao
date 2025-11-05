<?php
require_once '../config/config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("UPDATE inscricoes SET status = 'Aprovada' WHERE id = ? AND status = 'Pendente'");
$stmt->execute([$id]);

// Log da ação
$stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, descricao, ip) VALUES (?, ?, ?, ?)");
$stmt->execute([
    $_SESSION['user_id'],
    'Aprovação de Inscrição',
    "Inscrição ID: $id aprovada",
    $_SERVER['REMOTE_ADDR']
]);

header('Location: visualizar.php?id=' . $id . '&msg=aprovado');
exit;
?>
