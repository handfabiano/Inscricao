<?php
require_once '../config/config.php';
requireLogin();

$formato = $_GET['formato'] ?? 'csv';
$status = $_GET['status'] ?? '';
$modalidade = $_GET['modalidade'] ?? '';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

$pdo = getDBConnection();

// Construir query
$sql = "SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
        FROM inscricoes i
        LEFT JOIN modalidades m ON i.modalidade_id = m.id
        LEFT JOIN categorias c ON i.categoria_id = c.id
        WHERE 1=1";

$params = [];

if ($status) {
    $sql .= " AND i.status = ?";
    $params[] = $status;
}

if ($modalidade) {
    $sql .= " AND i.modalidade_id = ?";
    $params[] = $modalidade;
}

if ($dataInicio) {
    $sql .= " AND DATE(i.created_at) >= ?";
    $params[] = $dataInicio;
}

if ($dataFim) {
    $sql .= " AND DATE(i.created_at) <= ?";
    $params[] = $dataFim;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inscricoes = $stmt->fetchAll();

if ($formato === 'csv') {
    // Exportar CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inscricoes_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Cabeçalhos
    fputcsv($output, [
        'Protocolo',
        'Nome Completo',
        'CPF',
        'RG',
        'Data Nascimento',
        'Gênero',
        'Email',
        'Telefone',
        'Celular',
        'CEP',
        'Endereço',
        'Número',
        'Complemento',
        'Bairro',
        'Cidade',
        'Estado',
        'Modalidade',
        'Categoria',
        'Experiência (anos)',
        'Clube Anterior',
        'Responsável',
        'CPF Responsável',
        'Telefone Responsável',
        'Status',
        'Data Inscrição',
        'Observações'
    ], ';');

    // Dados
    foreach ($inscricoes as $insc) {
        fputcsv($output, [
            $insc['protocolo'],
            $insc['nome_completo'],
            formatCPF($insc['cpf']),
            $insc['rg'],
            date('d/m/Y', strtotime($insc['data_nascimento'])),
            $insc['genero'],
            $insc['email'],
            formatPhone($insc['telefone']),
            $insc['celular'] ? formatPhone($insc['celular']) : '',
            preg_replace('/(\d{5})(\d{3})/', '$1-$2', $insc['cep']),
            $insc['endereco'],
            $insc['numero'],
            $insc['complemento'],
            $insc['bairro'],
            $insc['cidade'],
            $insc['estado'],
            $insc['modalidade_nome'],
            $insc['categoria_nome'],
            $insc['experiencia_anos'],
            $insc['clube_anterior'],
            $insc['responsavel_nome'],
            $insc['responsavel_cpf'] ? formatCPF($insc['responsavel_cpf']) : '',
            $insc['responsavel_telefone'] ? formatPhone($insc['responsavel_telefone']) : '',
            $insc['status'],
            date('d/m/Y H:i', strtotime($insc['created_at'])),
            $insc['observacoes']
        ], ';');
    }

    fclose($output);
    exit;

} elseif ($formato === 'excel') {
    // Exportar Excel (formato HTML que o Excel entende)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=inscricoes_' . date('Y-m-d') . '.xls');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2563eb; color: white; font-weight: bold; }
    </style>';
    echo '</head>';
    echo '<body>';
    echo '<table>';

    // Cabeçalhos
    echo '<thead><tr>';
    echo '<th>Protocolo</th>';
    echo '<th>Nome Completo</th>';
    echo '<th>CPF</th>';
    echo '<th>Data Nascimento</th>';
    echo '<th>Email</th>';
    echo '<th>Telefone</th>';
    echo '<th>Modalidade</th>';
    echo '<th>Categoria</th>';
    echo '<th>Status</th>';
    echo '<th>Data Inscrição</th>';
    echo '</tr></thead>';

    echo '<tbody>';
    foreach ($inscricoes as $insc) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($insc['protocolo']) . '</td>';
        echo '<td>' . htmlspecialchars($insc['nome_completo']) . '</td>';
        echo '<td>' . formatCPF($insc['cpf']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($insc['data_nascimento'])) . '</td>';
        echo '<td>' . htmlspecialchars($insc['email']) . '</td>';
        echo '<td>' . formatPhone($insc['telefone']) . '</td>';
        echo '<td>' . htmlspecialchars($insc['modalidade_nome']) . '</td>';
        echo '<td>' . htmlspecialchars($insc['categoria_nome']) . '</td>';
        echo '<td>' . htmlspecialchars($insc['status']) . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($insc['created_at'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
    echo '</body>';
    echo '</html>';

    exit;
}
?>
