<?php
session_start();
require_once 'config/database.php';

// Get settings
$db = new Database();
$conn = $db->getConnection();

$site_name = 'NPS Survey System';
$site_description = 'Sistema de encuestas Net Promoter Score';

if ($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $site_name = $result['setting_value'];
    }
    
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_description'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $site_description = $result['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            margin: 2rem 0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .btn-custom {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .nps-score {
            font-size: 3rem;
            font-weight: bold;
        }
        
        .footer {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(44, 62, 80, 0.9);">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                <?php echo htmlspecialchars($site_name); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#surveys">Encuestas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports">Reportes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container">
        <div class="hero-section text-center">
            <h1 class="display-4 mb-4">
                <i class="fas fa-chart-line text-primary me-3"></i>
                Sistema NPS
            </h1>
            <p class="lead mb-4"><?php echo htmlspecialchars($site_description); ?></p>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon text-primary">
                            <i class="fas fa-poll"></i>
                        </div>
                        <h4>Encuestas Inteligentes</h4>
                        <p>Crea y gestiona encuestas NPS de manera fácil y eficiente</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon text-success">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Análisis Avanzado</h4>
                        <p>Obtén insights detallados con reportes y dashboards interactivos</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon text-warning">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Gestión de Clientes</h4>
                        <p>Segmenta y analiza el comportamiento de tus clientes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="hero-section">
            <h2 class="text-center mb-4">
                <i class="fas fa-tachometer-alt text-primary me-2"></i>
                Dashboard
            </h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <h3 id="totalResponses">0</h3>
                        <p>Total Respuestas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h3 id="npsScore">0</h3>
                        <p>NPS Score</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-thumbs-up fa-2x mb-3"></i>
                        <h3 id="promoters">0</h3>
                        <p>Promotores</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-thumbs-down fa-2x mb-3"></i>
                        <h3 id="detractors">0</h3>
                        <p>Detractores</p>
                    </div>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="feature-card">
                        <h4>Distribución NPS</h4>
                        <canvas id="npsChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>Últimas Respuestas</h4>
                        <div id="recentResponses">
                            <p class="text-muted">Cargando...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="hero-section text-center">
            <h3 class="mb-4">Acciones Rápidas</h3>
            <div class="row">
                <div class="col-md-4">
                    <a href="survey/create.php" class="btn btn-primary btn-custom w-100 mb-3">
                        <i class="fas fa-plus me-2"></i>Nueva Encuesta
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reports/index.php" class="btn btn-success btn-custom w-100 mb-3">
                        <i class="fas fa-chart-bar me-2"></i>Ver Reportes
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="admin/dashboard.php" class="btn btn-warning btn-custom w-100 mb-3">
                        <i class="fas fa-cog me-2"></i>Panel Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; 2024 <?php echo htmlspecialchars($site_name); ?>. Todos los derechos reservados.</p>
            <p>
                <i class="fas fa-server me-2"></i>
                Sistema desarrollado con PHP, MySQL y Docker
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadNPSChart();
        });

        function loadDashboardData() {
            // Simulate loading data (replace with actual API calls)
            setTimeout(() => {
                document.getElementById('totalResponses').textContent = '1,234';
                document.getElementById('npsScore').textContent = '72';
                document.getElementById('promoters').textContent = '456';
                document.getElementById('detractors').textContent = '123';
                
                // Load recent responses
                document.getElementById('recentResponses').innerHTML = `
                    <div class="d-flex justify-content-between mb-2">
                        <span>Juan Pérez</span>
                        <span class="badge bg-success">9</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>María García</span>
                        <span class="badge bg-warning">7</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Carlos López</span>
                        <span class="badge bg-danger">4</span>
                    </div>
                `;
            }, 1000);
        }

        function loadNPSChart() {
            const ctx = document.getElementById('npsChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Promotores (9-10)', 'Pasivos (7-8)', 'Detractores (0-6)'],
                    datasets: [{
                        data: [456, 345, 123],
                        backgroundColor: [
                            '#27ae60',
                            '#f39c12',
                            '#e74c3c'
                        ],
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
        }
    </script>
</body>
</html> 