-- =====================================================
-- MIGRATION: External Integrations System
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema de integrações com APIs externas
-- =====================================================

-- =====================================================
-- CONFIGURAÇÃO DE INTEGRAÇÕES
-- =====================================================

-- Provedores de integração disponíveis
CREATE TABLE IF NOT EXISTS integration_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    categoria ENUM('federation', 'payment', 'communication', 'streaming', 'erp', 'other') NOT NULL,

    -- Descrição
    descricao TEXT,
    logo_url VARCHAR(255),
    website VARCHAR(255),

    -- Configuração de API
    base_url VARCHAR(255),
    api_version VARCHAR(20),
    autenticacao_tipo ENUM('api_key', 'oauth2', 'jwt', 'basic_auth', 'custom') DEFAULT 'api_key',

    -- Documentação
    docs_url VARCHAR(255),
    support_email VARCHAR(100),

    -- Status
    ativo BOOLEAN DEFAULT TRUE,
    requer_aprovacao BOOLEAN DEFAULT FALSE COMMENT 'Requer aprovação manual para ativar',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_categoria (categoria),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Provedores de integração disponíveis';

-- Integrações configuradas por organização
CREATE TABLE IF NOT EXISTS integration_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    provider_id INT NOT NULL,

    -- Identificação
    nome_integracao VARCHAR(100) NOT NULL,

    -- Credenciais (CRIPTOGRAFADAS)
    credentials JSON COMMENT 'Credenciais criptografadas da API',

    -- Configurações específicas
    settings JSON COMMENT 'Configurações customizadas',

    -- Limites de uso
    rate_limit_per_hour INT DEFAULT 1000,
    rate_limit_per_day INT DEFAULT 10000,

    -- Estatísticas de uso
    total_requests INT DEFAULT 0,
    total_success INT DEFAULT 0,
    total_errors INT DEFAULT 0,
    last_request_at TIMESTAMP NULL,
    last_error_at TIMESTAMP NULL,
    last_error_message TEXT,

    -- Status
    status ENUM('ativo', 'inativo', 'erro', 'pendente_aprovacao') DEFAULT 'pendente_aprovacao',
    ativo BOOLEAN DEFAULT FALSE,

    -- Auditoria
    criado_por INT,
    aprovado_por INT NULL,
    aprovado_em TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES integration_providers(id),

    UNIQUE KEY unique_org_provider (organizacao_id, provider_id),
    INDEX idx_status (status),
    INDEX idx_organizacao (organizacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações de integrações por organização';

-- =====================================================
-- WEBHOOKS
-- =====================================================

-- Webhooks configurados
CREATE TABLE IF NOT EXISTS webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    integration_config_id INT NULL COMMENT 'Opcional - pode ser webhook genérico',

    -- Configuração
    nome VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL COMMENT 'URL de destino',
    metodo ENUM('POST', 'GET', 'PUT', 'PATCH') DEFAULT 'POST',

    -- Eventos que disparam o webhook
    eventos JSON COMMENT 'Lista de eventos: inscricao.criada, partida.finalizada, etc',

    -- Autenticação
    auth_type ENUM('none', 'bearer_token', 'api_key', 'hmac_signature', 'custom') DEFAULT 'none',
    auth_credentials JSON COMMENT 'Credenciais de autenticação',

    -- Headers customizados
    custom_headers JSON,

    -- Retry policy
    retry_enabled BOOLEAN DEFAULT TRUE,
    retry_max_attempts INT DEFAULT 3,
    retry_delay_seconds INT DEFAULT 60,

    -- Timeout
    timeout_seconds INT DEFAULT 30,

    -- Estatísticas
    total_dispatched INT DEFAULT 0,
    total_success INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    last_dispatch_at TIMESTAMP NULL,
    last_success_at TIMESTAMP NULL,
    last_error_at TIMESTAMP NULL,

    -- Status
    ativo BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (integration_config_id) REFERENCES integration_configs(id) ON DELETE SET NULL,

    INDEX idx_organizacao (organizacao_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Webhooks configurados';

-- Log de disparos de webhooks
CREATE TABLE IF NOT EXISTS webhook_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,

    -- Request
    evento VARCHAR(100) NOT NULL,
    payload JSON COMMENT 'Dados enviados',
    request_headers JSON,

    -- Response
    status_code INT,
    response_body TEXT,
    response_headers JSON,
    response_time_ms INT COMMENT 'Tempo de resposta em milissegundos',

    -- Erro
    erro BOOLEAN DEFAULT FALSE,
    erro_message TEXT,

    -- Retry
    attempt_number INT DEFAULT 1,

    dispatched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE,

    INDEX idx_webhook (webhook_id),
    INDEX idx_evento (evento),
    INDEX idx_dispatched (dispatched_at DESC),
    INDEX idx_erro (erro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de disparos de webhooks';

-- =====================================================
-- SISTEMA DE NOTIFICAÇÕES MULTI-CANAL
-- =====================================================

-- Templates de notificação
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NULL COMMENT 'NULL = template global',

    -- Identificação
    codigo VARCHAR(100) NOT NULL COMMENT 'inscricao_confirmada, pagamento_aprovado, etc',
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,

    -- Canais suportados
    canais JSON COMMENT 'email, sms, whatsapp, push',

    -- Conteúdo por canal
    email_subject VARCHAR(200),
    email_body TEXT,
    sms_body VARCHAR(160),
    whatsapp_body TEXT,
    push_title VARCHAR(100),
    push_body VARCHAR(200),

    -- Variáveis disponíveis
    variaveis_disponiveis JSON COMMENT 'Lista de variáveis que podem ser usadas',

    -- Configuração
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',

    ativo BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_org_codigo (organizacao_id, codigo),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Templates de notificações';

-- Fila de notificações
CREATE TABLE IF NOT EXISTS notification_queue (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    template_id INT NULL,

    -- Destinatário
    destinatario_tipo ENUM('usuario', 'atleta', 'email', 'telefone') NOT NULL,
    destinatario_id INT NULL,
    destinatario_email VARCHAR(150),
    destinatario_telefone VARCHAR(20),

    -- Canal de envio
    canal ENUM('email', 'sms', 'whatsapp', 'push') NOT NULL,

    -- Conteúdo
    assunto VARCHAR(200),
    corpo TEXT NOT NULL,
    dados_extras JSON COMMENT 'Anexos, botões, etc',

    -- Agendamento
    agendado_para TIMESTAMP NULL COMMENT 'NULL = enviar imediatamente',

    -- Status
    status ENUM('pendente', 'processando', 'enviado', 'erro', 'cancelado') DEFAULT 'pendente',
    tentativas INT DEFAULT 0,
    max_tentativas INT DEFAULT 3,

    -- Resultado
    enviado_em TIMESTAMP NULL,
    erro_mensagem TEXT,
    provider_response JSON COMMENT 'Resposta do provedor (Twilio, WhatsApp, etc)',

    -- Prioridade
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL,

    INDEX idx_status (status),
    INDEX idx_canal (canal),
    INDEX idx_agendado (agendado_para),
    INDEX idx_prioridade (prioridade, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Fila de notificações para processamento';

-- =====================================================
-- INTEGRAÇÕES ESPECÍFICAS
-- =====================================================

-- Integração CBF/COB - Validação de atletas
CREATE TABLE IF NOT EXISTS federation_athletes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,

    -- Dados da federação
    federacao ENUM('CBF', 'COB', 'FIFA', 'CONMEBOL', 'outro') NOT NULL,
    registro_federacao VARCHAR(50) NOT NULL COMMENT 'Número de registro na federação',

    -- Validação
    validado BOOLEAN DEFAULT FALSE,
    validado_em TIMESTAMP NULL,
    validado_por VARCHAR(100) COMMENT 'Sistema ou usuário que validou',

    -- Dados sincronizados
    dados_federacao JSON COMMENT 'Dados retornados pela API da federação',

    -- Status
    status ENUM('pendente', 'ativo', 'suspenso', 'transferido', 'inativo') DEFAULT 'pendente',

    -- Sincronização
    ultima_sincronizacao TIMESTAMP NULL,
    proxima_sincronizacao TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,

    UNIQUE KEY unique_atleta_federacao (atleta_id, federacao),
    INDEX idx_registro (registro_federacao),
    INDEX idx_validado (validado),
    INDEX idx_proxima_sync (proxima_sincronizacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Integração com federações esportivas';

-- Transmissões ao vivo (Streaming)
CREATE TABLE IF NOT EXISTS live_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partida_id INT NULL,
    competicao_id INT NULL,
    organizacao_id INT NOT NULL,

    -- Plataforma
    plataforma ENUM('youtube', 'facebook', 'twitch', 'custom') NOT NULL,

    -- IDs externos
    stream_id VARCHAR(100) COMMENT 'ID da stream na plataforma',
    stream_url VARCHAR(500),
    embed_url VARCHAR(500),

    -- Configurações
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    thumbnail_url VARCHAR(500),
    categoria VARCHAR(100),
    tags JSON,

    -- Privacidade
    privacidade ENUM('publico', 'nao_listado', 'privado') DEFAULT 'publico',

    -- Agendamento
    inicio_agendado TIMESTAMP,
    inicio_real TIMESTAMP NULL,
    fim_real TIMESTAMP NULL,

    -- Status
    status ENUM('agendado', 'ao_vivo', 'finalizado', 'cancelado', 'erro') DEFAULT 'agendado',

    -- Estatísticas
    visualizacoes_pico INT DEFAULT 0,
    total_visualizacoes INT DEFAULT 0,
    total_likes INT DEFAULT 0,
    total_comentarios INT DEFAULT 0,

    -- Gravação
    gravacao_disponivel BOOLEAN DEFAULT FALSE,
    gravacao_url VARCHAR(500),

    -- Erro
    erro_mensagem TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (partida_id) REFERENCES matches(id) ON DELETE SET NULL,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE SET NULL,
    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,

    INDEX idx_partida (partida_id),
    INDEX idx_status (status),
    INDEX idx_agendado (inicio_agendado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Transmissões ao vivo';

-- Mensagens WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    integration_config_id INT NULL,

    -- Destinatário
    telefone VARCHAR(20) NOT NULL COMMENT 'Formato internacional: +5511999999999',
    nome_destinatario VARCHAR(150),

    -- Mensagem
    tipo_mensagem ENUM('text', 'image', 'document', 'template', 'interactive') DEFAULT 'text',
    conteudo TEXT NOT NULL,

    -- Template (se aplicável)
    template_name VARCHAR(100),
    template_params JSON,

    -- Mídia (se aplicável)
    media_url VARCHAR(500),
    media_type VARCHAR(50),

    -- Envio
    status ENUM('pendente', 'enviado', 'entregue', 'lido', 'erro', 'rejeitado') DEFAULT 'pendente',
    whatsapp_message_id VARCHAR(100) COMMENT 'ID retornado pela API do WhatsApp',

    -- Timestamps
    enviado_em TIMESTAMP NULL,
    entregue_em TIMESTAMP NULL,
    lido_em TIMESTAMP NULL,

    -- Erro
    erro_codigo VARCHAR(50),
    erro_mensagem TEXT,

    -- Resposta
    resposta_recebida BOOLEAN DEFAULT FALSE,
    resposta_conteudo TEXT,
    resposta_em TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (integration_config_id) REFERENCES integration_configs(id) ON DELETE SET NULL,

    INDEX idx_telefone (telefone),
    INDEX idx_status (status),
    INDEX idx_created (created_at DESC),
    INDEX idx_whatsapp_id (whatsapp_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mensagens enviadas via WhatsApp Business';

-- =====================================================
-- LOGS DE INTEGRAÇÃO
-- =====================================================

-- Log geral de chamadas API
CREATE TABLE IF NOT EXISTS integration_api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    integration_config_id INT NOT NULL,

    -- Request
    metodo ENUM('GET', 'POST', 'PUT', 'PATCH', 'DELETE') NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    request_headers JSON,
    request_body TEXT,

    -- Response
    status_code INT,
    response_body TEXT,
    response_headers JSON,
    response_time_ms INT,

    -- Erro
    erro BOOLEAN DEFAULT FALSE,
    erro_message TEXT,

    -- Rate limiting
    rate_limit_remaining INT,
    rate_limit_reset_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (integration_config_id) REFERENCES integration_configs(id) ON DELETE CASCADE,

    INDEX idx_integration (integration_config_id),
    INDEX idx_created (created_at DESC),
    INDEX idx_erro (erro),
    INDEX idx_status (status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de chamadas para APIs externas';

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Provedores de integração pré-configurados
INSERT INTO integration_providers (nome, slug, categoria, descricao, base_url, autenticacao_tipo, ativo) VALUES
('CBF Connect', 'cbf_connect', 'federation', 'Integração oficial com a Confederação Brasileira de Futebol', 'https://api.cbf.com.br/v1', 'oauth2', TRUE),
('COB API', 'cob_api', 'federation', 'Comitê Olímpico Brasileiro - Validação de atletas', 'https://api.cob.org.br/v1', 'api_key', TRUE),
('WhatsApp Business', 'whatsapp_business', 'communication', 'Envio de mensagens via WhatsApp Business API', 'https://graph.facebook.com/v18.0', 'bearer_token', TRUE),
('Twilio SMS', 'twilio_sms', 'communication', 'Envio de SMS via Twilio', 'https://api.twilio.com/2010-04-01', 'basic_auth', TRUE),
('YouTube Live', 'youtube_live', 'streaming', 'Transmissões ao vivo no YouTube', 'https://www.googleapis.com/youtube/v3', 'oauth2', TRUE),
('Facebook Live', 'facebook_live', 'streaming', 'Transmissões ao vivo no Facebook', 'https://graph.facebook.com/v18.0', 'oauth2', TRUE),
('Mercado Pago', 'mercado_pago', 'payment', 'Gateway de pagamentos Mercado Pago', 'https://api.mercadopago.com', 'bearer_token', TRUE),
('PagSeguro', 'pagseguro', 'payment', 'Gateway de pagamentos PagSeguro', 'https://api.pagseguro.com', 'bearer_token', TRUE);

-- Templates de notificação padrão
INSERT INTO notification_templates (organizacao_id, codigo, nome, descricao, canais, email_subject, email_body, sms_body, whatsapp_body, prioridade, ativo) VALUES
(NULL, 'inscricao_confirmada', 'Inscrição Confirmada', 'Notificação de confirmação de inscrição', '["email", "sms", "whatsapp"]',
 'Inscrição Confirmada - {{competicao_nome}}',
 'Olá {{atleta_nome}},\n\nSua inscrição para {{competicao_nome}} foi confirmada!\n\nNúmero de inscrição: {{inscricao_numero}}\nData: {{competicao_data}}\n\nBoa sorte!',
 'Inscricao confirmada para {{competicao_nome}}. Numero: {{inscricao_numero}}',
 'Olá *{{atleta_nome}}*! ✅\n\nSua inscrição para *{{competicao_nome}}* foi confirmada!\n\n📋 Número: {{inscricao_numero}}\n📅 Data: {{competicao_data}}\n\nBoa sorte! 🏆',
 'normal', TRUE),

(NULL, 'pagamento_aprovado', 'Pagamento Aprovado', 'Confirmação de pagamento', '["email", "whatsapp"]',
 'Pagamento Aprovado - {{valor}}',
 'Olá {{nome}},\n\nSeu pagamento de R$ {{valor}} foi aprovado!\n\nTransação: {{transacao_id}}\nMétodo: {{metodo_pagamento}}\n\nObrigado!',
 NULL,
 'Olá *{{nome}}*! 💰\n\nSeu pagamento de *R$ {{valor}}* foi aprovado!\n\n✅ Transação: {{transacao_id}}\n\nObrigado!',
 'alta', TRUE),

(NULL, 'partida_lembrete', 'Lembrete de Partida', 'Lembrete de partida próxima', '["email", "sms", "whatsapp"]',
 'Lembrete: Partida amanhã - {{equipe_casa}} vs {{equipe_visitante}}',
 'Olá,\n\nLembramos que você tem uma partida amanhã:\n\n{{equipe_casa}} vs {{equipe_visitante}}\nData: {{data_hora}}\nLocal: {{local}}\n\nNos vemos lá!',
 'Lembrete: Partida amanha {{data_hora}} - {{local}}',
 '⚽ *Lembrete de Partida*\n\n{{equipe_casa}} vs {{equipe_visitante}}\n📅 {{data_hora}}\n📍 {{local}}\n\nNos vemos lá!',
 'alta', TRUE);

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- Visão de integrações ativas
CREATE OR REPLACE VIEW active_integrations AS
SELECT
    ic.id,
    ic.organizacao_id,
    o.nome as organizacao_nome,
    ip.nome as provider_nome,
    ip.categoria,
    ic.nome_integracao,
    ic.status,
    ic.total_requests,
    ic.total_success,
    ic.total_errors,
    ROUND((ic.total_success / NULLIF(ic.total_requests, 0)) * 100, 2) as taxa_sucesso,
    ic.last_request_at,
    ic.updated_at
FROM integration_configs ic
INNER JOIN integration_providers ip ON ic.provider_id = ip.id
INNER JOIN organizacoes o ON ic.organizacao_id = o.id
WHERE ic.ativo = TRUE;

-- Webhooks com estatísticas
CREATE OR REPLACE VIEW webhook_stats AS
SELECT
    w.id,
    w.organizacao_id,
    w.nome,
    w.url,
    w.ativo,
    w.total_dispatched,
    w.total_success,
    w.total_failed,
    ROUND((w.total_success / NULLIF(w.total_dispatched, 0)) * 100, 2) as taxa_sucesso,
    w.last_dispatch_at,
    w.last_success_at,
    w.last_error_at
FROM webhooks w
ORDER BY w.total_dispatched DESC;

-- Notificações pendentes prioritárias
CREATE OR REPLACE VIEW pending_notifications AS
SELECT
    nq.*,
    nt.nome as template_nome,
    CASE
        WHEN nq.agendado_para IS NULL THEN 'Imediato'
        WHEN nq.agendado_para <= NOW() THEN 'Atrasado'
        ELSE 'Agendado'
    END as urgencia
FROM notification_queue nq
LEFT JOIN notification_templates nt ON nq.template_id = nt.id
WHERE nq.status IN ('pendente', 'erro')
  AND (nq.agendado_para IS NULL OR nq.agendado_para <= NOW())
  AND nq.tentativas < nq.max_tentativas
ORDER BY
    FIELD(nq.prioridade, 'urgente', 'alta', 'normal', 'baixa'),
    nq.created_at ASC;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

CREATE INDEX idx_webhook_logs_recent ON webhook_logs(webhook_id, dispatched_at DESC);
CREATE INDEX idx_notification_queue_processing ON notification_queue(status, prioridade, agendado_para);
CREATE INDEX idx_integration_logs_recent ON integration_api_logs(integration_config_id, created_at DESC);

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Limpar logs antigos (manter últimos 90 dias)
DELIMITER //
CREATE PROCEDURE cleanup_old_logs()
BEGIN
    DECLARE dias_manter INT DEFAULT 90;

    DELETE FROM webhook_logs WHERE dispatched_at < DATE_SUB(NOW(), INTERVAL dias_manter DAY);
    DELETE FROM integration_api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL dias_manter DAY);
    DELETE FROM notification_queue WHERE status IN ('enviado', 'cancelado') AND created_at < DATE_SUB(NOW(), INTERVAL dias_manter DAY);
END//
DELIMITER ;

-- =====================================================
-- IMPORTANTE:
-- - Criptografar credenciais antes de salvar
-- - Implementar rate limiting por organização
-- - Monitorar uso de APIs para evitar custos excessivos
-- - Processar fila de notificações via cron job
-- - Implementar retry automático para falhas
-- =====================================================
