<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

$campaign = null;
$message = '';

// Obtener ID de la campaña
$campaign_id = $_GET['id'] ?? 1;

if ($campaign_id && $conn) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND is_active = 1");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Procesar respuesta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $campaign) {
    $score = $_POST['score'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if ($score !== null && $score >= 0 && $score <= 10) {
        try {
            $stmt = $conn->prepare("INSERT INTO nps_responses (campaign_id, score, comment, email, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$campaign_id, $score, $comment, $email])) {
                $message = '¡Gracias por tu respuesta! ID: ' . $conn->lastInsertId();
            } else {
                $message = 'Error al enviar la respuesta: ' . implode(', ', $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $message = 'Error al enviar la respuesta: ' . $e->getMessage();
        }
    } else {
        $message = 'Por favor selecciona una puntuación válida';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($campaign): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($campaign['name']) ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label"><?= htmlspecialchars($campaign['question']) ?></label>
                                    <div class="d-flex justify-content-between">
                                        <?php for ($i = 0; $i <= 10; $i++): ?>
                                            <label class="btn btn-outline-primary">
                                                <input type="radio" name="score" value="<?= $i ?>" required>
                                                <?= $i ?>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Comentarios (opcional)</label>
                                    <textarea class="form-control" name="comment" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email (opcional)</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Enviar Respuesta</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">Campaña no encontrada</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 