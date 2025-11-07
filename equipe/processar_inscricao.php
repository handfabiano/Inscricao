<?php
/**
 * Processa inscrição da equipe em competição
 */

require_once __DIR__ . '/../config/config.php';
requireEquipeLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('inscricoes.php', 'Método inválido', 'error');
}

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];
$competicaoId = $_POST['competicao_id'] ?? null;
$atletasIds = $_POST['atletas'] ?? [];

try {
    // Validações básicas
    if (empty($competicaoId)) {
        throw new Exception('Competição não informada');
    }

    if (empty($atletasIds) || !is_array($atletasIds)) {
        throw new Exception('Nenhum atleta selecionado');
    }

    // Buscar competição
    $stmt = $pdo->prepare("
        SELECT * FROM competicoes
        WHERE id = ? AND status = 'Aberta'
        AND data_inicio_inscricoes <= CURDATE()
        AND data_fim_inscricoes >= CURDATE()
    ");
    $stmt->execute([$competicaoId]);
    $competicao = $stmt->fetch();

    if (!$competicao) {
        throw new Exception('Competição não encontrada ou inscrições fechadas');
    }

    // Verificar se já está inscrito
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM inscricoes_competicoes
        WHERE competicao_id = ? AND equipe_id = ?
        AND status IN ('Pendente', 'Confirmada')
    ");
    $stmt->execute([$competicaoId, $equipeId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Equipe já inscrita nesta competição');
    }

    // Validar quantidade de atletas
    $qtdAtletas = count($atletasIds);
    if ($qtdAtletas < $competicao['min_atletas'] || $qtdAtletas > $competicao['max_atletas']) {
        throw new Exception("Quantidade de atletas deve ser entre {$competicao['min_atletas']} e {$competicao['max_atletas']}");
    }

    // Validar que todos os atletas pertencem à equipe
    $placeholders = implode(',', array_fill(0, count($atletasIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, nome_completo, genero, data_nascimento
        FROM atletas
        WHERE id IN ($placeholders)
        AND equipe_atual_id = ?
        AND ativo = 1
    ");
    $stmt->execute([...$atletasIds, $equipeId]);
    $atletas = $stmt->fetchAll();

    if (count($atletas) !== $qtdAtletas) {
        throw new Exception('Um ou mais atletas selecionados não pertencem à sua equipe ou estão inativos');
    }

    // Validar gênero se necessário
    if (!empty($competicao['genero_permitido']) && $competicao['genero_permitido'] !== 'Ambos') {
        foreach ($atletas as $atleta) {
            if ($atleta['genero'] !== $competicao['genero_permitido']) {
                throw new Exception("Atleta {$atleta['nome_completo']} não atende ao requisito de gênero ({$competicao['genero_permitido']})");
            }
        }
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Gerar protocolo único
    $protocolo = gerarProtocolo();

    // Inserir inscrição da equipe
    $stmt = $pdo->prepare("
        INSERT INTO inscricoes_competicoes
        (competicao_id, equipe_id, protocolo, status, created_at)
        VALUES (?, ?, ?, 'Pendente', NOW())
    ");
    $stmt->execute([$competicaoId, $equipeId, $protocolo]);
    $inscricaoId = $pdo->lastInsertId();

    // Inserir atletas da inscrição
    $stmt = $pdo->prepare("
        INSERT INTO inscricoes_atletas
        (inscricao_competicao_id, atleta_id, created_at)
        VALUES (?, ?, NOW())
    ");

    foreach ($atletasIds as $atletaId) {
        $stmt->execute([$inscricaoId, $atletaId]);
    }

    // Registrar histórico da equipe
    registrarHistoricoEquipe(
        $pdo,
        $equipeId,
        'Inscrição',
        "Equipe inscrita na competição: {$competicao['nome']} (Protocolo: {$protocolo})",
        [
            'competicao_id' => $competicaoId,
            'competicao_nome' => $competicao['nome'],
            'protocolo' => $protocolo,
            'qtd_atletas' => $qtdAtletas,
            'atletas_ids' => $atletasIds
        ]
    );

    // Registrar histórico de cada atleta
    foreach ($atletas as $atleta) {
        registrarHistoricoAtleta(
            $pdo,
            $atleta['id'],
            'Inscrição em Competição',
            "Inscrito na competição: {$competicao['nome']} (Protocolo: {$protocolo})",
            [
                'competicao_id' => $competicaoId,
                'competicao_nome' => $competicao['nome'],
                'protocolo' => $protocolo
            ]
        );
    }

    // Commit
    $pdo->commit();

    redirect(
        'minhas_inscricoes.php',
        "Inscrição realizada com sucesso! Protocolo: {$protocolo}",
        'success'
    );

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect(
        'inscricoes.php',
        'Erro ao processar inscrição: ' . $e->getMessage(),
        'error'
    );
}
?>
