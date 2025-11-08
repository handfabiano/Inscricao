-- =====================================================
-- MIGRATION: Payment System
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Sistema completo de pagamentos (PIX, Cartão, Boleto)
-- =====================================================

-- Tabela de configuração de gateways de pagamento
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,

    gateway VARCHAR(50) NOT NULL COMMENT 'mercadopago, pagseguro, asaas, stripe, etc',
    nome_exibicao VARCHAR(100) NOT NULL,

    -- Credenciais (armazenadas criptografadas)
    public_key TEXT COMMENT 'Chave pública',
    secret_key TEXT COMMENT 'Chave secreta (criptografada)',
    access_token TEXT COMMENT 'Token de acesso (criptografado)',

    -- Configurações específicas
    ambiente ENUM('sandbox', 'production') DEFAULT 'sandbox',
    webhook_url VARCHAR(255) COMMENT 'URL para receber notificações',
    webhook_secret VARCHAR(100) COMMENT 'Secret para validar webhooks',

    -- Métodos de pagamento aceitos
    aceita_pix BOOLEAN DEFAULT TRUE,
    aceita_cartao_credito BOOLEAN DEFAULT TRUE,
    aceita_cartao_debito BOOLEAN DEFAULT FALSE,
    aceita_boleto BOOLEAN DEFAULT FALSE,

    -- Taxas (em percentual)
    taxa_pix DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Taxa em % para PIX',
    taxa_cartao_credito DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Taxa em % para cartão',
    taxa_boleto DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Taxa em % para boleto',

    -- Controle
    ativo BOOLEAN DEFAULT TRUE,
    padrao BOOLEAN DEFAULT FALSE COMMENT 'Gateway padrão da organização',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_gateway (gateway),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuração de gateways de pagamento';

-- Tabela de transações de pagamento
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organizacao_id INT NOT NULL,
    gateway_id INT NOT NULL COMMENT 'Gateway utilizado',

    -- Referência interna
    tipo_referencia ENUM('inscricao', 'assinatura', 'outro') NOT NULL,
    referencia_id INT NOT NULL COMMENT 'ID da inscrição, assinatura, etc',

    -- Identificadores externos
    transaction_id VARCHAR(100) UNIQUE COMMENT 'ID da transação no gateway',
    external_id VARCHAR(100) COMMENT 'ID externo personalizado',

    -- Dados do pagamento
    metodo_pagamento ENUM('pix', 'cartao_credito', 'cartao_debito', 'boleto') NOT NULL,
    status ENUM('pending', 'processing', 'approved', 'rejected', 'cancelled', 'refunded') DEFAULT 'pending',

    -- Valores
    valor_original DECIMAL(10,2) NOT NULL COMMENT 'Valor original',
    valor_taxa DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Taxa do gateway',
    valor_liquido DECIMAL(10,2) NOT NULL COMMENT 'Valor líquido (após taxas)',
    valor_reembolsado DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor reembolsado',

    moeda VARCHAR(3) DEFAULT 'BRL',

    -- Dados específicos por método
    pix_qr_code TEXT COMMENT 'QR Code PIX',
    pix_qr_code_base64 LONGTEXT COMMENT 'QR Code em base64 para exibição',
    pix_copia_cola TEXT COMMENT 'Código PIX copia e cola',
    pix_expiration TIMESTAMP NULL COMMENT 'Expiração do PIX',

    boleto_url TEXT COMMENT 'URL do boleto',
    boleto_barcode VARCHAR(100) COMMENT 'Código de barras do boleto',
    boleto_expiration DATE COMMENT 'Vencimento do boleto',

    cartao_bandeira VARCHAR(20) COMMENT 'Visa, Mastercard, etc',
    cartao_ultimos_digitos VARCHAR(4) COMMENT 'Últimos 4 dígitos',
    cartao_parcelas INT DEFAULT 1 COMMENT 'Número de parcelas',

    -- Dados do pagador
    pagador_nome VARCHAR(150),
    pagador_email VARCHAR(100),
    pagador_cpf_cnpj VARCHAR(18),
    pagador_telefone VARCHAR(20),

    -- Timestamps
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_aprovacao TIMESTAMP NULL,
    data_rejeicao TIMESTAMP NULL,
    data_cancelamento TIMESTAMP NULL,
    data_reembolso TIMESTAMP NULL,

    -- Mensagens e erros
    mensagem_erro TEXT COMMENT 'Mensagem de erro em caso de rejeição',
    observacoes TEXT,

    -- Webhook
    webhook_payload JSON COMMENT 'Payload completo do webhook',
    webhook_recebido_em TIMESTAMP NULL,

    -- Controle
    ip_address VARCHAR(45),
    user_agent TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id) ON DELETE CASCADE,
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id),
    INDEX idx_organizacao (organizacao_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_referencia (tipo_referencia, referencia_id),
    INDEX idx_status (status),
    INDEX idx_metodo (metodo_pagamento),
    INDEX idx_data_criacao (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Transações de pagamento';

-- Tabela de log de webhooks
CREATE TABLE IF NOT EXISTS payment_webhooks_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_id INT,
    transaction_id VARCHAR(100),

    evento VARCHAR(100) COMMENT 'Tipo de evento (payment.updated, etc)',
    payload JSON COMMENT 'Payload completo do webhook',
    headers JSON COMMENT 'Headers da requisição',

    processado BOOLEAN DEFAULT FALSE,
    processado_em TIMESTAMP NULL,
    erro TEXT COMMENT 'Erro ao processar (se houver)',

    ip_address VARCHAR(45),
    recebido_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_transaction (transaction_id),
    INDEX idx_processado (processado),
    INDEX idx_recebido (recebido_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de webhooks recebidos';

-- Tabela de split de pagamento (divisão entre organizador e federação)
CREATE TABLE IF NOT EXISTS payment_splits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,

    destinatario_tipo ENUM('organizacao', 'federacao', 'plataforma') NOT NULL,
    destinatario_id INT COMMENT 'ID da organização ou federação',

    percentual DECIMAL(5,2) COMMENT 'Percentual do split',
    valor_fixo DECIMAL(10,2) COMMENT 'Valor fixo do split',
    valor_calculado DECIMAL(10,2) NOT NULL COMMENT 'Valor final calculado',

    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    processado_em TIMESTAMP NULL,

    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_destinatario (destinatario_tipo, destinatario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Split de pagamentos';

-- Tabela de reembolsos
CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,

    refund_id VARCHAR(100) UNIQUE COMMENT 'ID do reembolso no gateway',
    valor DECIMAL(10,2) NOT NULL,
    motivo TEXT,

    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',

    solicitado_por INT COMMENT 'ID do usuário que solicitou',
    solicitado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processado_em TIMESTAMP NULL,

    observacoes TEXT,

    FOREIGN KEY (transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Reembolsos de pagamentos';

-- Adicionar campo de pagamento nas inscrições
ALTER TABLE inscricoes_competicoes
ADD COLUMN payment_transaction_id INT DEFAULT NULL COMMENT 'ID da transação de pagamento' AFTER observacoes,
ADD COLUMN payment_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER payment_transaction_id,
ADD INDEX idx_payment (payment_transaction_id),
ADD FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL;

-- =====================================================
-- Inserir gateways de exemplo (sandbox)
-- =====================================================

INSERT INTO payment_gateways (
    organizacao_id, gateway, nome_exibicao,
    ambiente, aceita_pix, aceita_cartao_credito, aceita_boleto,
    taxa_pix, taxa_cartao_credito, taxa_boleto, ativo, padrao
) VALUES
(1, 'mercadopago', 'Mercado Pago', 'sandbox', TRUE, TRUE, TRUE, 0.99, 4.99, 3.49, TRUE, TRUE),
(1, 'asaas', 'Asaas', 'sandbox', TRUE, TRUE, TRUE, 0.00, 3.99, 1.99, FALSE, FALSE);

-- =====================================================
-- IMPORTANTE: Implementar criptografia para as credenciais
-- Use openssl_encrypt/decrypt com chave segura
-- =====================================================
