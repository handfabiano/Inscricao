-- ============================================================================
-- CRIAR EQUIPE DE TESTE
-- Execute no phpMyAdmin para criar uma equipe e fazer login
-- ============================================================================

-- Inserir equipe de teste
-- Usuário: equipe1
-- Senha: equipe123
INSERT INTO equipes (
    nome,
    sigla,
    responsavel_nome,
    responsavel_cpf,
    responsavel_email,
    responsavel_telefone,
    cidade,
    estado,
    usuario,
    senha,
    status,
    ativo,
    created_at
) VALUES (
    'Equipe Teste FC',
    'ETFC',
    'João da Silva',
    '12345678901',
    'joao@equipeteste.com',
    '95999887766',
    'Boa Vista',
    'RR',
    'equipe1',
    '$2y$10$wZQjB5z4gP4PQYX6LpqF5OEhL.yJVxq0OGy5t4qZ8oLKQhZ5eO0Nm',
    'Aprovada',
    1,
    NOW()
);

-- Verificar se foi criada
SELECT
    id,
    nome,
    usuario,
    responsavel_nome,
    status,
    created_at
FROM equipes
WHERE usuario = 'equipe1';

-- ============================================================================
-- CREDENCIAIS DE ACESSO
-- ============================================================================
-- URL: /equipe/login.php
-- Usuário: equipe1
-- Senha: equipe123
-- ============================================================================
