<?php
/**
 * SETUP DE EMERGÊNCIA - Cria tabelas essenciais AGORA
 * Execute este arquivo UMA VEZ para resolver todos os problemas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Emergencial</title>";
echo "<style>body{font-family:Arial;max-width:900px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;} .error{color:red;} .info{color:blue;} code{background:#eee;padding:2px 6px;}</style></head><body>";

echo "<h1>🚀 Setup de Emergência - Criando Tabelas</h1>\n";
echo "<hr>\n";

try {
    $pdo = getDBConnection();
    echo "<p class='success'>✅ Conexão com banco OK</p>\n";

    // 1. Criar tabela administradores
    echo "<h2>1. Criando tabela administradores</h2>\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✅ Tabela administradores criada</p>\n";

    // 2. Criar tabela modalidades
    echo "<h2>2. Criando tabela modalidades</h2>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS modalidades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            ativo BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nome (nome),
            INDEX idx_ativo (ativo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Inserir modalidades padrão
    $pdo->exec("INSERT IGNORE INTO modalidades (id, nome, descricao) VALUES
        (1, 'Futsal', 'Futebol de salão'),
        (2, 'Vôlei', 'Voleibol'),
        (3, 'Basquete', 'Basquetebol'),
        (4, 'Handebol', 'Handebol'),
        (5, 'Futebol de Campo', 'Futebol tradicional')
    ");
    echo "<p class='success'>✅ Tabela modalidades criada com 5 modalidades</p>\n";

    // 3. Criar tabela categorias
    echo "<h2>3. Criando tabela categorias</h2>\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("INSERT IGNORE INTO categorias (id, nome, descricao, idade_minima, idade_maxima) VALUES
        (1, 'Sub-12', 'Categoria até 12 anos', 0, 12),
        (2, 'Sub-15', 'Categoria até 15 anos', 13, 15),
        (3, 'Sub-18', 'Categoria até 18 anos', 16, 18),
        (4, 'Adulto', 'Categoria acima de 18 anos', 18, 100),
        (5, 'Livre', 'Categoria livre', 0, 100)
    ");
    echo "<p class='success'>✅ Tabela categorias criada com 5 categorias</p>\n";

    // 4. Verificar e criar outras tabelas essenciais
    echo "<h2>4. Verificando outras tabelas</h2>\n";

    $tabelas_essenciais = [
        'equipes' => "
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'competicoes' => "
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
                status ENUM('Aberta', 'Fechada', 'Em Andamento', 'Finalizada', 'Cancelada') DEFAULT 'Aberta',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (modalidade_id) REFERENCES modalidades(id),
                FOREIGN KEY (categoria_id) REFERENCES categorias(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'atletas' => "
            CREATE TABLE IF NOT EXISTS atletas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipe_atual_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                cpf VARCHAR(14) NOT NULL UNIQUE,
                data_nascimento DATE NOT NULL,
                sexo ENUM('M', 'F', 'Outro') NOT NULL,
                ativo BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (equipe_atual_id) REFERENCES equipes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'inscricoes_competicoes' => "
            CREATE TABLE IF NOT EXISTS inscricoes_competicoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                competicao_id INT NOT NULL,
                equipe_id INT NOT NULL,
                protocolo VARCHAR(50) UNIQUE NOT NULL,
                status ENUM('Pendente', 'Confirmada', 'Cancelada') DEFAULT 'Pendente',
                data_inscricao DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (competicao_id) REFERENCES competicoes(id) ON DELETE CASCADE,
                FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'convites_atletas' => "
            CREATE TABLE IF NOT EXISTS convites_atletas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipe_id INT NOT NULL,
                token VARCHAR(64) UNIQUE NOT NULL,
                nome_atleta VARCHAR(255),
                email_atleta VARCHAR(255),
                status ENUM('Pendente', 'Aceito', 'Recusado', 'Expirado') DEFAULT 'Pendente',
                data_expiracao DATETIME NOT NULL,
                data_aceite DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];

    foreach ($tabelas_essenciais as $nome => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>✅ Tabela $nome verificada/criada</p>\n";
        } catch (Exception $e) {
            echo "<p class='error'>⚠️ $nome: " . $e->getMessage() . "</p>\n";
        }
    }

    // 5. Criar admin padrão se não existir
    echo "<h2>5. Criando administrador padrão</h2>\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM administradores");
    $total = $stmt->fetchColumn();

    if ($total == 0) {
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO administradores (nome, email, senha, nivel, ativo)
            VALUES ('Administrador', 'admin@admin.com', '$senha', 'Master', 1)
        ");
        echo "<p class='success'>✅ Administrador criado!</p>\n";
        echo "<div style='background:#d4edda;padding:15px;border:2px solid #28a745;border-radius:5px;margin:20px 0;'>\n";
        echo "<h3>📧 Credenciais de Acesso:</h3>\n";
        echo "<p><strong>Email:</strong> admin@admin.com</p>\n";
        echo "<p><strong>Senha:</strong> admin123</p>\n";
        echo "<p style='color:red;'><strong>⚠️ ALTERE A SENHA APÓS O PRIMEIRO LOGIN!</strong></p>\n";
        echo "</div>\n";
    } else {
        echo "<p class='info'>ℹ️ Já existem $total administrador(es) cadastrado(s)</p>\n";
    }

    // 6. Resumo
    echo "<hr>\n";
    echo "<h2>✅ Setup Concluído!</h2>\n";
    echo "<h3>Próximos passos:</h3>\n";
    echo "<ol>\n";
    echo "<li><a href='login.php'>Fazer Login</a> (admin@admin.com / admin123)</li>\n";
    echo "<li><a href='configuracoes.php'>Acessar Configurações</a></li>\n";
    echo "<li><a href='../equipe/convites_atletas.php'>Testar Convites de Atletas</a></li>\n";
    echo "<li>Alterar a senha padrão do admin</li>\n";
    echo "<li><strong>DELETAR este arquivo (setup_emergencial.php) por segurança</strong></li>\n";
    echo "</ol>\n";

    echo "<hr>\n";
    echo "<h3>Tabelas Criadas:</h3>\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>\n";
    foreach ($tabelas as $tabela) {
        echo "<li>✅ $tabela</li>\n";
    }
    echo "</ul>\n";
    echo "<p>Total: <strong>" . count($tabelas) . " tabelas</strong></p>\n";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:20px;border:2px solid #dc3545;'>\n";
    echo "<h2>❌ Erro:</h2>\n";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    echo "<p><strong>Verifique:</strong></p>\n";
    echo "<ul><li>Credenciais do banco em config/database.php</li>\n";
    echo "<li>Se o MySQL está rodando</li></ul>\n";
    echo "</div>\n";
}

echo "</body></html>";
?>
