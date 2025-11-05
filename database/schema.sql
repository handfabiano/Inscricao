-- Sistema de Inscrição de Atletas
-- Banco de Dados MySQL

CREATE DATABASE IF NOT EXISTS inscricao_atletas;
USE inscricao_atletas;

-- Tabela de Modalidades Esportivas
CREATE TABLE IF NOT EXISTS modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    idade_minima INT,
    idade_maxima INT,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Atletas/Inscrições
CREATE TABLE IF NOT EXISTS inscricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocolo VARCHAR(20) UNIQUE NOT NULL,

    -- Dados Pessoais
    nome_completo VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    rg VARCHAR(20),
    data_nascimento DATE NOT NULL,
    genero ENUM('Masculino', 'Feminino', 'Outro') NOT NULL,

    -- Contato
    email VARCHAR(200) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    celular VARCHAR(20),

    -- Endereço
    cep VARCHAR(10),
    endereco VARCHAR(300),
    numero VARCHAR(10),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),

    -- Dados Esportivos
    modalidade_id INT,
    categoria_id INT,
    experiencia_anos INT,
    clube_anterior VARCHAR(200),

    -- Responsável (para menores de idade)
    responsavel_nome VARCHAR(200),
    responsavel_cpf VARCHAR(14),
    responsavel_telefone VARCHAR(20),
    responsavel_parentesco VARCHAR(50),

    -- Documentos
    foto_path VARCHAR(300),
    documento_identidade_path VARCHAR(300),
    comprovante_residencia_path VARCHAR(300),
    atestado_medico_path VARCHAR(300),

    -- Controle
    status ENUM('Pendente', 'Aprovada', 'Rejeitada', 'Cancelada') DEFAULT 'Pendente',
    observacoes TEXT,
    ip_origem VARCHAR(50),
    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),

    INDEX idx_cpf (cpf),
    INDEX idx_protocolo (protocolo),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Tabela de Usuários Administrativos
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    nivel ENUM('Admin', 'Operador') DEFAULT 'Operador',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100),
    descricao TEXT,
    ip VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir dados iniciais
INSERT INTO modalidades (nome, descricao) VALUES
('Futebol', 'Futebol de Campo'),
('Futsal', 'Futebol de Salão'),
('Basquete', 'Basquetebol'),
('Vôlei', 'Voleibol'),
('Handebol', 'Handebol'),
('Atletismo', 'Atletismo - Diversas Provas'),
('Natação', 'Natação'),
('Judô', 'Judô'),
('Karatê', 'Karatê'),
('Taekwondo', 'Taekwondo');

INSERT INTO categorias (nome, idade_minima, idade_maxima, descricao) VALUES
('Sub-11', 8, 11, 'Categoria Sub-11'),
('Sub-13', 11, 13, 'Categoria Sub-13'),
('Sub-15', 13, 15, 'Categoria Sub-15'),
('Sub-17', 15, 17, 'Categoria Sub-17'),
('Sub-20', 17, 20, 'Categoria Sub-20'),
('Adulto', 18, 99, 'Categoria Adulta');

-- Criar usuário admin padrão (senha: admin123)
INSERT INTO usuarios (username, password, nome, email, nivel) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@inscricoes.com', 'Admin');
