-- =====================================================
-- MIGRATION: Two-Factor Authentication (2FA)
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Adiciona suporte a autenticação de dois fatores (TOTP)
-- =====================================================

-- Adicionar campos 2FA na tabela de administradores (se ainda não existirem)
ALTER TABLE administradores
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE COMMENT 'Se 2FA está ativado',
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(32) COMMENT 'Secret TOTP (base32)',
ADD COLUMN IF NOT EXISTS two_factor_recovery_codes JSON COMMENT 'Códigos de recuperação (hashed)',
ADD COLUMN IF NOT EXISTS two_factor_confirmed_at TIMESTAMP NULL COMMENT 'Quando 2FA foi confirmado',
ADD INDEX idx_2fa_enabled (two_factor_enabled);

-- Adicionar campos 2FA na tabela de equipes (se ainda não existirem)
ALTER TABLE equipes
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE COMMENT 'Se 2FA está ativado',
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(32) COMMENT 'Secret TOTP (base32)',
ADD COLUMN IF NOT EXISTS two_factor_recovery_codes JSON COMMENT 'Códigos de recuperação (hashed)',
ADD COLUMN IF NOT EXISTS two_factor_confirmed_at TIMESTAMP NULL COMMENT 'Quando 2FA foi confirmado',
ADD INDEX idx_2fa_enabled (two_factor_enabled);

-- Tabela de logs de 2FA (tentativas, sucessos, falhas)
CREATE TABLE IF NOT EXISTS two_factor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'equipe', 'usuario_organizacao') NOT NULL,
    user_id INT NOT NULL,

    evento ENUM('setup_started', 'setup_completed', 'setup_cancelled', 'login_success', 'login_failed', 'recovery_used', 'disabled') NOT NULL,
    metodo ENUM('totp', 'recovery_code') COMMENT 'Método usado',

    ip_address VARCHAR(45),
    user_agent TEXT,
    observacoes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_type, user_id),
    INDEX idx_evento (evento),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de eventos de autenticação de dois fatores';

-- Tabela de sessões confiáveis (devices remembered)
CREATE TABLE IF NOT EXISTS trusted_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin', 'equipe', 'usuario_organizacao') NOT NULL,
    user_id INT NOT NULL,

    device_token VARCHAR(64) UNIQUE NOT NULL COMMENT 'Token único do dispositivo',
    device_name VARCHAR(200) COMMENT 'Nome amigável do dispositivo',

    ip_address VARCHAR(45),
    user_agent TEXT,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    expires_at TIMESTAMP NOT NULL COMMENT 'Data de expiração (30 dias)',
    revoked BOOLEAN DEFAULT FALSE,
    revoked_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_token (device_token),
    INDEX idx_user (user_type, user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_revoked (revoked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dispositivos confiáveis (lembrados) para 2FA';

-- =====================================================
-- Configurações de segurança do sistema
-- =====================================================

CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT COMMENT 'NULL = configuração global',

    -- Políticas de 2FA
    force_2fa_admin BOOLEAN DEFAULT FALSE COMMENT 'Forçar 2FA para admins',
    force_2fa_users BOOLEAN DEFAULT FALSE COMMENT 'Forçar 2FA para usuários',
    allow_trusted_devices BOOLEAN DEFAULT TRUE COMMENT 'Permitir dispositivos confiáveis',
    trusted_device_duration_days INT DEFAULT 30 COMMENT 'Dias que dispositivo é confiável',

    -- Políticas de senha
    min_password_length INT DEFAULT 8,
    require_uppercase BOOLEAN DEFAULT TRUE,
    require_lowercase BOOLEAN DEFAULT TRUE,
    require_numbers BOOLEAN DEFAULT TRUE,
    require_special_chars BOOLEAN DEFAULT FALSE,
    password_expiry_days INT DEFAULT 90 COMMENT 'Dias até senha expirar (0 = nunca)',

    -- Bloqueio de conta
    max_login_attempts INT DEFAULT 5,
    lockout_duration_minutes INT DEFAULT 15,

    -- Session
    session_timeout_minutes INT DEFAULT 120,
    require_password_on_sensitive_actions BOOLEAN DEFAULT TRUE,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT COMMENT 'ID do usuário que atualizou',

    UNIQUE KEY unique_org (organizacao_id),
    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações de segurança do sistema';

-- Inserir configuração de segurança padrão
INSERT INTO security_settings (organizacao_id) VALUES (NULL)
ON DUPLICATE KEY UPDATE id=id;

-- =====================================================
-- IMPORTANTE: Para produção, instalar biblioteca TOTP
-- Recomendado: composer require robthree/twofactorauth
-- =====================================================
