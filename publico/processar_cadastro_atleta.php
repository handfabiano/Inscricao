<?php
require_once '../config/config.php';

$pdo = getDBConnection();

// Verificar se foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido");
}

$token = $_POST['token'] ?? '';
$equipeId = $_POST['equipe_id'] ?? 0;

try {
    // Verificar convite
    $stmt = $pdo->prepare("
        SELECT * FROM convites_atletas
        WHERE token = ? AND equipe_id = ? AND status = 'Pendente'
    ");
    $stmt->execute([$token, $equipeId]);
    $convite = $stmt->fetch();

    if (!$convite) {
        throw new Exception("Convite inválido ou já utilizado.");
    }

    if (strtotime($convite['validade_ate']) < time()) {
        throw new Exception("Convite expirado.");
    }

    // Verificar se CPF já está cadastrado
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $stmt = $pdo->prepare("SELECT id FROM atletas WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception("CPF já cadastrado no sistema. Entre em contato com o administrador.");
    }

    // Upload de arquivos
    $uploadDir = '../uploads/atletas/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    function uploadArquivo($file, $prefix, $uploadDir) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nomeArquivo = $prefix . '_' . uniqid() . '.' . $extensao;
        $caminhoCompleto = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
            return $nomeArquivo;
        }
        return null;
    }

    $foto = uploadArquivo($_FILES['foto'] ?? null, 'foto', $uploadDir);
    $documentoIdentidade = uploadArquivo($_FILES['documento_identidade'] ?? null, 'doc', $uploadDir);
    $comprovanteResidencia = uploadArquivo($_FILES['comprovante_residencia'] ?? null, 'comp', $uploadDir);
    $atestadoMedico = uploadArquivo($_FILES['atestado_medico'] ?? null, 'atestado', $uploadDir);

    if (!$foto || !$documentoIdentidade || !$comprovanteResidencia) {
        throw new Exception("Erro ao fazer upload dos documentos obrigatórios.");
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Inserir atleta
    $stmt = $pdo->prepare("
        INSERT INTO atletas (
            nome_completo, cpf, rg, data_nascimento, genero,
            email, telefone, cep, endereco, numero, complemento,
            bairro, cidade, estado,
            equipe_atual_id, ativo,
            foto_path, documento_identidade_path,
            comprovante_residencia_path, atestado_medico_path,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, 1,
            ?, ?,
            ?, ?,
            NOW()
        )
    ");

    $stmt->execute([
        $_POST['nome_completo'],
        $cpf,
        $_POST['rg'],
        $_POST['data_nascimento'],
        $_POST['genero'],
        $_POST['email'],
        preg_replace('/[^0-9]/', '', $_POST['telefone']),
        preg_replace('/[^0-9]/', '', $_POST['cep']),
        $_POST['endereco'],
        $_POST['numero'],
        $_POST['complemento'] ?? null,
        $_POST['bairro'],
        $_POST['cidade'],
        $_POST['estado'],
        $equipeId,
        $foto,
        $documentoIdentidade,
        $comprovanteResidencia,
        $atestadoMedico
    ]);

    $atletaId = $pdo->lastInsertId();

    // Criar observações se houver dados esportivos
    $observacoes = [];
    if (!empty($_POST['modalidade_principal'])) {
        $observacoes[] = "Modalidade: " . $_POST['modalidade_principal'];
    }
    if (!empty($_POST['posicao'])) {
        $observacoes[] = "Posição: " . $_POST['posicao'];
    }
    if (!empty($_POST['experiencia'])) {
        $observacoes[] = "Experiência: " . $_POST['experiencia'];
    }

    // Atualizar observações do atleta se houver
    if (!empty($observacoes)) {
        $obsTexto = implode(" | ", $observacoes);
        $stmt = $pdo->prepare("UPDATE atletas SET observacoes = ? WHERE id = ?");
        $stmt->execute([$obsTexto, $atletaId]);
    }

    // Marcar convite como aceito
    $stmt = $pdo->prepare("
        UPDATE convites_atletas
        SET status = 'Aceito', usado_em = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$convite['id']]);

    // Confirmar transação
    $pdo->commit();

    // Enviar e-mail de confirmação ao atleta
    try {
        require_once '../includes/email_helper.php';
        emailCadastroAtleta(
            $_POST['nome_completo'],
            $convite['equipe_nome'],
            $_POST['email']
        );
    } catch (Exception $emailError) {
        // Email falhou mas cadastro foi realizado, continua normalmente
        error_log("Erro ao enviar email de confirmação: " . $emailError->getMessage());
    }

    // Redirecionar para página de sucesso
    $sucessoUrl = "cadastro_sucesso.php?equipe=" . urlencode($convite['equipe_nome']);
    header("Location: $sucessoUrl");
    exit;

} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Mostrar erro
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro no Cadastro</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                            <h2 class="text-danger">Erro no Cadastro</h2>
                            <p class="lead"><?php echo htmlspecialchars($e->getMessage()); ?></p>
                            <hr>
                            <a href="javascript:history.back()" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Voltar e Tentar Novamente
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
