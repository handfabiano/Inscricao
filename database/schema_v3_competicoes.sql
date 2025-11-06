-- ============================================================================
-- SISTEMA DE GESTÃO DE COMPETIÇÕES ESPORTIVAS - VERSÃO 3.0
-- Modelo: Competições → Equipes → Atletas → Inscrições
-- ============================================================================

-- Usar banco de dados
CREATE DATABASE IF NOT EXISTS inscricao_atletas_v3;
USE inscricao_atletas_v3;

-- ============================================================================
-- TABELAS DE CONFIGURAÇÃO BÁSICA
-- ============================================================================

-- Modalidades Esportivas
CREATE TABLE IF NOT EXISTS modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    idade_minima INT,
    idade_maxima INT,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- COMPETIÇÕES
-- ============================================================================

CREATE TABLE IF NOT EXISTS competicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Informações Básicas
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    banner_path VARCHAR(300),

    -- Períodos
    data_inicio_inscricoes DATE NOT NULL,
    data_fim_inscricoes DATE NOT NULL,
    data_inicio_evento DATE NOT NULL,
    data_fim_evento DATE NOT NULL,

    -- Configurações Esportivas
    modalidade_id INT NOT NULL,
    categorias_permitidas JSON, -- Array de IDs de categorias
    genero_permitido ENUM('Masculino', 'Feminino', 'Misto') NOT NULL,

    -- Regras de Equipe/Atletas
    min_atletas INT DEFAULT 1,
    max_atletas INT DEFAULT 99,
    idade_minima INT,
    idade_maxima INT,

    -- Financeiro
    taxa_inscricao DECIMAL(10,2) DEFAULT 0.00,
    taxa_por_atleta DECIMAL(10,2) DEFAULT 0.00,

    -- Documentos
    regulamento TEXT,
    documentos_obrigatorios JSON, -- Array de documentos necessários

    -- Localização
    local_evento VARCHAR(300),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    endereco_completo TEXT,

    -- Contato
    email_contato VARCHAR(200),
    telefone_contato VARCHAR(20),

    -- Status e Controle
    status ENUM('Rascunho', 'Aberta', 'Fechada', 'Em Andamento', 'Encerrada', 'Cancelada') DEFAULT 'Rascunho',
    vagas_limitadas TINYINT(1) DEFAULT 0,
    max_equipes INT,

    -- Extras
    observacoes TEXT,
    premiacao TEXT,

    -- Controle
    criado_por INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),
    INDEX idx_status (status),
    INDEX idx_datas (data_inicio_inscricoes, data_fim_inscricoes),
    INDEX idx_modalidade (modalidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- EQUIPES
-- ============================================================================

CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Informações Básicas
    nome VARCHAR(200) NOT NULL,
    sigla VARCHAR(10),
    ano_fundacao INT,
    cores VARCHAR(100),
    escudo_path VARCHAR(300),

    -- Responsável da Equipe
    responsavel_nome VARCHAR(200) NOT NULL,
    responsavel_cpf VARCHAR(14) UNIQUE NOT NULL,
    responsavel_rg VARCHAR(20),
    responsavel_email VARCHAR(200) NOT NULL,
    responsavel_telefone VARCHAR(20) NOT NULL,
    responsavel_celular VARCHAR(20),
    responsavel_cargo VARCHAR(100) DEFAULT 'Responsável',
    responsavel_data_nascimento DATE,

    -- Endereço da Equipe/Responsável
    cep VARCHAR(10),
    endereco VARCHAR(300),
    numero VARCHAR(10),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,

    -- Documentação
    documento_responsavel_path VARCHAR(300),
    ata_fundacao_path VARCHAR(300),

    -- Login (Responsável pode acessar o sistema)
    usuario VARCHAR(100) UNIQUE,
    senha VARCHAR(255),

    -- Status e Controle
    status ENUM('Pendente', 'Aprovada', 'Rejeitada', 'Suspensa') DEFAULT 'Pendente',
    ativo TINYINT(1) DEFAULT 1,
    motivo_status TEXT,

    -- Extras
    historia TEXT,
    site VARCHAR(200),
    redes_sociais JSON,

    -- Controle
    ip_cadastro VARCHAR(50),
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_responsavel_cpf (responsavel_cpf),
    INDEX idx_status (status),
    INDEX idx_cidade (cidade, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ATLETAS
-- ============================================================================

CREATE TABLE IF NOT EXISTS atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Informações Básicas
    nome_completo VARCHAR(200) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    rg VARCHAR(20),
    data_nascimento DATE NOT NULL,
    genero ENUM('Masculino', 'Feminino') NOT NULL,
    foto_path VARCHAR(300),

    -- Equipe Atual
    equipe_atual_id INT,
    data_entrada_equipe DATE,
    numero_camisa INT,
    posicao VARCHAR(100),

    -- Contato
    email VARCHAR(200),
    telefone VARCHAR(20),
    celular VARCHAR(20),

    -- Endereço
    cep VARCHAR(10),
    endereco VARCHAR(300),
    numero VARCHAR(10),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),

    -- Responsável Legal (se menor de 18 anos)
    responsavel_legal_nome VARCHAR(200),
    responsavel_legal_cpf VARCHAR(14),
    responsavel_legal_telefone VARCHAR(20),
    responsavel_legal_parentesco VARCHAR(50),

    -- Documentos
    documento_identidade_path VARCHAR(300),
    comprovante_residencia_path VARCHAR(300),
    atestado_medico_path VARCHAR(300),
    certidao_nascimento_path VARCHAR(300),

    -- Dados Esportivos
    peso DECIMAL(5,2),
    altura DECIMAL(3,2),
    tipo_sanguineo VARCHAR(5),
    observacoes_medicas TEXT,

    -- Status
    ativo TINYINT(1) DEFAULT 1,
    motivo_inativo TEXT,

    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (equipe_atual_id) REFERENCES equipes(id) ON DELETE SET NULL,
    INDEX idx_cpf (cpf),
    INDEX idx_equipe (equipe_atual_id),
    INDEX idx_nome (nome_completo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSCRIÇÕES EM COMPETIÇÕES
-- ============================================================================

CREATE TABLE IF NOT EXISTS inscricoes_competicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Referências
    competicao_id INT NOT NULL,
    equipe_id INT NOT NULL,
    categoria_id INT NOT NULL,

    -- Protocolo
    protocolo VARCHAR(30) UNIQUE NOT NULL,

    -- Configuração da Inscrição
    genero_equipe ENUM('Masculino', 'Feminino', 'Misto') NOT NULL,
    quantidade_atletas INT NOT NULL,

    -- Pagamento
    valor_total DECIMAL(10,2),
    valor_pago DECIMAL(10,2) DEFAULT 0.00,
    comprovante_pagamento_path VARCHAR(300),
    data_pagamento DATETIME,
    forma_pagamento VARCHAR(50),

    -- Status
    status ENUM('Pendente', 'Aguardando Pagamento', 'Paga', 'Aprovada', 'Rejeitada', 'Cancelada') DEFAULT 'Pendente',
    motivo_status TEXT,

    -- Controle
    aprovado_por INT,
    data_aprovacao DATETIME,
    observacoes TEXT,

    ip_origem VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),

    UNIQUE KEY unique_equipe_competicao (competicao_id, equipe_id, categoria_id),
    INDEX idx_protocolo (protocolo),
    INDEX idx_competicao (competicao_id),
    INDEX idx_equipe (equipe_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ATLETAS INSCRITOS NA COMPETIÇÃO
-- ============================================================================

CREATE TABLE IF NOT EXISTS inscricoes_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Referências
    inscricao_competicao_id INT NOT NULL,
    atleta_id INT NOT NULL,

    -- Dados da Inscrição
    numero_camisa INT,
    posicao VARCHAR(100),

    -- Status Individual do Atleta
    aprovado TINYINT(1) DEFAULT 0,
    motivo_reprovacao TEXT,
    pendencias JSON, -- Array de pendências (ex: documentos faltando)

    -- Controle
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (inscricao_competicao_id) REFERENCES inscricoes_competicoes(id) ON DELETE CASCADE,
    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,

    UNIQUE KEY unique_atleta_inscricao (inscricao_competicao_id, atleta_id),
    INDEX idx_inscricao (inscricao_competicao_id),
    INDEX idx_atleta (atleta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- HISTÓRICO DE ATLETAS
-- ============================================================================

CREATE TABLE IF NOT EXISTS historico_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    atleta_id INT NOT NULL,
    evento_tipo ENUM('Cadastro', 'Mudanca_Equipe', 'Inscricao_Competicao', 'Aprovacao', 'Reprovacao', 'Desligamento', 'Reativacao', 'Atualizacao') NOT NULL,
    descricao TEXT NOT NULL,

    -- Referências (opcionais)
    competicao_id INT,
    equipe_id INT,
    inscricao_id INT,

    -- Dados Adicionais
    dados_anteriores JSON,
    dados_novos JSON,

    -- Controle
    usuario_responsavel INT,
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (atleta_id) REFERENCES atletas(id) ON DELETE CASCADE,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE SET NULL,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE SET NULL,

    INDEX idx_atleta (atleta_id),
    INDEX idx_data (data_evento),
    INDEX idx_tipo (evento_tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- HISTÓRICO DE EQUIPES
-- ============================================================================

CREATE TABLE IF NOT EXISTS historico_equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    equipe_id INT NOT NULL,
    evento_tipo ENUM('Cadastro', 'Aprovacao', 'Rejeicao', 'Inscricao_Competicao', 'Adicao_Atleta', 'Remocao_Atleta', 'Atualizacao', 'Suspensao') NOT NULL,
    descricao TEXT NOT NULL,

    -- Referências (opcionais)
    competicao_id INT,
    atleta_id INT,

    -- Dados Adicionais
    dados_adicionais JSON,

    -- Controle
    usuario_responsavel INT,
    data_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE SET NULL,

    INDEX idx_equipe (equipe_id),
    INDEX idx_data (data_evento),
    INDEX idx_tipo (evento_tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- USUÁRIOS ADMINISTRATIVOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    nivel ENUM('Super Admin', 'Admin', 'Operador') DEFAULT 'Operador',
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- LOGS DO SISTEMA
-- ============================================================================

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100),
    descricao TEXT,
    tabela_afetada VARCHAR(50),
    registro_id INT,
    ip VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (created_at),
    INDEX idx_tabela (tabela_afetada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

-- Modalidades
INSERT INTO modalidades (nome, descricao, icone) VALUES
('Futebol', 'Futebol de Campo', 'fa-futbol'),
('Futsal', 'Futebol de Salão', 'fa-futbol'),
('Basquete', 'Basquetebol', 'fa-basketball-ball'),
('Vôlei', 'Voleibol', 'fa-volleyball-ball'),
('Handebol', 'Handebol', 'fa-handball'),
('Atletismo', 'Atletismo - Diversas Provas', 'fa-running'),
('Natação', 'Natação', 'fa-swimmer'),
('Judô', 'Judô', 'fa-fist-raised'),
('Karatê', 'Karatê', 'fa-fist-raised'),
('Taekwondo', 'Taekwondo', 'fa-fist-raised');

-- Categorias
INSERT INTO categorias (nome, idade_minima, idade_maxima, descricao) VALUES
('Sub-9', 6, 9, 'Categoria Sub-9'),
('Sub-11', 9, 11, 'Categoria Sub-11'),
('Sub-13', 11, 13, 'Categoria Sub-13'),
('Sub-15', 13, 15, 'Categoria Sub-15'),
('Sub-17', 15, 17, 'Categoria Sub-17'),
('Sub-20', 17, 20, 'Categoria Sub-20'),
('Adulto', 18, 99, 'Categoria Adulta'),
('Master', 35, 99, 'Categoria Master (+35 anos)');

-- Usuário Admin Padrão (senha: admin123)
INSERT INTO usuarios (username, password, nome, email, nivel) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador do Sistema', 'admin@sistema.com', 'Super Admin');

-- ============================================================================
-- COMPETIÇÃO DE EXEMPLO
-- ============================================================================

INSERT INTO competicoes (
    nome, descricao, modalidade_id,
    data_inicio_inscricoes, data_fim_inscricoes,
    data_inicio_evento, data_fim_evento,
    categorias_permitidas, genero_permitido,
    min_atletas, max_atletas,
    taxa_inscricao, local_evento, cidade, estado,
    status, regulamento
) VALUES (
    'Copa Roraima de Futsal 2025',
    'Primeira edição da Copa Roraima de Futsal, reunindo as melhores equipes do estado.',
    2, -- Futsal
    '2025-01-01', '2025-01-31',
    '2025-02-15', '2025-02-20',
    '[5, 6, 7]', -- Sub-15, Sub-17, Adulto
    'Masculino',
    8, 15,
    500.00,
    'Ginásio Poliesportivo de Boa Vista',
    'Boa Vista', 'RR',
    'Aberta',
    'Regulamento completo disponível no site oficial.'
);

-- ============================================================================
-- FIM DO SCHEMA
-- ============================================================================
