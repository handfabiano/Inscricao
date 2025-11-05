<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inscrição de Atletas</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1><i class="fas fa-running"></i> Inscrição de Atletas</h1>
            <nav>
                <a href="index.php" class="active">Inscrição</a>
                <a href="consulta.php">Consultar Inscrição</a>
                <a href="admin/login.php">Área Administrativa</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="form-wrapper">
            <div class="form-header">
                <h2>Formulário de Inscrição</h2>
                <p>Preencha todos os campos obrigatórios (*) para realizar sua inscrição</p>
            </div>

            <form id="inscricaoForm" action="includes/processar_inscricao.php" method="POST" enctype="multipart/form-data">

                <!-- Dados Pessoais -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Dados Pessoais</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label for="nome_completo">Nome Completo *</label>
                            <input type="text" id="nome_completo" name="nome_completo" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" maxlength="14" required>
                            <span class="error-message" id="cpf-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="rg">RG *</label>
                            <input type="text" id="rg" name="rg" required>
                        </div>

                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento *</label>
                            <input type="date" id="data_nascimento" name="data_nascimento" required>
                        </div>

                        <div class="form-group">
                            <label for="genero">Gênero *</label>
                            <select id="genero" name="genero" required>
                                <option value="">Selecione</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Feminino">Feminino</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Dados de Contato -->
                <div class="form-section">
                    <h3><i class="fas fa-phone"></i> Dados de Contato</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone *</label>
                            <input type="tel" id="telefone" name="telefone" required>
                        </div>

                        <div class="form-group">
                            <label for="celular">Celular</label>
                            <input type="tel" id="celular" name="celular">
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cep">CEP *</label>
                            <input type="text" id="cep" name="cep" maxlength="9" required>
                        </div>

                        <div class="form-group full">
                            <label for="endereco">Logradouro *</label>
                            <input type="text" id="endereco" name="endereco" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero">Número *</label>
                            <input type="text" id="numero" name="numero" required>
                        </div>

                        <div class="form-group">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento">
                        </div>

                        <div class="form-group">
                            <label for="bairro">Bairro *</label>
                            <input type="text" id="bairro" name="bairro" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="cidade">Cidade *</label>
                            <input type="text" id="cidade" name="cidade" required>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado *</label>
                            <select id="estado" name="estado" required>
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
                    <h3><i class="fas fa-medal"></i> Dados Esportivos</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modalidade_id">Modalidade *</label>
                            <select id="modalidade_id" name="modalidade_id" required>
                                <option value="">Selecione</option>
                                <?php
                                $pdo = getDBConnection();
                                $stmt = $pdo->query("SELECT id, nome FROM modalidades WHERE ativo = 1 ORDER BY nome");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="categoria_id">Categoria *</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, nome, idade_minima, idade_maxima FROM categorias WHERE ativo = 1 ORDER BY idade_minima");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['id']}'>{$row['nome']} ({$row['idade_minima']}-{$row['idade_maxima']} anos)</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="experiencia_anos">Anos de Experiência</label>
                            <input type="number" id="experiencia_anos" name="experiencia_anos" min="0" max="50">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full">
                            <label for="clube_anterior">Clube/Equipe Anterior</label>
                            <input type="text" id="clube_anterior" name="clube_anterior">
                        </div>
                    </div>
                </div>

                <!-- Dados do Responsável (para menores) -->
                <div class="form-section" id="responsavel-section" style="display: none;">
                    <h3><i class="fas fa-user-shield"></i> Dados do Responsável (Obrigatório para menores de 18 anos)</h3>
                    <div class="form-row">
                        <div class="form-group full">
                            <label for="responsavel_nome">Nome do Responsável</label>
                            <input type="text" id="responsavel_nome" name="responsavel_nome">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="responsavel_cpf">CPF do Responsável</label>
                            <input type="text" id="responsavel_cpf" name="responsavel_cpf" maxlength="14">
                        </div>

                        <div class="form-group">
                            <label for="responsavel_telefone">Telefone do Responsável</label>
                            <input type="tel" id="responsavel_telefone" name="responsavel_telefone">
                        </div>

                        <div class="form-group">
                            <label for="responsavel_parentesco">Parentesco</label>
                            <select id="responsavel_parentesco" name="responsavel_parentesco">
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

                <!-- Upload de Documentos -->
                <div class="form-section">
                    <h3><i class="fas fa-file-upload"></i> Documentos</h3>
                    <p class="info-text">Formatos aceitos: JPG, PNG, PDF | Tamanho máximo: 5MB por arquivo</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="foto">Foto 3x4 *</label>
                            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png" required>
                        </div>

                        <div class="form-group">
                            <label for="documento_identidade">Documento de Identidade *</label>
                            <input type="file" id="documento_identidade" name="documento_identidade" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="comprovante_residencia">Comprovante de Residência *</label>
                            <input type="file" id="comprovante_residencia" name="comprovante_residencia" accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>

                        <div class="form-group">
                            <label for="atestado_medico">Atestado Médico</label>
                            <input type="file" id="atestado_medico" name="atestado_medico" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                    </div>
                </div>

                <!-- Termos e Condições -->
                <div class="form-section">
                    <div class="checkbox-group">
                        <input type="checkbox" id="aceite_termos" name="aceite_termos" required>
                        <label for="aceite_termos">
                            Declaro que li e aceito os <a href="#" target="_blank">termos e condições</a> e autorizo o uso de meus dados para fins de inscrição *
                        </label>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">Limpar Formulário</button>
                    <button type="submit" class="btn btn-primary">Realizar Inscrição</button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistema de Inscrição de Atletas. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="public/js/script.js"></script>
</body>
</html>
