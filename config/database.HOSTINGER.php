<?php
/**
 * Configuração do Banco de Dados - HOSTINGER
 *
 * INSTRUÇÕES:
 * 1. Vá no painel Hostinger > Databases
 * 2. Pegue suas credenciais do banco de dados
 * 3. Substitua os valores abaixo
 * 4. Renomeie este arquivo para "database.php"
 */

// ============================================
// SUBSTITUA COM SUAS CREDENCIAIS DO HOSTINGER
// ============================================

define('DB_HOST', 'localhost');                    // Geralmente é "localhost"
define('DB_USER', 'u320952164_XXXXX');            // Ex: u320952164_admin
define('DB_PASS', 'COLOQUE_SUA_SENHA_AQUI');      // Senha do banco de dados
define('DB_NAME', 'u320952164_inscricao');        // Ex: u320952164_inscricao
define('DB_CHARSET', 'utf8mb4');

// ============================================
// NÃO ALTERE DAQUI PRA BAIXO
// ============================================

/**
 * Criar conexão com o banco de dados
 */
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
        // Log do erro
        error_log("Erro de conexão com banco: " . $e->getMessage());

        // Mensagem amigável em produção
        if (ini_get('display_errors')) {
            throw new Exception("Falha ao conectar ao banco de dados. Verifique as credenciais e se o servidor está ativo.");
        } else {
            throw new Exception("Erro ao conectar ao banco de dados. Contate o suporte.");
        }
    }
}

/**
 * Testar conexão (útil para debug)
 */
function testDBConnection() {
    try {
        $pdo = getDBConnection();
        return [
            'success' => true,
            'message' => 'Conexão OK!',
            'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
