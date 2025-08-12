<?php
require_once 'config/database.php';
require_once 'config/encryption.php';

$db = new Database();
$conn = $db->getConnection();

$campaign = null;
$message = '';
$messageType = '';

// Obtener token encriptado de la campaña
$token = $_GET['token'] ?? null;

if ($token && $conn) {
    try {
        $encryption = new Encryption();
        $campaign_id = $encryption->decryptCampaignData($token);
        
        if ($campaign_id === false) {
            $message = 'Enlace inválido o expirado';
            $messageType = 'danger';
        } else {
            // Verificar que la campaña existe y está activa
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
                } else {
                    // Marcar el enlace como usado en la base de datos
                    $stmt = $conn->prepare("UPDATE campaign_links SET is_used = TRUE, used_at = NOW(), used_ip = ? WHERE token = ? AND campaign_id = ?");
                    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $token, $campaign_id]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error procesando token: " . $e->getMessage());
        $message = 'Error al procesar el enlace';
        $messageType = 'danger';
    }
} else {
    $message = 'Enlace inválido';
    $messageType = 'danger';
}

// Procesar respuesta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $campaign) {
    $email = trim($_POST['email'] ?? '');
    $session_id = uniqid('survey_', true);
    
    try {
        $conn->beginTransaction();
        
        // Obtener todas las preguntas de la campaña
        $stmt = $conn->prepare("SELECT * FROM campaign_questions WHERE campaign_id = ? ORDER BY order_index");
        $stmt->execute([$campaign_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $allValid = true;
        $responses = [];
        
        foreach ($questions as $question) {
            $response_value = '';
            $response_score = null;
            
            if ($question['question_type'] === 'nps') {
                $score = $_POST['score_' . $question['id']] ?? null;
                if ($question['is_required'] && ($score === null || $score < 0 || $score > 10)) {
                    $allValid = false;
                    break;
                }
                $response_score = $score;
                $response_value = $score;
            } elseif ($question['question_type'] === 'rating') {
                $rating = $_POST['rating_' . $question['id']] ?? null;
                if ($question['is_required'] && ($rating === null || $rating < 1 || $rating > 5)) {
                    $allValid = false;
                    break;
                }
                $response_score = $rating;
                $response_value = $rating;
            } elseif ($question['question_type'] === 'text') {
                $text = trim($_POST['text_' . $question['id']] ?? '');
                if ($question['is_required'] && empty($text)) {
                    $allValid = false;
                    break;
                }
                $response_value = $text;
            } elseif ($question['question_type'] === 'multiple_choice') {
                $choice = $_POST['choice_' . $question['id']] ?? '';
                if ($question['is_required'] && empty($choice)) {
                    $allValid = false;
                    break;
                }
                $response_value = $choice;
            }
            
            $responses[] = [
                'question_id' => $question['id'],
                'response_value' => $response_value,
                'response_score' => $response_score
            ];
        }
        
        if ($allValid) {
            // Insertar todas las respuestas
            $stmt = $conn->prepare("INSERT INTO survey_responses (campaign_id, question_id, response_value, response_score, email, session_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            foreach ($responses as $response) {
                $stmt->execute([
                    $campaign_id,
                    $response['question_id'],
                    $response['response_value'],
                    $response['response_score'],
                    $email,
                    $session_id
                ]);
            }
            
            $conn->commit();
            $message = '¡Gracias por tu respuesta!';
            $messageType = 'success';
            $campaign = null; // Ocultar formulario después de enviar
        } else {
            $conn->rollBack();
            $message = 'Por favor completa todas las preguntas requeridas';
            $messageType = 'danger';
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $message = 'Error al enviar la respuesta: ' . $e->getMessage();
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
            gap: 15px;
            padding: 0 10px;
        }
        
        @media (max-width: 768px) {
            .score-buttons {
                gap: 10px;
                justify-content: center;
            }
            
            .score-btn {
                width: 55px;
                height: 55px;
            }
            
            .emoji-score {
                font-size: 1.5rem;
            }
        }
        
        .score-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #e9ecef;
            background: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
        }
        
        .score-btn:hover {
            border-color: #667eea;
            transform: scale(1.15);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .score-btn.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .emoji-score {
            font-size: 2rem;
            transition: all 0.3s ease;
        }
        
        .score-btn:hover .emoji-score {
            transform: scale(1.1);
        }
        
        .score-btn.selected .emoji-score {
            filter: brightness(1.2);
        }
        
        .score-indicator {
            text-align: center;
            margin: 1rem 0;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            display: none;
        }
        
        .score-indicator.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .score-buttons-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            border: 2px solid #e9ecef;
        }
        
        .score-buttons-container h4 {
            color: #495057;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .score-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .score-labels span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .score-labels .emoji-label {
            font-size: 1.2rem;
        }
        
        /* Estilos para preguntas de rating */
        .rating-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            border: 2px solid #e9ecef;
        }
        
        .rating-buttons {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            gap: 15px;
        }
        
        .rating-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #e9ecef;
            background: white;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
        }
        
        .rating-btn:hover {
            border-color: #ffc107;
            transform: scale(1.15);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .rating-btn.selected {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            border-color: #ffc107;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }
        
        .rating-star {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .rating-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #495057;
        }
        
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Estilos para preguntas de texto */
        .text-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            border: 2px solid #e9ecef;
        }
        
        /* Estilos para preguntas de opción múltiple */
        .choice-container {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            border: 2px solid #e9ecef;
        }
        
        .choice-container .form-check {
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .choice-container .form-check:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .choice-container .form-check-input:checked + .form-check-label {
            color: #667eea;
            font-weight: 600;
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
                     <?php
                     // Obtener todas las preguntas de la campaña
                     $stmt = $conn->prepare("SELECT * FROM campaign_questions WHERE campaign_id = ? ORDER BY order_index");
                     $stmt->execute([$campaign_id]);
                     $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                     
                     foreach ($questions as $index => $question): ?>
                         <div class="question-container mb-4">
                             <h4 class="mb-3">
                                 <?= htmlspecialchars($question['question_text']) ?>
                                 <?php if ($question['is_required']): ?>
                                     <span class="text-danger">*</span>
                                 <?php endif; ?>
                             </h4>
                             
                             <?php if ($question['question_type'] === 'nps'): ?>
                                 <div class="score-buttons-container">
                                     <div class="score-buttons">
                                         <?php 
                                         $emojis = [
                                             0 => '😢',   // Muy triste
                                             1 => '😞',   // Triste
                                             2 => '😕',   // Descontento
                                             3 => '😐',   // Neutral
                                             4 => '🙂',   // Ligeramente contento
                                             5 => '😊',   // Contento
                                             6 => '😃',   // Muy contento
                                             7 => '😄',   // Feliz
                                             8 => '😁',   // Muy feliz
                                             9 => '🤩',   // Extremadamente feliz
                                             10 => '🥰'   // Completamente enamorado/feliz
                                         ];
                                         
                                         foreach ($emojis as $score => $emoji): ?>
                                             <label class="score-btn" title="Puntuación: <?= $score ?>">
                                                 <input type="radio" name="score_<?= $question['id'] ?>" value="<?= $score ?>" <?= $question['is_required'] ? 'required' : '' ?> style="display: none;">
                                                 <span class="emoji-score"><?= $emoji ?></span>
                                             </label>
                                         <?php endforeach; ?>
                                     </div>
                                     
                                     <div class="score-labels">
                                         <span><span class="emoji-label">😢</span> Muy improbable</span>
                                         <span><span class="emoji-label">🥰</span> Muy probable</span>
                                     </div>
                                     
                                     <div class="score-indicator" id="scoreIndicator_<?= $question['id'] ?>">
                                         <strong>Puntuación seleccionada: <span id="selectedScore_<?= $question['id'] ?>"></span></strong>
                                     </div>
                                 </div>
                                 
                             <?php elseif ($question['question_type'] === 'rating'): ?>
                                 <div class="rating-container">
                                     <div class="rating-buttons">
                                         <?php for ($i = 1; $i <= 5; $i++): ?>
                                             <label class="rating-btn">
                                                 <input type="radio" name="rating_<?= $question['id'] ?>" value="<?= $i ?>" <?= $question['is_required'] ? 'required' : '' ?> style="display: none;">
                                                 <span class="rating-star">⭐</span>
                                                 <span class="rating-number"><?= $i ?></span>
                                             </label>
                                         <?php endfor; ?>
                                     </div>
                                     <div class="rating-labels">
                                         <span>Muy malo</span>
                                         <span>Excelente</span>
                                     </div>
                                 </div>
                                 
                             <?php elseif ($question['question_type'] === 'text'): ?>
                                 <div class="text-container">
                                     <textarea class="form-control" name="text_<?= $question['id'] ?>" rows="4" 
                                               placeholder="Escribe tu respuesta aquí..." <?= $question['is_required'] ? 'required' : '' ?>></textarea>
                                 </div>
                                 
                             <?php elseif ($question['question_type'] === 'multiple_choice'): ?>
                                 <div class="choice-container">
                                     <?php
                                     $options = json_decode($question['options'], true) ?: ['Opción 1', 'Opción 2', 'Opción 3'];
                                     foreach ($options as $option): ?>
                                         <div class="form-check">
                                             <input class="form-check-input" type="radio" name="choice_<?= $question['id'] ?>" 
                                                    value="<?= htmlspecialchars($option) ?>" <?= $question['is_required'] ? 'required' : '' ?>>
                                             <label class="form-check-label"><?= htmlspecialchars($option) ?></label>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php endif; ?>
                         </div>
                     <?php endforeach; ?>
                    
                                         <div class="mb-4">
                         <label for="email" class="form-label">
                             <i class="fas fa-envelope me-2"></i>
                             Email (opcional)
                         </label>
                         <input type="email" class="form-control" id="email" name="email" 
                                placeholder="tu@email.com">
                         <div class="form-text">Para recibir actualizaciones sobre mejoras</div>
                     </div>
                     
                     <div class="progress-container mb-4">
                         <div class="d-flex justify-content-between align-items-center mb-2">
                             <span class="text-muted">Progreso de la encuesta</span>
                             <span class="text-muted" id="progressText">0/<?= count($questions) ?></span>
                         </div>
                         <div class="progress" style="height: 10px;">
                             <div class="progress-bar bg-success" id="progressBar" role="progressbar" style="width: 0%"></div>
                         </div>
                     </div>
                     
                     <div class="text-center">
                         <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                             <i class="fas fa-paper-plane me-2"></i>
                             Enviar Respuesta
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
        // Función para inicializar todas las preguntas
        function initializeQuestions() {
            // Manejar botones de puntuación NPS
            document.querySelectorAll('.score-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const questionContainer = this.closest('.question-container');
                    const questionId = questionContainer.querySelector('input[type="radio"]').name.split('_')[1];
                    
                    // Remover selección anterior en esta pregunta
                    questionContainer.querySelectorAll('.score-btn').forEach(btn => btn.classList.remove('selected'));
                    
                    // Seleccionar botón actual
                    this.classList.add('selected');
                    
                    // Obtener la puntuación seleccionada
                    const score = this.querySelector('input[type="radio"]').value;
                    const emoji = this.querySelector('.emoji-score').textContent;
                    
                    // Mostrar indicador de puntuación
                    const indicator = questionContainer.querySelector(`#scoreIndicator_${questionId}`);
                    const scoreSpan = indicator.querySelector(`#selectedScore_${questionId}`);
                    scoreSpan.innerHTML = `${emoji} ${score}/10`;
                    indicator.classList.add('show');
                    
                    checkFormCompletion();
                });
            });
            
            // Manejar botones de rating
            document.querySelectorAll('.rating-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const questionContainer = this.closest('.question-container');
                    
                    // Remover selección anterior en esta pregunta
                    questionContainer.querySelectorAll('.rating-btn').forEach(btn => btn.classList.remove('selected'));
                    
                    // Seleccionar botón actual
                    this.classList.add('selected');
                    
                    checkFormCompletion();
                });
            });
            
            // Manejar campos de texto
            document.querySelectorAll('textarea[name^="text_"]').forEach(textarea => {
                textarea.addEventListener('input', checkFormCompletion);
            });
            
            // Manejar opciones múltiples
            document.querySelectorAll('input[name^="choice_"]').forEach(radio => {
                radio.addEventListener('change', checkFormCompletion);
            });
        }
        
        // Función para verificar si el formulario está completo
        function checkFormCompletion() {
            const submitBtn = document.getElementById('submitBtn');
            const requiredFields = document.querySelectorAll('[required]');
            let completedCount = 0;
            let totalRequired = 0;
            
            requiredFields.forEach(field => {
                if (field.type === 'radio') {
                    const name = field.name;
                    const checked = document.querySelector(`input[name="${name}"]:checked`);
                    if (checked) {
                        completedCount++;
                    }
                    totalRequired++;
                } else if (field.type === 'textarea') {
                    if (field.value.trim()) {
                        completedCount++;
                    }
                    totalRequired++;
                }
            });
            
            // Actualizar barra de progreso
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const progress = totalRequired > 0 ? (completedCount / totalRequired) * 100 : 0;
            
            progressBar.style.width = progress + '%';
            progressText.textContent = `${completedCount}/${totalRequired}`;
            
            // Cambiar color según el progreso
            if (progress === 100) {
                progressBar.className = 'progress-bar bg-success';
            } else if (progress >= 50) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
            
            submitBtn.disabled = completedCount < totalRequired;
            
            if (completedCount === totalRequired) {
                submitBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        // Validación del formulario
        document.getElementById('surveyForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            
            // Mostrar estado de carga
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
            submitBtn.disabled = true;
            
            // El formulario se enviará normalmente
        });
        
        // Agregar tooltips informativos para botones NPS
        document.querySelectorAll('.score-btn').forEach(button => {
            const score = button.querySelector('input[type="radio"]').value;
            const emoji = button.querySelector('.emoji-score').textContent;
            
            button.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = `Puntuación: ${score}/10`;
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 5px;
                    font-size: 12px;
                    white-space: nowrap;
                    z-index: 1000;
                    top: -40px;
                    left: 50%;
                    transform: translateX(-50%);
                    opacity: 0;
                    transition: opacity 0.3s;
                `;
                
                this.appendChild(tooltip);
                setTimeout(() => tooltip.style.opacity = '1', 10);
            });
            
            button.addEventListener('mouseleave', function() {
                const tooltip = this.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
        
        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', initializeQuestions);
    </script>
</body>
</html> 