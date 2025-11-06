<?php
require_once '../config/config.php';
requireEquipeLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['erro' => 'ID não fornecido']);
    exit;
}

$pdo = getDBConnection();

// Buscar atleta (validar que pertence à equipe logada)
$stmt = $pdo->prepare("
    SELECT * FROM atletas
    WHERE id = ? AND equipe_atual_id = ? AND ativo = 1
");
$stmt->execute([$_GET['id'], $_SESSION['equipe_id']]);
$atleta = $stmt->fetch();

if (!$atleta) {
    echo json_encode(['erro' => 'Atleta não encontrado']);
    exit;
}

// Formatar dados
$atleta['cpf_formatado'] = formatarCPF($atleta['cpf']);
$atleta['data_nascimento_formatada'] = formatarData($atleta['data_nascimento']);
$atleta['idade'] = calcularIdade($atleta['data_nascimento']);

if ($atleta['telefone']) {
    $atleta['telefone_formatado'] = formatarTelefone($atleta['telefone']);
}

if ($atleta['celular']) {
    $atleta['celular_formatado'] = formatarTelefone($atleta['celular']);
}

if ($atleta['responsavel_legal_cpf']) {
    $atleta['responsavel_legal_cpf_formatado'] = formatarCPF($atleta['responsavel_legal_cpf']);
}

if ($atleta['responsavel_legal_telefone']) {
    $atleta['responsavel_legal_telefone_formatado'] = formatarTelefone($atleta['responsavel_legal_telefone']);
}

echo json_encode($atleta);
?>
