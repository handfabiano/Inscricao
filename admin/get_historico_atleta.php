<?php
/**
 * API para buscar histórico de competições de um atleta
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

    // Buscar competições do atleta
    $stmt = $pdo->prepare("
        SELECT
            c.nome as competicao_nome,
            e.nome as equipe_nome,
            ic.status,
            ic.created_at as data_inscricao
        FROM inscricoes_atletas ia
        INNER JOIN inscricoes_competicoes ic ON ia.inscricao_competicao_id = ic.id
        INNER JOIN competicoes c ON ic.competicao_id = c.id
        INNER JOIN equipes e ON ic.equipe_id = e.id
        WHERE ia.atleta_id = ?
        ORDER BY ic.created_at DESC
    ");
    $stmt->execute([$atletaId]);
    $competicoes = $stmt->fetchAll();

    // Formatar datas
    foreach ($competicoes as &$comp) {
        $comp['data_inscricao'] = formatarDataHora($comp['data_inscricao']);
    }

    echo json_encode([
        'competicoes' => $competicoes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao buscar histórico: ' . $e->getMessage()]);
}
?>
