<?php
// admin/audit_logs.php
$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $pdo = Database::getInstance();
    
    // Pagination
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Total count
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM audit_logs");
    $totalLogs = $stmtCount->fetchColumn();
    $totalPages = ceil($totalLogs / $limit);
    
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name, u.email
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin Audit Logs Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load audit logs.";
    $logs = [];
    $totalPages = 1;
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="m-0 fw-bold text-dark">System Audit Logs</h6>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th class="pe-4">Record ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No audit logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4 text-muted small">
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($log['full_name']): ?>
                                        <div class="fw-medium text-dark"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($log['email']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">System / Unknown (ID: <?php echo $log['user_id']; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><code class="text-muted"><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></code></td>
                                <td class="pe-4"><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white border-top py-3 d-flex justify-content-center">
        <nav aria-label="Audit logs pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
