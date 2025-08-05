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

$message = '';
$messageType = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $question = trim($_POST['question']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO campaigns (name, description, question, start_date, end_date, is_active, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$name, $description, $question, $start_date, $end_date, $is_active, $_SESSION['user_id']])) {
            $message = 'Campaña creada exitosamente';
            $messageType = 'success';
        } else {
            $message = 'Error al crear la campaña';
            $messageType = 'danger';
        }
    } elseif ($action === 'update') {
        $campaign_id = $_POST['campaign_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $question = trim($_POST['question']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE campaigns SET name = ?, description = ?, question = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
        
        if ($stmt->execute([$name, $description, $question, $start_date, $end_date, $is_active, $campaign_id])) {
            $message = 'Campaña actualizada exitosamente';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar la campaña';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $campaign_id = $_POST['campaign_id'];
        
        // Verificar si hay respuestas asociadas
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM nps_responses WHERE campaign_id = ?");
        $stmt->execute([$campaign_id]);
        $responseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($responseCount > 0) {
            $message = 'No se puede eliminar la campaña porque tiene respuestas asociadas';
            $messageType = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
            
            if ($stmt->execute([$campaign_id])) {
                $message = 'Campaña eliminada exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar la campaña';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'toggle_status') {
        $campaign_id = $_POST['campaign_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE campaigns SET is_active = ? WHERE id = ?");
        
        if ($stmt->execute([$new_status, $campaign_id])) {
            $message = 'Estado de la campaña actualizado';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar el estado';
            $messageType = 'danger';
        }
    }
}

// Obtener lista de campañas con estadísticas
$campaigns = [];
if ($conn) {
    $stmt = $conn->query("
        SELECT 
            c.*,
            u.full_name as created_by_name,
            COUNT(r.id) as response_count,
            AVG(r.score) as avg_score,
            COUNT(CASE WHEN r.score >= 9 THEN 1 END) as promoters,
            COUNT(CASE WHEN r.score BETWEEN 7 AND 8 THEN 1 END) as passives,
            COUNT(CASE WHEN r.score <= 6 THEN 1 END) as detractors
        FROM campaigns c
        LEFT JOIN users u ON c.created_by = u.id
        LEFT JOIN nps_responses r ON c.id = r.campaign_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Campañas - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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
        
        .campaign-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
            border-left: 4px solid #667eea;
        }
        
        .campaign-card:hover {
            transform: translateY(-2px);
        }
        
        .campaign-card.active {
            border-left-color: #28a745;
        }
        
        .campaign-card.inactive {
            border-left-color: #dc3545;
        }
        
        .nps-score {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .status-badge {
            font-size: 0.8rem;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        <a class="nav-link active" href="campaigns.php">
                            <i class="fas fa-bullhorn me-2"></i>
                            Campañas
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reportes
                        </a>
                        <a class="nav-link" href="users.php">
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
                            <h2 class="mb-1">
                                <i class="fas fa-bullhorn me-2"></i>
                                Gestión de Campañas
                            </h2>
                            <p class="text-muted mb-0">Crea y administra encuestas NPS</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                            <i class="fas fa-plus me-2"></i>
                            Nueva Campaña
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Campaigns List -->
                    <div class="row">
                        <?php foreach ($campaigns as $campaign): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="campaign-card p-3 <?= $campaign['is_active'] ? 'active' : 'inactive' ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($campaign['name']) ?></h5>
                                            <p class="text-muted mb-0 small"><?= htmlspecialchars($campaign['description']) ?></p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editCampaign(<?= $campaign['id'] ?>)">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="viewResponses(<?= $campaign['id'] ?>)">
                                                    <i class="fas fa-chart-bar me-2"></i>Ver Respuestas
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="getShareLink(<?= $campaign['id'] ?>)">
                                                    <i class="fas fa-share me-2"></i>Compartir
                                                </a></li>
                                                <?php if ($campaign['response_count'] == 0): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteCampaign(<?= $campaign['id'] ?>, '<?= htmlspecialchars($campaign['name']) ?>')">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-<?= $campaign['is_active'] ? 'success' : 'secondary' ?> status-badge me-2">
                                            <?= $campaign['is_active'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                        <span class="badge bg-info status-badge">
                                            <?= $campaign['response_count'] ?> respuestas
                                        </span>
                                    </div>
                                    
                                    <?php if ($campaign['response_count'] > 0): ?>
                                        <div class="mb-3">
                                            <div class="nps-score text-center mb-2">
                                                <?= number_format($campaign['avg_score'], 1) ?>
                                            </div>
                                            <div class="row text-center small">
                                                <div class="col-4">
                                                    <div class="text-success">
                                                        <i class="fas fa-thumbs-up"></i><br>
                                                        <?= $campaign['promoters'] ?>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-warning">
                                                        <i class="fas fa-minus"></i><br>
                                                        <?= $campaign['passives'] ?>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-danger">
                                                        <i class="fas fa-thumbs-down"></i><br>
                                                        <?= $campaign['detractors'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="small text-muted">
                                        <div><i class="fas fa-user me-1"></i>Creada por: <?= htmlspecialchars($campaign['created_by_name']) ?></div>
                                        <div><i class="fas fa-calendar me-1"></i>Inicio: <?= date('d/m/Y', strtotime($campaign['start_date'])) ?></div>
                                        <div><i class="fas fa-calendar me-1"></i>Fin: <?= date('d/m/Y', strtotime($campaign['end_date'])) ?></div>
                                        <div><i class="fas fa-clock me-1"></i>Creada: <?= date('d/m/Y', strtotime($campaign['created_at'])) ?></div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $campaign['is_active'] ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-sm btn-<?= $campaign['is_active'] ? 'warning' : 'success' ?>">
                                                <i class="fas fa-<?= $campaign['is_active'] ? 'pause' : 'play' ?> me-1"></i>
                                                <?= $campaign['is_active'] ? 'Pausar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Campaign Modal -->
    <div class="modal fade" id="createCampaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bullhorn me-2"></i>
                        Nueva Campaña NPS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Campaña *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Fecha de Fin *</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">
                                            Campaña Activa
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe el propósito de esta campaña..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="question" class="form-label">Pregunta NPS *</label>
                            <textarea class="form-control" id="question" name="question" rows="3" required placeholder="¿Qué tan probable es que recomiendes nuestro servicio a un amigo o colega?"></textarea>
                            <div class="form-text">Esta será la pregunta que verán los encuestados</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Campaña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Campaign Modal -->
    <div class="modal fade" id="editCampaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Editar Campaña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="campaign_id" id="edit_campaign_id">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Nombre de la Campaña *</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_start_date" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="edit_end_date" class="form-label">Fecha de Fin *</label>
                                    <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                        <label class="form-check-label" for="edit_is_active">
                                            Campaña Activa
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_question" class="form-label">Pregunta NPS *</label>
                            <textarea class="form-control" id="edit_question" name="question" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Campaign Modal -->
    <div class="modal fade" id="deleteCampaignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="campaign_id" id="delete_campaign_id">
                        
                        <p>¿Estás seguro de que quieres eliminar la campaña <strong id="delete_campaign_name"></strong>?</p>
                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Share Link Modal -->
    <div class="modal fade" id="shareLinkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share me-2"></i>
                        Compartir Campaña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Comparte este enlace para que los usuarios puedan responder la encuesta:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="share_link" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary me-2" onclick="shareOnWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </button>
                        <button class="btn btn-info me-2" onclick="shareOnEmail()">
                            <i class="fas fa-envelope me-2"></i>Email
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para editar campaña
        function editCampaign(campaignId) {
            // Aquí cargarías los datos de la campaña en el modal
            document.getElementById('edit_campaign_id').value = campaignId;
            new bootstrap.Modal(document.getElementById('editCampaignModal')).show();
        }
        
        // Función para eliminar campaña
        function deleteCampaign(campaignId, campaignName) {
            document.getElementById('delete_campaign_id').value = campaignId;
            document.getElementById('delete_campaign_name').textContent = campaignName;
            new bootstrap.Modal(document.getElementById('deleteCampaignModal')).show();
        }
        
        // Función para obtener enlace de compartir
        function getShareLink(campaignId) {
            const baseUrl = window.location.origin;
            const shareUrl = `${baseUrl}/survey.php?id=${campaignId}`;
            document.getElementById('share_link').value = shareUrl;
            new bootstrap.Modal(document.getElementById('shareLinkModal')).show();
        }
        
        // Función para copiar al portapapeles
        function copyToClipboard() {
            const shareLink = document.getElementById('share_link');
            shareLink.select();
            document.execCommand('copy');
            alert('Enlace copiado al portapapeles');
        }
        
        // Función para compartir en WhatsApp
        function shareOnWhatsApp() {
            const shareLink = document.getElementById('share_link').value;
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent('Participa en nuestra encuesta: ' + shareLink)}`;
            window.open(whatsappUrl, '_blank');
        }
        
        // Función para compartir por email
        function shareOnEmail() {
            const shareLink = document.getElementById('share_link').value;
            const subject = 'Encuesta NPS';
            const body = `Hola,\n\nTe invitamos a participar en nuestra encuesta de satisfacción:\n\n${shareLink}\n\nGracias por tu tiempo.`;
            const mailtoUrl = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
            window.open(mailtoUrl);
        }
        
        // Función para ver respuestas (placeholder)
        function viewResponses(campaignId) {
            alert('Función de ver respuestas próximamente');
        }
        
        // Establecer fecha mínima para end_date
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
        });
        
        document.getElementById('edit_start_date').addEventListener('change', function() {
            document.getElementById('edit_end_date').min = this.value;
        });
    </script>
</body>
</html> 