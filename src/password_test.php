<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Encriptación de Contraseñas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-key me-2"></i>
                            Test de Encriptación de Contraseñas
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $message = '';
                        $messageType = '';
                        $hashedPassword = '';
                        $verificationResult = '';

                        if ($_POST) {
                            $password = $_POST['password'] ?? '';
                            $action = $_POST['action'] ?? '';
                            $testPassword = $_POST['test_password'] ?? '';
                            $storedHash = $_POST['stored_hash'] ?? '';

                            if ($action === 'hash' && !empty($password)) {
                                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                $message = "Contraseña encriptada exitosamente";
                                $messageType = 'success';
                            } elseif ($action === 'verify' && !empty($testPassword) && !empty($storedHash)) {
                                if (password_verify($testPassword, $storedHash)) {
                                    $verificationResult = "✅ CONTRASEÑA CORRECTA";
                                    $message = "La contraseña coincide con el hash";
                                    $messageType = 'success';
                                } else {
                                    $verificationResult = "❌ CONTRASEÑA INCORRECTA";
                                    $message = "La contraseña NO coincide con el hash";
                                    $messageType = 'danger';
                                }
                            }
                        }
                        ?>

                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario para Encriptar -->
                        <div class="mb-4">
                            <h5><i class="fas fa-lock me-2"></i>Encriptar Contraseña</h5>
                            <form method="POST" class="row g-3">
                                <div class="col-md-8">
                                    <label for="password" class="form-label">Contraseña a encriptar:</label>
                                    <input type="text" class="form-control" id="password" name="password" 
                                           placeholder="Ingresa la contraseña" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" name="action" value="hash" class="btn btn-primary w-100">
                                        <i class="fas fa-hashtag me-2"></i>Encriptar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php if ($hashedPassword): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-code me-2"></i>Hash Generado:</h6>
                                <div class="bg-dark text-light p-3 rounded">
                                    <code class="text-break"><?= htmlspecialchars($hashedPassword) ?></code>
                                </div>
                                <small class="text-muted">Copia este hash para insertarlo en la base de datos</small>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Formulario para Verificar -->
                        <div class="mb-4">
                            <h5><i class="fas fa-check-circle me-2"></i>Verificar Contraseña</h5>
                            <form method="POST" class="row g-3">
                                <div class="col-md-6">
                                    <label for="test_password" class="form-label">Contraseña a verificar:</label>
                                    <input type="text" class="form-control" id="test_password" name="test_password" 
                                           placeholder="Ingresa la contraseña" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="stored_hash" class="form-label">Hash almacenado:</label>
                                    <input type="text" class="form-control" id="stored_hash" name="stored_hash" 
                                           placeholder="Pega el hash aquí" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="action" value="verify" class="btn btn-success">
                                        <i class="fas fa-search me-2"></i>Verificar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php if ($verificationResult): ?>
                            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>">
                                <h6><?= $verificationResult ?></h6>
                                <small><?= $message ?></small>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Información de la Base de Datos -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-database me-2"></i>Información de la Base de Datos</h6>
                            <p class="mb-2">Para actualizar la contraseña en la base de datos, ejecuta:</p>
                            <div class="bg-dark text-light p-2 rounded">
                                <code>UPDATE users SET password_hash = 'TU_HASH_AQUI' WHERE username = 'admin';</code>
                            </div>
                        </div>

                        <!-- Ejemplo de Usuarios -->
                        <div class="mt-4">
                            <h6><i class="fas fa-users me-2"></i>Usuarios de Prueba</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Contraseña</th>
                                            <th>Hash (PASSWORD_DEFAULT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>admin</td>
                                            <td>admin123</td>
                                            <td><code class="text-break">$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</code></td>
                                        </tr>
                                        <tr>
                                            <td>user</td>
                                            <td>password</td>
                                            <td><code class="text-break">$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 