-- Tabela de configurações de e-mail
CREATE TABLE IF NOT EXISTS config_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_username VARCHAR(255) NOT NULL,
    smtp_password VARCHAR(255) NOT NULL,
    smtp_secure VARCHAR(10) DEFAULT 'tls',
    email_remetente VARCHAR(255) NOT NULL,
    nome_remetente VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão (desativada até ser configurada)
INSERT INTO config_email (smtp_host, smtp_port, smtp_username, smtp_password, email_remetente, nome_remetente, ativo)
VALUES ('smtp.gmail.com', 587, '', '', 'noreply@exemplo.com', 'Sistema de Competições', 0)
ON DUPLICATE KEY UPDATE id=id;
