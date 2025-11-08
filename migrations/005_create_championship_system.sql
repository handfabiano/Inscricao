-- =====================================================
-- MIGRATION: Championship System (Chaveamento e Jogos)
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema completo de chaveamento, jogos e súmulas
-- =====================================================

-- Formatos de competição
CREATE TABLE IF NOT EXISTS competition_formats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL COMMENT 'Nome do formato',
    codigo VARCHAR(50) UNIQUE NOT NULL COMMENT 'Código: eliminatoria_simples, pontos_corridos, grupos_mata_mata',
    descricao TEXT,

    -- Configuração
    permite_empate BOOLEAN DEFAULT TRUE,
    criterios_desempate JSON COMMENT 'Critérios em ordem de prioridade',

    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fases da competição (grupos, oitavas, quartas, semi, final)
CREATE TABLE IF NOT EXISTS competition_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competicao_id INT NOT NULL,

    nome VARCHAR(100) NOT NULL COMMENT 'Nome da fase: Grupo A, Oitavas, Quartas...',
    tipo VARCHAR(50) NOT NULL COMMENT 'grupo, oitavas, quartas, semi, final, terceiro_lugar',
    ordem INT NOT NULL COMMENT 'Ordem de execução das fases',

    formato_id INT COMMENT 'Formato desta fase (pode variar)',

    -- Configuração da fase
    num_equipes INT COMMENT 'Número de equipes nesta fase',
    num_classificados INT COMMENT 'Quantos classificam para próxima fase',
    jogos_ida_volta BOOLEAN DEFAULT FALSE,

    -- Status
    status ENUM('agendada', 'em_andamento', 'finalizada') DEFAULT 'agendada',
    data_inicio DATE,
    data_fim DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (formato_id) REFERENCES competition_formats(id),
    INDEX idx_competicao (competicao_id),
    INDEX idx_ordem (competicao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grupos (para competições com fase de grupos)
CREATE TABLE IF NOT EXISTS competition_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phase_id INT NOT NULL,

    nome VARCHAR(10) NOT NULL COMMENT 'A, B, C, D...',
    ordem INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (phase_id) REFERENCES competition_phases(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group (phase_id, nome),
    INDEX idx_phase (phase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipes nos grupos
CREATE TABLE IF NOT EXISTS group_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    equipe_id INT NOT NULL,

    -- Estatísticas
    jogos INT DEFAULT 0,
    vitorias INT DEFAULT 0,
    empates INT DEFAULT 0,
    derrotas INT DEFAULT 0,
    gols_pro INT DEFAULT 0,
    gols_contra INT DEFAULT 0,
    saldo_gols INT DEFAULT 0,
    pontos INT DEFAULT 0,

    -- Desempate
    aproveitamento DECIMAL(5,2) DEFAULT 0.00,
    cartoes_amarelos INT DEFAULT 0,
    cartoes_vermelhos INT DEFAULT 0,

    posicao INT COMMENT 'Posição no grupo',
    classificado BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (group_id) REFERENCES competition_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id),
    UNIQUE KEY unique_team_group (group_id, equipe_id),
    INDEX idx_group (group_id),
    INDEX idx_pontos (group_id, pontos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jogos/Partidas
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competicao_id INT NOT NULL,
    phase_id INT,
    group_id INT COMMENT 'Se for jogo de grupo',

    -- Equipes
    equipe_casa_id INT NOT NULL,
    equipe_visitante_id INT NOT NULL,

    -- Placar
    placar_casa INT,
    placar_visitante INT,
    placar_prorrogacao_casa INT COMMENT 'Se houver prorrogação',
    placar_prorrogacao_visitante INT,
    placar_penaltis_casa INT COMMENT 'Se houver pênaltis',
    placar_penaltis_visitante INT,

    -- Resultado
    resultado ENUM('casa', 'visitante', 'empate', 'w.o.') COMMENT 'Resultado final',
    vencedor_id INT COMMENT 'ID do vencedor (considerando pênaltis)',

    -- Status
    status ENUM('agendado', 'ao_vivo', 'intervalo', 'finalizado', 'adiado', 'cancelado', 'w.o.') DEFAULT 'agendado',

    -- Data e local
    data_hora DATETIME,
    local VARCHAR(200),
    cidade VARCHAR(100),
    estadio VARCHAR(200),

    -- Arbitragem (será preenchido)
    arbitro_principal_id INT,
    arbitro_assistente1_id INT,
    arbitro_assistente2_id INT,
    quarto_arbitro_id INT,

    -- Súmula
    sumula_confirmada BOOLEAN DEFAULT FALSE,
    sumula_confirmada_por INT COMMENT 'ID do administrador',
    sumula_confirmada_em TIMESTAMP NULL,

    -- Observações
    observacoes TEXT,
    publico_pagante INT,
    publico_total INT,
    renda_bruta DECIMAL(10,2),

    -- Controle
    rodada INT COMMENT 'Número da rodada (para pontos corridos)',
    tipo_jogo ENUM('ida', 'volta', 'jogo_unico') DEFAULT 'jogo_unico',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES competition_phases(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES competition_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_casa_id) REFERENCES equipes(id),
    FOREIGN KEY (equipe_visitante_id) REFERENCES equipes(id),
    FOREIGN KEY (vencedor_id) REFERENCES equipes(id),

    INDEX idx_competicao (competicao_id),
    INDEX idx_fase (phase_id),
    INDEX idx_data (data_hora),
    INDEX idx_status (status),
    INDEX idx_equipes (equipe_casa_id, equipe_visitante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eventos do jogo (gols, cartões, substituições)
CREATE TABLE IF NOT EXISTS match_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,

    tipo ENUM('gol', 'gol_contra', 'cartao_amarelo', 'cartao_vermelho', 'substituicao', 'penalti_perdido', 'outro') NOT NULL,

    -- Jogador envolvido
    atleta_id INT NOT NULL,
    equipe_id INT NOT NULL,

    -- Detalhes
    minuto INT NOT NULL,
    periodo ENUM('primeiro_tempo', 'segundo_tempo', 'prorrogacao_1', 'prorrogacao_2', 'penaltis') DEFAULT 'primeiro_tempo',

    -- Para substituições
    atleta_saiu_id INT COMMENT 'Jogador que saiu (para substituição)',
    atleta_entrou_id INT COMMENT 'Jogador que entrou (para substituição)',

    -- Para gols
    assistencia_id INT COMMENT 'Quem deu a assistência',
    tipo_gol VARCHAR(50) COMMENT 'cabeça, pé direito, pé esquerdo, pênalti, falta',

    observacoes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id),
    FOREIGN KEY (equipe_id) REFERENCES equipes(id),
    FOREIGN KEY (atleta_saiu_id) REFERENCES atletas(id),
    FOREIGN KEY (atleta_entrou_id) REFERENCES atletas(id),
    FOREIGN KEY (assistencia_id) REFERENCES atletas(id),

    INDEX idx_match (match_id),
    INDEX idx_tipo (tipo),
    INDEX idx_atleta (atleta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Escalação dos jogos
CREATE TABLE IF NOT EXISTS match_lineups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    equipe_id INT NOT NULL,
    atleta_id INT NOT NULL,

    titular BOOLEAN DEFAULT TRUE COMMENT 'Titular ou reserva',
    numero_camisa INT,
    posicao VARCHAR(50),

    -- Se entrou durante o jogo
    minuto_entrada INT COMMENT 'Se foi substituição',
    minuto_saida INT COMMENT 'Se foi substituído',

    -- Avaliação
    nota DECIMAL(3,1) COMMENT 'Nota do jogador (0-10)',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id),
    FOREIGN KEY (atleta_id) REFERENCES atletas(id),

    UNIQUE KEY unique_lineup (match_id, equipe_id, atleta_id),
    INDEX idx_match (match_id),
    INDEX idx_equipe (equipe_id),
    INDEX idx_atleta (atleta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Árbitros
CREATE TABLE IF NOT EXISTS referees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,

    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE,

    -- Contato
    email VARCHAR(100),
    telefone VARCHAR(20),
    celular VARCHAR(20),

    -- Endereço
    cidade VARCHAR(100),
    estado CHAR(2),

    -- Dados profissionais
    nivel VARCHAR(50) COMMENT 'Nacional, Internacional, Regional, Estadual',
    categoria VARCHAR(50) COMMENT 'Especial, FIFA, CBF1, CBF2, CBF3',
    registro_cbf VARCHAR(50),
    validade_registro DATE,

    -- Foto e documentos
    foto_path VARCHAR(255),
    documento_identidade_path VARCHAR(255),
    certidao_nascimento_path VARCHAR(255),
    comprovante_residencia_path VARCHAR(255),

    -- Estatísticas
    total_jogos INT DEFAULT 0,
    total_cartoes_amarelos INT DEFAULT 0,
    total_cartoes_vermelhos INT DEFAULT 0,
    avaliacao_media DECIMAL(3,2) DEFAULT 0.00,

    -- Controle
    ativo BOOLEAN DEFAULT TRUE,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_cpf (cpf),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar FKs dos árbitros em matches
ALTER TABLE matches
ADD CONSTRAINT fk_arbitro_principal FOREIGN KEY (arbitro_principal_id) REFERENCES referees(id),
ADD CONSTRAINT fk_arbitro_assistente1 FOREIGN KEY (arbitro_assistente1_id) REFERENCES referees(id),
ADD CONSTRAINT fk_arbitro_assistente2 FOREIGN KEY (arbitro_assistente2_id) REFERENCES referees(id),
ADD CONSTRAINT fk_quarto_arbitro FOREIGN KEY (quarto_arbitro_id) REFERENCES referees(id);

-- Avaliação de árbitros
CREATE TABLE IF NOT EXISTS referee_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    referee_id INT NOT NULL,

    avaliador_tipo ENUM('equipe', 'administrador', 'delegado') NOT NULL,
    avaliador_id INT NOT NULL,

    nota DECIMAL(3,2) NOT NULL COMMENT 'Nota de 0 a 10',
    criterios JSON COMMENT 'Notas por critério: dominio_jogo, decisoes, posicionamento, etc',

    comentarios TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (referee_id) REFERENCES referees(id),

    UNIQUE KEY unique_rating (match_id, referee_id, avaliador_tipo, avaliador_id),
    INDEX idx_referee (referee_id),
    INDEX idx_match (match_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Inserir formatos padrão
-- =====================================================

INSERT INTO competition_formats (nome, codigo, descricao, permite_empate, criterios_desempate) VALUES
('Eliminatória Simples', 'eliminatoria_simples', 'Mata-mata em jogo único. Perdeu, está eliminado.', FALSE, '["penaltis"]'),
('Eliminatória Dupla', 'eliminatoria_dupla', 'Mata-mata com jogos de ida e volta. Vence quem fizer mais gols no agregado.', FALSE, '["saldo_gols", "gols_fora", "penaltis"]'),
('Pontos Corridos', 'pontos_corridos', 'Todos contra todos. Vence quem tiver mais pontos ao final.', TRUE, '["pontos", "vitorias", "saldo_gols", "gols_pro", "confronto_direto"]'),
('Grupos + Mata-mata', 'grupos_mata_mata', 'Fase de grupos com pontos corridos, seguida de fase eliminatória.', TRUE, '["pontos", "vitorias", "saldo_gols", "gols_pro"]'),
('Suíço', 'suico', 'Sistema suíço: jogadores/equipes com mesma pontuação se enfrentam.', TRUE, '["pontos", "buchholz", "confronto_direto"]');

-- =====================================================
-- IMPORTANTE: Executar após esta migration:
-- - 006_create_statistics_system.sql (estatísticas)
-- - 007_create_ranking_system.sql (rankings)
-- =====================================================
