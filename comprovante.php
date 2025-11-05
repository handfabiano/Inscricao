<?php
require_once 'config/config.php';

$protocolo = $_GET['protocolo'] ?? '';

if (empty($protocolo)) {
    die('Protocolo não informado');
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT i.*, m.nome as modalidade_nome, c.nome as categoria_nome
    FROM inscricoes i
    LEFT JOIN modalidades m ON i.modalidade_id = m.id
    LEFT JOIN categorias c ON i.categoria_id = c.id
    WHERE i.protocolo = ?
");
$stmt->execute([$protocolo]);
$inscricao = $stmt->fetch();

if (!$inscricao) {
    die('Inscrição não encontrada');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Inscrição - <?php echo $protocolo; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: white;
        }

        .comprovante {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #2563eb;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header h2 {
            color: #1e293b;
            font-size: 20px;
        }

        .protocolo-box {
            background: #2563eb;
            color: white;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }

        .protocolo-box h3 {
            font-size: 28px;
            letter-spacing: 2px;
            margin-top: 10px;
        }

        .section {
            margin: 20px 0;
        }

        .section h4 {
            color: #2563eb;
            font-size: 16px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            padding: 10px;
            background: #f8fafc;
            border-radius: 4px;
        }

        .info-item strong {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .info-item span {
            color: #1e293b;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .status-pendente { background: #fef3c7; color: #92400e; }
        .status-aprovada { background: #d1fae5; color: #065f46; }
        .status-rejeitada { background: #fee2e2; color: #991b1b; }
        .status-cancelada { background: #dbeafe; color: #1e40af; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .qr-info {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .print-button:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">Imprimir / Salvar PDF</button>

    <div class="comprovante">
        <div class="header">
            <h1>COMPROVANTE DE INSCRIÇÃO</h1>
            <h2>Sistema de Inscrição de Atletas</h2>
        </div>

        <div class="protocolo-box">
            <p style="margin: 0; font-size: 14px;">Protocolo de Inscrição:</p>
            <h3><?php echo htmlspecialchars($protocolo); ?></h3>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <?php
            $statusClass = [
                'Pendente' => 'status-pendente',
                'Aprovada' => 'status-aprovada',
                'Rejeitada' => 'status-rejeitada',
                'Cancelada' => 'status-cancelada'
            ];
            $class = $statusClass[$inscricao['status']] ?? 'status-pendente';
            ?>
            <span class="status-badge <?php echo $class; ?>">
                Status: <?php echo strtoupper($inscricao['status']); ?>
            </span>
        </div>

        <div class="section">
            <h4>DADOS PESSOAIS</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nome Completo:</strong>
                    <span><?php echo htmlspecialchars($inscricao['nome_completo']); ?></span>
                </div>
                <div class="info-item">
                    <strong>CPF:</strong>
                    <span><?php echo formatCPF($inscricao['cpf']); ?></span>
                </div>
                <div class="info-item">
                    <strong>RG:</strong>
                    <span><?php echo htmlspecialchars($inscricao['rg']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Data de Nascimento:</strong>
                    <span><?php echo date('d/m/Y', strtotime($inscricao['data_nascimento'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Gênero:</strong>
                    <span><?php echo htmlspecialchars($inscricao['genero']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($inscricao['email']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Telefone:</strong>
                    <span><?php echo formatPhone($inscricao['telefone']); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <h4>ENDEREÇO</h4>
            <div class="info-item" style="grid-column: 1 / -1;">
                <span>
                    <?php
                    echo htmlspecialchars($inscricao['endereco']) . ', ' .
                         htmlspecialchars($inscricao['numero']);
                    if ($inscricao['complemento']) {
                        echo ' - ' . htmlspecialchars($inscricao['complemento']);
                    }
                    echo '<br>' . htmlspecialchars($inscricao['bairro']) . '<br>' .
                         htmlspecialchars($inscricao['cidade']) . ' - ' .
                         htmlspecialchars($inscricao['estado']) . '<br>' .
                         'CEP: ' . preg_replace('/(\d{5})(\d{3})/', '$1-$2', $inscricao['cep']);
                    ?>
                </span>
            </div>
        </div>

        <div class="section">
            <h4>DADOS ESPORTIVOS</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Modalidade:</strong>
                    <span><?php echo htmlspecialchars($inscricao['modalidade_nome']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Categoria:</strong>
                    <span><?php echo htmlspecialchars($inscricao['categoria_nome']); ?></span>
                </div>
                <?php if ($inscricao['experiencia_anos']): ?>
                <div class="info-item">
                    <strong>Anos de Experiência:</strong>
                    <span><?php echo $inscricao['experiencia_anos']; ?> anos</span>
                </div>
                <?php endif; ?>
                <?php if ($inscricao['clube_anterior']): ?>
                <div class="info-item">
                    <strong>Clube/Equipe Anterior:</strong>
                    <span><?php echo htmlspecialchars($inscricao['clube_anterior']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($inscricao['responsavel_nome']): ?>
        <div class="section">
            <h4>DADOS DO RESPONSÁVEL</h4>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nome:</strong>
                    <span><?php echo htmlspecialchars($inscricao['responsavel_nome']); ?></span>
                </div>
                <div class="info-item">
                    <strong>CPF:</strong>
                    <span><?php echo formatCPF($inscricao['responsavel_cpf']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Telefone:</strong>
                    <span><?php echo formatPhone($inscricao['responsavel_telefone']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Parentesco:</strong>
                    <span><?php echo htmlspecialchars($inscricao['responsavel_parentesco']); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="qr-info">
            <p><strong>Data de Inscrição:</strong> <?php echo date('d/m/Y H:i', strtotime($inscricao['created_at'])); ?></p>
            <p><strong>Última Atualização:</strong> <?php echo date('d/m/Y H:i', strtotime($inscricao['updated_at'])); ?></p>
        </div>

        <div class="footer">
            <p>Este documento é um comprovante oficial de inscrição.</p>
            <p>Para verificar a autenticidade, acesse o site e consulte o protocolo.</p>
            <p style="margin-top: 15px;">
                <strong>Sistema de Inscrição de Atletas</strong><br>
                Documento gerado em: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
