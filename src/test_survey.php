<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $campaign_id = $_POST['campaign_id'] ?? 1;
    $score = $_POST['score'] ?? 10;
    $comment = $_POST['comment'] ?? 'Test comment';
    $email = $_POST['email'] ?? 'test@test.com';
    
    try {
        $stmt = $conn->prepare("INSERT INTO nps_responses (campaign_id, score, comment, email, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$campaign_id, $score, $comment, $email])) {
            echo "SUCCESS: Response inserted with ID: " . $conn->lastInsertId();
        } else {
            echo "ERROR: " . implode(', ', $stmt->errorInfo());
        }
    } catch (PDOException $e) {
        echo "EXCEPTION: " . $e->getMessage();
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Survey</title>
</head>
<body>
    <h1>Test Survey Form</h1>
    <form method="POST">
        <p>Campaign ID: <input type="number" name="campaign_id" value="1"></p>
        <p>Score: <input type="number" name="score" value="10" min="0" max="10"></p>
        <p>Comment: <textarea name="comment">Test comment</textarea></p>
        <p>Email: <input type="email" name="email" value="test@test.com"></p>
        <button type="submit">Test Submit</button>
    </form>
</body>
</html> 