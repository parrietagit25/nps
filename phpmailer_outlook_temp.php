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
        $this->mailer->CharSet = "UTF-8";
        $this->mailer->isSMTP();
        $this->mailer->Host = "smtp-mail.outlook.com";
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = "notificaciones@grupopcr.com.pa";
        $this->mailer->Password = "R>xv7A=u[3WnJ{rDg;#S";
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->from_email = "notificaciones@grupopcr.com.pa";
        $this->from_name = "PCR notificaciones";
        $this->mailer->setFrom($this->from_email, $this->from_name);
    }

    public function sendSurveyToMultipleRecipients($campaign_id, $emails, $campaign_data) {
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($emails as $email) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results[] = ["email" => $email, "status" => "error", "message" => "Email inválido"];
                $error_count++;
                continue;
            }
            
            try {
                $result = $this->sendSurveyEmail($campaign_id, $email, $campaign_data);
                $results[] = $result;
                if ($result["status"] === "success") {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } catch (Exception $e) {
                $results[] = ["email" => $email, "status" => "error", "message" => $e->getMessage()];
                $error_count++;
            }
        }
        
        return ["total" => count($emails), "success" => $success_count, "error" => $error_count, "results" => $results];
    }

    public function sendSurveyEmail($campaign_id, $to_email, $campaign_data) {
        try {
            $survey_url = $this->generateSurveyUrl($campaign_id);
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = $campaign_data["name"];
            $html_content = $this->generateEmailHTML($campaign_data, $survey_url);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html_content;
            $text_content = $this->generateEmailText($campaign_data, $survey_url);
            $this->mailer->AltBody = $text_content;
            $this->mailer->send();
            return ["email" => $to_email, "status" => "success", "message" => "Email enviado correctamente"];
        } catch (Exception $e) {
            return ["email" => $to_email, "status" => "error", "message" => "Error al enviar: " . $e->getMessage()];
        }
    }

    private function generateSurveyUrl($campaign_id) {
        $base_url = getenv("BASE_URL") ?: "http://nps.grupopcr.com.pa";
        return $base_url . "/survey.php?id=" . $campaign_id;
    }

    private function generateEmailHTML($campaign_data, $survey_url) {
        return "<html><body><h1>" . htmlspecialchars($campaign_data["name"]) . "</h1><p>" . htmlspecialchars($campaign_data["description"]) . "</p><a href=\"" . $survey_url . "\">Participar en la Encuesta</a></body></html>";
    }

    private function generateEmailText($campaign_data, $survey_url) {
        return $campaign_data["name"] . "\n\n" . $campaign_data["description"] . "\n\nParticipar en la encuesta: " . $survey_url;
    }
} 