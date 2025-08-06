<?php
// Cargar variables de entorno desde archivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
    
    return true;
}

// Cargar variables de entorno
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
}

// Configuración por defecto para SendGrid
if (!isset($_ENV['SENDGRID_API_KEY'])) {
    $_ENV['SENDGRID_API_KEY'] = 'SG.your-api-key-here';
}

if (!isset($_ENV['FROM_EMAIL'])) {
    $_ENV['FROM_EMAIL'] = 'notificaciones@grupopcr.com.pa';
}

if (!isset($_ENV['FROM_NAME'])) {
    $_ENV['FROM_NAME'] = 'PCR notificaciones';
}
