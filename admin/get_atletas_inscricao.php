<?php
/**
 * API para buscar atletas de uma inscrição
 * Retorna JSON com dados da competição, equipe e atletas
 */

require_once '../config/config.php';
requireAdminLogin();

header('Content-Type: application/json');

$inscricaoId = (int)($_GET['id'] ?? 0);

if (!$inscricaoId) {
    echo json_encode(['erro' => 'ID de inscrição inválido']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Buscar dados da inscrição
    $stmt = $pdo->prepare("
        SELECT
            i.*,
            c.nome as competicao_nome,
            c.banner_path as competicao_banner,
            e.nome as equipe_nome,
            e.responsavel_nome as equipe_responsavel,
            m.nome as modalidade_nome
        FROM inscricoes_competicoes i
        INNER JOIN competicoes c ON i.competicao_id = c.id
        INNER JOIN equipes e ON i.equipe_id = e.id
        LEFT JOIN modalidades m ON c.modalidade_id = m.id
        WHERE i.id = ?
    ");
    $stmt->execute([$inscricaoId]);
    $inscricao = $stmt->fetch();

    if (!$inscricao) {
        echo json_encode(['erro' => 'Inscrição não encontrada']);
        exit;
    }

    // Buscar atletas da inscrição
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.nome,
            a.cpf,
            a.genero,
            a.data_nascimento,
            a.foto_path,
            TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
        FROM inscricoes_atletas ia
        INNER JOIN atletas a ON ia.atleta_id = a.id
        WHERE ia.inscricao_competicao_id = ?
        ORDER BY a.nome
    ");
    $stmt->execute([$inscricaoId]);
    $atletas = $stmt->fetchAll();

    // Formatar CPF para exibição
    foreach ($atletas as &$atleta) {
        $atleta['cpf'] = formatarCPF($atleta['cpf']);
    }

    // Retornar dados
    echo json_encode([
        'competicao' => [
            'nome' => $inscricao['competicao_nome'],
            'modalidade' => $inscricao['modalidade_nome'] ?? 'N/A',
            'banner' => $inscricao['competicao_banner']
        ],
        'equipe' => [
            'nome' => $inscricao['equipe_nome'],
            'responsavel' => $inscricao['equipe_responsavel']
        ],
        'inscricao' => [
            'protocolo' => $inscricao['protocolo'],
            'status' => $inscricao['status'],
            'data_inscricao' => formatarDataHora($inscricao['created_at'])
        ],
        'atletas' => $atletas
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
