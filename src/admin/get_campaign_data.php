<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$campaign_id = $_POST['campaign_id'] ?? null;

if (!$campaign_id || !$conn) {
    echo json_encode(['success' => false, 'message' => 'ID de campaña inválido']);
    exit;
}

try {
    // Obtener datos de la campaña
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campaign) {
        echo json_encode(['success' => false, 'message' => 'Campaña no encontrada']);
        exit;
    }
    
    // Obtener preguntas de la campaña
    $stmt = $conn->prepare("SELECT * FROM campaign_questions WHERE campaign_id = ? ORDER BY order_index");
    $stmt->execute([$campaign_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar las opciones JSON para cada pregunta
    foreach ($questions as &$question) {
        if (isset($question['options']) && $question['options']) {
            $question['options'] = json_decode($question['options'], true);
        } else {
            $question['options'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'campaign' => $campaign,
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
