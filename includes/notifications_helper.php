<?php
/**
 * Notifications Helper
 *
 * Sistema multi-canal de notificações (Email, SMS, WhatsApp, Push)
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/integrations_helper.php';

// =====================================================
// ENVIO DE NOTIFICAÇÕES
// =====================================================

/**
 * Envia notificação usando template
 *
 * @param int $organizacao_id
 * @param string $template_codigo
 * @param array $destinatario ['tipo' => 'email/telefone', 'valor' => '...']
 * @param array $variaveis Variáveis para substituir no template
 * @param array $canais ['email', 'sms', 'whatsapp']
 * @param string $prioridade
 * @param datetime $agendado_para
 * @return array
 */
function enviarNotificacao($organizacao_id, $template_codigo, $destinatario, $variaveis = [], $canais = ['email'], $prioridade = 'normal', $agendado_para = null) {
    $pdo = getDBConnection();

    try {
        // Buscar template
        $stmt = $pdo->prepare("
            SELECT * FROM notification_templates
            WHERE (organizacao_id = ? OR organizacao_id IS NULL)
              AND codigo = ?
              AND ativo = TRUE
            ORDER BY organizacao_id DESC
            LIMIT 1
        ");
        $stmt->execute([$organizacao_id, $template_codigo]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            return ['success' => false, 'message' => 'Template não encontrado'];
        }

        $canais_template = json_decode($template['canais'], true);
        $canais_enviar = array_intersect($canais, $canais_template);

        if (empty($canais_enviar)) {
            return ['success' => false, 'message' => 'Nenhum canal válido para este template'];
        }

        $resultados = [];

        foreach ($canais_enviar as $canal) {
            $resultado = enviarNotificacaoCanal(
                $organizacao_id,
                $canal,
                $destinatario,
                $template,
                $variaveis,
                $prioridade,
                $agendado_para
            );

            $resultados[$canal] = $resultado;
        }

        return [
            'success' => true,
            'resultados' => $resultados,
            'total_enviados' => count(array_filter($resultados, fn($r) => $r['success']))
        ];

    } catch (Exception $e) {
        error_log("Erro ao enviar notificação: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao enviar notificação'];
    }
}

/**
 * Envia notificação em um canal específico
 */
function enviarNotificacaoCanal($organizacao_id, $canal, $destinatario, $template, $variaveis, $prioridade, $agendado_para) {
    $pdo = getDBConnection();

    // Processar template com variáveis
    $conteudo = processarTemplate($template, $canal, $variaveis);

    if (!$conteudo) {
        return ['success' => false, 'message' => "Template não configurado para canal {$canal}"];
    }

    // Resolver destinatário
    $dest_info = resolverDestinatario($destinatario, $canal);

    if (!$dest_info['success']) {
        return $dest_info;
    }

    // Inserir na fila
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notification_queue (
                organizacao_id, template_id, destinatario_tipo, destinatario_id,
                destinatario_email, destinatario_telefone, canal,
                assunto, corpo, prioridade, agendado_para
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $organizacao_id,
            $template['id'],
            $dest_info['tipo'],
            $dest_info['id'],
            $dest_info['email'],
            $dest_info['telefone'],
            $canal,
            $conteudo['assunto'] ?? null,
            $conteudo['corpo'],
            $prioridade,
            $agendado_para
        ]);

        $notification_id = $pdo->lastInsertId();

        // Se não agendado, processar imediatamente
        if (!$agendado_para) {
            return processarNotificacao($notification_id);
        }

        return [
            'success' => true,
            'notification_id' => $notification_id,
            'status' => 'agendado'
        ];

    } catch (Exception $e) {
        error_log("Erro ao adicionar à fila: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao adicionar à fila'];
    }
}

/**
 * Processa template substituindo variáveis
 */
function processarTemplate($template, $canal, $variaveis) {
    $campo_assunto = null;
    $campo_corpo = null;

    switch ($canal) {
        case 'email':
            $campo_assunto = 'email_subject';
            $campo_corpo = 'email_body';
            break;
        case 'sms':
            $campo_corpo = 'sms_body';
            break;
        case 'whatsapp':
            $campo_corpo = 'whatsapp_body';
            break;
        case 'push':
            $campo_assunto = 'push_title';
            $campo_corpo = 'push_body';
            break;
    }

    if (!$campo_corpo || !$template[$campo_corpo]) {
        return null;
    }

    $assunto = $campo_assunto && $template[$campo_assunto] ?
        substituirVariaveis($template[$campo_assunto], $variaveis) : null;

    $corpo = substituirVariaveis($template[$campo_corpo], $variaveis);

    return [
        'assunto' => $assunto,
        'corpo' => $corpo
    ];
}

/**
 * Substitui variáveis no texto
 */
function substituirVariaveis($texto, $variaveis) {
    foreach ($variaveis as $chave => $valor) {
        $texto = str_replace('{{' . $chave . '}}', $valor, $texto);
    }
    return $texto;
}

/**
 * Resolve informações do destinatário
 */
function resolverDestinatario($destinatario, $canal) {
    $pdo = getDBConnection();

    $result = [
        'success' => true,
        'tipo' => $destinatario['tipo'] ?? 'email',
        'id' => null,
        'email' => null,
        'telefone' => null
    ];

    if ($destinatario['tipo'] === 'usuario') {
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE id = ?");
        $stmt->execute([$destinatario['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }

        $result['id'] = $usuario['id'];
        $result['email'] = $usuario['email'];

    } elseif ($destinatario['tipo'] === 'atleta') {
        $stmt = $pdo->prepare("SELECT id, email, telefone FROM atletas WHERE id = ?");
        $stmt->execute([$destinatario['id']]);
        $atleta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atleta) {
            return ['success' => false, 'message' => 'Atleta não encontrado'];
        }

        $result['id'] = $atleta['id'];
        $result['email'] = $atleta['email'];
        $result['telefone'] = $atleta['telefone'];

    } elseif ($destinatario['tipo'] === 'email') {
        $result['email'] = $destinatario['valor'];

    } elseif ($destinatario['tipo'] === 'telefone') {
        $result['telefone'] = $destinatario['valor'];
    }

    // Validar se tem o contato necessário para o canal
    if ($canal === 'email' && !$result['email']) {
        return ['success' => false, 'message' => 'Email não disponível'];
    }

    if (in_array($canal, ['sms', 'whatsapp']) && !$result['telefone']) {
        return ['success' => false, 'message' => 'Telefone não disponível'];
    }

    return $result;
}

// =====================================================
// PROCESSAMENTO DA FILA
// =====================================================

/**
 * Processa notificação individual
 */
function processarNotificacao($notification_id) {
    $pdo = getDBConnection();

    try {
        // Buscar notificação
        $stmt = $pdo->prepare("SELECT * FROM notification_queue WHERE id = ?");
        $stmt->execute([$notification_id]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$notif || $notif['status'] !== 'pendente') {
            return ['success' => false, 'message' => 'Notificação não disponível'];
        }

        // Marcar como processando
        $pdo->prepare("UPDATE notification_queue SET status = 'processando' WHERE id = ?")
            ->execute([$notification_id]);

        // Enviar conforme canal
        switch ($notif['canal']) {
            case 'email':
                $resultado = enviarEmail($notif);
                break;
            case 'sms':
                $resultado = enviarSMS($notif);
                break;
            case 'whatsapp':
                $resultado = enviarWhatsApp($notif);
                break;
            case 'push':
                $resultado = enviarPushNotification($notif);
                break;
            default:
                $resultado = ['success' => false, 'message' => 'Canal não suportado'];
        }

        // Atualizar status
        if ($resultado['success']) {
            $stmt = $pdo->prepare("
                UPDATE notification_queue
                SET status = 'enviado',
                    enviado_em = NOW(),
                    provider_response = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($resultado['response'] ?? []), $notification_id]);
        } else {
            $tentativas = $notif['tentativas'] + 1;
            $status = $tentativas >= $notif['max_tentativas'] ? 'erro' : 'pendente';

            $stmt = $pdo->prepare("
                UPDATE notification_queue
                SET status = ?,
                    tentativas = ?,
                    erro_mensagem = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $tentativas, $resultado['message'], $notification_id]);
        }

        return $resultado;

    } catch (Exception $e) {
        error_log("Erro ao processar notificação: " . $e->getMessage());

        $pdo->prepare("
            UPDATE notification_queue
            SET status = 'erro',
                erro_mensagem = ?
            WHERE id = ?
        ")->execute([$e->getMessage(), $notification_id]);

        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Processa fila de notificações pendentes
 */
function processarFilaNotificacoes($limit = 50) {
    $pdo = getDBConnection();

    // Buscar notificações pendentes
    $stmt = $pdo->prepare("
        SELECT id FROM notification_queue
        WHERE status = 'pendente'
          AND (agendado_para IS NULL OR agendado_para <= NOW())
          AND tentativas < max_tentativas
        ORDER BY prioridade DESC, created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $resultados = [
        'processadas' => 0,
        'sucesso' => 0,
        'erro' => 0
    ];

    foreach ($notificacoes as $notif_id) {
        $resultado = processarNotificacao($notif_id);
        $resultados['processadas']++;

        if ($resultado['success']) {
            $resultados['sucesso']++;
        } else {
            $resultados['erro']++;
        }
    }

    return $resultados;
}

// =====================================================
// CANAIS DE ENVIO
// =====================================================

/**
 * Envia email
 */
function enviarEmail($notificacao) {
    // Implementar com PHPMailer ou serviço SMTP
    // Placeholder

    $to = $notificacao['destinatario_email'];
    $subject = $notificacao['assunto'];
    $message = $notificacao['corpo'];
    $headers = "From: noreply@sistema.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $enviado = mail($to, $subject, nl2br($message), $headers);

    return [
        'success' => $enviado,
        'message' => $enviado ? 'Email enviado' : 'Falha ao enviar email'
    ];
}

/**
 * Envia SMS via Twilio
 */
function enviarSMS($notificacao) {
    $organizacao_id = $notificacao['organizacao_id'];

    // Buscar configuração Twilio
    $config = getIntegrationConfig($organizacao_id, 'twilio_sms');

    if (!$config) {
        return ['success' => false, 'message' => 'Twilio não configurado'];
    }

    $credentials = json_decode($config['credentials'], true);

    // Chamar API Twilio
    $response = apiCall($config['id'], "/Accounts/{$credentials['account_sid']}/Messages.json", 'POST', [
        'To' => $notificacao['destinatario_telefone'],
        'From' => $credentials['phone_number'],
        'Body' => $notificacao['corpo']
    ]);

    return $response;
}

/**
 * Envia mensagem WhatsApp
 */
function enviarWhatsApp($notificacao) {
    $organizacao_id = $notificacao['organizacao_id'];
    $pdo = getDBConnection();

    // Buscar configuração WhatsApp Business
    $config = getIntegrationConfig($organizacao_id, 'whatsapp_business');

    if (!$config) {
        return ['success' => false, 'message' => 'WhatsApp Business não configurado'];
    }

    $credentials = json_decode($config['credentials'], true);
    $phone_number_id = $credentials['phone_number_id'];

    // Preparar payload
    $telefone = preg_replace('/[^0-9]/', '', $notificacao['destinatario_telefone']);

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $telefone,
        'type' => 'text',
        'text' => [
            'body' => $notificacao['corpo']
        ]
    ];

    // Chamar API WhatsApp
    $response = apiCall($config['id'], "/{$phone_number_id}/messages", 'POST', $payload);

    // Se sucesso, registrar em whatsapp_messages
    if ($response['success']) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO whatsapp_messages (
                    organizacao_id, integration_config_id, telefone,
                    tipo_mensagem, conteudo, whatsapp_message_id, status
                ) VALUES (?, ?, ?, 'text', ?, ?, 'enviado')
            ");
            $stmt->execute([
                $organizacao_id,
                $config['id'],
                $telefone,
                $notificacao['corpo'],
                $response['data']['messages'][0]['id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Erro ao registrar WhatsApp message: " . $e->getMessage());
        }
    }

    return $response;
}

/**
 * Envia push notification
 */
function enviarPushNotification($notificacao) {
    // Implementar com Firebase Cloud Messaging ou similar
    // Placeholder

    return [
        'success' => false,
        'message' => 'Push notifications não implementado ainda'
    ];
}

// =====================================================
// WEBHOOKS
// =====================================================

/**
 * Dispara webhook para evento
 */
function dispararWebhook($organizacao_id, $evento, $payload) {
    $pdo = getDBConnection();

    try {
        // Buscar webhooks para este evento
        $stmt = $pdo->prepare("
            SELECT * FROM webhooks
            WHERE organizacao_id = ?
              AND ativo = TRUE
              AND JSON_CONTAINS(eventos, ?)
        ");
        $stmt->execute([$organizacao_id, json_encode($evento)]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultados = [];

        foreach ($webhooks as $webhook) {
            $resultado = executarWebhook($webhook, $evento, $payload);
            $resultados[] = $resultado;
        }

        return [
            'success' => true,
            'total_disparado' => count($webhooks),
            'resultados' => $resultados
        ];

    } catch (Exception $e) {
        error_log("Erro ao disparar webhook: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Executa webhook individual
 */
function executarWebhook($webhook, $evento, $payload, $attempt = 1) {
    $pdo = getDBConnection();

    // Preparar headers
    $headers = ['Content-Type' => 'application/json'];

    // Adicionar autenticação
    if ($webhook['auth_type'] !== 'none') {
        $auth_creds = json_decode($webhook['auth_credentials'], true);

        switch ($webhook['auth_type']) {
            case 'bearer_token':
                $headers['Authorization'] = 'Bearer ' . $auth_creds['token'];
                break;
            case 'api_key':
                $headers['X-API-Key'] = $auth_creds['api_key'];
                break;
        }
    }

    // Headers customizados
    if ($webhook['custom_headers']) {
        $custom = json_decode($webhook['custom_headers'], true);
        $headers = array_merge($headers, $custom);
    }

    // Executar request
    $start_time = microtime(true);
    $response = executeHttpRequest($webhook['url'], $webhook['metodo'], $payload, $headers);
    $response_time = round((microtime(true) - $start_time) * 1000);

    $sucesso = $response['success'] && $response['status_code'] >= 200 && $response['status_code'] < 300;

    // Registrar log
    $stmt = $pdo->prepare("
        INSERT INTO webhook_logs (
            webhook_id, evento, payload, request_headers,
            status_code, response_body, response_headers, response_time_ms,
            erro, erro_message, attempt_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $webhook['id'],
        $evento,
        json_encode($payload),
        json_encode($headers),
        $response['status_code'] ?? null,
        json_encode($response['data'] ?? ''),
        json_encode($response['headers'] ?? []),
        $response_time,
        !$sucesso,
        $response['error'] ?? null,
        $attempt
    ]);

    // Atualizar estatísticas
    $campo_incrementar = $sucesso ? 'total_success' : 'total_failed';

    $pdo->prepare("
        UPDATE webhooks
        SET total_dispatched = total_dispatched + 1,
            {$campo_incrementar} = {$campo_incrementar} + 1,
            last_dispatch_at = NOW(),
            last_success_at = IF(?, NOW(), last_success_at),
            last_error_at = IF(?, NOW(), last_error_at)
        WHERE id = ?
    ")->execute([$sucesso, !$sucesso, $webhook['id']]);

    // Retry se necessário
    if (!$sucesso && $webhook['retry_enabled'] && $attempt < $webhook['retry_max_attempts']) {
        sleep($webhook['retry_delay_seconds']);
        return executarWebhook($webhook, $evento, $payload, $attempt + 1);
    }

    return [
        'success' => $sucesso,
        'status_code' => $response['status_code'] ?? null,
        'attempts' => $attempt
    ];
}
