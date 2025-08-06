<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/sendgrid.php';

$db = new Database();
$conn = $db->getConnection();

$campaign_id = $_GET['id'] ?? null;
$campaign = null;
$message = '';
$messageType = '';

// Obtener datos de la campaña
if ($campaign_id && $conn) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        $message = 'Campaña no encontrada';
        $messageType = 'danger';
    }
} else {
    $message = 'ID de campaña inválido';
    $messageType = 'danger';
}

// Procesar envío de emails
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $campaign) {
    $emails_text = $_POST['emails'] ?? '';
    $emails = array_filter(array_map('trim', explode("\n", $emails_text)));
    
    if (empty($emails)) {
        $message = 'Por favor ingresa al menos un email';
        $messageType = 'danger';
    } else {
        try {
            $sendgrid_service = new SendGridService();
            $result = $sendgrid_service->sendSurveyToMultipleRecipients($campaign_id, $emails, $campaign);
            
            if ($result) {
                $message = "Envío completado: {$result['success']} exitosos, {$result['error']} errores de {$result['total']} total";
                $messageType = $result['error'] > 0 ? 'warning' : 'success';
            } else {
                $message = 'Error: No se pudo procesar el envío';
                $messageType = 'danger';
            }
            
        } catch (Exception $e) {
            $message = 'Error al enviar emails: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Campaña - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="fas fa-envelope me-2"></i>
                            Enviar Campaña por Email
                        </h2>
                        <a href="campaigns.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver a Campañas
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($campaign): ?>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Enviar Encuesta
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="emailForm">
                                            <div class="mb-3">
                                                <label for="emails" class="form-label">
                                                    <i class="fas fa-envelope me-2"></i>
                                                    Emails (uno por línea)
                                                </label>
                                                <textarea class="form-control" id="emails" name="emails" rows="10" 
                                                          placeholder="usuario1@ejemplo.com&#10;usuario2@ejemplo.com&#10;usuario3@ejemplo.com" required></textarea>
                                                <div class="form-text">
                                                    Ingresa un email por línea. Se enviará la encuesta a cada destinatario.
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="confirmSend" required>
                                                    <label class="form-check-label" for="confirmSend">
                                                        Confirmo que quiero enviar esta encuesta a los destinatarios especificados
                                                    </label>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary btn-lg" id="sendBtn">
                                                    <i class="fas fa-paper-plane me-2"></i>
                                                    Enviar Encuestas
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Detalles de la Campaña
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <h5><?= htmlspecialchars($campaign['name']) ?></h5>
                                        <p class="text-muted"><?= htmlspecialchars($campaign['description']) ?></p>
                                        
                                        <div class="mb-3">
                                            <strong>Pregunta:</strong><br>
                                            <?= htmlspecialchars($campaign['question']) ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Estado:</strong><br>
                                            <span class="badge bg-<?= $campaign['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $campaign['is_active'] ? 'Activa' : 'Inactiva' ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Fechas:</strong><br>
                                            <small class="text-muted">
                                                Desde: <?= $campaign['start_date'] ?><br>
                                                Hasta: <?= $campaign['end_date'] ?>
                                            </small>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            <strong>Consejo:</strong> Los destinatarios recibirán un email con un enlace directo a la encuesta.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No se pudo cargar la información de la campaña.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validación del formulario
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            const emails = document.getElementById('emails').value.trim();
            const confirmSend = document.getElementById('confirmSend').checked;
            
            if (!emails) {
                e.preventDefault();
                alert('Por favor ingresa al menos un email');
                return false;
            }
            
            if (!confirmSend) {
                e.preventDefault();
                alert('Por favor confirma que quieres enviar las encuestas');
                return false;
            }
            
            // Mostrar estado de carga
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            sendBtn.disabled = true;
        });
        
        // Contador de emails
        document.getElementById('emails').addEventListener('input', function() {
            const emails = this.value.trim();
            const emailCount = emails ? emails.split('\n').filter(email => email.trim()).length : 0;
            
            // Actualizar el texto de ayuda
            const helpText = this.parentNode.querySelector('.form-text');
            helpText.textContent = `Ingresa un email por línea. Se enviará la encuesta a cada destinatario. (${emailCount} emails detectados)`;
        });
    </script>
</body>
</html> 