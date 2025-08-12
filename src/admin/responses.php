<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$campaign_id = $_GET['id'] ?? null;
$campaign = null;
$responses = [];
$stats = [];

// Obtener datos de la campaña
if ($campaign_id && $conn) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($campaign) {
        // Obtener respuestas de la nueva tabla survey_responses
        $stmt = $conn->prepare("
            SELECT 
                sr.*,
                cq.question_text,
                cq.question_type
            FROM survey_responses sr
            JOIN campaign_questions cq ON sr.question_id = cq.id
            WHERE sr.campaign_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$campaign_id]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // También obtener respuestas de la tabla legacy para compatibilidad
        $stmt = $conn->prepare("SELECT * FROM nps_responses WHERE campaign_id = ? ORDER BY created_at DESC");
        $stmt->execute([$campaign_id]);
        $legacyResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar respuestas y calcular estadísticas
        $allResponses = array_merge($responses, $legacyResponses);
        $total_responses = count($allResponses);
        $promoters = 0;
        $passives = 0;
        $detractors = 0;
        $total_score = 0;
        
        foreach ($allResponses as $response) {
            $score = $response['score'] ?? $response['response_score'] ?? 0;
            if ($score > 0) {
                $total_score += $score;
                
                if ($score >= 9) {
                    $promoters++;
                } elseif ($score >= 7) {
                    $passives++;
                } else {
                    $detractors++;
                }
            }
        }
        
        $avg_score = $total_responses > 0 ? round($total_score / $total_responses, 1) : 0;
        $nps_score = $total_responses > 0 ? round((($promoters - $detractors) / $total_responses) * 100, 1) : 0;
        
        $stats = [
            'total' => $total_responses,
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'avg_score' => $avg_score,
            'nps_score' => $nps_score
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas de Campaña - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .nps-score {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .response-item {
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
            border-radius: 8px;
        }
        
        .score-badge {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0.5rem 1rem;
        }
        
        .score-promoter { background: #28a745; color: white; }
        .score-passive { background: #ffc107; color: #212529; }
        .score-detractor { background: #dc3545; color: white; }
        .score-text { background: #6c757d; color: white; }
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
                            <i class="fas fa-chart-bar me-2"></i>
                            Respuestas de Campaña
                        </h2>
                        <a href="campaigns.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver a Campañas
                        </a>
                    </div>
                    
                    <?php if ($campaign): ?>
                        <!-- Información de la Campaña -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información de la Campaña
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4><?= htmlspecialchars($campaign['name']) ?></h4>
                                        <p class="text-muted"><?= htmlspecialchars($campaign['description']) ?></p>
                                        <p><strong>Preguntas:</strong> 
                                            <?php
                                            // Obtener preguntas de la campaña
                                            $stmt = $conn->prepare("SELECT question_text FROM campaign_questions WHERE campaign_id = ? ORDER BY order_index");
                                            $stmt->execute([$campaign_id]);
                                            $questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                            if ($questions) {
                                                echo htmlspecialchars(implode(', ', $questions));
                                            } else {
                                                echo 'Sin preguntas definidas';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-<?= $campaign['is_active'] ? 'success' : 'secondary' ?> fs-6">
                                            <?= $campaign['is_active'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d/m/Y', strtotime($campaign['start_date'])) ?> - 
                                                <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="nps-score"><?= $stats['nps_score'] ?></div>
                                    <div class="text-muted">NPS Score</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="h3 text-success"><?= $stats['promoters'] ?></div>
                                    <div class="text-muted">Promotores</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="h3 text-warning"><?= $stats['passives'] ?></div>
                                    <div class="text-muted">Pasivos</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card text-center">
                                    <div class="h3 text-danger"><?= $stats['detractors'] ?></div>
                                    <div class="text-muted">Detractores</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráfico -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Distribución de Respuestas</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="responseChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Estadísticas Generales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="h4 text-primary"><?= $stats['total'] ?></div>
                                                <div class="text-muted">Total Respuestas</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="h4 text-info"><?= $stats['avg_score'] ?></div>
                                                <div class="text-muted">Puntuación Promedio</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de Respuestas -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Lista de Respuestas (<?= count($responses) ?>)
                                </h6>
                                <button class="btn btn-sm btn-outline-primary" onclick="exportResponses()">
                                    <i class="fas fa-download me-2"></i>Exportar
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($responses)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No hay respuestas aún</h5>
                                        <p class="text-muted">Las respuestas aparecerán aquí cuando los usuarios completen la encuesta.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($responses as $response): ?>
                                        <div class="response-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    <?php 
                                                    $score = $response['score'] ?? $response['response_score'] ?? 0;
                                                    $questionType = $response['question_type'] ?? 'nps';
                                                    
                                                    if ($score > 0):
                                                    ?>
                                                        <span class="score-badge score-<?= $score >= 9 ? 'promoter' : ($score >= 7 ? 'passive' : 'detractor') ?>">
                                                            <?= $score ?>/10
                                                        </span>
                                                    <?php elseif ($questionType === 'multiple_choice'): ?>
                                                        <span class="score-badge score-text">Opción</span>
                                                    <?php elseif ($questionType === 'text'): ?>
                                                        <span class="score-badge score-text">Texto</span>
                                                    <?php else: ?>
                                                        <span class="score-badge score-text">N/A</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php 
                                                    $comment = $response['comment'] ?? $response['response_text'] ?? '';
                                                    $responseValue = $response['response_value'] ?? '';
                                                    
                                                    if ($questionType === 'multiple_choice' && $responseValue): ?>
                                                        <p class="mb-1"><strong>Respuesta:</strong></p>
                                                        <p class="mb-0"><?= htmlspecialchars($responseValue) ?></p>
                                                    <?php elseif ($comment): ?>
                                                        <p class="mb-1"><strong>Comentario:</strong></p>
                                                        <p class="mb-0"><?= htmlspecialchars($comment) ?></p>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-0">Sin respuesta</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?php if ($response['question_text']): ?>
                                                        <p class="mb-1"><strong>Pregunta:</strong></p>
                                                        <p class="mb-0 small"><?= htmlspecialchars($response['question_text']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($response['email']): ?>
                                                        <p class="mb-0 mt-2"><strong>Email:</strong> <?= htmlspecialchars($response['email']) ?></p>
                                                    <?php else: ?>
                                                        <p class="text-muted mb-0 mt-2">Sin email</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($response['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
        // Gráfico de distribución de respuestas
        const ctx = document.getElementById('responseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Promotores', 'Pasivos', 'Detractores'],
                datasets: [{
                    data: [<?= $stats['promoters'] ?>, <?= $stats['passives'] ?>, <?= $stats['detractors'] ?>],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Función para exportar respuestas
        function exportResponses() {
            // Aquí implementarías la exportación a CSV/Excel
            alert('Función de exportación próximamente');
        }
    </script>
</body>
</html>
