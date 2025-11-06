<?php
/**
 * API: Retorna detalhes de uma inscrição
 */

require_once __DIR__ . '/../config/config.php';
requireEquipeLogin();

header('Content-Type: application/json');

$inscricaoId = $_GET['id'] ?? null;
$equipeId = $_SESSION['equipe_id'];

if (!$inscricaoId) {
    echo json_encode(['erro' => 'ID da inscrição não informado']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Buscar inscrição (validar que pertence à equipe logada)
    $stmt = $pdo->prepare("
        SELECT
            ic.*,
            c.nome as competicao_nome,
            c.data_inicio_evento,
            c.data_fim_evento,
            m.nome as modalidade_nome
        FROM inscricoes_competicoes ic
        INNER JOIN competicoes c ON ic.competicao_id = c.id
        LEFT JOIN modalidades m ON c.modalidade_id = m.id
        WHERE ic.id = ? AND ic.equipe_id = ?
    ");
    $stmt->execute([$inscricaoId, $equipeId]);
    $inscricao = $stmt->fetch();

    if (!$inscricao) {
        echo json_encode(['erro' => 'Inscrição não encontrada']);
        exit;
    }

    // Buscar atletas da inscrição
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.nome_completo,
            a.data_nascimento,
            a.genero,
            a.foto_path
        FROM inscricoes_atletas ia
        INNER JOIN atletas a ON ia.atleta_id = a.id
        WHERE ia.inscricao_competicao_id = ?
        ORDER BY a.nome_completo
    ");
    $stmt->execute([$inscricaoId]);
    $atletas = $stmt->fetchAll();

    // Formatar dados dos atletas
    $atletasFormatados = [];
    foreach ($atletas as $atleta) {
        $atletasFormatados[] = [
            'id' => $atleta['id'],
            'nome' => $atleta['nome_completo'],
            'idade' => calcularIdade($atleta['data_nascimento']),
            'genero' => $atleta['genero'],
            'foto_path' => $atleta['foto_path'] ? FOTO_URL . $atleta['foto_path'] : null
        ];
    }

    // Definir classe do status
    $statusClass = 'secondary';
    if ($inscricao['status'] === 'Pendente') {
        $statusClass = 'warning';
    } elseif ($inscricao['status'] === 'Confirmada') {
        $statusClass = 'success';
    } elseif ($inscricao['status'] === 'Cancelada') {
        $statusClass = 'danger';
    }

    // Montar resposta
    $resposta = [
        'inscricao' => [
            'id' => $inscricao['id'],
            'protocolo' => $inscricao['protocolo'],
            'status' => $inscricao['status'],
            'status_class' => $statusClass,
            'data_inscricao' => formatarDataHora($inscricao['data_inscricao']),
            'observacoes' => $inscricao['observacoes']
        ],
        'competicao' => [
            'id' => $inscricao['competicao_id'],
            'nome' => $inscricao['competicao_nome'],
            'modalidade' => $inscricao['modalidade_nome'],
            'data_inicio' => $inscricao['data_inicio_evento'] ? formatarData($inscricao['data_inicio_evento']) : null,
            'data_fim' => $inscricao['data_fim_evento'] ? formatarData($inscricao['data_fim_evento']) : null
        ],
        'atletas' => $atletasFormatados
    ];

    echo json_encode($resposta);

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao carregar detalhes: ' . $e->getMessage()]);
}
?>
