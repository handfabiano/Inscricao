<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];
$atletaId = $_GET['id'] ?? null;

if (!$atletaId) {
    redirect('atletas.php', 'Atleta não encontrado', 'error');
}

// Buscar dados do atleta e verificar se pertence à equipe
$stmt = $pdo->prepare("
    SELECT a.*
    FROM atletas a
    WHERE a.id = ? AND a.equipe_atual_id = ?
");
$stmt->execute([$atletaId, $equipeId]);
$atleta = $stmt->fetch();

if (!$atleta) {
    redirect('atletas.php', 'Atleta não encontrado ou não pertence à sua equipe', 'error');
}

$mensagem = '';
$mensagemTipo = '';

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['nome_completo', 'cpf', 'rg', 'data_nascimento', 'genero'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("Campo obrigatório: " . str_replace('_', ' ', $campo));
            }
        }

        // Validar CPF
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        if (!validarCPF($cpf)) {
            throw new Exception('CPF inválido');
        }

        // Verificar se CPF já existe em outro atleta
        $stmt = $pdo->prepare("SELECT id FROM atletas WHERE cpf = ? AND id != ?");
        $stmt->execute([$cpf, $atletaId]);
        if ($stmt->fetch()) {
            throw new Exception('CPF já cadastrado para outro atleta');
        }

        // Upload da foto (opcional na edição)
        $fotoFileName = $atleta['foto_path'];
        if (!empty($_FILES['foto']['name'])) {
            // Deletar foto antiga
            if ($fotoFileName && file_exists(FOTO_PATH . $fotoFileName)) {
                unlink(FOTO_PATH . $fotoFileName);
            }

            $fotoFileName = uploadFile(
                $_FILES['foto'],
                'foto',
                FOTO_PATH,
                ALLOWED_IMAGE_EXTENSIONS,
                MAX_FOTO_SIZE
            );
        }

        // Calcular idade e validar responsável legal
        $idade = calcularIdade($_POST['data_nascimento']);
        if ($idade < 18) {
            if (empty($_POST['responsavel_legal_nome']) || empty($_POST['responsavel_legal_cpf']) || empty($_POST['responsavel_legal_telefone'])) {
                throw new Exception('Dados do responsável legal são obrigatórios para menores de 18 anos');
            }

            $cpfResponsavel = preg_replace('/[^0-9]/', '', $_POST['responsavel_legal_cpf']);
            if (!validarCPF($cpfResponsavel)) {
                throw new Exception('CPF do responsável legal inválido');
            }
        }

        // Atualizar atleta (equipe atual NÃO pode ser alterada por equipes)
        $stmt = $pdo->prepare("
            UPDATE atletas SET
                nome_completo = ?,
                cpf = ?,
                rg = ?,
                data_nascimento = ?,
                genero = ?,
                foto_path = ?,
                email = ?,
                telefone = ?,
                celular = ?,
                cep = ?,
                endereco = ?,
                numero = ?,
                complemento = ?,
                bairro = ?,
                cidade = ?,
                estado = ?,
                responsavel_legal_nome = ?,
                responsavel_legal_cpf = ?,
                responsavel_legal_telefone = ?,
                responsavel_legal_parentesco = ?,
                peso = ?,
                altura = ?,
                tipo_sanguineo = ?
            WHERE id = ? AND equipe_atual_id = ?
        ");

        $stmt->execute([
            sanitize($_POST['nome_completo']),
            $cpf,
            sanitize($_POST['rg']),
            $_POST['data_nascimento'],
            $_POST['genero'],
            $fotoFileName,
            sanitize($_POST['email'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['celular'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
            sanitize($_POST['endereco'] ?? ''),
            sanitize($_POST['numero'] ?? ''),
            sanitize($_POST['complemento'] ?? ''),
            sanitize($_POST['bairro'] ?? ''),
            sanitize($_POST['cidade'] ?? ''),
            sanitize($_POST['estado'] ?? ''),
            sanitize($_POST['responsavel_legal_nome'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['responsavel_legal_cpf'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['responsavel_legal_telefone'] ?? ''),
            sanitize($_POST['responsavel_legal_parentesco'] ?? ''),
            !empty($_POST['peso']) ? floatval($_POST['peso']) : null,
            !empty($_POST['altura']) ? floatval($_POST['altura']) : null,
            sanitize($_POST['tipo_sanguineo'] ?? ''),
            $atletaId,
            $equipeId // Segurança extra
        ]);

        // Registrar histórico
        registrarHistoricoAtleta($pdo, $atletaId, 'Atualizacao', "Dados atualizados pela equipe " . $_SESSION['equipe_nome']);

        redirect('atletas.php', 'Atleta atualizado com sucesso!', 'success');

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $mensagemTipo = 'error';

        // Deletar nova foto se houver erro
        if (isset($fotoFileName) && $fotoFileName != $atleta['foto_path'] && file_exists(FOTO_PATH . $fotoFileName)) {
            unlink(FOTO_PATH . $fotoFileName);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Atleta - Sistema de Competições</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .foto-preview {
            max-width: 200px;
            max-height: 250px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-trophy"></i> Sistema de Competições
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="atletas.php">
                            <i class="fas fa-users"></i> Atletas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['equipe_nome']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-edit text-primary"></i>
                Editar Atleta
            </h2>
            <a href="atletas.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $mensagemTipo === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $mensagemTipo === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensagem); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Foto Atual e Nova -->
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <label class="form-label fw-bold">Foto Atual</label><br>
                            <?php if ($atleta['foto_path']): ?>
                                <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                     class="foto-preview mb-2"
                                     alt="Foto atual">
                            <?php else: ?>
                                <div class="alert alert-info">Sem foto cadastrada</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold">
                                <i class="fas fa-image"></i> Nova Foto (opcional - JPG, PNG - máx 2MB)
                            </label>
                            <input type="file" name="foto" class="form-control" accept="image/*" onchange="previewFoto(event)">
                            <small class="text-muted">Deixe em branco para manter a foto atual</small>
                            <div id="previewFoto" class="mt-3" style="display:none;">
                                <img id="imgPreview" class="foto-preview" alt="Preview">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Dados Pessoais -->
                    <h5 class="mb-3">
                        <i class="fas fa-user"></i> Dados Pessoais
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome_completo" class="form-control" required
                                   value="<?php echo htmlspecialchars($atleta['nome_completo']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF *</label>
                            <input type="text" name="cpf" class="form-control" required maxlength="14"
                                   value="<?php echo formatarCPF($atleta['cpf']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">RG *</label>
                            <input type="text" name="rg" class="form-control" required
                                   value="<?php echo htmlspecialchars($atleta['rg']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" class="form-control" required id="dataNascimento"
                                   value="<?php echo $atleta['data_nascimento']; ?>" onchange="verificarIdade()">
                            <small id="idadeInfo" class="text-muted"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gênero *</label>
                            <select name="genero" class="form-select" required>
                                <option value="">Selecione...</option>
                                <option value="Masculino" <?php echo $atleta['genero'] === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="Feminino" <?php echo $atleta['genero'] === 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Contato -->
                    <h5 class="mb-3">
                        <i class="fas fa-phone"></i> Contato
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" class="form-control" placeholder="(95) 3224-5566"
                                   value="<?php echo $atleta['telefone'] ? formatarTelefone($atleta['telefone']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular</label>
                            <input type="text" name="celular" class="form-control" placeholder="(95) 99988-7766"
                                   value="<?php echo $atleta['celular'] ? formatarTelefone($atleta['celular']) : ''; ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Endereço -->
                    <h5 class="mb-3">
                        <i class="fas fa-map-marker-alt"></i> Endereço
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" class="form-control" maxlength="9" placeholder="69300-000"
                                   value="<?php echo htmlspecialchars($atleta['cep'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="endereco" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['endereco'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" name="numero" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Complemento</label>
                            <input type="text" name="complemento" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['complemento'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="bairro" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['bairro'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cidade</label>
                            <input type="text" name="cidade" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">UF</option>
                                <option value="RR" <?php echo $atleta['estado'] === 'RR' ? 'selected' : ''; ?>>RR</option>
                                <option value="AC" <?php echo $atleta['estado'] === 'AC' ? 'selected' : ''; ?>>AC</option>
                                <option value="AM" <?php echo $atleta['estado'] === 'AM' ? 'selected' : ''; ?>>AM</option>
                                <option value="AP" <?php echo $atleta['estado'] === 'AP' ? 'selected' : ''; ?>>AP</option>
                                <option value="PA" <?php echo $atleta['estado'] === 'PA' ? 'selected' : ''; ?>>PA</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Responsável Legal -->
                    <h5 class="mb-3">
                        <i class="fas fa-user-shield"></i> Responsável Legal
                        <small class="text-muted">(obrigatório para menores de 18 anos)</small>
                    </h5>
                    <div class="row g-3 mb-4" id="responsavelSection">
                        <div class="col-md-4">
                            <label class="form-label">Nome do Responsável</label>
                            <input type="text" name="responsavel_legal_nome" class="form-control"
                                   value="<?php echo htmlspecialchars($atleta['responsavel_legal_nome'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF do Responsável</label>
                            <input type="text" name="responsavel_legal_cpf" class="form-control" maxlength="14"
                                   value="<?php echo $atleta['responsavel_legal_cpf'] ? formatarCPF($atleta['responsavel_legal_cpf']) : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone do Responsável</label>
                            <input type="text" name="responsavel_legal_telefone" class="form-control"
                                   value="<?php echo $atleta['responsavel_legal_telefone'] ? formatarTelefone($atleta['responsavel_legal_telefone']) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Parentesco</label>
                            <input type="text" name="responsavel_legal_parentesco" class="form-control" placeholder="Pai, Mãe..."
                                   value="<?php echo htmlspecialchars($atleta['responsavel_legal_parentesco'] ?? ''); ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Dados Físicos -->
                    <h5 class="mb-3">
                        <i class="fas fa-heartbeat"></i> Dados Físicos
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" name="peso" class="form-control" step="0.1"
                                   value="<?php echo $atleta['peso'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Altura (m)</label>
                            <input type="number" name="altura" class="form-control" step="0.01"
                                   value="<?php echo $atleta['altura'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo Sanguíneo</label>
                            <select name="tipo_sanguineo" class="form-select">
                                <option value="">Selecione...</option>
                                <?php
                                $tipos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($tipos as $tipo) {
                                    $selected = ($atleta['tipo_sanguineo'] === $tipo) ? 'selected' : '';
                                    echo "<option value=\"$tipo\" $selected>$tipo</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Botões -->
                    <div class="d-flex justify-content-between">
                        <a href="atletas.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewFoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imgPreview').src = e.target.result;
                    document.getElementById('previewFoto').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        function verificarIdade() {
            const dataNasc = document.getElementById('dataNascimento').value;
            if (dataNasc) {
                const hoje = new Date();
                const nascimento = new Date(dataNasc);
                let idade = hoje.getFullYear() - nascimento.getFullYear();
                const mes = hoje.getMonth() - nascimento.getMonth();
                if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                    idade--;
                }

                const info = document.getElementById('idadeInfo');
                info.textContent = idade + ' anos';

                if (idade < 18) {
                    info.textContent += ' (menor de idade - responsável obrigatório)';
                    info.className = 'text-danger fw-bold';
                }
            }
        }

        // Verificar idade ao carregar a página
        window.addEventListener('load', verificarIdade);
    </script>
</body>
</html>
