-- Tabela de convites para atletas
CREATE TABLE IF NOT EXISTS convites_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    email_atleta VARCHAR(255),
    nome_atleta VARCHAR(255),
    status ENUM('Pendente', 'Aceito', 'Expirado') DEFAULT 'Pendente',
    validade_ate DATETIME NOT NULL,
    usado_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_status (status),
    INDEX idx_equipe (equipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
