-- =====================================================
-- MIGRATION: Schema Base do Sistema
-- Versão: 1.0
-- Data: 2025-11-10
-- Descrição: Cria todas as tabelas base do sistema de gestão esportiva
-- =====================================================

-- Tabela de Administradores
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('Master', 'Admin', 'Moderador') DEFAULT 'Admin',
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Modalidades
CREATE TABLE IF NOT EXISTS modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir modalidades padrão
INSERT IGNORE INTO modalidades (id, nome, descricao) VALUES
(1, 'Futsal', 'Futebol de salão'),
(2, 'Vôlei', 'Voleibol'),
(3, 'Basquete', 'Basquetebol'),
(4, 'Handebol', 'Handebol'),
(5, 'Futebol de Campo', 'Futebol tradicional');

-- Tabela de Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    idade_minima INT,
    idade_maxima INT,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias padrão
INSERT IGNORE INTO categorias (id, nome, descricao, idade_minima, idade_maxima) VALUES
(1, 'Sub-12', 'Categoria até 12 anos', 0, 12),
(2, 'Sub-15', 'Categoria até 15 anos', 13, 15),
(3, 'Sub-18', 'Categoria até 18 anos', 16, 18),
(4, 'Adulto', 'Categoria acima de 18 anos', 18, 100),
(5, 'Livre', 'Categoria livre', 0, 100);

-- Tabela de Competições
CREATE TABLE IF NOT EXISTS competicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    modalidade_id INT NOT NULL,
    categoria_id INT NOT NULL,
    data_inicio_inscricao DATE NOT NULL,
    data_fim_inscricao DATE NOT NULL,
    data_inicio_evento DATE NOT NULL,
    data_fim_evento DATE NOT NULL,
    local_evento VARCHAR(255),
    max_equipes INT DEFAULT 16,
    min_atletas_equipe INT DEFAULT 5,
    max_atletas_equipe INT DEFAULT 15,
    valor_inscricao DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('Aberta', 'Fechada', 'Em Andamento', 'Finalizada', 'Cancelada') DEFAULT 'Aberta',
    banner VARCHAR(255),
    regulamento TEXT,
    informacoes_adicionais TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    INDEX idx_modalidade (modalidade_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_status (status),
    INDEX idx_data_inicio (data_inicio_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Equipes
CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    responsavel_nome VARCHAR(255) NOT NULL,
    responsavel_cpf VARCHAR(14) NOT NULL,
    responsavel_telefone VARCHAR(20) NOT NULL,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    logo VARCHAR(255),
    status ENUM('Pendente', 'Aprovada', 'Rejeitada', 'Suspensa') DEFAULT 'Pendente',
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_ativo (ativo),
    INDEX idx_responsavel_cpf (responsavel_cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Atletas
CREATE TABLE IF NOT EXISTS atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_atual_id INT NOT NULL COMMENT 'ID da equipe do atleta',
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE NOT NULL,
    sexo ENUM('M', 'F', 'Outro') NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(255),
    nome_responsavel VARCHAR(255),
    telefone_responsavel VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    foto VARCHAR(255),
    documento_identidade VARCHAR(255),
    data_entrada_equipe DATE,
    ativo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_atual_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_equipe (equipe_atual_id),
    INDEX idx_cpf (cpf),
    INDEX idx_ativo (ativo),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Inscrições de Equipes em Competições
CREATE TABLE IF NOT EXISTS inscricoes_competicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competicao_id INT NOT NULL,
    equipe_id INT NOT NULL,
    protocolo VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('Pendente', 'Confirmada', 'Cancelada') DEFAULT 'Pendente',
    data_inscricao DATETIME NOT NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_competicao (competicao_id),
    INDEX idx_equipe (equipe_id),
    INDEX idx_status (status),
    INDEX idx_protocolo (protocolo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Atletas Inscritos em cada Inscrição
CREATE TABLE IF NOT EXISTS inscricoes_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inscricao_competicao_id INT NOT NULL,
    atleta_id INT NOT NULL,
    data_inscricao DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inscricao_competicao_id) REFERENCES inscricoes_competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    INDEX idx_inscricao (inscricao_competicao_id),
    INDEX idx_atleta (atleta_id),
    UNIQUE KEY unique_inscricao_atleta (inscricao_competicao_id, atleta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Equipes
CREATE TABLE IF NOT EXISTS historico_equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    evento_tipo VARCHAR(50) NOT NULL,
    descricao TEXT,
    dados_adicionais JSON,
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_equipe (equipe_id),
    INDEX idx_data (data_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Atletas
CREATE TABLE IF NOT EXISTS historico_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    evento_tipo VARCHAR(50) NOT NULL,
    descricao TEXT,
    dados_novos JSON,
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    INDEX idx_atleta (atleta_id),
    INDEX idx_data (data_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Convites para Atletas
CREATE TABLE IF NOT EXISTS convites_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    nome_atleta VARCHAR(255),
    email_atleta VARCHAR(255),
    telefone_atleta VARCHAR(20),
    status ENUM('Pendente', 'Aceito', 'Recusado', 'Expirado') DEFAULT 'Pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME NOT NULL,
    data_aceite DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    INDEX idx_equipe (equipe_id),
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar tabelas criadas
SELECT 'Schema base criado com sucesso!' as status;

SELECT
    TABLE_NAME,
    TABLE_ROWS,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN (
    'administradores', 'modalidades', 'categorias', 'competicoes',
    'equipes', 'atletas', 'inscricoes_competicoes', 'inscricoes_atletas',
    'historico_equipes', 'historico_atletas', 'convites_atletas'
)
ORDER BY TABLE_NAME;
