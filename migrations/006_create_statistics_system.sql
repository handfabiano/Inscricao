-- =====================================================
-- MIGRATION: Statistics System (Estatísticas Avançadas)
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema de estatísticas detalhadas por atleta
-- =====================================================

-- Estatísticas gerais por atleta
CREATE TABLE IF NOT EXISTS athlete_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    competicao_id INT COMMENT 'NULL = estatísticas globais',

    -- Jogos
    total_jogos INT DEFAULT 0,
    jogos_titular INT DEFAULT 0,
    jogos_reserva INT DEFAULT 0,
    minutos_jogados INT DEFAULT 0,

    -- Gols
    total_gols INT DEFAULT 0,
    gols_pe_direito INT DEFAULT 0,
    gols_pe_esquerdo INT DEFAULT 0,
    gols_cabeca INT DEFAULT 0,
    gols_penalti INT DEFAULT 0,
    gols_falta INT DEFAULT 0,

    -- Assistências
    total_assistencias INT DEFAULT 0,

    -- Disciplina
    cartoes_amarelos INT DEFAULT 0,
    cartoes_vermelhos INT DEFAULT 0,
    faltas_cometidas INT DEFAULT 0,
    faltas_sofridas INT DEFAULT 0,

    -- Defesa (goleiros)
    gols_sofridos INT DEFAULT 0,
    defesas INT DEFAULT 0,
    penaltis_defendidos INT DEFAULT 0,
    jogos_sem_sofrer INT DEFAULT 0,

    -- Performance
    nota_media DECIMAL(3,2) DEFAULT 0.00,
    mvp_count INT DEFAULT 0 COMMENT 'Quantas vezes foi MVP',

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,

    UNIQUE KEY unique_stats (atleta_id, competicao_id),
    INDEX idx_atleta (atleta_id),
    INDEX idx_competicao (competicao_id),
    INDEX idx_gols (total_gols DESC),
    INDEX idx_assistencias (total_assistencias DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Estatísticas detalhadas por atleta';

-- Estatísticas por jogo (performance individual)
CREATE TABLE IF NOT EXISTS match_player_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    atleta_id INT NOT NULL,
    equipe_id INT NOT NULL,

    -- Tempo de jogo
    minutos_jogados INT DEFAULT 0,
    titular BOOLEAN DEFAULT TRUE,

    -- Ofensiva
    gols INT DEFAULT 0,
    assistencias INT DEFAULT 0,
    finalizacoes INT DEFAULT 0,
    finalizacoes_gol INT DEFAULT 0,
    passes_certos INT DEFAULT 0,
    passes_errados INT DEFAULT 0,
    dribles_certos INT DEFAULT 0,
    dribles_errados INT DEFAULT 0,

    -- Defensiva
    desarmes INT DEFAULT 0,
    interceptacoes INT DEFAULT 0,
    faltas_cometidas INT DEFAULT 0,
    faltas_sofridas INT DEFAULT 0,

    -- Disciplina
    cartoes_amarelos INT DEFAULT 0,
    cartoes_vermelhos INT DEFAULT 0,

    -- Goleiro
    defesas INT DEFAULT 0,
    gols_sofridos INT DEFAULT 0,

    -- Avaliação
    nota DECIMAL(3,2) COMMENT 'Nota 0-10',
    mvp BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id),

    UNIQUE KEY unique_player_match (match_id, atleta_id),
    INDEX idx_match (match_id),
    INDEX idx_atleta (atleta_id),
    INDEX idx_nota (nota DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Estatísticas de jogadores por partida';

-- Artilharia (automatizada)
CREATE OR REPLACE VIEW artilharia AS
SELECT
    a.id as atleta_id,
    a.nome_completo,
    a.foto_path,
    e.nome as equipe_nome,
    e.id as equipe_id,
    c.id as competicao_id,
    c.nome as competicao_nome,
    ast.total_gols,
    ast.total_assistencias,
    ast.total_jogos,
    ROUND(ast.total_gols / NULLIF(ast.total_jogos, 0), 2) as media_gols,
    ast.cartoes_amarelos,
    ast.cartoes_vermelhos
FROM athlete_statistics ast
INNER JOIN atletas a ON ast.atleta_id = a.id
INNER JOIN equipes e ON a.equipe_atual_id = e.id
LEFT JOIN competicoes c ON ast.competicao_id = c.id
WHERE ast.total_gols > 0
ORDER BY ast.total_gols DESC, ast.total_assistencias DESC;

-- =====================================================
-- Triggers para atualizar estatísticas automaticamente
-- =====================================================

DELIMITER $$

-- Trigger ao adicionar evento de jogo
CREATE TRIGGER after_match_event_insert
AFTER INSERT ON match_events
FOR EACH ROW
BEGIN
    -- Atualizar estatísticas do jogo
    IF NEW.tipo = 'gol' THEN
        UPDATE match_player_stats
        SET gols = gols + 1
        WHERE match_id = NEW.match_id AND atleta_id = NEW.atleta_id;

        -- Assistência
        IF NEW.assistencia_id IS NOT NULL THEN
            UPDATE match_player_stats
            SET assistencias = assistencias + 1
            WHERE match_id = NEW.match_id AND atleta_id = NEW.assistencia_id;
        END IF;
    END IF;

    IF NEW.tipo IN ('cartao_amarelo', 'cartao_vermelho') THEN
        UPDATE match_player_stats
        SET cartoes_amarelos = IF(NEW.tipo = 'cartao_amarelo', cartoes_amarelos + 1, cartoes_amarelos),
            cartoes_vermelhos = IF(NEW.tipo = 'cartao_vermelho', cartoes_vermelhos + 1, cartoes_vermelhos)
        WHERE match_id = NEW.match_id AND atleta_id = NEW.atleta_id;
    END IF;
END$$

-- Trigger ao finalizar partida - consolidar estatísticas
CREATE TRIGGER after_match_finish
AFTER UPDATE ON matches
FOR EACH ROW
BEGIN
    IF NEW.status = 'finalizado' AND OLD.status != 'finalizado' THEN
        -- Atualizar estatísticas globais de todos os jogadores da partida
        INSERT INTO athlete_statistics (
            atleta_id, competicao_id, total_jogos, jogos_titular, jogos_reserva,
            minutos_jogados, total_gols, total_assistencias,
            cartoes_amarelos, cartoes_vermelhos
        )
        SELECT
            mps.atleta_id,
            NEW.competicao_id,
            1,
            IF(mps.titular, 1, 0),
            IF(mps.titular, 0, 1),
            mps.minutos_jogados,
            mps.gols,
            mps.assistencias,
            mps.cartoes_amarelos,
            mps.cartoes_vermelhos
        FROM match_player_stats mps
        WHERE mps.match_id = NEW.id
        ON DUPLICATE KEY UPDATE
            total_jogos = total_jogos + 1,
            jogos_titular = jogos_titular + IF(VALUES(jogos_titular) = 1, 1, 0),
            jogos_reserva = jogos_reserva + IF(VALUES(jogos_reserva) = 1, 1, 0),
            minutos_jogados = minutos_jogados + VALUES(minutos_jogados),
            total_gols = total_gols + VALUES(total_gols),
            total_assistencias = total_assistencias + VALUES(total_assistencias),
            cartoes_amarelos = cartoes_amarelos + VALUES(cartoes_amarelos),
            cartoes_vermelhos = cartoes_vermelhos + VALUES(cartoes_vermelhos);
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- IMPORTANTE: Estatísticas são atualizadas automaticamente
-- via triggers ao registrar eventos de jogo
-- =====================================================
