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

// Obtener estadísticas básicas
$stats = [];
if ($conn) {
    // Total de campañas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM campaigns");
    $stats['campaigns'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de respuestas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM nps_responses");
    $stats['responses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Usuarios activos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // NPS promedio
    $stmt = $conn->query("SELECT AVG(score) as average FROM nps_responses");
    //$stats['avg_nps'] = round($stmt->fetch(PDO::FETCH_ASSOC)['average'], 2);
    $stats['avg_nps'] = round($stmt->fetch(PDO::FETCH_ASSOC)['average'] ?? 0, 2);

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
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
        
        .stats-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            NPS System
                        </h4>
                        <small class="text-white-50">Panel de Administración</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-bullhorn me-2"></i>
                            Campañas
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-users me-2"></i>
                            Usuarios
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog me-2"></i>
                            Configuración
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Dashboard</h2>
                            <p class="text-muted mb-0">Bienvenido, <?= htmlspecialchars($_SESSION['full_name']) ?></p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Último acceso: <?= date('d/m/Y H:i') ?></small>
                        </div>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary text-white me-3">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?= $stats['campaigns'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Campañas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success text-white me-3">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?= $stats['responses'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Respuestas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info text-white me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?= $stats['users'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">Usuarios</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning text-white me-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?= $stats['avg_nps'] ?? 0 ?></h3>
                                        <p class="text-muted mb-0">NPS Promedio</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Respuestas NPS por Mes
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="npsChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Distribución NPS
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="npsPieChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        Actividad Reciente
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Usuario</th>
                                                    <th>Acción</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-user-circle me-2"></i>
                                                        <?= htmlspecialchars($_SESSION['username']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Login exitoso</span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i:s') ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
        // NPS Line Chart
        const ctx = document.getElementById('npsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'NPS Score',
                    data: [7.2, 7.8, 8.1, 7.9, 8.3, 8.5],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });

        // NPS Pie Chart
        const pieCtx = document.getElementById('npsPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Promotores (9-10)', 'Pasivos (7-8)', 'Detractores (0-6)'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
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
    </script>
</body>
</html> 