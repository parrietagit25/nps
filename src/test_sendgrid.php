<?php
require_once 'config/sendgrid.php';

// Test SendGrid configuration
try {
    $sendgrid_service = new SendGridService();
    
    // Test data
    $test_campaign_data = [
        "name" => "Test Campaign",
        "description" => "This is a test campaign",
        "question" => "¿Qué tan probable es que recomiendes nuestro servicio?"
    ];
    
    $test_email = "test@example.com"; // Replace with a real email for testing
    
    echo "<h2>SendGrid Test</h2>";
    echo "<p>Testing SendGrid configuration...</p>";
    
    // Test single email
    $result = $sendgrid_service->sendSurveyEmail(1, $test_email, $test_campaign_data);
    
    echo "<h3>Test Result:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result["status"] === "success") {
        echo "<p style='color: green;'>✅ SendGrid is working correctly!</p>";
    } else {
        echo "<p style='color: red;'>❌ SendGrid error: " . $result["message"] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
