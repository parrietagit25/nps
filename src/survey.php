<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$campaign = null;
$message = '';
$messageType = '';

// Obtener ID de la campaña
$campaign_id = $_GET['id'] ?? null;

if ($campaign_id && $conn) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND is_active = 1");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        $message = 'Campaña no encontrada o inactiva';
        $messageType = 'danger';
    } else {
        // Verificar fechas
        $today = date('Y-m-d');
        if ($today < $campaign['start_date'] || $today > $campaign['end_date']) {
            $message = 'Esta encuesta no está disponible en este momento';
            $messageType = 'warning';
        }
    }
} else {
    $message = 'Enlace inválido';
    $messageType = 'danger';
}

// Procesar respuesta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $campaign) {
    $score = $_POST['score'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if ($score !== null && $score >= 0 && $score <= 10) {
        $stmt = $conn->prepare("INSERT INTO nps_responses (campaign_id, score, comment, email, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$campaign_id, $score, $comment, $email])) {
            $message = '¡Gracias por tu respuesta!';
            $messageType = 'success';
            $campaign = null; // Ocultar formulario después de enviar
        } else {
            $message = 'Error al enviar la respuesta';
            $messageType = 'danger';
        }
    } else {
        $message = 'Por favor selecciona una puntuación válida';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta NPS - <?= htmlspecialchars($campaign['name'] ?? 'NPS System') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .survey-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .survey-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .survey-body {
            padding: 2rem;
        }
        
        .score-buttons {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .score-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #e9ecef;
            background: white;
            color: #6c757d;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .score-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: scale(1.1);
        }
        
        .score-btn.selected {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }
        
        .score-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .thank-you {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .thank-you i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="survey-card">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show m-3" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($campaign && !$message): ?>
            <div class="survey-header">
                <h2 class="mb-2">
                    <i class="fas fa-star me-2"></i>
                    <?= htmlspecialchars($campaign['name']) ?>
                </h2>
                <p class="mb-0"><?= htmlspecialchars($campaign['description']) ?></p>
            </div>
            
            <div class="survey-body">
                <form method="POST" id="surveyForm">
                    <div class="mb-4">
                        <h4 class="text-center mb-3"><?= htmlspecialchars($campaign['question']) ?></h4>
                        
                        <div class="score-buttons">
                            <?php for ($i = 0; $i <= 10; $i++): ?>
                                <button type="button" class="score-btn" data-score="<?= $i ?>">
                                    <?= $i ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="score-labels">
                            <span>Muy improbable</span>
                            <span>Muy probable</span>
                        </div>
                        
                        <input type="hidden" name="score" id="selectedScore" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="comment" class="form-label">
                            <i class="fas fa-comment me-2"></i>
                            Comentarios (opcional)
                        </label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" 
                                  placeholder="Cuéntanos más sobre tu experiencia..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>
                            Email (opcional)
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="tu@email.com">
                        <div class="form-text">Para recibir actualizaciones sobre mejoras</div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane me-2"></i>
                            Enviar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($messageType === 'success'): ?>
            <div class="thank-you">
                <i class="fas fa-check-circle"></i>
                <h3 class="mb-3">¡Gracias por tu respuesta!</h3>
                <p class="text-muted">Tu opinión es muy importante para nosotros.</p>
                <p class="text-muted">Trabajamos constantemente para mejorar nuestros servicios.</p>
            </div>
        <?php else: ?>
            <div class="survey-body text-center">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <h3 class="mt-3"><?= $message ?></h3>
                <p class="text-muted">Por favor, verifica el enlace o contacta al administrador.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Manejo de botones de puntuación
        const scoreButtons = document.querySelectorAll('.score-btn');
        const selectedScoreInput = document.getElementById('selectedScore');
        const submitBtn = document.getElementById('submitBtn');
        
        scoreButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remover selección anterior
                scoreButtons.forEach(btn => btn.classList.remove('selected'));
                
                // Seleccionar botón actual
                this.classList.add('selected');
                
                // Actualizar input hidden
                selectedScoreInput.value = this.dataset.score;
                
                // Habilitar botón de envío
                submitBtn.disabled = false;
            });
        });
        
        // Validación del formulario
        document.getElementById('surveyForm').addEventListener('submit', function(e) {
            if (!selectedScoreInput.value) {
                e.preventDefault();
                alert('Por favor selecciona una puntuación');
                return false;
            }
        });
        
        // Animación de carga al enviar
        submitBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            this.disabled = true;
        });
    </script>
</body>
</html> 