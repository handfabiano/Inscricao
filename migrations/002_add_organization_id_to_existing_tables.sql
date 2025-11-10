-- =====================================================
-- MIGRATION: Adicionar organization_id às tabelas existentes
-- Versão: 1.1 (Idempotente - pode ser executado múltiplas vezes)
-- Data: 2025-11-09
-- Descrição: Adiciona campo organization_id em todas as tabelas
--            para implementar isolamento de dados por organização
-- =====================================================

-- Procedure auxiliar para adicionar coluna se não existir
DELIMITER //

DROP PROCEDURE IF EXISTS add_column_if_not_exists//
CREATE PROCEDURE add_column_if_not_exists(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    DECLARE col_exists INT DEFAULT 0;

    SELECT COUNT(*)
    INTO col_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND COLUMN_NAME = p_column_name;

    IF col_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN ', p_column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

-- Procedure auxiliar para adicionar índice se não existir
DROP PROCEDURE IF EXISTS add_index_if_not_exists//
CREATE PROCEDURE add_index_if_not_exists(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_column_name VARCHAR(64)
)
BEGIN
    DECLARE idx_exists INT DEFAULT 0;

    SELECT COUNT(*)
    INTO idx_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND INDEX_NAME = p_index_name;

    IF idx_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (`', p_column_name, '`)');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

-- Procedure auxiliar para adicionar foreign key se não existir
DROP PROCEDURE IF EXISTS add_fk_if_not_exists//
CREATE PROCEDURE add_fk_if_not_exists(
    IN p_table_name VARCHAR(64),
    IN p_constraint_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_ref_table VARCHAR(64),
    IN p_ref_column VARCHAR(64)
)
BEGIN
    DECLARE fk_exists INT DEFAULT 0;

    SELECT COUNT(*)
    INTO fk_exists
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND CONSTRAINT_NAME = p_constraint_name;

    IF fk_exists = 0 THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table_name, '` ',
            'ADD CONSTRAINT `', p_constraint_name, '` ',
            'FOREIGN KEY (`', p_column_name, '`) ',
            'REFERENCES `', p_ref_table, '` (`', p_ref_column, '`)'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

-- =====================================================
-- ADICIONAR COLUNAS NAS TABELAS
-- =====================================================

-- Equipes
CALL add_column_if_not_exists('equipes', 'organizacao_id',
    '`organizacao_id` INT NOT NULL DEFAULT 1 COMMENT "Organização proprietária" AFTER `id`');
CALL add_index_if_not_exists('equipes', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('equipes', 'fk_equipes_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Competições
CALL add_column_if_not_exists('competicoes', 'organizacao_id',
    '`organizacao_id` INT NOT NULL DEFAULT 1 COMMENT "Organização proprietária" AFTER `id`');
CALL add_index_if_not_exists('competicoes', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('competicoes', 'fk_competicoes_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Atletas
CALL add_column_if_not_exists('atletas', 'organizacao_id',
    '`organizacao_id` INT NOT NULL DEFAULT 1 COMMENT "Organização proprietária" AFTER `id`');
CALL add_index_if_not_exists('atletas', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('atletas', 'fk_atletas_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Administradores
CALL add_column_if_not_exists('administradores', 'organizacao_id',
    '`organizacao_id` INT DEFAULT NULL COMMENT "Organização (NULL = super admin)" AFTER `id`');
CALL add_index_if_not_exists('administradores', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('administradores', 'fk_administradores_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Inscrições
CALL add_column_if_not_exists('inscricoes_competicoes', 'organizacao_id',
    '`organizacao_id` INT NOT NULL DEFAULT 1 COMMENT "Organização proprietária" AFTER `id`');
CALL add_index_if_not_exists('inscricoes_competicoes', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('inscricoes_competicoes', 'fk_inscricoes_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Modalidades
CALL add_column_if_not_exists('modalidades', 'organizacao_id',
    '`organizacao_id` INT DEFAULT NULL COMMENT "NULL = modalidade global, senão específica da org" AFTER `id`');
CALL add_index_if_not_exists('modalidades', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('modalidades', 'fk_modalidades_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- Categorias
CALL add_column_if_not_exists('categorias', 'organizacao_id',
    '`organizacao_id` INT DEFAULT NULL COMMENT "NULL = categoria global, senão específica da org" AFTER `id`');
CALL add_index_if_not_exists('categorias', 'idx_organizacao', 'organizacao_id');
CALL add_fk_if_not_exists('categorias', 'fk_categorias_organizacao', 'organizacao_id', 'organizacoes', 'id');

-- =====================================================
-- REMOVER DEFAULT APÓS MIGRAÇÃO (SEGURANÇA)
-- =====================================================

-- Remover DEFAULT das colunas NOT NULL para forçar especificação explícita
-- Isso impede que novos registros sejam criados sem organização por engano

SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'equipes'
        AND COLUMN_NAME = 'organizacao_id'
        AND COLUMN_DEFAULT IS NOT NULL
    ),
    'ALTER TABLE equipes MODIFY organizacao_id INT NOT NULL COMMENT "Organização proprietária"',
    'SELECT "Column already modified" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'competicoes'
        AND COLUMN_NAME = 'organizacao_id'
        AND COLUMN_DEFAULT IS NOT NULL
    ),
    'ALTER TABLE competicoes MODIFY organizacao_id INT NOT NULL COMMENT "Organização proprietária"',
    'SELECT "Column already modified" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    EXISTS(
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'atletas'
        AND COLUMN_NAME = 'organizacao_id'
        AND COLUMN_DEFAULT IS NOT NULL
    ),
    'ALTER TABLE atletas MODIFY organizacao_id INT NOT NULL COMMENT "Organização proprietária"',
    'SELECT "Column already modified" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- LIMPAR PROCEDURES TEMPORÁRIAS
-- =====================================================

DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DROP PROCEDURE IF EXISTS add_fk_if_not_exists;

-- =====================================================
-- MIGRATION COMPLETA
-- =====================================================
