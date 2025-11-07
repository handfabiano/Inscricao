-- Script para adicionar colunas de resultados à tabela inscricoes_competicoes
-- Execute este script no banco de dados caso as colunas não existam

-- Adicionar coluna colocacao
ALTER TABLE `inscricoes_competicoes`
ADD COLUMN IF NOT EXISTS `colocacao` INT NULL DEFAULT NULL COMMENT 'Colocação da equipe na competição' AFTER `status`;

-- Adicionar coluna pontuacao
ALTER TABLE `inscricoes_competicoes`
ADD COLUMN IF NOT EXISTS `pontuacao` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Pontuação obtida pela equipe' AFTER `colocacao`;

-- Adicionar coluna updated_at caso não exista
ALTER TABLE `inscricoes_competicoes`
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Criar índice para otimizar buscas por colocação
CREATE INDEX IF NOT EXISTS `idx_colocacao` ON `inscricoes_competicoes` (`colocacao`);

-- Mensagem de sucesso
SELECT 'Colunas de resultados adicionadas com sucesso!' AS message;
