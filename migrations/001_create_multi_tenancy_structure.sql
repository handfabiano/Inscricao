-- =====================================================
-- MIGRATION: Multi-Tenancy Structure
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Cria estrutura completa de multi-tenancy
--            para suportar múltiplas organizações/federações
-- =====================================================

-- Tabela de Planos de Assinatura
CREATE TABLE IF NOT EXISTS planos_assinatura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL COMMENT 'Nome do plano (Free, Pro, Enterprise)',
    descricao TEXT COMMENT 'Descrição detalhada do plano',

    -- Limites do plano
    max_eventos INT DEFAULT NULL COMMENT 'Máximo de eventos simultâneos (NULL = ilimitado)',
    max_equipes INT DEFAULT NULL COMMENT 'Máximo de equipes (NULL = ilimitado)',
    max_atletas INT DEFAULT NULL COMMENT 'Máximo de atletas (NULL = ilimitado)',
    max_administradores INT DEFAULT 3 COMMENT 'Máximo de usuários admin',
    max_storage_gb INT DEFAULT 5 COMMENT 'Armazenamento em GB',
    max_emails_mes INT DEFAULT 1000 COMMENT 'E-mails por mês',

    -- Funcionalidades
    permite_api BOOLEAN DEFAULT FALSE COMMENT 'Acesso à API',
    permite_whatsapp BOOLEAN DEFAULT FALSE COMMENT 'Notificações WhatsApp',
    permite_sms BOOLEAN DEFAULT FALSE COMMENT 'Notificações SMS',
    permite_custom_domain BOOLEAN DEFAULT FALSE COMMENT 'Domínio personalizado',
    permite_custom_branding BOOLEAN DEFAULT FALSE COMMENT 'Marca personalizada',
    permite_relatorios_avancados BOOLEAN DEFAULT FALSE COMMENT 'Relatórios avançados',
    permite_multiidioma BOOLEAN DEFAULT FALSE COMMENT 'Multi-idioma',
    suporte_nivel VARCHAR(20) DEFAULT 'email' COMMENT 'Nível de suporte: email, chat, prioritario, dedicado',

    -- Precificação
    valor_mensal DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor mensal em R$',
    valor_anual DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor anual em R$ (com desconto)',
    taxa_por_evento DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Taxa variável por evento',

    -- Controle
    ativo BOOLEAN DEFAULT TRUE,
    ordem_exibicao INT DEFAULT 0 COMMENT 'Ordem de exibição no site',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem_exibicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Planos de assinatura do sistema';

-- Tabela de Organizações (Federações, Confederações, Clubes)
CREATE TABLE IF NOT EXISTS organizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Identificação
    nome VARCHAR(200) NOT NULL COMMENT 'Nome da organização',
    sigla VARCHAR(20) COMMENT 'Sigla (ex: FPFS, CBF)',
    tipo ENUM('federacao', 'confederacao', 'liga', 'clube', 'associacao', 'outro') DEFAULT 'federacao',

    -- Dados institucionais
    cnpj VARCHAR(18) UNIQUE COMMENT 'CNPJ da organização',
    inscricao_estadual VARCHAR(30),
    inscricao_municipal VARCHAR(30),

    -- Endereço
    logradouro VARCHAR(200),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado CHAR(2),
    cep VARCHAR(10),
    pais VARCHAR(3) DEFAULT 'BRA',

    -- Contato
    telefone VARCHAR(20),
    email VARCHAR(100) NOT NULL,
    website VARCHAR(200),

    -- Responsável legal
    responsavel_nome VARCHAR(150) NOT NULL,
    responsavel_cpf VARCHAR(14),
    responsavel_cargo VARCHAR(100),
    responsavel_email VARCHAR(100),
    responsavel_telefone VARCHAR(20),

    -- Plano e assinatura
    plano_id INT NOT NULL COMMENT 'Plano contratado',
    data_inicio_assinatura DATE NOT NULL COMMENT 'Data de início da assinatura',
    data_fim_assinatura DATE COMMENT 'Data de término (NULL = ativa)',
    status_assinatura ENUM('trial', 'ativa', 'suspensa', 'cancelada', 'inadimplente') DEFAULT 'trial',

    -- Branding personalizado
    logo_path VARCHAR(255) COMMENT 'Logo da organização',
    cor_primaria VARCHAR(7) DEFAULT '#1a56db' COMMENT 'Cor primária (hex)',
    cor_secundaria VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Cor secundária (hex)',
    dominio_customizado VARCHAR(100) COMMENT 'Subdomínio ou domínio próprio',

    -- Configurações
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    idioma_padrao VARCHAR(5) DEFAULT 'pt_BR',
    moeda VARCHAR(3) DEFAULT 'BRL',

    -- Uso e estatísticas
    total_eventos INT DEFAULT 0,
    total_equipes INT DEFAULT 0,
    total_atletas INT DEFAULT 0,
    storage_usado_gb DECIMAL(10,2) DEFAULT 0.00,
    emails_enviados_mes INT DEFAULT 0,
    ultimo_reset_emails DATE COMMENT 'Data do último reset do contador mensal',

    -- API
    api_key VARCHAR(64) UNIQUE COMMENT 'Chave de API',
    api_secret VARCHAR(128) COMMENT 'Secret da API (hash)',
    api_ativa BOOLEAN DEFAULT FALSE,
    api_rate_limit INT DEFAULT 100 COMMENT 'Requisições por minuto',

    -- Controle
    ativo BOOLEAN DEFAULT TRUE,
    verificado BOOLEAN DEFAULT FALSE COMMENT 'Organização verificada pela equipe',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (plano_id) REFERENCES planos_assinatura(id),
    INDEX idx_cnpj (cnpj),
    INDEX idx_email (email),
    INDEX idx_plano (plano_id),
    INDEX idx_status_assinatura (status_assinatura),
    INDEX idx_ativo (ativo),
    INDEX idx_dominio (dominio_customizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Organizações (multi-tenancy)';

-- Tabela de usuários da organização (além dos admins do sistema)
CREATE TABLE IF NOT EXISTS usuarios_organizacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,

    -- Dados do usuário
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',

    -- Perfil
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    telefone VARCHAR(20),
    foto_path VARCHAR(255),

    -- Permissões
    papel ENUM('proprietario', 'administrador', 'gestor', 'operador', 'visualizador') DEFAULT 'operador',
    permissoes JSON COMMENT 'Permissões específicas em formato JSON',

    -- 2FA
    two_factor_secret VARCHAR(32) COMMENT 'Secret TOTP para 2FA',
    two_factor_ativo BOOLEAN DEFAULT FALSE,
    two_factor_recovery_codes JSON COMMENT 'Códigos de recuperação',

    -- Sessão e segurança
    ultimo_login TIMESTAMP NULL,
    ultimo_ip VARCHAR(45),
    tentativas_login INT DEFAULT 0,
    bloqueado_ate TIMESTAMP NULL,

    -- Controle
    ativo BOOLEAN DEFAULT TRUE,
    verificado BOOLEAN DEFAULT FALSE COMMENT 'E-mail verificado',
    token_verificacao VARCHAR(64),
    token_reset_senha VARCHAR(64),
    token_reset_expira TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_org (organizacao_id, email),
    INDEX idx_email (email),
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuários das organizações';

-- Tabela de histórico de assinaturas (para auditoria)
CREATE TABLE IF NOT EXISTS historico_assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    plano_id INT NOT NULL,

    evento_tipo ENUM('criacao', 'upgrade', 'downgrade', 'renovacao', 'cancelamento', 'suspensao', 'reativacao') NOT NULL,
    plano_anterior_id INT COMMENT 'ID do plano anterior (em caso de mudança)',

    valor_cobrado DECIMAL(10,2),
    metodo_pagamento VARCHAR(50),
    transacao_id VARCHAR(100),

    data_inicio DATE,
    data_fim DATE,

    observacoes TEXT,
    realizado_por INT COMMENT 'ID do usuário que realizou a ação',
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (plano_id) REFERENCES planos_assinatura(id),
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_data (data_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de mudanças em assinaturas';

-- Tabela de tokens de API (para rastreamento individual)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    usuario_id INT COMMENT 'Usuário que gerou o token',

    nome VARCHAR(100) NOT NULL COMMENT 'Nome descritivo do token',
    token VARCHAR(64) UNIQUE NOT NULL,
    token_hash VARCHAR(128) NOT NULL COMMENT 'Hash do token completo',

    permissoes JSON COMMENT 'Permissões específicas deste token',

    ultimo_uso TIMESTAMP NULL,
    ultimo_ip VARCHAR(45),
    total_requisicoes INT DEFAULT 0,

    expira_em TIMESTAMP NULL COMMENT 'Data de expiração (NULL = não expira)',
    ativo BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revogado_em TIMESTAMP NULL,
    revogado_por INT COMMENT 'Quem revogou o token',

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokens de API individuais';

-- =====================================================
-- Inserir planos padrão
-- =====================================================

INSERT INTO planos_assinatura (
    nome, descricao,
    max_eventos, max_equipes, max_atletas, max_administradores, max_storage_gb, max_emails_mes,
    permite_api, permite_whatsapp, permite_sms, permite_custom_domain, permite_custom_branding,
    permite_relatorios_avancados, permite_multiidioma, suporte_nivel,
    valor_mensal, valor_anual, ordem_exibicao, ativo
) VALUES
(
    'Free',
    'Plano gratuito para pequenas organizações e testes',
    3, 10, 100, 2, 2, 500,
    FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'email',
    0.00, 0.00, 1, TRUE
),
(
    'Pro',
    'Plano profissional para federações estaduais e ligas regionais',
    20, 100, 2000, 5, 50, 5000,
    TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, FALSE, 'chat',
    497.00, 4970.00, 2, TRUE
),
(
    'Enterprise',
    'Plano completo para confederações e grandes federações',
    NULL, NULL, NULL, 20, 500, 50000,
    TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 'dedicado',
    1997.00, 19970.00, 3, TRUE
);

-- =====================================================
-- Inserir organização padrão (migração dos dados atuais)
-- =====================================================

INSERT INTO organizacoes (
    nome, sigla, tipo,
    email, responsavel_nome,
    plano_id, data_inicio_assinatura, status_assinatura,
    ativo, verificado
) VALUES (
    'Organização Padrão',
    'ORG',
    'federacao',
    'admin@sistema.com',
    'Administrador do Sistema',
    1, -- Plano Free
    CURDATE(),
    'ativa',
    TRUE,
    TRUE
);

-- =====================================================
-- IMPORTANTE: Após executar esta migration, execute:
-- 002_add_organization_id_to_existing_tables.sql
-- =====================================================
