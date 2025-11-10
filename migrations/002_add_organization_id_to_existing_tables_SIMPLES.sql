-- =====================================================
-- MIGRATION: Adicionar organization_id às tabelas existentes (VERSÃO SIMPLES)
-- Versão: 2.0 (Sem uso de information_schema)
-- Data: 2025-11-10
-- Descrição: Adiciona campo organization_id em todas as tabelas
--            Esta versão NÃO usa information_schema (compatível com Hostinger)
--            ATENÇÃO: Pode gerar erros se já executada, mas são seguros de ignorar
-- =====================================================

-- Ignorar erros de coluna/índice já existente
SET sql_notes = 0;

-- =====================================================
-- ADICIONAR COLUNAS NAS TABELAS
-- =====================================================

-- Equipes (organizacao_id pode ser NULL para equipes públicas)
ALTER TABLE equipes ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização proprietária' AFTER id;
ALTER TABLE equipes ADD INDEX idx_organizacao (organizacao_id);

-- Competicoes
ALTER TABLE competicoes ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização proprietária' AFTER id;
ALTER TABLE competicoes ADD INDEX idx_organizacao (organizacao_id);

-- Atletas
ALTER TABLE atletas ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização proprietária (via equipe)' AFTER id;
ALTER TABLE atletas ADD INDEX idx_organizacao (organizacao_id);

-- Administradores (NULL = super admin multi-org)
ALTER TABLE administradores ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização (NULL = super admin)' AFTER id;
ALTER TABLE administradores ADD INDEX idx_organizacao (organizacao_id);

-- Inscricoes
ALTER TABLE inscricoes_competicoes ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização proprietária' AFTER id;
ALTER TABLE inscricoes_competicoes ADD INDEX idx_organizacao (organizacao_id);

-- Modalidades
ALTER TABLE modalidades ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização (NULL = global)' AFTER id;
ALTER TABLE modalidades ADD INDEX idx_organizacao (organizacao_id);

-- Categorias
ALTER TABLE categorias ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização (NULL = global)' AFTER id;
ALTER TABLE categorias ADD INDEX idx_organizacao (organizacao_id);

-- =====================================================
-- ADICIONAR FOREIGN KEYS (OPCIONAL - só se tabela organizacoes existir)
-- =====================================================

-- Tentar adicionar FKs (vai falhar silenciosamente se organizacoes não existir)
-- Isso permite usar o sistema sem multi-tenancy

SET @fk_sql = 'ALTER TABLE equipes ADD CONSTRAINT fk_equipes_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE competicoes ADD CONSTRAINT fk_competicoes_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE atletas ADD CONSTRAINT fk_atletas_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE administradores ADD CONSTRAINT fk_administradores_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE inscricoes_competicoes ADD CONSTRAINT fk_inscricoes_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE modalidades ADD CONSTRAINT fk_modalidades_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_sql = 'ALTER TABLE categorias ADD CONSTRAINT fk_categorias_organizacao FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id)';
PREPARE stmt FROM @fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Restaurar avisos
SET sql_notes = 1;

SELECT 'Migration 002 SIMPLES executada! (Ignorar erros de "Duplicate column" se houver)' as status;
