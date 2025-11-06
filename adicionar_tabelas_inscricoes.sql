-- ============================================================================
-- TABELAS PARA SISTEMA DE INSCRIÇÕES
-- Execute este SQL no seu banco de dados
-- ============================================================================

-- Tabela de inscrições de equipes em competições
CREATE TABLE IF NOT EXISTS inscricoes_competicoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competicao_id INT NOT NULL,
    equipe_id INT NOT NULL,
    protocolo VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('Pendente', 'Confirmada', 'Cancelada') DEFAULT 'Pendente',
    data_inscricao DATETIME NOT NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_competicao (competicao_id),
    INDEX idx_equipe (equipe_id),
    INDEX idx_status (status),
    INDEX idx_protocolo (protocolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de atletas inscritos em cada inscrição
CREATE TABLE IF NOT EXISTS inscricoes_atletas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inscricao_competicao_id INT NOT NULL,
    atleta_id INT NOT NULL,
    data_inscricao DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inscricao_competicao_id) REFERENCES inscricoes_competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    INDEX idx_inscricao (inscricao_competicao_id),
    INDEX idx_atleta (atleta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar se tabelas foram criadas
SELECT 'Tabelas criadas com sucesso!' as status;
SELECT
    TABLE_NAME,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('inscricoes_competicoes', 'inscricoes_atletas');
