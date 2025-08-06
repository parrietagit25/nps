<?php
session_start();
require_once 'config/database.php';
require_once 'config/sendgrid.php';

$message = '';
$messageType = '';
$debug_info = [];

// Información de debug
$debug_info['env_file_exists'] = file_exists(__DIR__ . '/../../.env');
$debug_info['sendgrid_api_key'] = $_ENV['SENDGRID_API_KEY'] ?? 'NO_CONFIGURADA';
$debug_info['from_email'] = $_ENV['FROM_EMAIL'] ?? 'NO_CONFIGURADA';
$debug_info['from_name'] = $_ENV['FROM_NAME'] ?? 'NO_CONFIGURADA';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_email = $_POST['test_email'] ?? '';
    
    if (!empty($test_email)) {
        try {
            $sendgrid_service = new SendGridService();
            
            // Datos de prueba
            $test_campaign = [
                'name' => 'Prueba de SendGrid',
                'description' => 'Esta es una prueba del sistema de envío de emails',
                'question' => '¿Qué tan probable es que recomiendes nuestro servicio?'
            ];
            
            $result = $sendgrid_service->sendSurveyEmail(1, $test_email, $test_campaign);
            
            if ($result['status'] === 'success') {
                $message = '✅ Email de prueba enviado correctamente';
                $messageType = 'success';
            } else {
                $message = '❌ Error al enviar email: ' . $result['message'];
                $messageType = 'danger';
            }
            
        } catch (Exception $e) {
            $message = '❌ Excepción: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'Por favor ingresa un email de prueba';
        $messageType = 'warning';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SendGrid - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .card {
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .debug-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .debug-info pre {
            margin: 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>
                            Test de Configuración SendGrid
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Información de Debug -->
                        <div class="debug-info">
                            <h6><i class="fas fa-bug me-2"></i>Información de Debug:</h6>
                            <pre><?= json_encode($debug_info, JSON_PRETTY_PRINT) ?></pre>
                        </div>
                        
                        <!-- Formulario de prueba -->
                        <div class="mb-4">
                            <h5><i class="fas fa-paper-plane me-2"></i>Enviar Email de Prueba</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">Email de prueba:</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email" 
                                           placeholder="tu-email@ejemplo.com" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-send me-2"></i>Enviar Email de Prueba
                                </button>
                            </form>
                        </div>
                        
                        <hr>
                        
                        <!-- Información -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Información:</h6>
                            <ul class="mb-0">
                                <li>Esta página prueba la configuración de SendGrid</li>
                                <li>Verifica que la API key esté configurada correctamente</li>
                                <li>Si hay errores, revisa los logs del servidor</li>
                                <li>El email de prueba incluirá un enlace a una encuesta de ejemplo</li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
