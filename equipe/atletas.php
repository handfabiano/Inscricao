<?php
/**
 * Página de Listagem de Atletas
 * Exibe todos os atletas da equipe com filtros e detalhes
 */

require_once '../config/config.php';
requireEquipeLogin();

$pdo = getDBConnection();
$equipeId = $_SESSION['equipe_id'];

// Buscar filtros
$busca = $_GET['busca'] ?? '';
$genero = $_GET['genero'] ?? '';

// Montar query com filtros
$sql = "SELECT * FROM atletas WHERE equipe_atual_id = ? AND ativo = 1";
$params = [$equipeId];

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

$pageTitle = 'Meus Atletas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Competições</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
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
                        <a class="nav-link" href="minhas_inscricoes.php">
                            <i class="fas fa-history"></i> Histórico
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
        <!-- Mensagens -->
        <?php exibirMensagem(); ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-users"></i> Meus Atletas</h2>
                <p class="text-muted mb-0">Total: <?php echo count($atletas); ?> atleta(s) cadastrado(s)</p>
            </div>
            <a href="cadastrar_atleta.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Cadastrar Atleta
            </a>
        </div>

        <!-- Filtros -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <label for="busca" class="form-label">
                            <i class="fas fa-search"></i> Buscar
                        </label>
                        <input type="text"
                               class="form-control"
                               id="busca"
                               name="busca"
                               placeholder="Nome ou CPF"
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="genero" class="form-label">
                            <i class="fas fa-venus-mars"></i> Gênero
                        </label>
                        <select class="form-select" id="genero" name="genero">
                            <option value="">Todos</option>
                            <option value="Masculino" <?php echo $genero === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo $genero === 'Feminino' ? 'selected' : ''; ?>>Feminino</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <?php if ($busca || $genero): ?>
                            <a href="atletas.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Atletas -->
        <?php if (empty($atletas)): ?>
            <div class="card text-center p-5 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-users text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mb-3">
                        <?php if ($busca || $genero): ?>
                            Nenhum atleta encontrado com os filtros aplicados
                        <?php else: ?>
                            Nenhum atleta cadastrado ainda
                        <?php endif; ?>
                    </h4>
                    <?php if (!$busca && !$genero): ?>
                        <a href="cadastrar_atleta.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus"></i> Cadastrar Primeiro Atleta
                        </a>
                    <?php else: ?>
                        <a href="atletas.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Tabela Responsiva -->
            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Foto</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th class="text-center">Gênero</th>
                                <th class="text-center">Idade</th>
                                <th>Cadastrado</th>
                                <th class="text-center" style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($atletas as $atleta):
                                $idade = calcularIdade($atleta['data_nascimento']);
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($atleta['foto_path']): ?>
                                            <img src="<?php echo FOTO_URL . $atleta['foto_path']; ?>"
                                                 class="foto-circular"
                                                 alt="<?php echo htmlspecialchars($atleta['nome_completo']); ?>"
                                                 title="<?php echo htmlspecialchars($atleta['nome_completo']); ?>">
                                        <?php else: ?>
                                            <div class="foto-circular bg-secondary text-white d-flex align-items-center justify-content-center fw-bold">
                                                <?php echo strtoupper(substr($atleta['nome_completo'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($atleta['nome_completo']); ?></strong>
                                        <?php if ($idade < 18): ?>
                                            <span class="badge bg-warning text-dark ms-1">
                                                <i class="fas fa-child"></i> Menor
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="text-dark"><?php echo formatarCPF($atleta['cpf']); ?></code>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($atleta['genero'] === 'Masculino'): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-mars"></i> Masculino
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-venus"></i> Feminino
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?php echo $idade; ?></strong> anos
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo formatarData($atleta['created_at']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary"
                                                onclick="verDetalhes(<?php echo $atleta['id']; ?>)"
                                                title="Ver detalhes completos">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center text-muted bg-light">
                    <i class="fas fa-info-circle"></i>
                    Exibindo <?php echo count($atletas); ?> atleta(s)
                    <?php if ($busca || $genero): ?>
                        <span class="badge bg-primary ms-2">Filtros ativos</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Detalhes do Atleta -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalhesLabel">
                        <i class="fas fa-user-circle"></i> Detalhes do Atleta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="conteudoDetalhes">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="text-muted mt-3">Carregando dados do atleta...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modalInstance = null;

        function verDetalhes(atletaId) {
            const modal = document.getElementById('modalDetalhes');
            const conteudo = document.getElementById('conteudoDetalhes');

            // Mostrar loading
            conteudo.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="text-muted mt-3">Carregando dados do atleta...</p>
                </div>
            `;

            // Abrir modal
            modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            // Buscar dados do atleta via AJAX
            fetch(`get_atleta.php?id=${atletaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        conteudo.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.erro}
                            </div>
                        `;
                        return;
                    }

                    const atleta = data;
                    let html = '';

                    // Foto
                    if (atleta.foto_path) {
                        html += `
                            <div class="text-center mb-4">
                                <img src="<?php echo FOTO_URL; ?>${atleta.foto_path}"
                                     class="img-thumbnail"
                                     style="max-width: 200px; max-height: 250px; object-fit: cover; border-radius: 8px;"
                                     alt="${atleta.nome_completo}">
                            </div>
                        `;
                    }

                    // Dados Pessoais
                    html += `
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-user text-primary"></i> Dados Pessoais
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <strong class="text-muted d-block small">Nome Completo</strong>
                                <div>${atleta.nome_completo}</div>
                            </div>
                            <div class="col-md-3">
                                <strong class="text-muted d-block small">CPF</strong>
                                <div><code>${atleta.cpf_formatado}</code></div>
                            </div>
                            <div class="col-md-3">
                                <strong class="text-muted d-block small">RG</strong>
                                <div>${atleta.rg || 'N/A'}</div>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-muted d-block small">Data de Nascimento</strong>
                                <div>${atleta.data_nascimento_formatada}</div>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-muted d-block small">Gênero</strong>
                                <div>
                                    ${atleta.genero === 'Masculino'
                                        ? '<span class="badge bg-primary"><i class="fas fa-mars"></i> Masculino</span>'
                                        : '<span class="badge bg-info"><i class="fas fa-venus"></i> Feminino</span>'}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-muted d-block small">Idade</strong>
                                <div><strong>${atleta.idade}</strong> anos</div>
                            </div>
                        </div>
                    `;

                    // Contato
                    if (atleta.email || atleta.telefone || atleta.celular) {
                        html += `
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-phone text-primary"></i> Contato
                            </h5>
                            <div class="row g-3 mb-4">
                        `;
                        if (atleta.email) {
                            html += `
                                <div class="col-md-6">
                                    <strong class="text-muted d-block small">E-mail</strong>
                                    <div><a href="mailto:${atleta.email}">${atleta.email}</a></div>
                                </div>
                            `;
                        }
                        if (atleta.telefone) {
                            html += `
                                <div class="col-md-3">
                                    <strong class="text-muted d-block small">Telefone</strong>
                                    <div>${atleta.telefone_formatado}</div>
                                </div>
                            `;
                        }
                        if (atleta.celular) {
                            html += `
                                <div class="col-md-3">
                                    <strong class="text-muted d-block small">Celular</strong>
                                    <div>${atleta.celular_formatado}</div>
                                </div>
                            `;
                        }
                        html += `</div>`;
                    }

                    // Endereço
                    if (atleta.endereco) {
                        html += `
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-map-marker-alt text-primary"></i> Endereço
                            </h5>
                            <div class="mb-4">
                                <p class="mb-1">
                                    ${atleta.endereco}${atleta.numero ? ', ' + atleta.numero : ''}${atleta.complemento ? ' - ' + atleta.complemento : ''}
                                </p>
                                <p class="mb-1">
                                    ${atleta.bairro ? atleta.bairro + ' - ' : ''}${atleta.cidade}/${atleta.estado}
                                </p>
                                ${atleta.cep ? `<p class="mb-0">CEP: ${atleta.cep}</p>` : ''}
                            </div>
                        `;
                    }

                    // Responsável Legal
                    if (atleta.responsavel_legal_nome) {
                        html += `
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user-shield text-primary"></i> Responsável Legal
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <strong class="text-muted d-block small">Nome</strong>
                                    <div>${atleta.responsavel_legal_nome}</div>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-muted d-block small">Parentesco</strong>
                                    <div>${atleta.responsavel_legal_parentesco}</div>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-muted d-block small">CPF</strong>
                                    <div><code>${atleta.responsavel_legal_cpf_formatado}</code></div>
                                </div>
                                <div class="col-md-6">
                                    <strong class="text-muted d-block small">Telefone</strong>
                                    <div>${atleta.responsavel_legal_telefone_formatado}</div>
                                </div>
                            </div>
                        `;
                    }

                    // Dados Físicos
                    if (atleta.peso || atleta.altura || atleta.tipo_sanguineo) {
                        html += `
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-heartbeat text-primary"></i> Dados Físicos
                            </h5>
                            <div class="row g-3">
                        `;
                        if (atleta.peso) {
                            html += `
                                <div class="col-md-4">
                                    <strong class="text-muted d-block small">Peso</strong>
                                    <div>${atleta.peso} kg</div>
                                </div>
                            `;
                        }
                        if (atleta.altura) {
                            html += `
                                <div class="col-md-4">
                                    <strong class="text-muted d-block small">Altura</strong>
                                    <div>${atleta.altura} m</div>
                                </div>
                            `;
                        }
                        if (atleta.tipo_sanguineo) {
                            html += `
                                <div class="col-md-4">
                                    <strong class="text-muted d-block small">Tipo Sanguíneo</strong>
                                    <div><span class="badge bg-danger">${atleta.tipo_sanguineo}</span></div>
                                </div>
                            `;
                        }
                        html += `</div>`;
                    }

                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erro:', error);
                    conteudo.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados do atleta.
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>
