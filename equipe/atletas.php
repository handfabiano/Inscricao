<?php
require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();

// Buscar filtros
$busca = $_GET['busca'] ?? '';
$genero = $_GET['genero'] ?? '';

// Montar query
$sql = "SELECT * FROM atletas WHERE equipe_atual_id = ? AND ativo = 1";
$params = [$_SESSION['equipe_id']];

if ($busca) {
    $sql .= " AND (nome_completo LIKE ? OR cpf LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($genero) {
    $sql .= " AND genero = ?";
    $params[] = $genero;
}

$sql .= " ORDER BY nome_completo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$atletas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Atletas - Sistema v3.0</title>
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
        <div class="d-flex justify-between align-center mb-3">
            <h2>Meus Atletas (<?php echo count($atletas); ?>)</h2>
            <a href="cadastrar_atleta.php" class="btn btn-success">+ Cadastrar Atleta</a>
        </div>

        <!-- Filtros -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <form method="GET" action="">
                <div class="grid grid-3">
                    <div class="form-group">
                        <label>Buscar</label>
                        <input type="text" name="busca" placeholder="Nome ou CPF" value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <div class="form-group">
                        <label>Gênero</label>
                        <select name="genero">
                            <option value="">Todos</option>
                            <option value="Masculino" <?php echo $genero === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo $genero === 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="margin-right: 0.5rem;">Filtrar</button>
                        <?php if ($busca || $genero): ?>
                            <a href="atletas.php" class="btn btn-secondary">Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de Atletas -->
        <div class="card">
            <?php if (empty($atletas)): ?>
                <div style="text-align: center; padding: 3rem; color: #64748b;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <?php if ($busca || $genero): ?>
                            Nenhum atleta encontrado com os filtros aplicados.
                        <?php else: ?>
                            Nenhum atleta cadastrado ainda.
                        <?php endif; ?>
                    </p>
                    <a href="cadastrar_atleta.php" class="btn btn-success">Cadastrar Primeiro Atleta</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">Foto</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Gênero</th>
                            <th>Idade</th>
                            <th>Cadastrado</th>
                            <th style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atletas as $atleta): ?>
                            <?php $idade = calcularIdade($atleta['data_nascimento']); ?>
                            <tr>
                                <td>
                                    <?php if ($atleta['foto_path']): ?>
                                        <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                             class="foto-circular"
                                             alt="<?php echo htmlspecialchars($atleta['nome_completo']); ?>"
                                             title="<?php echo htmlspecialchars($atleta['nome_completo']); ?>">
                                    <?php else: ?>
                                        <div class="foto-circular" style="background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: bold;">
                                            <?php echo strtoupper(substr($atleta['nome_completo'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($atleta['nome_completo']); ?></strong>
                                    <?php if ($idade < 18): ?>
                                        <span class="badge badge-warning" style="margin-left: 0.5rem; font-size: 0.75rem;">Menor</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatarCPF($atleta['cpf']); ?></td>
                                <td><?php echo $atleta['genero']; ?></td>
                                <td><?php echo $idade; ?> anos</td>
                                <td><?php echo formatarData($atleta['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="verDetalhes(<?php echo $atleta['id']; ?>)">
                                        Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="padding: 1rem; text-align: center; color: #64748b; font-size: 0.9rem;">
                    Total de <?php echo count($atletas); ?> atleta(s) cadastrado(s)
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="index.php" class="btn btn-secondary">Voltar ao Dashboard</a>
        </div>
    </main>

    <!-- Modal Detalhes do Atleta -->
    <div id="modalDetalhes" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Atleta</h2>
                <button class="close" onclick="fecharModal()">&times;</button>
            </div>
            <div id="conteudoDetalhes" style="padding: 1rem;">
                <p style="text-align: center; color: #64748b;">Carregando...</p>
            </div>
        </div>
    </div>

    <script>
        function verDetalhes(atletaId) {
            const modal = document.getElementById('modalDetalhes');
            const conteudo = document.getElementById('conteudoDetalhes');

            modal.classList.add('show');
            conteudo.innerHTML = '<p style="text-align: center; color: #64748b;">Carregando...</p>';

            // Buscar dados do atleta
            fetch(`get_atleta.php?id=${atletaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        conteudo.innerHTML = `<p style="color: #ef4444;">${data.erro}</p>`;
                        return;
                    }

                    const atleta = data;
                    let html = '';

                    // Foto
                    if (atleta.foto_path) {
                        html += `<div style="text-align: center; margin-bottom: 1.5rem;">
                            <img src="<?php echo FOTO_URL; ?>${atleta.foto_path}"
                                 style="width: 150px; height: 200px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                        </div>`;
                    }

                    // Dados pessoais
                    html += `<h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Dados Pessoais</h3>`;
                    html += `<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">`;
                    html += `<div><strong>Nome:</strong><br>${atleta.nome_completo}</div>`;
                    html += `<div><strong>CPF:</strong><br>${atleta.cpf_formatado}</div>`;
                    html += `<div><strong>RG:</strong><br>${atleta.rg}</div>`;
                    html += `<div><strong>Data Nascimento:</strong><br>${atleta.data_nascimento_formatada}</div>`;
                    html += `<div><strong>Gênero:</strong><br>${atleta.genero}</div>`;
                    html += `<div><strong>Idade:</strong><br>${atleta.idade} anos</div>`;
                    html += `</div>`;

                    // Contato
                    if (atleta.email || atleta.telefone || atleta.celular) {
                        html += `<h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Contato</h3>`;
                        html += `<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">`;
                        if (atleta.email) html += `<div><strong>Email:</strong><br>${atleta.email}</div>`;
                        if (atleta.telefone) html += `<div><strong>Telefone:</strong><br>${atleta.telefone_formatado}</div>`;
                        if (atleta.celular) html += `<div><strong>Celular:</strong><br>${atleta.celular_formatado}</div>`;
                        html += `</div>`;
                    }

                    // Endereço
                    if (atleta.endereco) {
                        html += `<h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Endereço</h3>`;
                        html += `<div style="margin-bottom: 1.5rem;">`;
                        html += `<p>${atleta.endereco}${atleta.numero ? ', ' + atleta.numero : ''}${atleta.complemento ? ' - ' + atleta.complemento : ''}</p>`;
                        html += `<p>${atleta.bairro ? atleta.bairro + ' - ' : ''}${atleta.cidade}/${atleta.estado}</p>`;
                        if (atleta.cep) html += `<p>CEP: ${atleta.cep}</p>`;
                        html += `</div>`;
                    }

                    // Responsável Legal
                    if (atleta.responsavel_legal_nome) {
                        html += `<h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Responsável Legal</h3>`;
                        html += `<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">`;
                        html += `<div><strong>Nome:</strong><br>${atleta.responsavel_legal_nome}</div>`;
                        html += `<div><strong>Parentesco:</strong><br>${atleta.responsavel_legal_parentesco}</div>`;
                        html += `<div><strong>CPF:</strong><br>${atleta.responsavel_legal_cpf_formatado}</div>`;
                        html += `<div><strong>Telefone:</strong><br>${atleta.responsavel_legal_telefone_formatado}</div>`;
                        html += `</div>`;
                    }

                    // Dados Físicos
                    if (atleta.peso || atleta.altura || atleta.tipo_sanguineo) {
                        html += `<h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0;">Dados Físicos</h3>`;
                        html += `<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">`;
                        if (atleta.peso) html += `<div><strong>Peso:</strong><br>${atleta.peso} kg</div>`;
                        if (atleta.altura) html += `<div><strong>Altura:</strong><br>${atleta.altura} m</div>`;
                        if (atleta.tipo_sanguineo) html += `<div><strong>Tipo Sanguíneo:</strong><br>${atleta.tipo_sanguineo}</div>`;
                        html += `</div>`;
                    }

                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    conteudo.innerHTML = '<p style="color: #ef4444;">Erro ao carregar dados</p>';
                });
        }

        function fecharModal() {
            document.getElementById('modalDetalhes').classList.remove('show');
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetalhes');
            if (event.target === modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
