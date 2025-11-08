<?php
/**
 * API RESTful v1 - Main Router
 *
 * Sistema de API RESTful para integração com sistemas externos
 *
 * @version 1.0
 * @date 2025-11-08
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-API-Secret');
header('Content-Type: application/json; charset=utf-8');

// Tratamento de preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/multi_tenancy_helper.php';
require_once __DIR__ . '/../../includes/funcoes_auxiliares.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/rate_limit.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Classe principal do Router da API
 */
class ApiRouter {
    private $routes = [];
    private $organizacao_id = null;

    /**
     * Registra uma rota
     */
    public function register($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Executa o router
     */
    public function run() {
        try {
            // Obter método e path
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            // Remover /api/v1 do path
            $path = preg_replace('#^/api/v1#', '', $path);
            $path = $path ?: '/';

            // Autenticação (exceto para rota de login)
            if ($path !== '/auth/login' && $path !== '/') {
                $auth_result = ApiAuth::authenticate();
                if (!$auth_result['success']) {
                    $this->sendError($auth_result['message'], $auth_result['code']);
                    return;
                }
                $this->organizacao_id = $auth_result['organizacao_id'];
                setCurrentOrganization($this->organizacao_id);

                // Rate limiting
                $rate_limit = RateLimit::check($this->organizacao_id);
                if (!$rate_limit['allowed']) {
                    $this->sendError('Rate limit excedido. Tente novamente em ' . $rate_limit['retry_after'] . ' segundos', 429, [
                        'retry_after' => $rate_limit['retry_after']
                    ]);
                    return;
                }
            }

            // Procurar rota correspondente
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }

                $pattern = $this->pathToRegex($route['path']);
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remover match completo
                    call_user_func_array($route['handler'], $matches);
                    return;
                }
            }

            // Rota não encontrada
            $this->sendError('Endpoint não encontrado', 404);

        } catch (Exception $e) {
            error_log("Erro na API: " . $e->getMessage());
            $this->sendError('Erro interno do servidor', 500);
        }
    }

    /**
     * Converte path para regex
     */
    private function pathToRegex($path) {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Envia resposta de sucesso
     */
    public static function sendSuccess($data = [], $message = 'Sucesso', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Envia resposta de erro
     */
    public static function sendError($message, $code = 400, $details = []) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Obtém body JSON da requisição
     */
    public static function getJsonBody() {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            self::sendError('JSON inválido: ' . json_last_error_msg(), 400);
        }

        return $data ?? [];
    }

    /**
     * Valida campos obrigatórios
     */
    public static function validateRequired($data, $required_fields) {
        $missing = [];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            self::sendError('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
        }
    }
}

// =====================================================
// ROTAS DA API
// =====================================================

$router = new ApiRouter();

// Rota raiz (documentação)
$router->register('GET', '/', function() {
    ApiRouter::sendSuccess([
        'version' => 'v1',
        'documentation' => '/api/v1/docs',
        'endpoints' => [
            'POST /auth/login' => 'Autenticação',
            'GET /competitions' => 'Listar competições',
            'POST /competitions' => 'Criar competição',
            'GET /competitions/{id}' => 'Detalhes da competição',
            'PUT /competitions/{id}' => 'Atualizar competição',
            'DELETE /competitions/{id}' => 'Excluir competição',
            'GET /teams' => 'Listar equipes',
            'POST /teams' => 'Criar equipe',
            'GET /teams/{id}' => 'Detalhes da equipe',
            'PUT /teams/{id}' => 'Atualizar equipe',
            'GET /athletes' => 'Listar atletas',
            'POST /athletes' => 'Criar atleta',
            'GET /athletes/{id}' => 'Detalhes do atleta',
            'PUT /athletes/{id}' => 'Atualizar atleta',
            'POST /registrations' => 'Criar inscrição',
            'GET /registrations' => 'Listar inscrições',
            'GET /registrations/{id}' => 'Detalhes da inscrição',
            'PUT /registrations/{id}/status' => 'Atualizar status da inscrição',
            'GET /rankings' => 'Rankings e classificações'
        ]
    ], 'API v1 - Sistema de Gestão de Eventos Esportivos');
});

// Autenticação
require_once __DIR__ . '/routes/auth.php';
registerAuthRoutes($router);

// Competições
require_once __DIR__ . '/routes/competitions.php';
registerCompetitionRoutes($router);

// Equipes
require_once __DIR__ . '/routes/teams.php';
registerTeamRoutes($router);

// Atletas
require_once __DIR__ . '/routes/athletes.php';
registerAthleteRoutes($router);

// Inscrições
require_once __DIR__ . '/routes/registrations.php';
registerRegistrationRoutes($router);

// Rankings
require_once __DIR__ . '/routes/rankings.php';
registerRankingRoutes($router);

// Executar router
$router->run();
