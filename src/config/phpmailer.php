<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PHPMailerService {
    private $mailer;
    private $from_email;
    private $from_name;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Configuración SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com'; // o el servidor SMTP correspondiente
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'notificaciones@grupopcr.com.pa';
        $this->mailer->Password = 'ghhpsqstqbfyscpc';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->CharSet = 'UTF-8';
        
        // Configuración del remitente
        $this->from_email = 'notificaciones@grupopcr.com.pa';
        $this->from_name = 'NPS Survey System';
        
        $this->mailer->setFrom($this->from_email, $this->from_name);
    }

    /**
     * Enviar encuesta NPS a múltiples destinatarios
     */
    public function sendSurveyToMultipleRecipients($campaign_id, $emails, $campaign_data) {
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($emails as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results[] = [
                    'email' => $email,
                    'status' => 'error',
                    'message' => 'Email inválido'
                ];
                $error_count++;
                continue;
            }

            try {
                $result = $this->sendSurveyEmail($campaign_id, $email, $campaign_data);
                $results[] = $result;
                
                if ($result['status'] === 'success') {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } catch (Exception $e) {
                $results[] = [
                    'email' => $email,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                $error_count++;
            }
        }

        return [
            'total' => count($emails),
            'success' => $success_count,
            'error' => $error_count,
            'results' => $results
        ];
    }

    /**
     * Enviar encuesta NPS a un destinatario
     */
    public function sendSurveyEmail($campaign_id, $to_email, $campaign_data) {
        try {
            $survey_url = $this->generateSurveyUrl($campaign_id);
            
            // Limpiar destinatarios anteriores
            $this->mailer->clearAddresses();
            
            // Configurar destinatario
            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = $campaign_data['name'];
            
            // Contenido HTML del correo
            $html_content = $this->generateEmailHTML($campaign_data, $survey_url);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html_content;
            
            // Contenido texto plano
            $text_content = $this->generateEmailText($campaign_data, $survey_url);
            $this->mailer->AltBody = $text_content;

            // Enviar email
            $this->mailer->send();
            
            return [
                'email' => $to_email,
                'status' => 'success',
                'message' => 'Email enviado correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'email' => $to_email,
                'status' => 'error',
                'message' => 'Error al enviar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar URL de la encuesta
     */
    private function generateSurveyUrl($campaign_id) {
        $base_url = 'http://nps.grupopcr.com.pa';
        return $base_url . '/survey.php?id=' . $campaign_id;
    }

    /**
     * Generar contenido HTML del correo
     */
    private function generateEmailHTML($campaign_data, $survey_url) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($campaign_data['name']) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                <h1 style="margin: 0; font-size: 28px;">' . htmlspecialchars($campaign_data['name']) . '</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">' . htmlspecialchars($campaign_data['description']) . '</p>
            </div>
            
            <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;">
                <h2 style="color: #333; margin-top: 0;">' . htmlspecialchars($campaign_data['question']) . '</h2>
                <p style="color: #666; font-size: 16px;">Tu opinión es muy importante para nosotros. Por favor, toma un momento para completar esta breve encuesta.</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $survey_url . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 18px 35px; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px; display: inline-block; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;">🚀 Participar en la Encuesta</a>
            </div>
            
            <div style="background: #e9ecef; padding: 20px; border-radius: 8px; margin-top: 25px;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>¿Qué es NPS?</strong><br>
                    Net Promoter Score (NPS) es una métrica que mide la lealtad del cliente. 
                    Tu respuesta nos ayuda a mejorar nuestros servicios.
                </p>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;">
                <p>Si tienes problemas para acceder a la encuesta, copia y pega este enlace en tu navegador:</p>
                <p style="word-break: break-all; color: #667eea;">' . $survey_url . '</p>
            </div>
        </body>
        </html>';
    }

    /**
     * Generar contenido texto plano del correo
     */
    private function generateEmailText($campaign_data, $survey_url) {
        return $campaign_data['name'] . "\n\n" .
               $campaign_data['description'] . "\n\n" .
               "Pregunta: " . $campaign_data['question'] . "\n\n" .
               "Tu opinión es muy importante para nosotros. Por favor, toma un momento para completar esta breve encuesta.\n\n" .
               "Participar en la encuesta: " . $survey_url . "\n\n" .
               "¿Qué es NPS?\n" .
               "Net Promoter Score (NPS) es una métrica que mide la lealtad del cliente. Tu respuesta nos ayuda a mejorar nuestros servicios.\n\n" .
               "Si tienes problemas para acceder a la encuesta, copia y pega este enlace en tu navegador:\n" .
               $survey_url;
    }
} 