<?php
/**
 * Exemplo de Configuração do Banco de Dados
 *
 * INSTRUÇÕES:
 * 1. Copie este arquivo para database.php (se ainda não existir)
 * 2. Configure as credenciais corretas da Hostinger
 * 3. NUNCA commite database.php com credenciais reais
 */

// ============================================================================
// CONFIGURAÇÃO PARA DESENVOLVIMENTO LOCAL
// ============================================================================
/*
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inscricao_atletas');
define('DB_CHARSET', 'utf8mb4');
*/

// ============================================================================
// CONFIGURAÇÃO PARA HOSTINGER (PRODUÇÃO)
// ============================================================================
// Descomente e configure com suas credenciais da Hostinger:

define('DB_HOST', 'localhost');  // Geralmente 'localhost' na Hostinger
define('DB_USER', 'u320952164_inscricao');  // Seu usuário do banco (encontre no hPanel)
define('DB_PASS', 'SUA_SENHA_AQUI');  // Senha do banco de dados
define('DB_NAME', 'u320952164_inscricao');  // Nome do banco (encontre no hPanel)
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// FUNÇÃO DE CONEXÃO
// ============================================================================

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de conexão: " . $e->getMessage());
        // Re-throw exception para que o código chamador possa lidar com ela
        throw new Exception("Falha ao conectar ao banco de dados. Verifique as credenciais e se o servidor está ativo.", 0, $e);
    }
}

// ============================================================================
// COMO ENCONTRAR SUAS CREDENCIAIS NA HOSTINGER
// ============================================================================
/*
1. Acesse hpanel.hostinger.com
2. Vá em "Hospedagem" → Selecione seu plano
3. Clique em "Bancos de Dados" (MySQL Databases)
4. Você verá:
   - Nome do banco de dados (DB_NAME)
   - Nome de usuário (DB_USER)
   - Servidor (DB_HOST) - geralmente 'localhost'
5. Se precisar criar novo banco:
   - Clique em "Criar novo banco de dados"
   - Anote usuário e senha criados
6. Substitua os valores acima com suas credenciais reais
*/
?>
