<?php
// includes/sidebar.php
?>
<!-- Sidebar -->
<div class="sidebar border-end bg-dark text-white" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom text-center py-4 fs-5 fw-bold text-uppercase tracking-wider">
        <i class="bi bi-car-front-fill me-2 text-info"></i><span class="d-none d-md-inline">VMS Tracker</span>
    </div>
    <div class="list-group list-group-flush mt-3">
        <a href="<?php echo BASE_URL; ?>dashboard/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'vehicles') !== false ? 'active' : ''; ?>">
            <i class="bi bi-car-front me-2"></i> Vehicles
        </a>
        
        <a href="<?php echo BASE_URL; ?>services/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'services') !== false ? 'active' : ''; ?>">
            <i class="bi bi-tools me-2"></i> Service Records
        </a>
        
        <a href="<?php echo BASE_URL; ?>maintenance/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'maintenance') !== false ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check me-2"></i> Maintenance
        </a>
        
        <a href="<?php echo BASE_URL; ?>reports/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : ''; ?>">
            <i class="bi bi-graph-up me-2"></i> Reports
        </a>
        
        <a href="<?php echo BASE_URL; ?>settings/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'active' : ''; ?>">
            <i class="bi bi-gear me-2"></i> Settings
        </a>
    </div>
</div>
