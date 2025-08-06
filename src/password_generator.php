<?php
session_start();
require_once 'config/database.php';

$message = '';
$messageType = '';
$generated_hash = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $generated_hash = $hash;
            $message = 'Hash generado correctamente';
            $messageType = 'success';
        } else {
            $message = 'Por favor ingresa una contraseña';
            $messageType = 'danger';
        }
    } elseif ($action === 'verify') {
        $hash = $_POST['hash'] ?? '';
        $test_password = $_POST['test_password'] ?? '';
        
        if (!empty($hash) && !empty($test_password)) {
            if (password_verify($test_password, $hash)) {
                $message = '✅ Contraseña correcta - Hash válido';
                $messageType = 'success';
            } else {
                $message = '❌ Contraseña incorrecta - Hash inválido';
                $messageType = 'danger';
            }
        } else {
            $message = 'Por favor ingresa tanto el hash como la contraseña a verificar';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Contraseñas - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .card {
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Generador de Contraseñas Hash
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Generar Hash -->
                        <div class="mb-4">
                            <h5><i class="fas fa-plus-circle me-2"></i>Generar Hash</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña:</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <input type="hidden" name="action" value="generate">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-magic me-2"></i>Generar Hash
                                </button>
                            </form>
                            
                            <?php if ($generated_hash): ?>
                                <div class="mt-3">
                                    <label class="form-label">Hash generado:</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($generated_hash) ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Verificar Hash -->
                        <div class="mb-4">
                            <h5><i class="fas fa-check-circle me-2"></i>Verificar Hash</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="hash" class="form-label">Hash:</label>
                                    <textarea class="form-control" id="hash" name="hash" rows="3" placeholder="Pega el hash aquí"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="test_password" class="form-label">Contraseña a verificar:</label>
                                    <input type="password" class="form-control" id="test_password" name="test_password">
                                </div>
                                <input type="hidden" name="action" value="verify">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-search me-2"></i>Verificar
                                </button>
                            </form>
                        </div>
                        
                        <hr>
                        
                        <!-- Información -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Información:</h6>
                            <ul class="mb-0">
                                <li>Esta herramienta genera hashes seguros para contraseñas</li>
                                <li>Los hashes son compatibles con PHP password_hash()</li>
                                <li>Puedes usar estos hashes en la base de datos</li>
                                <li>Contraseña por defecto del admin: <strong>admin123</strong></li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home me-2"></i>Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToClipboard(button) {
            const input = button.parentElement.querySelector('input');
            input.select();
            document.execCommand('copy');
            
            // Cambiar temporalmente el texto del botón
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>
</html>
