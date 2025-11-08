<?php
/**
 * Helper para envio de e-mails usando PHPMailer
 * Usar configurações do banco de dados
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Se PHPMailer não estiver disponível, usar fallback com mail()
function enviarEmail($destinatario, $assunto, $corpo, $nomeDestinatario = '') {
    global $pdo;

    try {
        // Buscar configurações de e-mail
        $stmt = $pdo->query("SELECT * FROM config_email WHERE ativo = 1 LIMIT 1");
        $config = $stmt->fetch();

        if (!$config) {
            // Configuração não encontrada ou desativada
            error_log("Configuração de e-mail não encontrada ou desativada");
            return false;
        }

        // Verificar se PHPMailer está disponível
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return enviarEmailPHPMailer($config, $destinatario, $assunto, $corpo, $nomeDestinatario);
        } else {
            // Fallback para função mail() nativa do PHP
            return enviarEmailNativo($config, $destinatario, $assunto, $corpo, $nomeDestinatario);
        }

    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: " . $e->getMessage());
        return false;
    }
}

function enviarEmailPHPMailer($config, $destinatario, $assunto, $corpo, $nomeDestinatario) {
    try {
        $mail = new PHPMailer(true);

        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'];
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = 'UTF-8';

        // Remetente
        $mail->setFrom($config['email_remetente'], $config['nome_remetente']);

        // Destinatário
        $mail->addAddress($destinatario, $nomeDestinatario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $corpo;
        $mail->AltBody = strip_tags($corpo);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erro PHPMailer: " . $e->getMessage());
        return false;
    }
}

function enviarEmailNativo($config, $destinatario, $assunto, $corpo, $nomeDestinatario) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $config['nome_remetente'] . " <" . $config['email_remetente'] . ">" . "\r\n";

    $to = $nomeDestinatario ? "$nomeDestinatario <$destinatario>" : $destinatario;

    return mail($to, $assunto, $corpo, $headers);
}

/**
 * Templates de e-mail
 */

function emailCadastroAtleta($nomeAtleta, $nomeEquipe, $emailAtleta) {
    $assunto = "Bem-vindo ao Sistema de Competições Esportivas!";

    $corpo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Cadastro Realizado com Sucesso!</h1>
            </div>
            <div class='content'>
                <h2>Olá, $nomeAtleta!</h2>
                <p>Seja muito bem-vindo(a) ao <strong>Sistema de Gestão de Competições Esportivas</strong>!</p>

                <p>Você foi cadastrado(a) com sucesso na equipe:</p>
                <h3 style='color: #667eea;'>🏆 $nomeEquipe</h3>

                <p><strong>Próximos Passos:</strong></p>
                <ul>
                    <li>Seus dados já estão registrados no sistema</li>
                    <li>O responsável pela equipe receberá uma notificação</li>
                    <li>Você poderá participar das competições da equipe</li>
                    <li>Mantenha contato com o responsável da equipe para mais informações</li>
                </ul>

                <p style='margin-top: 30px;'><strong>Informações Importantes:</strong></p>
                <p>📧 Este é um e-mail automático de confirmação de cadastro.<br>
                📱 Em caso de dúvidas, entre em contato com o responsável pela sua equipe.</p>

                <div class='footer'>
                    <p>© 2025 Sistema de Gestão de Competições Esportivas<br>
                    Este é um e-mail automático, não responda esta mensagem.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

    return enviarEmail($emailAtleta, $assunto, $corpo, $nomeAtleta);
}

function emailInscricaoEquipe($nomeEquipe, $nomeCompeticao, $protocolo, $emailEquipe, $responsavelNome) {
    $assunto = "Inscrição Realizada - $nomeCompeticao";

    $corpo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .protocolo { background: white; padding: 20px; text-align: center; border: 2px dashed #10b981; border-radius: 5px; margin: 20px 0; }
            .protocolo-numero { font-size: 24px; font-weight: bold; color: #10b981; font-family: monospace; }
            .info-box { background: white; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Inscrição Realizada!</h1>
            </div>
            <div class='content'>
                <h2>Olá, $responsavelNome!</h2>
                <p>A inscrição da equipe <strong>$nomeEquipe</strong> foi realizada com sucesso!</p>

                <div class='protocolo'>
                    <p style='margin: 0 0 10px 0; color: #6c757d;'>Protocolo de Inscrição</p>
                    <div class='protocolo-numero'>$protocolo</div>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #6c757d;'>Guarde este número para acompanhamento</p>
                </div>

                <div class='info-box'>
                    <h3 style='margin-top: 0;'>📋 Detalhes da Inscrição</h3>
                    <p><strong>Competição:</strong> $nomeCompeticao</p>
                    <p><strong>Equipe:</strong> $nomeEquipe</p>
                    <p><strong>Status:</strong> <span style='color: #ffc107;'>⏳ Pendente de Aprovação</span></p>
                </div>

                <p><strong>Próximos Passos:</strong></p>
                <ul>
                    <li>Aguarde a análise da administração</li>
                    <li>Você será notificado sobre a aprovação ou rejeição</li>
                    <li>Acompanhe o status no painel da equipe</li>
                    <li>Em caso de aprovação, você receberá mais instruções</li>
                </ul>

                <p style='margin-top: 30px;'><strong>Informações Importantes:</strong></p>
                <p>📧 Você receberá um e-mail quando houver atualização no status da inscrição.<br>
                📱 Acesse o painel da equipe para mais detalhes.</p>

                <div class='footer'>
                    <p>© 2025 Sistema de Gestão de Competições Esportivas<br>
                    Este é um e-mail automático, não responda esta mensagem.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

    return enviarEmail($emailEquipe, $assunto, $corpo, $responsavelNome);
}

function emailAprovacaoInscricao($nomeEquipe, $nomeCompeticao, $emailEquipe, $responsavelNome, $dataEvento) {
    $assunto = "Inscrição APROVADA - $nomeCompeticao";

    $corpo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-box { background: #d1fae5; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0; }
            .info-box { background: white; padding: 15px; border-left: 4px solid #10b981; margin: 15px 0; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 INSCRIÇÃO APROVADA!</h1>
            </div>
            <div class='content'>
                <h2>Parabéns, $responsavelNome!</h2>

                <div class='success-box'>
                    <h3 style='color: #10b981; margin: 0;'>✅ Sua inscrição foi APROVADA!</h3>
                </div>

                <p>A inscrição da equipe <strong>$nomeEquipe</strong> na competição <strong>$nomeCompeticao</strong> foi aprovada pela administração!</p>

                <div class='info-box'>
                    <h3 style='margin-top: 0;'>📋 Informações da Competição</h3>
                    <p><strong>Competição:</strong> $nomeCompeticao</p>
                    <p><strong>Data do Evento:</strong> $dataEvento</p>
                    <p><strong>Equipe:</strong> $nomeEquipe</p>
                    <p><strong>Status:</strong> <span style='color: #10b981;'>✅ Confirmada</span></p>
                </div>

                <p><strong>Próximos Passos:</strong></p>
                <ul>
                    <li>Prepare sua equipe para a competição</li>
                    <li>Verifique os regulamentos e requisitos</li>
                    <li>Acompanhe atualizações no painel da equipe</li>
                    <li>Entre em contato com a organização em caso de dúvidas</li>
                </ul>

                <p style='margin-top: 30px; text-align: center;'><strong>Boa sorte na competição! 🏆</strong></p>

                <div class='footer'>
                    <p>© 2025 Sistema de Gestão de Competições Esportivas<br>
                    Este é um e-mail automático, não responda esta mensagem.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

    return enviarEmail($emailEquipe, $assunto, $corpo, $responsavelNome);
}

function emailRejeicaoInscricao($nomeEquipe, $nomeCompeticao, $emailEquipe, $responsavelNome, $motivo = '') {
    $assunto = "Inscrição Não Aprovada - $nomeCompeticao";

    $motivoTexto = $motivo ? "<p><strong>Motivo:</strong> $motivo</p>" : "";

    $corpo = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .alert-box { background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
            .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>❌ Inscrição Não Aprovada</h1>
            </div>
            <div class='content'>
                <h2>Olá, $responsavelNome</h2>

                <p>Informamos que a inscrição da equipe <strong>$nomeEquipe</strong> na competição <strong>$nomeCompeticao</strong> não foi aprovada.</p>

                <div class='alert-box'>
                    $motivoTexto
                    <p>Para mais informações, entre em contato com a administração do sistema.</p>
                </div>

                <p>Agradecemos seu interesse e esperamos poder contar com sua participação em futuras competições!</p>

                <div class='footer'>
                    <p>© 2025 Sistema de Gestão de Competições Esportivas<br>
                    Este é um e-mail automático, não responda esta mensagem.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

    return enviarEmail($emailEquipe, $assunto, $corpo, $responsavelNome);
}
?>
