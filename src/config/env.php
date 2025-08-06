<?php
// Cargar variables de entorno desde archivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        error_log("ENV Debug - Archivo .env NO encontrado en: " . $path);
        return false;
    }
    
    error_log("ENV Debug - Archivo .env ENCONTRADO en: " . $path);
    
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
            error_log("ENV Debug - Variable cargada: " . $name . " = " . $value);
        }
    }
    
    return true;
}

// Cargar variables de entorno directamente desde .env
$envFile = __DIR__ . '/../.env';
loadEnv($envFile);

// Verificar variables específicas
error_log("ENV Debug - SENDGRID_API_KEY: " . ($_ENV['SENDGRID_API_KEY'] ?? 'NO_CONFIGURADA'));
error_log("ENV Debug - FROM_EMAIL: " . ($_ENV['FROM_EMAIL'] ?? 'NO_CONFIGURADA'));
error_log("ENV Debug - FROM_NAME: " . ($_ENV['FROM_NAME'] ?? 'NO_CONFIGURADA'));
?>
