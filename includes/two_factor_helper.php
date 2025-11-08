<?php
/**
 * Two-Factor Authentication Helper
 *
 * Funções para autenticação de dois fatores (TOTP - Time-based One-Time Password)
 * Compatível com Google Authenticator, Microsoft Authenticator, Authy, etc.
 *
 * @version 1.0
 * @date 2025-11-08
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gera um secret aleatório para TOTP
 *
 * @return string Secret em base32
 */
function generateTwoFactorSecret() {
    $secret = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet

    for ($i = 0; $i < 32; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }

    return $secret;
}

/**
 * Gera códigos de recuperação
 *
 * @param int $count Número de códigos (padrão: 10)
 * @return array Array com códigos e seus hashes
 */
function generateRecoveryCodes($count = 10) {
    $codes = [];
    $hashes = [];

    for ($i = 0; $i < $count; $i++) {
        // Gerar código de 8 dígitos
        $code = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        // Formatar como XXXX-XXXX
        $formatted = substr($code, 0, 4) . '-' . substr($code, 4, 4);

        $codes[] = $formatted;
        $hashes[] = password_hash($code, PASSWORD_DEFAULT);
    }

    return [
        'codes' => $codes,
        'hashes' => $hashes
    ];
}

/**
 * Converte base32 para binário
 *
 * @param string $base32 String em base32
 * @return string String binária
 */
function base32Decode($base32) {
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper($base32);
    $paddingCharCount = substr_count($base32, '=');
    $allowedValues = [6, 4, 3, 1, 0];

    if (!in_array($paddingCharCount, $allowedValues)) {
        return false;
    }

    for ($i = 0; $i < 4; $i++) {
        if ($paddingCharCount == $allowedValues[$i] &&
            substr($base32, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
            return false;
        }
    }

    $base32 = str_replace('=', '', $base32);
    $base32chars2 = str_split($base32chars);

    $binary = '';
    foreach (str_split($base32) as $char) {
        $binary .= sprintf('%05b', array_search($char, $base32chars2));
    }

    $binaryArray = str_split($binary, 8);
    $binaryString = '';
    foreach ($binaryArray as $bin) {
        $binaryString .= chr(bindec(str_pad($bin, 8, '0', STR_PAD_RIGHT)));
    }

    return $binaryString;
}

/**
 * Gera código TOTP
 *
 * @param string $secret Secret em base32
 * @param int $timeSlice Fatia de tempo (timestamp / 30)
 * @return string Código de 6 dígitos
 */
function generateTOTP($secret, $timeSlice = null) {
    if ($timeSlice === null) {
        $timeSlice = floor(time() / 30);
    }

    $secretKey = base32Decode($secret);

    // Pack time into binary string
    $time = pack('N*', 0) . pack('N*', $timeSlice);

    // Hash with secret
    $hash = hash_hmac('sha1', $time, $secretKey, true);

    // Use last byte to get offset
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

    // Get 4 bytes from offset
    $truncatedHash = substr($hash, $offset, 4);

    // Unpack binary value
    $value = unpack('N', $truncatedHash)[1];

    // Only 32 bits
    $value = $value & 0x7FFFFFFF;

    // Modulo to get 6 digits
    $modulo = pow(10, 6);

    return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
}

/**
 * Verifica código TOTP
 *
 * @param string $secret Secret em base32
 * @param string $code Código fornecido pelo usuário
 * @param int $discrepancy Janela de tempo (padrão: 1 = ±30 segundos)
 * @return bool Código válido
 */
function verifyTOTP($secret, $code, $discrepancy = 1) {
    $currentTimeSlice = floor(time() / 30);

    // Verificar código atual e adjacentes (para compensar drift de relógio)
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calculatedCode = generateTOTP($secret, $currentTimeSlice + $i);
        if ($calculatedCode === $code) {
            return true;
        }
    }

    return false;
}

/**
 * Gera QR code URL para configurar authenticator
 *
 * @param string $secret Secret em base32
 * @param string $label Label (email ou nome do usuário)
 * @param string $issuer Nome do sistema
 * @return string URL do QR code
 */
function getTwoFactorQRCodeUrl($secret, $label, $issuer = 'Sistema Eventos Esportivos') {
    $otpauthUrl = sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        rawurlencode($issuer),
        rawurlencode($label),
        $secret,
        rawurlencode($issuer)
    );

    // Usar Google Charts API para gerar QR Code
    return sprintf(
        'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=%s',
        urlencode($otpauthUrl)
    );
}

/**
 * Inicia configuração de 2FA para um usuário
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @return array ['secret' => string, 'qr_code_url' => string, 'recovery_codes' => array]
 */
function setupTwoFactor($user_type, $user_id) {
    try {
        $pdo = getDBConnection();

        // Gerar secret
        $secret = generateTwoFactorSecret();

        // Gerar códigos de recuperação
        $recovery = generateRecoveryCodes(10);

        // Obter email/nome do usuário
        if ($user_type === 'admin') {
            $stmt = $pdo->prepare("SELECT nome, email FROM administradores WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT nome, responsavel_email as email FROM equipes WHERE id = ?");
        }
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }

        // Salvar secret temporariamente (será confirmado depois)
        $table = $user_type === 'admin' ? 'administradores' : 'equipes';
        $stmt = $pdo->prepare("
            UPDATE $table
            SET two_factor_secret = ?,
                two_factor_recovery_codes = ?,
                two_factor_enabled = FALSE,
                two_factor_confirmed_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([
            $secret,
            json_encode($recovery['hashes']),
            $user_id
        ]);

        // Log
        logTwoFactorEvent($user_type, $user_id, 'setup_started');

        return [
            'success' => true,
            'secret' => $secret,
            'qr_code_url' => getTwoFactorQRCodeUrl($secret, $user['email']),
            'recovery_codes' => $recovery['codes']
        ];

    } catch (PDOException $e) {
        error_log("Erro ao configurar 2FA: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao configurar 2FA'];
    }
}

/**
 * Confirma configuração de 2FA verificando código
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @param string $code Código TOTP para verificação
 * @return array ['success' => bool, 'message' => string]
 */
function confirmTwoFactor($user_type, $user_id, $code) {
    try {
        $pdo = getDBConnection();

        // Obter secret
        $table = $user_type === 'admin' ? 'administradores' : 'equipes';
        $stmt = $pdo->prepare("SELECT two_factor_secret FROM $table WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['two_factor_secret']) {
            return ['success' => false, 'message' => '2FA não iniciado'];
        }

        // Verificar código
        if (!verifyTOTP($user['two_factor_secret'], $code)) {
            logTwoFactorEvent($user_type, $user_id, 'login_failed', 'totp');
            return ['success' => false, 'message' => 'Código inválido'];
        }

        // Ativar 2FA
        $stmt = $pdo->prepare("
            UPDATE $table
            SET two_factor_enabled = TRUE,
                two_factor_confirmed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);

        // Log
        logTwoFactorEvent($user_type, $user_id, 'setup_completed');

        return ['success' => true, 'message' => '2FA ativado com sucesso'];

    } catch (PDOException $e) {
        error_log("Erro ao confirmar 2FA: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao confirmar 2FA'];
    }
}

/**
 * Desativa 2FA para um usuário
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @return bool Sucesso
 */
function disableTwoFactor($user_type, $user_id) {
    try {
        $pdo = getDBConnection();

        $table = $user_type === 'admin' ? 'administradores' : 'equipes';
        $stmt = $pdo->prepare("
            UPDATE $table
            SET two_factor_enabled = FALSE,
                two_factor_secret = NULL,
                two_factor_recovery_codes = NULL,
                two_factor_confirmed_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);

        // Revogar todos os dispositivos confiáveis
        $pdo->prepare("
            UPDATE trusted_devices
            SET revoked = TRUE, revoked_at = NOW()
            WHERE user_type = ? AND user_id = ?
        ")->execute([$user_type, $user_id]);

        // Log
        logTwoFactorEvent($user_type, $user_id, 'disabled');

        return true;

    } catch (PDOException $e) {
        error_log("Erro ao desativar 2FA: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica código de recuperação
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @param string $code Código de recuperação
 * @return bool Código válido
 */
function verifyRecoveryCode($user_type, $user_id, $code) {
    try {
        $pdo = getDBConnection();

        // Remover formatação
        $code = str_replace('-', '', $code);

        // Obter códigos salvos
        $table = $user_type === 'admin' ? 'administradores' : 'equipes';
        $stmt = $pdo->prepare("SELECT two_factor_recovery_codes FROM $table WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['two_factor_recovery_codes']) {
            return false;
        }

        $recovery_codes = json_decode($user['two_factor_recovery_codes'], true);

        // Verificar cada código hash
        foreach ($recovery_codes as $index => $hash) {
            if (password_verify($code, $hash)) {
                // Remover código usado
                unset($recovery_codes[$index]);

                // Atualizar no banco
                $stmt = $pdo->prepare("
                    UPDATE $table
                    SET two_factor_recovery_codes = ?
                    WHERE id = ?
                ");
                $stmt->execute([json_encode(array_values($recovery_codes)), $user_id]);

                // Log
                logTwoFactorEvent($user_type, $user_id, 'recovery_used', 'recovery_code');

                return true;
            }
        }

        return false;

    } catch (PDOException $e) {
        error_log("Erro ao verificar código de recuperação: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se usuário tem 2FA ativado
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @return bool 2FA ativado
 */
function hasTwoFactorEnabled($user_type, $user_id) {
    try {
        $pdo = getDBConnection();

        $table = $user_type === 'admin' ? 'administradores' : 'equipes';
        $stmt = $pdo->prepare("SELECT two_factor_enabled FROM $table WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['two_factor_enabled'];

    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Cria dispositivo confiável
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $user_id ID do usuário
 * @param string $device_name Nome amigável do dispositivo
 * @param int $duration_days Dias de validade (padrão: 30)
 * @return string Token do dispositivo
 */
function createTrustedDevice($user_type, $user_id, $device_name, $duration_days = 30) {
    try {
        $pdo = getDBConnection();

        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime("+$duration_days days"));

        $stmt = $pdo->prepare("
            INSERT INTO trusted_devices (
                user_type, user_id, device_token, device_name,
                ip_address, user_agent, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_type,
            $user_id,
            $token,
            $device_name,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expires_at
        ]);

        return $token;

    } catch (PDOException $e) {
        error_log("Erro ao criar dispositivo confiável: " . $e->getMessage());
        return null;
    }
}

/**
 * Verifica se dispositivo é confiável
 *
 * @param string $token Token do dispositivo
 * @return array|false Dados do dispositivo ou false
 */
function verifyTrustedDevice($token) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM trusted_devices
            WHERE device_token = ?
              AND revoked = FALSE
              AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device) {
            // Atualizar last_used
            $pdo->prepare("UPDATE trusted_devices SET last_used = NOW() WHERE id = ?")
                ->execute([$device['id']]);
        }

        return $device ?: false;

    } catch (PDOException $e) {
        error_log("Erro ao verificar dispositivo confiável: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra evento de 2FA
 *
 * @param string $user_type Tipo de usuário
 * @param int $user_id ID do usuário
 * @param string $evento Tipo de evento
 * @param string $metodo Método usado (opcional)
 * @param string $observacoes Observações (opcional)
 */
function logTwoFactorEvent($user_type, $user_id, $evento, $metodo = null, $observacoes = null) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            INSERT INTO two_factor_logs (
                user_type, user_id, evento, metodo, ip_address, user_agent, observacoes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_type,
            $user_id,
            $evento,
            $metodo,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $observacoes
        ]);

    } catch (PDOException $e) {
        error_log("Erro ao registrar log 2FA: " . $e->getMessage());
    }
}

/**
 * Obtém configurações de segurança
 *
 * @param int $organizacao_id ID da organização (null = global)
 * @return array Configurações
 */
function getSecuritySettings($organizacao_id = null) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT * FROM security_settings WHERE organizacao_id = ? OR organizacao_id IS NULL ORDER BY organizacao_id DESC LIMIT 1");
        $stmt->execute([$organizacao_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    } catch (PDOException $e) {
        error_log("Erro ao obter configurações de segurança: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica se 2FA é obrigatório para o usuário
 *
 * @param string $user_type 'admin' ou 'equipe'
 * @param int $organizacao_id ID da organização
 * @return bool 2FA obrigatório
 */
function isTwoFactorRequired($user_type, $organizacao_id = null) {
    $settings = getSecuritySettings($organizacao_id);

    if ($user_type === 'admin') {
        return $settings['force_2fa_admin'] ?? false;
    }

    return $settings['force_2fa_users'] ?? false;
}
