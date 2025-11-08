<?php
/**
 * Multi-Tenancy Helper Functions
 *
 * Funções auxiliares para gerenciar multi-tenancy (múltiplas organizações)
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Obtém a organização atual da sessão
 *
 * @return array|null Array com dados da organização ou null se não encontrada
 */
function getCurrentOrganization() {
    if (!isset($_SESSION['organizacao_id'])) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT o.*, p.nome as plano_nome, p.*
            FROM organizacoes o
            INNER JOIN planos_assinatura p ON o.plano_id = p.id
            WHERE o.id = ? AND o.ativo = TRUE
        ");
        $stmt->execute([$_SESSION['organizacao_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter organização atual: " . $e->getMessage());
        return null;
    }
}

/**
 * Define a organização atual na sessão
 *
 * @param int $organizacao_id ID da organização
 * @return bool Sucesso
 */
function setCurrentOrganization($organizacao_id) {
    $_SESSION['organizacao_id'] = $organizacao_id;

    // Atualizar cache de organização
    $_SESSION['organizacao_data'] = getCurrentOrganization();

    return true;
}

/**
 * Obtém o ID da organização atual
 *
 * @return int|null ID da organização ou null
 */
function getCurrentOrganizationId() {
    return $_SESSION['organizacao_id'] ?? null;
}

/**
 * Verifica se a organização tem acesso a uma funcionalidade
 *
 * @param string $feature Nome da funcionalidade (ex: 'permite_api', 'permite_whatsapp')
 * @return bool
 */
function organizationHasFeature($feature) {
    $org = getCurrentOrganization();
    if (!$org) {
        return false;
    }

    // Se a feature existe no plano, retornar o valor
    if (isset($org[$feature])) {
        return (bool)$org[$feature];
    }

    return false;
}

/**
 * Verifica se a organização atingiu o limite de um recurso
 *
 * @param string $resource Nome do recurso (eventos, equipes, atletas, storage, emails)
 * @return array ['atingido' => bool, 'atual' => int, 'limite' => int|null, 'percentual' => float]
 */
function checkOrganizationLimit($resource) {
    $org = getCurrentOrganization();
    if (!$org) {
        return ['atingido' => true, 'atual' => 0, 'limite' => 0, 'percentual' => 100];
    }

    $limites = [
        'eventos' => ['atual' => 'total_eventos', 'max' => 'max_eventos'],
        'equipes' => ['atual' => 'total_equipes', 'max' => 'max_equipes'],
        'atletas' => ['atual' => 'total_atletas', 'max' => 'max_atletas'],
        'storage' => ['atual' => 'storage_usado_gb', 'max' => 'max_storage_gb'],
        'emails' => ['atual' => 'emails_enviados_mes', 'max' => 'max_emails_mes']
    ];

    if (!isset($limites[$resource])) {
        return ['atingido' => false, 'atual' => 0, 'limite' => null, 'percentual' => 0];
    }

    $campo_atual = $limites[$resource]['atual'];
    $campo_max = $limites[$resource]['max'];

    $atual = $org[$campo_atual] ?? 0;
    $limite = $org[$campo_max] ?? null;

    // NULL significa ilimitado
    if ($limite === null) {
        return ['atingido' => false, 'atual' => $atual, 'limite' => null, 'percentual' => 0];
    }

    $atingido = $atual >= $limite;
    $percentual = $limite > 0 ? ($atual / $limite) * 100 : 0;

    return [
        'atingido' => $atingido,
        'atual' => $atual,
        'limite' => $limite,
        'percentual' => round($percentual, 2)
    ];
}

/**
 * Incrementa o contador de uso de um recurso
 *
 * @param string $resource Nome do recurso
 * @param int $quantidade Quantidade a incrementar (padrão: 1)
 * @return bool Sucesso
 */
function incrementOrganizationUsage($resource, $quantidade = 1) {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return false;
    }

    $campos = [
        'eventos' => 'total_eventos',
        'equipes' => 'total_equipes',
        'atletas' => 'total_atletas',
        'storage' => 'storage_usado_gb',
        'emails' => 'emails_enviados_mes'
    ];

    if (!isset($campos[$resource])) {
        return false;
    }

    try {
        $pdo = getDBConnection();
        $campo = $campos[$resource];
        $stmt = $pdo->prepare("UPDATE organizacoes SET $campo = $campo + ? WHERE id = ?");
        $stmt->execute([$quantidade, $org_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao incrementar uso da organização: " . $e->getMessage());
        return false;
    }
}

/**
 * Decrementa o contador de uso de um recurso
 *
 * @param string $resource Nome do recurso
 * @param int $quantidade Quantidade a decrementar (padrão: 1)
 * @return bool Sucesso
 */
function decrementOrganizationUsage($resource, $quantidade = 1) {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return false;
    }

    $campos = [
        'eventos' => 'total_eventos',
        'equipes' => 'total_equipes',
        'atletas' => 'total_atletas',
        'storage' => 'storage_usado_gb',
        'emails' => 'emails_enviados_mes'
    ];

    if (!isset($campos[$resource])) {
        return false;
    }

    try {
        $pdo = getDBConnection();
        $campo = $campos[$resource];
        // Garantir que não fique negativo
        $stmt = $pdo->prepare("UPDATE organizacoes SET $campo = GREATEST(0, $campo - ?) WHERE id = ?");
        $stmt->execute([$quantidade, $org_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao decrementar uso da organização: " . $e->getMessage());
        return false;
    }
}

/**
 * Reseta o contador mensal de emails
 * Deve ser executado mensalmente via cron
 *
 * @return bool Sucesso
 */
function resetMonthlyEmailCounter() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE organizacoes
            SET emails_enviados_mes = 0,
                ultimo_reset_emails = CURDATE()
            WHERE ultimo_reset_emails < CURDATE() - INTERVAL 1 MONTH
               OR ultimo_reset_emails IS NULL
        ");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao resetar contador de emails: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se a assinatura da organização está ativa
 *
 * @return bool
 */
function isOrganizationSubscriptionActive() {
    $org = getCurrentOrganization();
    if (!$org) {
        return false;
    }

    // Verificar status
    if ($org['status_assinatura'] !== 'ativa' && $org['status_assinatura'] !== 'trial') {
        return false;
    }

    // Verificar data de expiração
    if ($org['data_fim_assinatura'] && $org['data_fim_assinatura'] < date('Y-m-d')) {
        return false;
    }

    return true;
}

/**
 * Adiciona filtro de organização a uma query SQL
 *
 * @param string $tabela Nome da tabela
 * @param string $alias Alias da tabela (opcional)
 * @return string Cláusula WHERE para adicionar à query
 */
function getOrganizationFilter($tabela = null, $alias = null) {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return "1=0"; // Bloquear tudo se não houver organização
    }

    $prefix = $alias ? "$alias." : ($tabela ? "$tabela." : "");
    return "{$prefix}organizacao_id = $org_id";
}

/**
 * Valida se um registro pertence à organização atual
 *
 * @param string $tabela Nome da tabela
 * @param int $registro_id ID do registro
 * @param string $campo_id Nome do campo ID (padrão: 'id')
 * @return bool
 */
function validateOrganizationOwnership($tabela, $registro_id, $campo_id = 'id') {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return false;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM $tabela
            WHERE $campo_id = ? AND organizacao_id = ?
        ");
        $stmt->execute([$registro_id, $org_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao validar propriedade da organização: " . $e->getMessage());
        return false;
    }
}

/**
 * Gera uma chave de API única para a organização
 *
 * @return string API Key
 */
function generateApiKey() {
    return bin2hex(random_bytes(32));
}

/**
 * Gera um secret de API
 *
 * @return array ['secret' => string, 'hash' => string]
 */
function generateApiSecret() {
    $secret = bin2hex(random_bytes(64));
    $hash = password_hash($secret, PASSWORD_DEFAULT);
    return ['secret' => $secret, 'hash' => $hash];
}

/**
 * Atualiza as credenciais de API da organização
 *
 * @return array|false Array com ['api_key' => string, 'api_secret' => string] ou false em erro
 */
function regenerateOrganizationApiCredentials() {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return false;
    }

    $api_key = generateApiKey();
    $secret_data = generateApiSecret();

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE organizacoes
            SET api_key = ?,
                api_secret = ?,
                api_ativa = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$api_key, $secret_data['hash'], $org_id]);

        return [
            'api_key' => $api_key,
            'api_secret' => $secret_data['secret']
        ];
    } catch (PDOException $e) {
        error_log("Erro ao regenerar credenciais de API: " . $e->getMessage());
        return false;
    }
}

/**
 * Valida credenciais de API
 *
 * @param string $api_key API Key
 * @param string $api_secret API Secret
 * @return int|false ID da organização ou false se inválido
 */
function validateApiCredentials($api_key, $api_secret) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, api_secret
            FROM organizacoes
            WHERE api_key = ?
              AND api_ativa = TRUE
              AND ativo = TRUE
              AND status_assinatura IN ('ativa', 'trial')
        ");
        $stmt->execute([$api_key]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            return false;
        }

        if (!password_verify($api_secret, $org['api_secret'])) {
            return false;
        }

        return $org['id'];
    } catch (PDOException $e) {
        error_log("Erro ao validar credenciais de API: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra uso da API
 *
 * @param int $organizacao_id ID da organização
 * @param string $endpoint Endpoint acessado
 * @param string $metodo Método HTTP
 * @param string $ip IP do cliente
 * @return bool
 */
function logApiUsage($organizacao_id, $endpoint, $metodo, $ip) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema (
                organizacao_id, tipo, descricao, ip_address, user_agent
            ) VALUES (?, 'api_request', ?, ?, ?)
        ");
        $stmt->execute([
            $organizacao_id,
            "$metodo $endpoint",
            $ip,
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao registrar uso da API: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém informações do plano da organização
 *
 * @return array|null Dados do plano
 */
function getOrganizationPlan() {
    $org = getCurrentOrganization();
    if (!$org) {
        return null;
    }

    return [
        'nome' => $org['plano_nome'] ?? 'Desconhecido',
        'max_eventos' => $org['max_eventos'],
        'max_equipes' => $org['max_equipes'],
        'max_atletas' => $org['max_atletas'],
        'max_storage_gb' => $org['max_storage_gb'],
        'max_emails_mes' => $org['max_emails_mes'],
        'permite_api' => (bool)$org['permite_api'],
        'permite_whatsapp' => (bool)$org['permite_whatsapp'],
        'permite_custom_domain' => (bool)$org['permite_custom_domain'],
        'permite_relatorios_avancados' => (bool)$org['permite_relatorios_avancados']
    ];
}

/**
 * Exibe alerta visual se um limite está sendo atingido
 *
 * @param string $resource Nome do recurso
 * @param int $threshold Percentual de aviso (padrão: 80%)
 * @return string HTML do alerta ou string vazia
 */
function showLimitAlert($resource, $threshold = 80) {
    $limit = checkOrganizationLimit($resource);

    if ($limit['limite'] === null) {
        return ''; // Ilimitado
    }

    if ($limit['percentual'] < $threshold) {
        return ''; // Abaixo do threshold
    }

    $nivel = $limit['atingido'] ? 'danger' : 'warning';
    $mensagem = $limit['atingido']
        ? "Limite de $resource atingido! ({$limit['atual']}/{$limit['limite']})"
        : "Atenção: {$limit['percentual']}% do limite de $resource utilizado ({$limit['atual']}/{$limit['limite']})";

    return "<div class='alert alert-$nivel'>$mensagem</div>";
}
