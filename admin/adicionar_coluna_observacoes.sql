-- Adicionar coluna observacoes na tabela atletas caso não exista
ALTER TABLE atletas ADD COLUMN IF NOT EXISTS observacoes TEXT NULL AFTER atestado_medico_path;
