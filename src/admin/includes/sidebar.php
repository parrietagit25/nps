<?php
// Obtener el nombre del archivo actual para marcar el enlace activo
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar p-3">
    <div class="text-center mb-4">
        <h4 class="text-white mb-0">
            <i class="fas fa-chart-line me-2"></i>
            NPS System
        </h4>
        <small class="text-white-50">Panel de Administración</small>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>
            Dashboard
        </a>
        <a class="nav-link <?= $current_page == 'campaigns.php' ? 'active' : '' ?>" href="campaigns.php">
            <i class="fas fa-bullhorn me-2"></i>
            Campañas
        </a>
        <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="users.php">
            <i class="fas fa-users me-2"></i>
            Usuarios
        </a>
        <a class="nav-link <?= $current_page == '#' ? 'active' : '' ?>" href="#">
            <i class="fas fa-envelope me-2"></i>
            Reportes
        </a>
        <hr class="text-white-50">
        <a class="nav-link" href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>
            Cerrar Sesión
        </a>
    </nav>
</div>
