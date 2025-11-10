-- =====================================================
-- MIGRATION: Fix convites_atletas column names
-- Versão: 1.1
-- Data: 2025-11-10
-- Descrição: Renomeia colunas validade_ate e usado_em para data_expiracao e data_aceite
-- =====================================================

-- Verificar estrutura atual
SELECT 'Estrutura ANTES da migration:' as info;
DESCRIBE convites_atletas;

-- Renomear coluna validade_ate para data_expiracao
ALTER TABLE convites_atletas
CHANGE COLUMN validade_ate data_expiracao DATETIME NOT NULL;

-- Renomear coluna usado_em para data_aceite
ALTER TABLE convites_atletas
CHANGE COLUMN usado_em data_aceite DATETIME NULL;

-- Adicionar colunas que faltam (se não existirem)
-- telefone_atleta
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'convites_atletas'
    AND COLUMN_NAME = 'telefone_atleta'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE convites_atletas ADD COLUMN telefone_atleta VARCHAR(20) AFTER email_atleta',
    'SELECT "Coluna telefone_atleta já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- data_criacao
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'convites_atletas'
    AND COLUMN_NAME = 'data_criacao'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE convites_atletas ADD COLUMN data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status',
    'SELECT "Coluna data_criacao já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar ENUM do status para incluir 'Recusado'
ALTER TABLE convites_atletas
MODIFY COLUMN status ENUM('Pendente', 'Aceito', 'Recusado', 'Expirado') DEFAULT 'Pendente';

-- Verificar estrutura final
SELECT 'Estrutura DEPOIS da migration:' as info;
DESCRIBE convites_atletas;

-- Verificar dados dos convites
SELECT 'Total de convites na tabela:' as info;
SELECT COUNT(*) as total FROM convites_atletas;

SELECT 'Migration 001 aplicada com sucesso!' as status;
