<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NPS Project - Docker PHP MySQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 NPS Project - Docker Setup</h1>
        
        <div class="status success">
            <h2>✅ PHP está funcionando correctamente</h2>
            <p><strong>Versión de PHP:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></p>
            <p><strong>Fecha y hora:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <div class="status info">
            <h3>📋 Información del Sistema</h3>
            <ul>
                <li><strong>Extensiones PHP disponibles:</strong></li>
                <ul>
                    <?php
                    $extensions = ['pdo_mysql', 'mysqli', 'mbstring', 'gd', 'zip'];
                    foreach ($extensions as $ext) {
                        $status = extension_loaded($ext) ? '✅' : '❌';
                        echo "<li>$status $ext</li>";
                    }
                    ?>
                </ul>
            </ul>
        </div>

        <div class="status info">
            <h3>🔧 Configuración de Base de Datos</h3>
            <p><strong>Host:</strong> <?php echo getenv('MYSQL_HOST') ?: 'mysql'; ?></p>
            <p><strong>Base de datos:</strong> <?php echo getenv('MYSQL_DATABASE') ?: 'nps_db'; ?></p>
            <p><strong>Usuario:</strong> <?php echo getenv('MYSQL_USER') ?: 'nps_user'; ?></p>
        </div>

        <div class="status info">
            <h3>🌐 Acceso a Servicios</h3>
            <ul>
                <li><strong>Aplicación PHP:</strong> <a href="http://localhost" target="_blank">http://localhost</a> (puerto 80)</li>
                <li><strong>phpMyAdmin:</strong> <a href="http://localhost:8080" target="_blank">http://localhost:8080</a></li>
                <li><strong>MySQL:</strong> localhost:3306</li>
            </ul>
        </div>

        <div class="status info">
            <h3>📝 Próximos Pasos</h3>
            <ol>
                <li>Desarrolla tu aplicación PHP en el directorio <code>src/</code></li>
                <li>Configura la conexión a la base de datos</li>
                <li>Usa <code>docker-compose up -d</code> para iniciar los servicios</li>
                <li>Usa <code>docker-compose down</code> para detener los servicios</li>
            </ol>
        </div>
    </div>
</body>
</html> 