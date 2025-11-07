<?php
require_once '../config/config.php';
requireAdminLogin();

$pdo = getDBConnection();

// Obter mês e ano atual ou do filtro
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$filtroModalidade = $_GET['modalidade'] ?? '';

// Validar mês e ano
if ($mes < 1 || $mes > 12) $mes = (int)date('m');
if ($ano < 2020 || $ano > 2030) $ano = (int)date('Y');

// Buscar modalidades para filtro
$modalidades = $pdo->query("SELECT id, nome FROM modalidades ORDER BY nome")->fetchAll();

// Buscar competições do mês
$primeiroDia = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimoDia = date('Y-m-t', strtotime($primeiroDia));

$sql = "
    SELECT
        c.*,
        m.nome as modalidade_nome,
        m.icone as modalidade_icone,
        (SELECT COUNT(*) FROM inscricoes_competicoes WHERE competicao_id = c.id) as total_inscricoes
    FROM competicoes c
    LEFT JOIN modalidades m ON c.modalidade_id = m.id
    WHERE (
        (c.data_inicio_evento BETWEEN ? AND ?)
        OR (c.data_fim_evento BETWEEN ? AND ?)
        OR (c.data_inicio_inscricoes BETWEEN ? AND ?)
        OR (c.data_fim_inscricoes BETWEEN ? AND ?)
    )
";

$params = [$primeiroDia, $ultimoDia, $primeiroDia, $ultimoDia, $primeiroDia, $ultimoDia, $primeiroDia, $ultimoDia];

if (!empty($filtroModalidade)) {
    $sql .= " AND c.modalidade_id = ?";
    $params[] = $filtroModalidade;
}

$sql .= " ORDER BY c.data_inicio_evento";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$competicoes = $stmt->fetchAll();

// Organizar competições por data
$eventosPorDia = [];
foreach ($competicoes as $comp) {
    // Data de início do evento
    $dataInicio = date('Y-m-d', strtotime($comp['data_inicio_evento']));
    if (!isset($eventosPorDia[$dataInicio])) {
        $eventosPorDia[$dataInicio] = [];
    }
    $eventosPorDia[$dataInicio][] = [
        'tipo' => 'evento_inicio',
        'competicao' => $comp
    ];

    // Data de início das inscrições
    $dataInicioInsc = date('Y-m-d', strtotime($comp['data_inicio_inscricoes']));
    if (!isset($eventosPorDia[$dataInicioInsc])) {
        $eventosPorDia[$dataInicioInsc] = [];
    }
    $eventosPorDia[$dataInicioInsc][] = [
        'tipo' => 'inscricao_inicio',
        'competicao' => $comp
    ];

    // Data de fim das inscrições
    $dataFimInsc = date('Y-m-d', strtotime($comp['data_fim_inscricoes']));
    if (!isset($eventosPorDia[$dataFimInsc])) {
        $eventosPorDia[$dataFimInsc] = [];
    }
    $eventosPorDia[$dataFimInsc][] = [
        'tipo' => 'inscricao_fim',
        'competicao' => $comp
    ];
}

// Nomes dos meses e dias
$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$pageTitle = 'Calendário de Eventos';
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
        .calendario {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .calendario-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .calendario-header div {
            padding: 15px;
            text-align: center;
            font-weight: bold;
            color: #495057;
        }

        .calendario-body {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            min-height: 500px;
        }

        .dia {
            border: 1px solid #e9ecef;
            padding: 8px;
            min-height: 100px;
            position: relative;
            cursor: pointer;
            transition: background 0.2s;
        }

        .dia:hover {
            background: #f8f9fa;
        }

        .dia-numero {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .dia.outro-mes {
            background: #f8f9fa;
            opacity: 0.5;
        }

        .dia.hoje {
            background: #e7f3ff;
        }

        .dia.hoje .dia-numero {
            background: #0d6efd;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .evento-badge {
            font-size: 10px;
            padding: 2px 5px;
            margin: 2px 0;
            border-radius: 3px;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .evento-inicio {
            background: #0d6efd;
            color: white;
        }

        .evento-inscricao {
            background: #198754;
            color: white;
        }

        .evento-inscricao-fim {
            background: #dc3545;
            color: white;
        }

        .legenda {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legenda-cor {
            width: 20px;
            height: 12px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shield-alt"></i> Admin - Sistema de Competições
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
                        <a class="nav-link" href="competicoes.php">
                            <i class="fas fa-trophy"></i> Competições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="inscricoes.php">
                            <i class="fas fa-clipboard-list"></i> Inscrições
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="calendario.php">
                            <i class="fas fa-calendar-alt"></i> Calendário
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_nome']); ?>
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

    <div class="container-fluid mt-4 mb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-calendar-alt text-primary"></i>
                Calendário de Eventos
            </h2>
        </div>

        <!-- Controles e Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-list"></i> Filtrar por Modalidade
                        </label>
                        <select class="form-select" id="filtroModalidade" onchange="aplicarFiltro()">
                            <option value="">Todas as Modalidades</option>
                            <?php foreach ($modalidades as $mod): ?>
                                <option value="<?php echo $mod['id']; ?>"
                                        <?php echo $filtroModalidade == $mod['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <a href="?mes=<?php echo $mes == 1 ? 12 : $mes - 1; ?>&ano=<?php echo $mes == 1 ? $ano - 1 : $ano; ?><?php echo $filtroModalidade ? '&modalidade=' . $filtroModalidade : ''; ?>"
                               class="btn btn-outline-primary">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                            <a href="?mes=<?php echo date('m'); ?>&ano=<?php echo date('Y'); ?><?php echo $filtroModalidade ? '&modalidade=' . $filtroModalidade : ''; ?>"
                               class="btn btn-primary">
                                Hoje
                            </a>
                            <a href="?mes=<?php echo $mes == 12 ? 1 : $mes + 1; ?>&ano=<?php echo $mes == 12 ? $ano + 1 : $ano; ?><?php echo $filtroModalidade ? '&modalidade=' . $filtroModalidade : ''; ?>"
                               class="btn btn-outline-primary">
                                Próximo <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <h4 class="mb-0"><?php echo $nomesMeses[$mes] . ' de ' . $ano; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legenda -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="legenda">
                    <div class="legenda-item">
                        <div class="legenda-cor evento-inicio"></div>
                        <span>Início do Evento</span>
                    </div>
                    <div class="legenda-item">
                        <div class="legenda-cor evento-inscricao"></div>
                        <span>Abertura de Inscrições</span>
                    </div>
                    <div class="legenda-item">
                        <div class="legenda-cor evento-inscricao-fim"></div>
                        <span>Encerramento de Inscrições</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendário -->
        <div class="calendario">
            <div class="calendario-header">
                <div>Domingo</div>
                <div>Segunda</div>
                <div>Terça</div>
                <div>Quarta</div>
                <div>Quinta</div>
                <div>Sexta</div>
                <div>Sábado</div>
            </div>
            <div class="calendario-body">
                <?php
                $primeiroDiaMes = mktime(0, 0, 0, $mes, 1, $ano);
                $diasNoMes = date('t', $primeiroDiaMes);
                $diaSemanaInicio = date('w', $primeiroDiaMes);
                $hoje = date('Y-m-d');

                // Dias do mês anterior
                $mesAnterior = $mes == 1 ? 12 : $mes - 1;
                $anoAnterior = $mes == 1 ? $ano - 1 : $ano;
                $diasMesAnterior = date('t', mktime(0, 0, 0, $mesAnterior, 1, $anoAnterior));

                for ($i = $diaSemanaInicio - 1; $i >= 0; $i--) {
                    $dia = $diasMesAnterior - $i;
                    echo "<div class='dia outro-mes'><div class='dia-numero'>$dia</div></div>";
                }

                // Dias do mês atual
                for ($dia = 1; $dia <= $diasNoMes; $dia++) {
                    $dataAtual = "$ano-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
                    $isHoje = $dataAtual === $hoje;
                    $classeHoje = $isHoje ? 'hoje' : '';

                    echo "<div class='dia $classeHoje' onclick='verEventosDia(\"$dataAtual\")'>";
                    echo "<div class='dia-numero'>$dia</div>";

                    // Mostrar eventos do dia
                    if (isset($eventosPorDia[$dataAtual])) {
                        foreach ($eventosPorDia[$dataAtual] as $evento) {
                            $tipo = $evento['tipo'];
                            $comp = $evento['competicao'];

                            if ($tipo === 'evento_inicio') {
                                echo "<span class='evento-badge evento-inicio' title='" . htmlspecialchars($comp['nome']) . "'>";
                                echo "<i class='fas fa-trophy'></i> " . htmlspecialchars(substr($comp['nome'], 0, 15));
                                echo "</span>";
                            } elseif ($tipo === 'inscricao_inicio') {
                                echo "<span class='evento-badge evento-inscricao' title='Inscrições abrem: " . htmlspecialchars($comp['nome']) . "'>";
                                echo "<i class='fas fa-door-open'></i> Insc. abre";
                                echo "</span>";
                            } elseif ($tipo === 'inscricao_fim') {
                                echo "<span class='evento-badge evento-inscricao-fim' title='Inscrições encerram: " . htmlspecialchars($comp['nome']) . "'>";
                                echo "<i class='fas fa-door-closed'></i> Insc. fecha";
                                echo "</span>";
                            }
                        }
                    }

                    echo "</div>";
                }

                // Completar semana com dias do próximo mês
                $diasExibidos = $diaSemanaInicio + $diasNoMes;
                $diasRestantes = 7 - ($diasExibidos % 7);
                if ($diasRestantes < 7) {
                    for ($dia = 1; $dia <= $diasRestantes; $dia++) {
                        echo "<div class='dia outro-mes'><div class='dia-numero'>$dia</div></div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal - Eventos do Dia -->
    <div class="modal fade" id="modalEventosDia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-day"></i> Eventos do Dia
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalEventosDiaConteudo">
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-3 mt-5">
        <div class="container text-center text-muted">
            <small>&copy; 2025 Sistema de Gestão de Competições Esportivas</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const eventosPorDia = <?php echo json_encode($eventosPorDia, JSON_UNESCAPED_UNICODE); ?>;

        function aplicarFiltro() {
            const modalidade = document.getElementById('filtroModalidade').value;
            const url = new URL(window.location.href);
            if (modalidade) {
                url.searchParams.set('modalidade', modalidade);
            } else {
                url.searchParams.delete('modalidade');
            }
            window.location.href = url.toString();
        }

        function verEventosDia(data) {
            const eventos = eventosPorDia[data];
            const modal = new bootstrap.Modal(document.getElementById('modalEventosDia'));
            const conteudo = document.getElementById('modalEventosDiaConteudo');

            if (!eventos || eventos.length === 0) {
                conteudo.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Nenhum evento nesta data.</div>';
                modal.show();
                return;
            }

            // Agrupar eventos por competição
            const competicoes = {};
            eventos.forEach(evento => {
                const compId = evento.competicao.id;
                if (!competicoes[compId]) {
                    competicoes[compId] = {
                        dados: evento.competicao,
                        tipos: []
                    };
                }
                competicoes[compId].tipos.push(evento.tipo);
            });

            let html = '<div class="list-group">';

            Object.values(competicoes).forEach(comp => {
                html += '<div class="list-group-item">';
                html += '<h6 class="mb-2"><i class="fas fa-trophy text-primary"></i> ' + comp.dados.nome + '</h6>';

                if (comp.dados.modalidade_nome) {
                    html += '<p class="mb-2"><strong>Modalidade:</strong> ' + comp.dados.modalidade_nome + '</p>';
                }

                html += '<div class="mb-2">';
                comp.tipos.forEach(tipo => {
                    if (tipo === 'evento_inicio') {
                        html += '<span class="badge bg-primary me-2"><i class="fas fa-trophy"></i> Início do Evento</span>';
                    } else if (tipo === 'inscricao_inicio') {
                        html += '<span class="badge bg-success me-2"><i class="fas fa-door-open"></i> Abertura de Inscrições</span>';
                    } else if (tipo === 'inscricao_fim') {
                        html += '<span class="badge bg-danger me-2"><i class="fas fa-door-closed"></i> Encerramento de Inscrições</span>';
                    }
                });
                html += '</div>';

                html += '<small class="text-muted">';
                html += '<i class="fas fa-calendar"></i> Evento: ' + formatarData(comp.dados.data_inicio_evento) + ' até ' + formatarData(comp.dados.data_fim_evento);
                html += '</small>';

                html += '</div>';
            });

            html += '</div>';

            conteudo.innerHTML = html;
            modal.show();
        }

        function formatarData(data) {
            const partes = data.split('-');
            return partes[2] + '/' + partes[1] + '/' + partes[0];
        }
    </script>
</body>
</html>
