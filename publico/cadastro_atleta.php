<?php
require_once '../config/config.php';

$pdo = getDBConnection();

// Verificar token
$token = $_GET['token'] ?? '';
$mensagemErro = '';

if (empty($token)) {
    $mensagemErro = "Link de convite inválido. Entre em contato com sua equipe.";
}

// Buscar informações do convite
$convite = null;
$equipe = null;

if (!$mensagemErro) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, e.nome as equipe_nome, e.cidade as equipe_municipio
            FROM convites_atletas c
            INNER JOIN equipes e ON c.equipe_id = e.id
            WHERE c.token = ?
        ");
        $stmt->execute([$token]);
        $convite = $stmt->fetch();

        if (!$convite) {
            $mensagemErro = "Convite não encontrado ou inválido.";
        } elseif ($convite['status'] === 'Aceito') {
            $mensagemErro = "Este convite já foi utilizado.";
        } else {
            // Compatibilidade com nomes antigos e novos de colunas
            $data_expiracao = isset($convite['data_expiracao']) ? $convite['data_expiracao'] : $convite['validade_ate'];

            if ($convite['status'] === 'Expirado' || strtotime($data_expiracao) < time()) {
                $mensagemErro = "Este convite expirou. Solicite um novo convite à sua equipe.";
            }
        }

    } catch (Exception $e) {
        $mensagemErro = "Erro ao verificar convite: " . $e->getMessage();
    }
}

$pageTitle = 'Cadastro de Atleta';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
        }
        .header-convite {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-bottom: 3px solid #dee2e6;
            color: #6c757d;
        }
        .step.active {
            border-bottom-color: #667eea;
            color: #667eea;
            font-weight: bold;
        }
        .step.completed {
            border-bottom-color: #198754;
            color: #198754;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .required-indicator {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <?php if ($mensagemErro): ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Oops!</h4>
                    <p class="mb-0"><?php echo htmlspecialchars($mensagemErro); ?></p>
                    <hr>
                    <a href="../equipe/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Ir para Login
                    </a>
                </div>
            <?php else: ?>
                <!-- Header com info do convite -->
                <div class="header-convite">
                    <h2><i class="fas fa-user-plus"></i> Bem-vindo(a)!</h2>
                    <p class="mb-0">Você foi convidado(a) para fazer parte da equipe:</p>
                    <h3 class="mt-2"><strong><?php echo htmlspecialchars($convite['equipe_nome']); ?></strong></h3>
                    <p class="mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($convite['equipe_municipio']); ?></p>
                    <?php if ($convite['nome_atleta']): ?>
                        <p class="mt-2 mb-0"><small>Convite para: <?php echo htmlspecialchars($convite['nome_atleta']); ?></small></p>
                    <?php endif; ?>
                </div>

                <!-- Indicador de Passos -->
                <div class="step-indicator">
                    <div class="step active">
                        <i class="fas fa-user"></i><br>
                        Dados Pessoais
                    </div>
                    <div class="step">
                        <i class="fas fa-map-marker-alt"></i><br>
                        Endereço
                    </div>
                    <div class="step">
                        <i class="fas fa-running"></i><br>
                        Dados Esportivos
                    </div>
                    <div class="step">
                        <i class="fas fa-file-upload"></i><br>
                        Documentos
                    </div>
                </div>

                <!-- Formulário -->
                <form id="formCadastro" action="processar_cadastro_atleta.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="equipe_id" value="<?php echo $convite['equipe_id']; ?>">

                    <!-- Dados Pessoais -->
                    <div class="form-section">
                        <h5><i class="fas fa-user text-primary"></i> Dados Pessoais</h5>
                        <p class="text-muted small mb-3">Campos com <span class="required-indicator">*</span> são obrigatórios</p>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Nome Completo <span class="required-indicator">*</span></label>
                                <input type="text" name="nome_completo" class="form-control" required
                                       value="<?php echo htmlspecialchars($convite['nome_atleta'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CPF <span class="required-indicator">*</span></label>
                                <input type="text" name="cpf" class="form-control" maxlength="14" required
                                       placeholder="000.000.000-00" id="cpf">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">RG <span class="required-indicator">*</span></label>
                                <input type="text" name="rg" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data de Nascimento <span class="required-indicator">*</span></label>
                                <input type="date" name="data_nascimento" class="form-control" required id="dataNascimento">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gênero <span class="required-indicator">*</span></label>
                                <select name="genero" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Feminino">Feminino</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Telefone <span class="required-indicator">*</span></label>
                                <input type="tel" name="telefone" class="form-control" required
                                       placeholder="(00) 00000-0000" id="telefone">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Email <span class="required-indicator">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($convite['email_atleta'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="form-section">
                        <h5><i class="fas fa-map-marker-alt text-success"></i> Endereço</h5>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CEP <span class="required-indicator">*</span></label>
                                <input type="text" name="cep" class="form-control" maxlength="9" required
                                       placeholder="00000-000" id="cep">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Logradouro <span class="required-indicator">*</span></label>
                                <input type="text" name="endereco" class="form-control" required id="endereco">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Número <span class="required-indicator">*</span></label>
                                <input type="text" name="numero" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="complemento" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bairro <span class="required-indicator">*</span></label>
                                <input type="text" name="bairro" class="form-control" required id="bairro">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Cidade <span class="required-indicator">*</span></label>
                                <input type="text" name="cidade" class="form-control" required id="cidade">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Estado <span class="required-indicator">*</span></label>
                                <select name="estado" class="form-select" required id="estado">
                                    <option value="">Selecione</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapá</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceará</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espírito Santo</option>
                                    <option value="GO">Goiás</option>
                                    <option value="MA">Maranhão</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Pará</option>
                                    <option value="PB">Paraíba</option>
                                    <option value="PR">Paraná</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piauí</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP">São Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dados Esportivos -->
                    <div class="form-section">
                        <h5><i class="fas fa-running text-warning"></i> Dados Esportivos</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modalidade Principal</label>
                                <input type="text" name="modalidade_principal" class="form-control"
                                       placeholder="Ex: Futebol, Vôlei, Basquete...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Posição/Função</label>
                                <input type="text" name="posicao" class="form-control"
                                       placeholder="Ex: Atacante, Levantador, Armador...">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Experiência Anterior (Opcional)</label>
                                <textarea name="experiencia" class="form-control" rows="3"
                                          placeholder="Descreva sua experiência em competições, times anteriores, etc."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Documentos -->
                    <div class="form-section">
                        <h5><i class="fas fa-file-upload text-info"></i> Documentos</h5>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle"></i>
                            Formatos aceitos: JPG, PNG, PDF | Tamanho máximo: 5MB por arquivo
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Foto 3x4 <span class="required-indicator">*</span></label>
                                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Documento de Identidade <span class="required-indicator">*</span></label>
                                <input type="file" name="documento_identidade" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Comprovante de Residência <span class="required-indicator">*</span></label>
                                <input type="file" name="comprovante_residencia" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Atestado Médico (Opcional)</label>
                                <input type="file" name="atestado_medico" class="form-control"
                                       accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>
                    </div>

                    <!-- Termos -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="aceite_termos" name="aceite_termos" required>
                        <label class="form-check-label" for="aceite_termos">
                            Declaro que as informações fornecidas são verdadeiras e autorizo o uso dos meus dados para fins de
                            cadastro e participação em competições esportivas. <span class="required-indicator">*</span>
                        </label>
                    </div>

                    <!-- Botões -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-circle"></i> Completar Cadastro
                        </button>
                        <p class="text-center text-muted small mb-0">
                            Ao completar, você será vinculado automaticamente à equipe
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscaras
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = value;
        });

        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });

        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });

        // Buscar CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        }
                    });
            }
        });

        // Validação de idade (verificar se é menor de 18 anos)
        document.getElementById('dataNascimento').addEventListener('change', function() {
            const dataNasc = new Date(this.value);
            const hoje = new Date();
            let idade = hoje.getFullYear() - dataNasc.getFullYear();
            const mes = hoje.getMonth() - dataNasc.getMonth();
            if (mes < 0 || (mes === 0 && hoje.getDate() < dataNasc.getDate())) {
                idade--;
            }

            if (idade < 18) {
                alert('Atenção: Atletas menores de 18 anos precisam de autorização dos responsáveis. Entre em contato com sua equipe para providenciar a documentação necessária.');
            }
        });
    </script>
</body>
</html>
