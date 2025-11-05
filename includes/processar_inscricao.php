<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $pdo = getDBConnection();

    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }

    // Validar campos obrigatórios
    $camposObrigatorios = [
        'nome_completo', 'cpf', 'rg', 'data_nascimento', 'genero',
        'email', 'telefone', 'cep', 'endereco', 'numero', 'bairro',
        'cidade', 'estado', 'modalidade_id', 'categoria_id'
    ];

    foreach ($camposObrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("Campo obrigatório não preenchido: $campo");
        }
    }

    // Validar CPF
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    if (!validateCPF($cpf)) {
        throw new Exception('CPF inválido');
    }

    // Verificar se CPF já existe
    $stmt = $pdo->prepare("SELECT id FROM inscricoes WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception('CPF já cadastrado no sistema');
    }

    // Validar idade e responsável
    $idade = calcularIdade($_POST['data_nascimento']);
    if ($idade < 18) {
        if (empty($_POST['responsavel_nome']) || empty($_POST['responsavel_cpf']) || empty($_POST['responsavel_telefone'])) {
            throw new Exception('Dados do responsável são obrigatórios para menores de 18 anos');
        }

        $cpfResponsavel = preg_replace('/[^0-9]/', '', $_POST['responsavel_cpf']);
        if (!validateCPF($cpfResponsavel)) {
            throw new Exception('CPF do responsável inválido');
        }
    }

    // Processar upload de arquivos
    $uploadedFiles = [];
    $requiredFiles = ['foto', 'documento_identidade', 'comprovante_residencia'];
    $optionalFiles = ['atestado_medico'];

    foreach ($requiredFiles as $fileField) {
        if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Arquivo obrigatório não enviado: $fileField");
        }

        $uploadedFiles[$fileField] = uploadFile($_FILES[$fileField], $fileField);
    }

    foreach ($optionalFiles as $fileField) {
        if (!empty($_FILES[$fileField]) && $_FILES[$fileField]['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedFiles[$fileField] = uploadFile($_FILES[$fileField], $fileField);
        } else {
            $uploadedFiles[$fileField] = null;
        }
    }

    // Gerar protocolo único
    $protocolo = generateProtocol();

    // Preparar dados para inserção
    $dados = [
        'protocolo' => $protocolo,
        'nome_completo' => sanitize($_POST['nome_completo']),
        'cpf' => $cpf,
        'rg' => sanitize($_POST['rg']),
        'data_nascimento' => $_POST['data_nascimento'],
        'genero' => $_POST['genero'],
        'email' => sanitize($_POST['email']),
        'telefone' => preg_replace('/[^0-9]/', '', $_POST['telefone']),
        'celular' => !empty($_POST['celular']) ? preg_replace('/[^0-9]/', '', $_POST['celular']) : null,
        'cep' => preg_replace('/[^0-9]/', '', $_POST['cep']),
        'endereco' => sanitize($_POST['endereco']),
        'numero' => sanitize($_POST['numero']),
        'complemento' => !empty($_POST['complemento']) ? sanitize($_POST['complemento']) : null,
        'bairro' => sanitize($_POST['bairro']),
        'cidade' => sanitize($_POST['cidade']),
        'estado' => $_POST['estado'],
        'modalidade_id' => (int)$_POST['modalidade_id'],
        'categoria_id' => (int)$_POST['categoria_id'],
        'experiencia_anos' => !empty($_POST['experiencia_anos']) ? (int)$_POST['experiencia_anos'] : null,
        'clube_anterior' => !empty($_POST['clube_anterior']) ? sanitize($_POST['clube_anterior']) : null,
        'responsavel_nome' => !empty($_POST['responsavel_nome']) ? sanitize($_POST['responsavel_nome']) : null,
        'responsavel_cpf' => !empty($_POST['responsavel_cpf']) ? preg_replace('/[^0-9]/', '', $_POST['responsavel_cpf']) : null,
        'responsavel_telefone' => !empty($_POST['responsavel_telefone']) ? preg_replace('/[^0-9]/', '', $_POST['responsavel_telefone']) : null,
        'responsavel_parentesco' => !empty($_POST['responsavel_parentesco']) ? $_POST['responsavel_parentesco'] : null,
        'foto_path' => $uploadedFiles['foto'],
        'documento_identidade_path' => $uploadedFiles['documento_identidade'],
        'comprovante_residencia_path' => $uploadedFiles['comprovante_residencia'],
        'atestado_medico_path' => $uploadedFiles['atestado_medico'],
        'status' => 'Pendente',
        'ip_origem' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Inserir no banco de dados
    $sql = "INSERT INTO inscricoes (
        protocolo, nome_completo, cpf, rg, data_nascimento, genero,
        email, telefone, celular, cep, endereco, numero, complemento,
        bairro, cidade, estado, modalidade_id, categoria_id,
        experiencia_anos, clube_anterior, responsavel_nome, responsavel_cpf,
        responsavel_telefone, responsavel_parentesco, foto_path,
        documento_identidade_path, comprovante_residencia_path,
        atestado_medico_path, status, ip_origem, user_agent
    ) VALUES (
        :protocolo, :nome_completo, :cpf, :rg, :data_nascimento, :genero,
        :email, :telefone, :celular, :cep, :endereco, :numero, :complemento,
        :bairro, :cidade, :estado, :modalidade_id, :categoria_id,
        :experiencia_anos, :clube_anterior, :responsavel_nome, :responsavel_cpf,
        :responsavel_telefone, :responsavel_parentesco, :foto_path,
        :documento_identidade_path, :comprovante_residencia_path,
        :atestado_medico_path, :status, :ip_origem, :user_agent
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    // Redirecionar para página de sucesso
    header('Location: ../sucesso.php?protocolo=' . $protocolo);
    exit;

} catch (Exception $e) {
    // Em caso de erro, deletar arquivos enviados
    if (isset($uploadedFiles)) {
        foreach ($uploadedFiles as $file) {
            if ($file && file_exists(UPLOAD_PATH . $file)) {
                unlink(UPLOAD_PATH . $file);
            }
        }
    }

    // Redirecionar com erro
    header('Location: ../index.php?erro=' . urlencode($e->getMessage()));
    exit;
}

// Função para upload de arquivo
function uploadFile($file, $fieldName) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro ao fazer upload do arquivo: $fieldName");
    }

    $allowedExtensions = ALLOWED_EXTENSIONS;
    $maxSize = MAX_FILE_SIZE;

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception("Extensão não permitida para o arquivo: $fieldName");
    }

    if ($fileSize > $maxSize) {
        throw new Exception("Arquivo muito grande: $fieldName (máximo 5MB)");
    }

    // Gerar nome único
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $uploadPath = UPLOAD_PATH . $newFileName;

    if (!move_uploaded_file($fileTmp, $uploadPath)) {
        throw new Exception("Erro ao mover arquivo: $fieldName");
    }

    return $newFileName;
}
?>
