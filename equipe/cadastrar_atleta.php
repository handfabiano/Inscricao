<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$mensagem = '';
$mensagemTipo = '';

// Processar cadastro
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

        // Verificar se CPF já existe
        $stmt = $pdo->prepare("SELECT id FROM atletas WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            throw new Exception('CPF já cadastrado no sistema');
        }

        // Validar foto (OBRIGATÓRIA)
        if (empty($_FILES['foto']['name'])) {
            throw new Exception('Foto 3x4 é obrigatória');
        }

        // Upload da foto
        $fotoFileName = uploadFile(
            $_FILES['foto'],
            'foto',
            FOTO_PATH,
            ALLOWED_IMAGE_EXTENSIONS,
            MAX_FOTO_SIZE
        );

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

        // Inserir atleta
        $stmt = $pdo->prepare("
            INSERT INTO atletas (
                nome_completo, cpf, rg, data_nascimento, genero, foto_path,
                equipe_atual_id, data_entrada_equipe,
                email, telefone, celular,
                cep, endereco, numero, complemento, bairro, cidade, estado,
                responsavel_legal_nome, responsavel_legal_cpf, responsavel_legal_telefone, responsavel_legal_parentesco,
                peso, altura, tipo_sanguineo,
                ativo, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $stmt->execute([
            sanitize($_POST['nome_completo']),
            $cpf,
            sanitize($_POST['rg']),
            $_POST['data_nascimento'],
            $_POST['genero'],
            $fotoFileName,
            $_SESSION['equipe_id'],
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
            sanitize($_POST['tipo_sanguineo'] ?? '')
        ]);

        $atletaId = $pdo->lastInsertId();

        // Registrar histórico
        registrarHistoricoAtleta($pdo, $atletaId, 'Cadastro', "Atleta cadastrado na equipe {$_SESSION['equipe_nome']}");
        registrarHistoricoEquipe($pdo, $_SESSION['equipe_id'], 'Adicao_Atleta', "Atleta " . sanitize($_POST['nome_completo']) . " adicionado à equipe");

        $mensagem = 'Atleta cadastrado com sucesso!';
        $mensagemTipo = 'success';

        // Limpar formulário
        $_POST = [];

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $mensagemTipo = 'error';

        // Deletar foto se houver erro
        if (isset($fotoFileName) && file_exists(FOTO_PATH . $fotoFileName)) {
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
    <title>Cadastrar Atleta - Sistema v3.0</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="header" style="background: linear-gradient(135deg, #10b981, #059669);">
        <div class="container">
            <div class="header-content">
                <h1>👥 Sistema v3.0 - Equipe</h1>
                <nav>
                    <a href="index.php">Dashboard</a>
                    <a href="cadastrar_atleta.php">Cadastrar Atleta</a>
                    <a href="atletas.php">Meus Atletas</a>
                    <a href="logout.php">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 20px;">
        <h2>Cadastrar Novo Atleta</h2>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $mensagemTipo; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Dados Pessoais</h3>

                <div class="form-group">
                    <label>Foto 3x4 * (JPG, PNG - máx 2MB)</label>
                    <input type="file" name="foto" accept="image/*" required onchange="previewFoto(event)" id="inputFoto">
                    <small>A foto é obrigatória e será usada na identificação do atleta</small>
                    <div id="previewFoto" class="image-preview" style="margin-top: 1rem;">
                        <img id="imgPreview" alt="Preview" style="max-width: 150px; max-height: 200px; border-radius: 8px; border: 2px solid #e2e8f0;">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="nome_completo" required value="<?php echo htmlspecialchars($_POST['nome_completo'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>CPF *</label>
                        <input type="text" name="cpf" required maxlength="14" placeholder="000.000.000-00" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>RG *</label>
                        <input type="text" name="rg" required value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" required id="dataNascimento" onchange="verificarIdade()" value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                        <small id="idadeInfo"></small>
                    </div>

                    <div class="form-group">
                        <label>Gênero *</label>
                        <select name="genero" required>
                            <option value="">Selecione...</option>
                            <option value="Masculino" <?php echo (($_POST['genero'] ?? '') === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo (($_POST['genero'] ?? '') === 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Contato</h3>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" placeholder="(95) 3224-5566" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Celular</label>
                        <input type="text" name="celular" placeholder="(95) 99988-7766" value="<?php echo htmlspecialchars($_POST['celular'] ?? ''); ?>">
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Endereço</h3>

                <div class="grid grid-4">
                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="cep" maxlength="9" placeholder="69300-000" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Endereço</label>
                        <input type="text" name="endereco" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="numero" value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="complemento" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="bairro" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="cidade" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Selecione...</option>
                        <option value="RR" <?php echo (($_POST['estado'] ?? '') === 'RR') ? 'selected' : ''; ?>>Roraima</option>
                        <option value="AM" <?php echo (($_POST['estado'] ?? '') === 'AM') ? 'selected' : ''; ?>>Amazonas</option>
                        <option value="AC" <?php echo (($_POST['estado'] ?? '') === 'AC') ? 'selected' : ''; ?>>Acre</option>
                        <option value="RO" <?php echo (($_POST['estado'] ?? '') === 'RO') ? 'selected' : ''; ?>>Rondônia</option>
                    </select>
                </div>

                <!-- Responsável Legal (aparece se menor de 18) -->
                <div id="camposResponsavel" style="display: none;">
                    <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; color: #ef4444;">
                        Responsável Legal * (Obrigatório para menores de 18 anos)
                    </h3>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Nome do Responsável *</label>
                            <input type="text" name="responsavel_legal_nome" id="responsavelNome">
                        </div>

                        <div class="form-group">
                            <label>CPF do Responsável *</label>
                            <input type="text" name="responsavel_legal_cpf" id="responsavelCpf" maxlength="14" placeholder="000.000.000-00">
                        </div>

                        <div class="form-group">
                            <label>Telefone do Responsável *</label>
                            <input type="text" name="responsavel_legal_telefone" id="responsavelTelefone" placeholder="(95) 99988-7766">
                        </div>

                        <div class="form-group">
                            <label>Parentesco *</label>
                            <select name="responsavel_legal_parentesco" id="responsavelParentesco">
                                <option value="">Selecione...</option>
                                <option value="Pai">Pai</option>
                                <option value="Mãe">Mãe</option>
                                <option value="Avô">Avô</option>
                                <option value="Avó">Avó</option>
                                <option value="Tio">Tio</option>
                                <option value="Tia">Tia</option>
                                <option value="Responsável Legal">Responsável Legal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <h3 style="margin: 1.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Dados Físicos (Opcional)</h3>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Peso (kg)</label>
                        <input type="number" name="peso" step="0.1" placeholder="Ex: 75.5">
                    </div>

                    <div class="form-group">
                        <label>Altura (m)</label>
                        <input type="number" name="altura" step="0.01" placeholder="Ex: 1.75">
                    </div>

                    <div class="form-group">
                        <label>Tipo Sanguíneo</label>
                        <select name="tipo_sanguineo">
                            <option value="">Selecione...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success">Cadastrar Atleta</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Preview da foto
        function previewFoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('previewFoto');
            const img = document.getElementById('imgPreview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.add('show');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.remove('show');
            }
        }

        // Verificar idade e exibir campos de responsável
        function verificarIdade() {
            const dataNascimento = document.getElementById('dataNascimento').value;
            if (!dataNascimento) return;

            const hoje = new Date();
            const nascimento = new Date(dataNascimento);
            let idade = hoje.getFullYear() - nascimento.getFullYear();
            const mes = hoje.getMonth() - nascimento.getMonth();

            if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                idade--;
            }

            const idadeInfo = document.getElementById('idadeInfo');
            const camposResponsavel = document.getElementById('camposResponsavel');
            const responsavelInputs = ['responsavelNome', 'responsavelCpf', 'responsavelTelefone', 'responsavelParentesco'];

            idadeInfo.textContent = `Idade: ${idade} anos`;

            if (idade < 18) {
                idadeInfo.style.color = '#ef4444';
                idadeInfo.textContent += ' - Responsável legal obrigatório';
                camposResponsavel.style.display = 'block';

                // Tornar campos obrigatórios
                responsavelInputs.forEach(id => {
                    document.getElementById(id).required = true;
                });
            } else {
                idadeInfo.style.color = '#10b981';
                camposResponsavel.style.display = 'none';

                // Remover obrigatoriedade
                responsavelInputs.forEach(id => {
                    document.getElementById(id).required = false;
                });
            }
        }

        // Verificar idade ao carregar (se já tiver data preenchida)
        window.addEventListener('DOMContentLoaded', function() {
            verificarIdade();
        });
    </script>
</body>
</html>
