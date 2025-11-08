<?php
/**
 * Payment Helper Functions
 *
 * Funções auxiliares para processamento de pagamentos
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/multi_tenancy_helper.php';

// Chave de criptografia (DEVE SER ALTERADA EM PRODUÇÃO e armazenada em .env)
define('PAYMENT_ENCRYPTION_KEY', 'CHANGE_THIS_IN_PRODUCTION_USE_ENV_FILE');

/**
 * Criptografa dados sensíveis
 */
function encryptPaymentData($data) {
    $key = PAYMENT_ENCRYPTION_KEY;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Descriptografa dados sensíveis
 */
function decryptPaymentData($data) {
    $key = PAYMENT_ENCRYPTION_KEY;
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Obtém gateway padrão da organização
 */
function getDefaultPaymentGateway() {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return null;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM payment_gateways
            WHERE organizacao_id = ? AND ativo = TRUE AND padrao = TRUE
            LIMIT 1
        ");
        $stmt->execute([$org_id]);
        $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($gateway && $gateway['secret_key']) {
            // Descriptografar credenciais
            $gateway['secret_key_decrypted'] = decryptPaymentData($gateway['secret_key']);
            if ($gateway['access_token']) {
                $gateway['access_token_decrypted'] = decryptPaymentData($gateway['access_token']);
            }
        }

        return $gateway;
    } catch (PDOException $e) {
        error_log("Erro ao obter gateway: " . $e->getMessage());
        return null;
    }
}

/**
 * Cria uma nova transação de pagamento
 */
function createPaymentTransaction($dados) {
    $org_id = getCurrentOrganizationId();
    if (!$org_id) {
        return ['success' => false, 'message' => 'Organização não identificada'];
    }

    $gateway = getDefaultPaymentGateway();
    if (!$gateway) {
        return ['success' => false, 'message' => 'Gateway de pagamento não configurado'];
    }

    try {
        $pdo = getDBConnection();

        // Gerar ID externo único
        $external_id = 'PAY' . $org_id . time() . rand(1000, 9999);

        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                organizacao_id, gateway_id, tipo_referencia, referencia_id,
                external_id, metodo_pagamento, status,
                valor_original, valor_taxa, valor_liquido,
                pagador_nome, pagador_email, pagador_cpf_cnpj, pagador_telefone,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $valor_taxa = calcularTaxaGateway($dados['valor'], $dados['metodo_pagamento'], $gateway);
        $valor_liquido = $dados['valor'] - $valor_taxa;

        $stmt->execute([
            $org_id,
            $gateway['id'],
            $dados['tipo_referencia'],
            $dados['referencia_id'],
            $external_id,
            $dados['metodo_pagamento'],
            $dados['valor'],
            $valor_taxa,
            $valor_liquido,
            $dados['pagador_nome'] ?? null,
            $dados['pagador_email'] ?? null,
            $dados['pagador_cpf_cnpj'] ?? null,
            $dados['pagador_telefone'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        $transaction_id = $pdo->lastInsertId();

        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'external_id' => $external_id
        ];

    } catch (PDOException $e) {
        error_log("Erro ao criar transação: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao criar transação'];
    }
}

/**
 * Calcula taxa do gateway
 */
function calcularTaxaGateway($valor, $metodo, $gateway) {
    $taxa_percentual = 0;

    switch ($metodo) {
        case 'pix':
            $taxa_percentual = $gateway['taxa_pix'] ?? 0;
            break;
        case 'cartao_credito':
        case 'cartao_debito':
            $taxa_percentual = $gateway['taxa_cartao_credito'] ?? 0;
            break;
        case 'boleto':
            $taxa_percentual = $gateway['taxa_boleto'] ?? 0;
            break;
    }

    return ($valor * $taxa_percentual) / 100;
}

/**
 * Processa pagamento PIX
 */
function processarPagamentoPix($transaction_id, $valor, $descricao) {
    $gateway = getDefaultPaymentGateway();

    switch ($gateway['gateway']) {
        case 'mercadopago':
            return processarPixMercadoPago($transaction_id, $valor, $descricao, $gateway);
        case 'asaas':
            return processarPixAsaas($transaction_id, $valor, $descricao, $gateway);
        default:
            return ['success' => false, 'message' => 'Gateway não suportado'];
    }
}

/**
 * Processa PIX via Mercado Pago
 */
function processarPixMercadoPago($transaction_id, $valor, $descricao, $gateway) {
    $access_token = $gateway['access_token_decrypted'] ?? null;

    if (!$access_token) {
        return ['success' => false, 'message' => 'Access token não configurado'];
    }

    $url = $gateway['ambiente'] === 'production'
        ? 'https://api.mercadopago.com/v1/payments'
        : 'https://api.mercadopago.com/v1/payments';

    $data = [
        'transaction_amount' => floatval($valor),
        'description' => $descricao,
        'payment_method_id' => 'pix',
        'payer' => [
            'email' => 'test@test.com' // Será substituído pelos dados reais
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $result = json_decode($response, true);

        // Atualizar transação com dados do PIX
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE payment_transactions
            SET transaction_id = ?,
                pix_qr_code = ?,
                pix_qr_code_base64 = ?,
                pix_copia_cola = ?,
                status = 'processing'
            WHERE id = ?
        ");
        $stmt->execute([
            $result['id'],
            $result['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            $result['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            $result['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            $transaction_id
        ]);

        return [
            'success' => true,
            'qr_code' => $result['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'qr_code_base64' => $result['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null
        ];
    }

    return ['success' => false, 'message' => 'Erro ao processar PIX', 'response' => $response];
}

/**
 * Processa PIX via Asaas
 */
function processarPixAsaas($transaction_id, $valor, $descricao, $gateway) {
    // TODO: Implementar integração com Asaas
    return ['success' => false, 'message' => 'Asaas PIX em desenvolvimento'];
}

/**
 * Processa pagamento com cartão
 */
function processarPagamentoCartao($transaction_id, $dados_cartao) {
    // TODO: Implementar processamento de cartão
    return ['success' => false, 'message' => 'Pagamento com cartão em desenvolvimento'];
}

/**
 * Webhook handler - processa notificações dos gateways
 */
function processPaymentWebhook($gateway_name, $payload) {
    try {
        $pdo = getDBConnection();

        // Registrar webhook
        $stmt = $pdo->prepare("
            INSERT INTO payment_webhooks_log (gateway_id, evento, payload, headers, ip_address)
            VALUES (NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $gateway_name,
            json_encode($payload),
            json_encode(getallheaders()),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $log_id = $pdo->lastInsertId();

        // Processar conforme o gateway
        switch ($gateway_name) {
            case 'mercadopago':
                return processWebhookMercadoPago($payload, $log_id);
            case 'asaas':
                return processWebhookAsaas($payload, $log_id);
            default:
                return ['success' => false, 'message' => 'Gateway desconhecido'];
        }

    } catch (Exception $e) {
        error_log("Erro ao processar webhook: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao processar webhook'];
    }
}

/**
 * Processa webhook do Mercado Pago
 */
function processWebhookMercadoPago($payload, $log_id) {
    if (!isset($payload['data']['id'])) {
        return ['success' => false, 'message' => 'Payload inválido'];
    }

    $payment_id = $payload['data']['id'];

    // Buscar detalhes do pagamento
    $gateway = getDefaultPaymentGateway();
    $access_token = $gateway['access_token_decrypted'] ?? null;

    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$payment_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $payment = json_decode($response, true);

    if (!$payment) {
        return ['success' => false, 'message' => 'Erro ao buscar pagamento'];
    }

    // Atualizar transação
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE payment_transactions
        SET status = ?,
            data_aprovacao = ?,
            webhook_payload = ?,
            webhook_recebido_em = NOW()
        WHERE transaction_id = ?
    ");

    $status = mapMercadoPagoStatus($payment['status']);
    $data_aprovacao = ($status === 'approved') ? date('Y-m-d H:i:s') : null;

    $stmt->execute([
        $status,
        $data_aprovacao,
        json_encode($payment),
        $payment_id
    ]);

    // Marcar webhook como processado
    $pdo->prepare("UPDATE payment_webhooks_log SET processado = TRUE, processado_em = NOW() WHERE id = ?")->execute([$log_id]);

    return ['success' => true];
}

/**
 * Mapeia status do Mercado Pago para o sistema
 */
function mapMercadoPagoStatus($mp_status) {
    $map = [
        'pending' => 'pending',
        'approved' => 'approved',
        'authorized' => 'processing',
        'in_process' => 'processing',
        'in_mediation' => 'processing',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'charged_back' => 'refunded'
    ];

    return $map[$mp_status] ?? 'pending';
}

/**
 * Processa webhook do Asaas
 */
function processWebhookAsaas($payload, $log_id) {
    // TODO: Implementar processamento de webhook Asaas
    return ['success' => false, 'message' => 'Asaas webhook em desenvolvimento'];
}

/**
 * Solicita reembolso
 */
function solicitarReembolso($transaction_id, $valor, $motivo, $usuario_id) {
    try {
        $pdo = getDBConnection();

        // Verificar se transação existe e está aprovada
        $stmt = $pdo->prepare("
            SELECT * FROM payment_transactions
            WHERE id = ? AND status = 'approved'
        ");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            return ['success' => false, 'message' => 'Transação não encontrada ou não está aprovada'];
        }

        // Criar registro de reembolso
        $stmt = $pdo->prepare("
            INSERT INTO payment_refunds (transaction_id, valor, motivo, solicitado_por, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$transaction_id, $valor, $motivo, $usuario_id]);

        return ['success' => true, 'refund_id' => $pdo->lastInsertId()];

    } catch (PDOException $e) {
        error_log("Erro ao solicitar reembolso: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao processar reembolso'];
    }
}
