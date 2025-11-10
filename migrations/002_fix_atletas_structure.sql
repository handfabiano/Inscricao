-- =====================================================
-- MIGRATION: Fix atletas table structure
-- Versão: 1.2
-- Data: 2025-11-10
-- Descrição: Adiciona/renomeia colunas na tabela atletas
-- =====================================================

-- Verificar estrutura atual
SELECT 'Estrutura ANTES da migration:' as info;
DESC atletas;

-- Adicionar coluna nome_completo se não existir (alias para nome)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'nome_completo'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN nome_completo VARCHAR(255) NOT NULL AFTER id',
    'SELECT "Coluna nome_completo já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna genero se não existir (alias para sexo)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'genero'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN genero VARCHAR(20) AFTER data_nascimento',
    'SELECT "Coluna genero já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar colunas de endereço detalhado se não existirem
-- CEP
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'cep'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN cep VARCHAR(10) AFTER telefone_responsavel',
    'SELECT "Coluna cep já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Numero
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'numero'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN numero VARCHAR(10) AFTER endereco',
    'SELECT "Coluna numero já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Complemento
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'complemento'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN complemento VARCHAR(100) AFTER numero',
    'SELECT "Coluna complemento já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bairro
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'bairro'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN bairro VARCHAR(100) AFTER complemento',
    'SELECT "Coluna bairro já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colunas de documentos com _path
-- foto_path
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'foto_path'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN foto_path VARCHAR(255) AFTER estado',
    'SELECT "Coluna foto_path já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- documento_identidade_path
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'documento_identidade_path'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN documento_identidade_path VARCHAR(255) AFTER foto_path',
    'SELECT "Coluna documento_identidade_path já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comprovante_residencia_path
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'comprovante_residencia_path'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN comprovante_residencia_path VARCHAR(255) AFTER documento_identidade_path',
    'SELECT "Coluna comprovante_residencia_path já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- atestado_medico_path
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'atestado_medico_path'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN atestado_medico_path VARCHAR(255) AFTER comprovante_residencia_path',
    'SELECT "Coluna atestado_medico_path já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- observacoes
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'atletas'
    AND COLUMN_NAME = 'observacoes'
);

SET @sql = IF(
    @col_exists = 0,
    'ALTER TABLE atletas ADD COLUMN observacoes TEXT AFTER atestado_medico_path',
    'SELECT "Coluna observacoes já existe" as info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Copiar dados de nome para nome_completo se nome_completo estiver vazio
UPDATE atletas SET nome_completo = nome WHERE nome_completo IS NULL OR nome_completo = '';

-- Copiar dados de sexo para genero se genero estiver vazio
UPDATE atletas SET genero = sexo WHERE genero IS NULL OR genero = '';

-- Verificar estrutura final
SELECT 'Estrutura DEPOIS da migration:' as info;
DESC atletas;

SELECT 'Migration 002 aplicada com sucesso!' as status;
