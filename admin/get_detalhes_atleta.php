<?php
/**
 * API para buscar detalhes de um atleta
 */

require_once '../config/config.php';
requireAdminLogin();

header('Content-Type: application/json');

$atletaId = (int)($_GET['atleta_id'] ?? 0);

if (!$atletaId) {
    echo json_encode(['erro' => 'ID de atleta inválido']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Buscar dados do atleta
    $stmt = $pdo->prepare("
        SELECT
            a.*,
            e.nome as equipe_nome,
            TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
        FROM atletas a
        LEFT JOIN equipes e ON a.equipe_atual_id = e.id
        WHERE a.id = ?
    ");
    $stmt->execute([$atletaId]);
    $atleta = $stmt->fetch();

    if (!$atleta) {
        echo json_encode(['erro' => 'Atleta não encontrado']);
        exit;
    }

    // Formatar dados
    $atleta['cpf'] = formatarCPF($atleta['cpf']);
    $atleta['data_nascimento'] = formatarData($atleta['data_nascimento']);
    $atleta['ativo'] = (bool)$atleta['ativo'];

    echo json_encode([
        'atleta' => $atleta
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
}
?>
