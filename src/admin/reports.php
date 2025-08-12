<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/env.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Obtener filtros
$campaign_id = $_GET['campaign_id'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$score_filter = $_GET['score_filter'] ?? '';

// Obtener campañas para el filtro
$campaigns = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT id, name FROM campaigns WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener estadísticas generales
$stats = [];
if ($conn) {
    // Total de respuestas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nps_responses");
    $stmt->execute();
    $stats['total_responses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Promedio general
    $stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM nps_responses");
    $stmt->execute();
    $stats['avg_score'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    // Distribución de scores
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN score <= 6 THEN 'Detractores'
                WHEN score <= 8 THEN 'Pasivos'
                ELSE 'Promotores'
            END as category,
            COUNT(*) as count
        FROM nps_responses 
        GROUP BY category
    ");
    $stmt->execute();
    $stats['distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top campañas
    $stmt = $conn->prepare("
        SELECT 
            c.name,
            COUNT(DISTINCT r.session_id) as responses,
            AVG(r.response_score) as avg_score
        FROM campaigns c
        LEFT JOIN survey_responses r ON c.id = r.campaign_id AND r.response_score IS NOT NULL
        WHERE c.is_active = 1
        GROUP BY c.id, c.name
        ORDER BY responses DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['top_campaigns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos filtrados
$filtered_data = [];
if ($conn) {
    $where_conditions = [];
    $params = [];
    
    if ($campaign_id) {
        $where_conditions[] = "r.campaign_id = ?";
        $params[] = $campaign_id;
    }
    
    if ($date_from) {
        $where_conditions[] = "r.created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    
    if ($date_to) {
        $where_conditions[] = "r.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    
    if ($score_filter) {
        switch ($score_filter) {
            case 'detractors':
                $where_conditions[] = "r.response_score <= 6";
                break;
            case 'passives':
                $where_conditions[] = "r.response_score BETWEEN 7 AND 8";
                break;
            case 'promoters':
                $where_conditions[] = "r.response_score >= 9";
                break;
        }
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            c.name as campaign_name,
            cq.question_text,
            cq.question_type
        FROM survey_responses r
        LEFT JOIN campaigns c ON r.campaign_id = c.id
        LEFT JOIN campaign_questions cq ON r.question_id = cq.id
        {$where_clause}
        ORDER BY r.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $filtered_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calcular NPS Score
$nps_score = 0;
if (!empty($stats['distribution'])) {
    $detractors = 0;
    $promoters = 0;
    $total = 0;
    
    foreach ($stats['distribution'] as $dist) {
        $total += $dist['count'];
        if ($dist['category'] === 'Detractores') {
            $detractors = $dist['count'];
        } elseif ($dist['category'] === 'Promotores') {
            $promoters = $dist['count'];
        }
    }
    
    if ($total > 0) {
        $nps_score = round((($promoters - $detractors) / $total) * 100, 1);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - NPS System</title>
    
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .nps-score {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
        }
        
        .nps-positive {
            color: #28a745;
        }
        
        .nps-negative {
            color: #dc3545;
        }
        
        .nps-neutral {
            color: #ffc107;
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
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes y Estadísticas
                        </h2>
                        <div>
                            <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>
                            <button class="btn btn-primary" onclick="exportData()">
                                <i class="fas fa-download me-2"></i>Exportar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-filter me-2"></i>
                                Filtros
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="campaign_id" class="form-label">Campaña</label>
                                    <select class="form-select" id="campaign_id" name="campaign_id">
                                        <option value="">Todas las campañas</option>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <option value="<?= $campaign['id'] ?>" <?= $campaign_id == $campaign['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($campaign['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Desde</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="score_filter" class="form-label">Filtrar por Score</label>
                                    <select class="form-select" id="score_filter" name="score_filter">
                                        <option value="">Todos los scores</option>
                                        <option value="detractors" <?= $score_filter == 'detractors' ? 'selected' : '' ?>>Detractores (0-6)</option>
                                        <option value="passives" <?= $score_filter == 'passives' ? 'selected' : '' ?>>Pasivos (7-8)</option>
                                        <option value="promoters" <?= $score_filter == 'promoters' ? 'selected' : '' ?>>Promotores (9-10)</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Estadísticas Generales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($stats['total_responses']) ?></div>
                                <div class="stat-label">Total Respuestas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= $stats['avg_score'] ?></div>
                                <div class="stat-label">Promedio General</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number <?= $nps_score >= 0 ? 'nps-positive' : 'nps-negative' ?>"><?= $nps_score ?></div>
                                <div class="stat-label">NPS Score</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= count($campaigns) ?></div>
                                <div class="stat-label">Campañas Activas</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráficos -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Distribución de Scores
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="distributionChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Top Campañas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="campaignsChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de Datos -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2"></i>
                                Respuestas Recientes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Campaña</th>
                                            <th>Score</th>
                                            <th>Categoría</th>
                                            <th>Comentario</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_data as $response): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($response['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($response['campaign_name'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $response['score'] <= 6 ? 'danger' : ($response['score'] <= 8 ? 'warning' : 'success') ?>">
                                                        <?= $response['score'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $category = $response['score'] <= 6 ? 'Detractor' : ($response['score'] <= 8 ? 'Pasivo' : 'Promotor');
                                                    $color = $response['score'] <= 6 ? 'danger' : ($response['score'] <= 8 ? 'warning' : 'success');
                                                    ?>
                                                    <span class="badge bg-<?= $color ?>"><?= $category ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($response['comment']): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($response['comment']) ?>">
                                                            <?= htmlspecialchars($response['comment']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin comentario</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($response['email'] ?? 'Anónimo') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Datos para los gráficos
        const distributionData = <?= json_encode($stats['distribution']) ?>;
        const campaignsData = <?= json_encode($stats['top_campaigns']) ?>;
        
        // Gráfico de distribución
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: distributionData.map(item => item.category),
                datasets: [{
                    data: distributionData.map(item => item.count),
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Gráfico de campañas
        const campaignsCtx = document.getElementById('campaignsChart').getContext('2d');
        new Chart(campaignsCtx, {
            type: 'bar',
            data: {
                labels: campaignsData.map(item => item.name),
                datasets: [{
                    label: 'Promedio de Score',
                    data: campaignsData.map(item => parseFloat(item.avg_score)),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Función para exportar datos
        function exportData() {
            const table = document.querySelector('table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            let csv = 'Fecha,Campaña,Score,Categoría,Comentario,Email\n';
            
            rows.slice(1).forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                const rowData = cells.map(cell => {
                    let text = cell.textContent.trim();
                    // Escapar comillas para CSV
                    if (text.includes(',')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    return text;
                });
                csv += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'nps_report_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
