<?php
// includes/sidebar.php
?>
<!-- Sidebar -->
<div class="sidebar border-end bg-dark text-white" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom text-center py-3 px-1">
        <div class="d-flex justify-content-between align-items-center px-1 sidebar-brand-row">
            <div class="brand-content fw-bold text-uppercase d-flex align-items-center text-nowrap" style="font-size: 0.8rem; margin-left: -10px;">
                <!-- Logo image for collapsed state -->
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="Icon" class="collapse-only-icon img-fluid" style="display: none; max-height: 24px;">
                <!-- Logo and text for expanded state -->
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="VMS Tracker Logo" class="sidebar-brand-text img-fluid" style="max-height: 28px; margin-right: -8px;">
                <span class="sidebar-brand-text">VMS Tracker</span>
            </div>
            <button type="button" class="btn btn-sm text-white sidebar-toggle-btn d-none d-md-block flex-shrink-0" aria-label="Toggle sidebar" aria-expanded="true" title="Toggle Sidebar">
                <i class="bi bi-chevron-left toggle-icon fs-5"></i>
            </button>
        </div>
    </div>
    <div class="list-group list-group-flush mt-3">
        <a href="<?php echo BASE_URL; ?>dashboard/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
            <i class="bi bi-speedometer2 me-2"></i> <span class="nav-text">Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'vehicles') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Vehicles">
            <i class="bi bi-car-front me-2"></i> <span class="nav-text">Vehicles</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>services/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'services') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Service Records">
            <i class="bi bi-tools me-2"></i> <span class="nav-text">Service Records</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>maintenance/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'maintenance') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Maintenance">
            <i class="bi bi-calendar-check me-2"></i> <span class="nav-text">Maintenance</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>reports/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Reports">
            <i class="bi bi-graph-up me-2"></i> <span class="nav-text">Reports</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>settings/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0 py-3 <?php echo strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
            <i class="bi bi-gear me-2"></i> <span class="nav-text">Settings</span>
        </a>
    </div>
</div>
