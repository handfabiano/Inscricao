<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipe_id = $_SESSION['equipe_id'];
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Dados básicos
        $nome_completo = sanitize($_POST['nome_completo']);
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $rg = sanitize($_POST['rg']);
        $data_nascimento = $_POST['data_nascimento'];
        $genero = $_POST['genero'];

        // Validar CPF
        if (!validateCPF($cpf)) {
            throw new Exception('CPF inválido');
        }

        // Verificar se CPF já existe
        $stmt = $pdo->prepare("SELECT id FROM atletas WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            throw new Exception('CPF já cadastrado no sistema');
        }

        // Upload de foto (OBRIGATÓRIO)
        if (empty($_FILES['foto']['name'])) {
            throw new Exception('Foto 3x4 é obrigatória');
        }

        $fotoFileName = uploadFile(
            $_FILES['foto'],
            'foto',
            FOTO_PATH,
            ALLOWED_IMAGE_EXTENSIONS,
            MAX_FOTO_SIZE
        );

        // Upload de documentos
        $docIdentidadeFileName = null;
        if (!empty($_FILES['documento_identidade']['name'])) {
            $docIdentidadeFileName = uploadFile(
                $_FILES['documento_identidade'],
                'documento_identidade',
                DOC_PATH,
                ALLOWED_DOC_EXTENSIONS,
                MAX_FILE_SIZE
            );
        }

        $atestadoFileName = null;
        if (!empty($_FILES['atestado_medico']['name'])) {
            $atestadoFileName = uploadFile(
                $_FILES['atestado_medico'],
                'atestado_medico',
                DOC_PATH,
                ALLOWED_DOC_EXTENSIONS,
                MAX_FILE_SIZE
            );
        }

        // Dados do responsável legal (se menor)
        $idade = calcularIdade($data_nascimento);
        $responsavel_legal_nome = null;
        $responsavel_legal_cpf = null;
        $responsavel_legal_telefone = null;
        $responsavel_legal_parentesco = null;

        if ($idade < 18) {
            $responsavel_legal_nome = sanitize($_POST['responsavel_legal_nome']);
            $responsavel_legal_cpf = preg_replace('/[^0-9]/', '', $_POST['responsavel_legal_cpf']);
            $responsavel_legal_telefone = preg_replace('/[^0-9]/', '', $_POST['responsavel_legal_telefone']);
            $responsavel_legal_parentesco = sanitize($_POST['responsavel_legal_parentesco']);

            if (!validateCPF($responsavel_legal_cpf)) {
                throw new Exception('CPF do responsável legal inválido');
            }
        }

        // Inserir atleta
        $sql = "INSERT INTO atletas (
            nome_completo, cpf, rg, data_nascimento, genero, foto_path,
            equipe_atual_id, data_entrada_equipe, numero_camisa, posicao,
            email, telefone, celular,
            cep, endereco, numero, complemento, bairro, cidade, estado,
            responsavel_legal_nome, responsavel_legal_cpf,
            responsavel_legal_telefone, responsavel_legal_parentesco,
            documento_identidade_path, atestado_medico_path,
            peso, altura, tipo_sanguineo
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, CURDATE(), ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nome_completo, $cpf, $rg, $data_nascimento, $genero, $fotoFileName,
            $equipe_id, $_POST['numero_camisa'] ?? null, sanitize($_POST['posicao'] ?? ''),
            sanitize($_POST['email'] ?? ''), preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''), preg_replace('/[^0-9]/', '', $_POST['celular'] ?? ''),
            preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''), sanitize($_POST['endereco'] ?? ''), sanitize($_POST['numero'] ?? ''), sanitize($_POST['complemento'] ?? ''), sanitize($_POST['bairro'] ?? ''), sanitize($_POST['cidade'] ?? ''), $_POST['estado'] ?? '',
            $responsavel_legal_nome, $responsavel_legal_cpf, $responsavel_legal_telefone, $responsavel_legal_parentesco,
            $docIdentidadeFileName, $atestadoFileName,
            $_POST['peso'] ?? null, $_POST['altura'] ?? null, $_POST['tipo_sanguineo'] ?? null
        ]);

        $atleta_id = $pdo->lastInsertId();

        // Registrar no histórico
        registrarHistorico($pdo, 'atleta', $atleta_id, 'Cadastro', "Atleta cadastrado na equipe {$_SESSION['equipe_nome']}", [
            'equipe_id' => $equipe_id,
            'responsavel' => $_SESSION['responsavel_nome']
        ]);

        registrarHistorico($pdo, 'equipe', $equipe_id, 'Adicao_Atleta', "Atleta {$nome_completo} adicionado à equipe", [
            'atleta_id' => $atleta_id
        ]);

        $mensagem = "Atleta cadastrado com sucesso!";
        header('Location: atletas.php?msg=cadastrado');
        exit;

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Atleta</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($_SESSION['equipe_nome']); ?></h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="atletas.php">Atletas</a>
                <a href="inscricoes.php">Inscrições</a>
                <a href="competicoes.php">Competições</a>
                <a href="logout.php">Sair</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="form-wrapper">
            <div class="form-header">
                <h2><i class="fas fa-user-plus"></i> Cadastrar Novo Atleta</h2>
                <p>Preencha todos os dados do atleta. Foto é obrigatória.</p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="formAtleta">
                <!-- Foto 3x4 -->
                <div class="form-section">
                    <h3><i class="fas fa-camera"></i> Foto 3x4 *</h3>
                    <div class="form-group">
                        <label>Foto do Atleta (JPG ou PNG, máx 2MB)</label>
                        <input type="file" name="foto" accept=".jpg,.jpeg,.png" required id="inputFoto">
                        <div id="previewFoto" style="margin-top: 15px; display: none;">
                            <img id="imgPreview" style="max-width: 150px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        </div>
                    </div>
                </div>

                <!-- Dados Pessoais -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Dados Pessoais</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label>Nome Completo *</label>
                            <input type="text" name="nome_completo" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>CPF *</label>
                            <input type="text" name="cpf" id="cpf" maxlength="14" required>
                        </div>
                        <div class="form-group">
                            <label>RG *</label>
                            <input type="text" name="rg" required>
                        </div>
                        <div class="form-group">
                            <label>Data de Nascimento *</label>
                            <input type="date" name="data_nascimento" id="dataNascimento" required>
                        </div>
                        <div class="form-group">
                            <label>Gênero *</label>
                            <select name="genero" required>
                                <option value="">Selecione</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Feminino">Feminino</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Dados Esportivos -->
                <div class="form-section">
                    <h3><i class="fas fa-medal"></i> Dados Esportivos</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Posição</label>
                            <input type="text" name="posicao" placeholder="Ex: Atacante, Goleiro, etc">
                        </div>
                        <div class="form-group">
                            <label>Número da Camisa</label>
                            <input type="number" name="numero_camisa" min="1" max="999">
                        </div>
                        <div class="form-group">
                            <label>Peso (kg)</label>
                            <input type="number" name="peso" step="0.1" min="30" max="200">
                        </div>
                        <div class="form-group">
                            <label>Altura (m)</label>
                            <input type="number" name="altura" step="0.01" min="1.0" max="2.5" placeholder="Ex: 1.75">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tipo Sanguíneo</label>
                        <select name="tipo_sanguineo">
                            <option value="">Selecione</option>
                            <option>A+</option>
                            <option>A-</option>
                            <option>B+</option>
                            <option>B-</option>
                            <option>AB+</option>
                            <option>AB-</option>
                            <option>O+</option>
                            <option>O-</option>
                        </select>
                    </div>
                </div>

                <!-- Contato -->
                <div class="form-section">
                    <h3><i class="fas fa-phone"></i> Contato</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="tel" name="telefone" id="telefone">
                        </div>
                        <div class="form-group">
                            <label>Celular</label>
                            <input type="tel" name="celular" id="celular">
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CEP</label>
                            <input type="text" name="cep" id="cep" maxlength="9">
                        </div>
                        <div class="form-group full">
                            <label>Logradouro</label>
                            <input type="text" name="endereco" id="endereco">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Número</label>
                            <input type="text" name="numero">
                        </div>
                        <div class="form-group">
                            <label>Complemento</label>
                            <input type="text" name="complemento">
                        </div>
                        <div class="form-group">
                            <label>Bairro</label>
                            <input type="text" name="bairro" id="bairro">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cidade</label>
                            <input type="text" name="cidade" id="cidade">
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="estado" id="estado">
                                <option value="">Selecione</option>
                                <option value="RR">Roraima</option>
                                <!-- Outros estados -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Responsável Legal (se menor) -->
                <div class="form-section" id="secaoResponsavel" style="display: none;">
                    <h3><i class="fas fa-user-shield"></i> Responsável Legal (Obrigatório para menores de 18 anos)</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label>Nome do Responsável</label>
                            <input type="text" name="responsavel_legal_nome" id="respNome">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>CPF do Responsável</label>
                            <input type="text" name="responsavel_legal_cpf" id="respCpf" maxlength="14">
                        </div>
                        <div class="form-group">
                            <label>Telefone do Responsável</label>
                            <input type="tel" name="responsavel_legal_telefone" id="respTelefone">
                        </div>
                        <div class="form-group">
                            <label>Parentesco</label>
                            <select name="responsavel_legal_parentesco" id="respParentesco">
                                <option value="">Selecione</option>
                                <option value="Pai">Pai</option>
                                <option value="Mãe">Mãe</option>
                                <option value="Avô/Avó">Avô/Avó</option>
                                <option value="Tio/Tia">Tio/Tia</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Documentos -->
                <div class="form-section">
                    <h3><i class="fas fa-file-upload"></i> Documentos</h3>
                    <p class="info-text">Formatos: JPG, PNG, PDF | Máx 5MB por arquivo</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Documento de Identidade</label>
                            <input type="file" name="documento_identidade" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <div class="form-group">
                            <label>Atestado Médico</label>
                            <input type="file" name="atestado_medico" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <a href="atletas.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Cadastrar Atleta
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema v3.0</p>
        </div>
    </footer>

    <script src="../public/js/script.js"></script>
    <script>
        // Preview da foto
        document.getElementById('inputFoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('imgPreview').src = event.target.result;
                    document.getElementById('previewFoto').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Verificar idade para responsável legal
        document.getElementById('dataNascimento').addEventListener('change', function() {
            const dataNasc = new Date(this.value);
            const hoje = new Date();
            let idade = hoje.getFullYear() - dataNasc.getFullYear();
            const m = hoje.getMonth() - dataNasc.getMonth();
            if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
                idade--;
            }

            const secao = document.getElementById('secaoResponsavel');
            const inputs = ['respNome', 'respCpf', 'respTelefone', 'respParentesco'];

            if (idade < 18) {
                secao.style.display = 'block';
                inputs.forEach(id => {
                    document.getElementById(id).setAttribute('required', 'required');
                });
            } else {
                secao.style.display = 'none';
                inputs.forEach(id => {
                    document.getElementById(id).removeAttribute('required');
                });
            }
        });
    </script>
</body>
</html>
