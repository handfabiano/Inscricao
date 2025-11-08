-- =====================================================
-- MIGRATION: Ranking System (ELO Rating)
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema de rankings dinâmicos com ELO rating
-- =====================================================

-- Rankings de equipes (ELO)
CREATE TABLE IF NOT EXISTS team_rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    modalidade_id INT NOT NULL,

    -- ELO Rating
    elo_rating INT DEFAULT 1500 COMMENT 'Rating ELO (inicia em 1500)',
    elo_peak INT DEFAULT 1500 COMMENT 'Maior ELO já atingido',
    elo_history JSON COMMENT 'Histórico de variações do ELO',

    -- Estatísticas gerais
    total_partidas INT DEFAULT 0,
    vitorias INT DEFAULT 0,
    empates INT DEFAULT 0,
    derrotas INT DEFAULT 0,
    gols_pro INT DEFAULT 0,
    gols_contra INT DEFAULT 0,

    -- Performance
    sequencia_vitorias INT DEFAULT 0 COMMENT 'Sequência atual de vitórias',
    sequencia_derrotas INT DEFAULT 0 COMMENT 'Sequência atual de derrotas',
    maior_sequencia_vitorias INT DEFAULT 0,
    maior_sequencia_derrotas INT DEFAULT 0,

    -- Rankings
    posicao_nacional INT COMMENT 'Posição no ranking nacional',
    posicao_estadual INT COMMENT 'Posição no ranking estadual',
    posicao_regional INT COMMENT 'Posição no ranking regional',

    -- Períodos
    elo_mes_anterior INT COMMENT 'ELO do mês anterior',
    elo_ano_anterior INT COMMENT 'ELO do ano anterior',

    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),

    UNIQUE KEY unique_team_modalidade (equipe_id, modalidade_id),
    INDEX idx_elo (elo_rating DESC),
    INDEX idx_modalidade (modalidade_id, elo_rating DESC),
    INDEX idx_equipe (equipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Rankings ELO por equipe e modalidade';

-- Histórico de mudanças no ELO
CREATE TABLE IF NOT EXISTS elo_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ranking_id INT NOT NULL,
    match_id INT COMMENT 'Partida que causou a mudança',

    elo_antes INT NOT NULL,
    elo_depois INT NOT NULL,
    variacao INT NOT NULL COMMENT 'Positivo ou negativo',

    oponente_id INT COMMENT 'Equipe adversária',
    oponente_elo_antes INT,

    resultado ENUM('vitoria', 'empate', 'derrota') NOT NULL,

    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ranking_id) REFERENCES team_rankings(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL,
    FOREIGN KEY (oponente_id) REFERENCES equipes(id),

    INDEX idx_ranking (ranking_id),
    INDEX idx_data (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de mudanças no ELO';

-- Rankings de atletas
CREATE TABLE IF NOT EXISTS athlete_rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    modalidade_id INT NOT NULL,

    -- Rating individual
    skill_rating DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Rating de habilidade',

    -- Rankings específicos
    ranking_gols INT COMMENT 'Posição no ranking de gols',
    ranking_assistencias INT COMMENT 'Posição no ranking de assistências',
    ranking_nota_media INT COMMENT 'Posição no ranking de notas',

    -- Estatísticas base
    total_gols INT DEFAULT 0,
    total_assistencias INT DEFAULT 0,
    total_jogos INT DEFAULT 0,
    nota_media DECIMAL(3,2) DEFAULT 0.00,

    -- Performance
    forma_atual DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Forma atual (últimos 5 jogos)',
    consistencia DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Consistência de performance',

    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),

    UNIQUE KEY unique_athlete_modalidade (atleta_id, modalidade_id),
    INDEX idx_skill (skill_rating DESC),
    INDEX idx_modalidade (modalidade_id),
    INDEX idx_gols (total_gols DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Rankings de atletas';

-- Comparações diretas (head-to-head)
CREATE TABLE IF NOT EXISTS head_to_head (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe1_id INT NOT NULL,
    equipe2_id INT NOT NULL,
    modalidade_id INT NOT NULL,

    -- Confrontos
    total_jogos INT DEFAULT 0,
    vitorias_equipe1 INT DEFAULT 0,
    empates INT DEFAULT 0,
    vitorias_equipe2 INT DEFAULT 0,

    gols_equipe1 INT DEFAULT 0,
    gols_equipe2 INT DEFAULT 0,

    -- Último confronto
    ultimo_jogo_id INT,
    ultimo_jogo_data DATE,
    ultimo_resultado VARCHAR(50),

    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (equipe1_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe2_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),
    FOREIGN KEY (ultimo_jogo_id) REFERENCES matches(id) ON DELETE SET NULL,

    UNIQUE KEY unique_confronto (equipe1_id, equipe2_id, modalidade_id),
    INDEX idx_equipes (equipe1_id, equipe2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de confrontos diretos';

-- =====================================================
-- Views úteis
-- =====================================================

-- Ranking nacional (todas as modalidades)
CREATE OR REPLACE VIEW ranking_nacional AS
SELECT
    tr.equipe_id,
    e.nome as equipe_nome,
    e.cidade,
    e.estado,
    e.escudo_path,
    m.nome as modalidade,
    tr.elo_rating,
    tr.posicao_nacional,
    tr.total_partidas,
    tr.vitorias,
    tr.empates,
    tr.derrotas,
    ROUND((tr.vitorias / NULLIF(tr.total_partidas, 0)) * 100, 2) as aproveitamento,
    tr.sequencia_vitorias,
    tr.ultima_atualizacao
FROM team_rankings tr
INNER JOIN equipes e ON tr.equipe_id = e.id
INNER JOIN modalidades m ON tr.modalidade_id = m.id
ORDER BY tr.elo_rating DESC;

-- Top artilheiros
CREATE OR REPLACE VIEW top_artilheiros AS
SELECT
    ar.atleta_id,
    a.nome_completo,
    a.foto_path,
    e.nome as equipe,
    ar.total_gols,
    ar.total_assistencias,
    ar.total_jogos,
    ROUND(ar.total_gols / NULLIF(ar.total_jogos, 0), 2) as media_gols,
    ar.nota_media,
    ar.ranking_gols as posicao,
    m.nome as modalidade
FROM athlete_rankings ar
INNER JOIN atletas a ON ar.atleta_id = a.id
INNER JOIN equipes e ON a.equipe_atual_id = e.id
INNER JOIN modalidades m ON ar.modalidade_id = m.id
WHERE ar.total_gols > 0
ORDER BY ar.total_gols DESC, ar.total_assistencias DESC
LIMIT 100;

-- =====================================================
-- IMPORTANTE: Execute após esta migration:
-- - Funções PHP para calcular ELO em ranking_helper.php
-- =====================================================
