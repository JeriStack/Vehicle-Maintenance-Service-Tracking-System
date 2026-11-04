<?php
// reports/index.php
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-graph-up text-primary me-2"></i>Reports & Analytics</h1>
</div>

<div class="row g-4 mb-4">
    <!-- Maintenance Summary -->
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-elevate">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                        <i class="bi bi-clipboard-data fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Maintenance Summary</h5>
                </div>
                <p class="text-muted small mb-4">Overview of total, completed, upcoming, and overdue services across all vehicles.</p>
                <a href="<?php echo BASE_URL; ?>reports/maintenance.php" class="btn btn-outline-primary w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <!-- Service History -->
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-elevate">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Service History</h5>
                </div>
                <p class="text-muted small mb-4">Detailed chronological list of all service activities. Filter by vehicle, date, and type.</p>
                <a href="<?php echo BASE_URL; ?>reports/service_history.php" class="btn btn-outline-info w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <!-- Cost Report -->
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-elevate">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                        <i class="bi bi-currency-dollar fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Cost Analysis</h5>
                </div>
                <p class="text-muted small mb-4">Analyze your maintenance spending by vehicle, service type, and time period.</p>
                <a href="<?php echo BASE_URL; ?>reports/costs.php" class="btn btn-outline-success w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <!-- Monthly Summary -->
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-elevate">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning text-dark rounded p-3 me-3">
                        <i class="bi bi-calendar-month fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Monthly Summary</h5>
                </div>
                <p class="text-muted small mb-4">Month-by-month breakdown of service counts and associated costs.</p>
                <a href="<?php echo BASE_URL; ?>reports/monthly.php" class="btn btn-outline-warning text-dark w-100">View Report</a>
            </div>
        </div>
    </div>
    
    <!-- Yearly Summary -->
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-elevate">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded p-3 me-3">
                        <i class="bi bi-calendar3 fs-3"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Yearly Summary</h5>
                </div>
                <p class="text-muted small mb-4">Annual overview of maintenance activities for long-term tracking.</p>
                <a href="<?php echo BASE_URL; ?>reports/yearly.php" class="btn btn-outline-secondary w-100">View Report</a>
            </div>
        </div>
    </div>
</div>

<style>
.hover-elevate {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-elevate:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
