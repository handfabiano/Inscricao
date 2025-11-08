-- =====================================================
-- MIGRATION: Adicionar organization_id às tabelas existentes
-- Versão: 1.0
-- Data: 2025-11-08
-- Descrição: Adiciona campo organization_id em todas as tabelas
--            para implementar isolamento de dados por organização
-- =====================================================

-- Adicionar organization_id na tabela de equipes
ALTER TABLE equipes
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de competições
ALTER TABLE competicoes
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de atletas
ALTER TABLE atletas
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de administradores
ALTER TABLE administradores
ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização (NULL = super admin)' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de inscrições
ALTER TABLE inscricoes_competicoes
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de modalidades
ALTER TABLE modalidades
ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'NULL = modalidade global, senão específica da org' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de categorias
ALTER TABLE categorias
ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'NULL = categoria global, senão específica da org' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de convites de atletas
ALTER TABLE convites_atletas
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de notificações de email
ALTER TABLE notificacoes_email
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização que enviou' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de logs do sistema
ALTER TABLE logs_sistema
ADD COLUMN organizacao_id INT DEFAULT NULL COMMENT 'Organização relacionada ao log' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de histórico de equipes
ALTER TABLE historico_equipes
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- Adicionar organization_id na tabela de histórico de atletas
ALTER TABLE historico_atletas
ADD COLUMN organizacao_id INT NOT NULL DEFAULT 1 COMMENT 'Organização proprietária' AFTER id,
ADD INDEX idx_organizacao (organizacao_id),
ADD FOREIGN KEY (organizacao_id) REFERENCES organizacoes(id);

-- =====================================================
-- Atualizar dados existentes
-- =====================================================

-- Todos os registros existentes serão associados à organização ID 1 (Organização Padrão)
-- Isso já está feito através do DEFAULT 1 nas alterações acima

-- =====================================================
-- Remover DEFAULT após migração (segurança)
-- =====================================================

-- Após garantir que todos os dados foram migrados, remover o DEFAULT
-- para forçar a especificação explícita da organização em novos registros

ALTER TABLE equipes ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE competicoes ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE atletas ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE inscricoes_competicoes ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE convites_atletas ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE notificacoes_email ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE historico_equipes ALTER COLUMN organizacao_id DROP DEFAULT;
ALTER TABLE historico_atletas ALTER COLUMN organizacao_id DROP DEFAULT;

-- =====================================================
-- IMPORTANTE: Execute esta migration APÓS a 001
-- =====================================================
