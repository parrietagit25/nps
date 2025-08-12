<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';
require_once '../config/encryption.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$campaign_id = $_POST['campaign_id'] ?? null;

if (!$campaign_id) {
    echo json_encode(['success' => false, 'message' => 'ID de campaña requerido']);
    exit;
}

try {
    // Verificar que la campaña existe y pertenece al usuario o es admin
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar que la campaña existe y está activa
    $stmt = $conn->prepare("SELECT id, is_active FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        throw new Exception('Campaña no encontrada');
    }
    
    if (!$campaign['is_active']) {
        throw new Exception('La campaña no está activa');
    }
    
    // Generar token encriptado
    $encryption = new Encryption();
    $token = $encryption->encryptCampaignData($campaign_id, 168); // 7 días de validez
    
    // Registrar el enlace generado en la base de datos para auditoría
    $stmt = $conn->prepare("INSERT INTO campaign_links (campaign_id, generated_by, token, expires_at, created_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())");
    $stmt->execute([$campaign_id, $_SESSION['user_id'], $token]);
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'expires_at' => date('Y-m-d H:i:s', time() + (168 * 3600))
    ]);
    
} catch (Exception $e) {
    error_log("Error generando enlace seguro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
