<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$messageType = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Validar que el usuario no exista
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $message = 'El nombre de usuario ya existe';
            $messageType = 'danger';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
            
            if ($stmt->execute([$username, $password_hash, $full_name, $email, $role])) {
                $message = 'Usuario creado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al crear el usuario';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'update') {
        $user_id = $_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
        
        if ($stmt->execute([$full_name, $email, $role, $is_active, $user_id])) {
            $message = 'Usuario actualizado exitosamente';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar el usuario';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'];
        
        // No permitir eliminar el usuario actual
        if ($user_id == $_SESSION['user_id']) {
            $message = 'No puedes eliminar tu propia cuenta';
            $messageType = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            
            if ($stmt->execute([$user_id])) {
                $message = 'Usuario eliminado exitosamente';
                $messageType = 'success';
            } else {
                $message = 'Error al eliminar el usuario';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'change_password') {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($stmt->execute([$password_hash, $user_id])) {
            $message = 'Contraseña actualizada exitosamente';
            $messageType = 'success';
        } else {
            $message = 'Error al actualizar la contraseña';
            $messageType = 'danger';
        }
    }
}

// Obtener lista de usuarios
$users = [];
if ($conn) {
    $stmt = $conn->query("SELECT id, username, full_name, email, role, is_active, created_at, last_login FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - NPS System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        .user-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">
                                <i class="fas fa-users me-2"></i>
                                Gestión de Usuarios
                            </h2>
                            <p class="text-muted mb-0">Administra los usuarios del sistema</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="fas fa-plus me-2"></i>
                            Nuevo Usuario
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Users List -->
                    <div class="row">
                        <?php foreach ($users as $user): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="user-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
                                            <p class="text-muted mb-0">@<?= htmlspecialchars($user['username']) ?></p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editUser(<?= $user['id'] ?>)">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="changePassword(<?= $user['id'] ?>)">
                                                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                                </a></li>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')">
                                                        <i class="fas fa-trash me-2"></i>Eliminar
                                                    </a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> me-2">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $user['is_active'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="small text-muted">
                                        <div><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></div>
                                        <div><i class="fas fa-calendar me-1"></i>Creado: <?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                                        <?php if ($user['last_login']): ?>
                                            <div><i class="fas fa-clock me-1"></i>Último acceso: <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rol *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>
                        Editar Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Rol *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Usuario Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>
                        Cambiar Contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="user_id" id="change_password_user_id">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        
                        <p>¿Estás seguro de que quieres eliminar al usuario <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función para editar usuario
        function editUser(userId) {
            // Aquí cargarías los datos del usuario en el modal
            document.getElementById('edit_user_id').value = userId;
            // Cargar datos del usuario via AJAX o pasar datos desde PHP
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        // Función para cambiar contraseña
        function changePassword(userId) {
            document.getElementById('change_password_user_id').value = userId;
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        }
        
        // Función para eliminar usuario
        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
        
        // Validar confirmación de contraseña
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            const submitBtn = document.querySelector('#changePasswordModal .btn-primary');
            
            if (password === confirm) {
                submitBtn.disabled = false;
                this.setCustomValidity('');
            } else {
                submitBtn.disabled = true;
                this.setCustomValidity('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html> 