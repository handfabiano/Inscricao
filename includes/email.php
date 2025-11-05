<?php
/**
 * Sistema de Envio de Emails
 * Suporta PHPMailer ou mail() nativo
 */

class EmailSystem {
    private $from;
    private $fromName;

    public function __construct() {
        $this->from = SMTP_FROM;
        $this->fromName = SMTP_FROM_NAME;
    }

    /**
     * Envia email usando mail() nativo do PHP
     */
    public function enviarEmail($to, $subject, $body, $isHTML = true) {
        $headers = "MIME-Version: 1.0" . "\r\n";

        if ($isHTML) {
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        } else {
            $headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
        }

        $headers .= "From: {$this->fromName} <{$this->from}>" . "\r\n";
        $headers .= "Reply-To: {$this->from}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        return mail($to, $subject, $body, $headers);
    }

    /**
     * Email de confirmação de inscrição
     */
    public function enviarConfirmacaoInscricao($inscricao) {
        $subject = "Confirmação de Inscrição - Protocolo: {$inscricao['protocolo']}";

        $body = $this->getTemplateConfirmacao($inscricao);

        return $this->enviarEmail($inscricao['email'], $subject, $body);
    }

    /**
     * Email de aprovação
     */
    public function enviarAprovacao($inscricao) {
        $subject = "Inscrição Aprovada - Protocolo: {$inscricao['protocolo']}";

        $body = $this->getTemplateAprovacao($inscricao);

        return $this->enviarEmail($inscricao['email'], $subject, $body);
    }

    /**
     * Email de rejeição
     */
    public function enviarRejeicao($inscricao) {
        $subject = "Inscrição Rejeitada - Protocolo: {$inscricao['protocolo']}";

        $body = $this->getTemplateRejeicao($inscricao);

        return $this->enviarEmail($inscricao['email'], $subject, $body);
    }

    /**
     * Template HTML de confirmação
     */
    private function getTemplateConfirmacao($inscricao) {
        $protocolo = htmlspecialchars($inscricao['protocolo']);
        $nome = htmlspecialchars($inscricao['nome_completo']);
        $modalidade = htmlspecialchars($inscricao['modalidade_nome'] ?? 'N/A');
        $categoria = htmlspecialchars($inscricao['categoria_nome'] ?? 'N/A');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2563eb, #1e40af); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; }
                .protocolo { background: #2563eb; color: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .protocolo h2 { margin: 0; font-size: 28px; letter-spacing: 2px; }
                .info { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2563eb; }
                .footer { background: #1e293b; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; }
                .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Inscrição Recebida com Sucesso!</h1>
                </div>

                <div class='content'>
                    <p>Olá, <strong>{$nome}</strong>!</p>

                    <p>Sua inscrição foi recebida com sucesso e está em análise.</p>

                    <div class='protocolo'>
                        <p style='margin: 0; font-size: 14px;'>Protocolo de Inscrição:</p>
                        <h2>{$protocolo}</h2>
                    </div>

                    <div class='info'>
                        <h3>Dados da Inscrição:</h3>
                        <p><strong>Modalidade:</strong> {$modalidade}</p>
                        <p><strong>Categoria:</strong> {$categoria}</p>
                        <p><strong>Status:</strong> Pendente</p>
                    </div>

                    <p><strong>Próximos Passos:</strong></p>
                    <ul>
                        <li>Sua inscrição será analisada pela equipe responsável</li>
                        <li>Você receberá um email com a confirmação ou solicitação de ajustes</li>
                        <li>O prazo de análise é de até 5 dias úteis</li>
                    </ul>

                    <p style='text-align: center;'>
                        <a href='" . BASE_URL . "/consulta.php' class='btn'>Consultar Inscrição</a>
                    </p>
                </div>

                <div class='footer'>
                    <p>Sistema de Inscrição de Atletas</p>
                    <p>Este é um email automático, não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template HTML de aprovação
     */
    private function getTemplateAprovacao($inscricao) {
        $protocolo = htmlspecialchars($inscricao['protocolo']);
        $nome = htmlspecialchars($inscricao['nome_completo']);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; }
                .success-icon { font-size: 60px; text-align: center; margin: 20px 0; }
                .footer { background: #1e293b; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; }
                .btn { display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Inscrição Aprovada!</h1>
                </div>

                <div class='content'>
                    <div class='success-icon'>✅</div>

                    <p>Parabéns, <strong>{$nome}</strong>!</p>

                    <p>Sua inscrição de protocolo <strong>{$protocolo}</strong> foi aprovada!</p>

                    <p>Você já pode participar das atividades esportivas na modalidade escolhida.</p>

                    <p><strong>Orientações:</strong></p>
                    <ul>
                        <li>Compareça ao local designado com seu documento de identidade</li>
                        <li>Leve o comprovante de inscrição (disponível para download)</li>
                        <li>Esteja preparado para os treinamentos</li>
                    </ul>

                    <p style='text-align: center;'>
                        <a href='" . BASE_URL . "/comprovante.php?protocolo={$protocolo}' class='btn'>Baixar Comprovante</a>
                    </p>
                </div>

                <div class='footer'>
                    <p>Sistema de Inscrição de Atletas</p>
                    <p>Boa sorte nos treinamentos!</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template HTML de rejeição
     */
    private function getTemplateRejeicao($inscricao) {
        $protocolo = htmlspecialchars($inscricao['protocolo']);
        $nome = htmlspecialchars($inscricao['nome_completo']);
        $observacoes = htmlspecialchars($inscricao['observacoes'] ?? 'Não informado');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8fafc; padding: 30px; }
                .alert { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; }
                .footer { background: #1e293b; color: white; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; }
                .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Inscrição Não Aprovada</h1>
                </div>

                <div class='content'>
                    <p>Olá, <strong>{$nome}</strong>,</p>

                    <p>Informamos que sua inscrição de protocolo <strong>{$protocolo}</strong> não foi aprovada.</p>

                    <div class='alert'>
                        <strong>Motivo:</strong><br>
                        {$observacoes}
                    </div>

                    <p><strong>O que fazer agora?</strong></p>
                    <ul>
                        <li>Verifique o motivo da rejeição acima</li>
                        <li>Corrija as pendências identificadas</li>
                        <li>Faça uma nova inscrição, se necessário</li>
                        <li>Entre em contato conosco em caso de dúvidas</li>
                    </ul>

                    <p style='text-align: center;'>
                        <a href='" . BASE_URL . "' class='btn'>Fazer Nova Inscrição</a>
                    </p>
                </div>

                <div class='footer'>
                    <p>Sistema de Inscrição de Atletas</p>
                    <p>Em caso de dúvidas, entre em contato.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
