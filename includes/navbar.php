<?php
// includes/navbar.php
?>
<!-- Top navigation-->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3 shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
        
        <div class="d-none d-md-block ms-3">
            <h5 class="mb-0 text-dark fw-bold"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h5>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center gap-2">
                <!-- Light / Dark Mode Toggle -->
                <li class="nav-item">
                    <button type="button" class="nav-link vms-theme-toggle border-0 bg-transparent d-inline-flex align-items-center justify-content-center" id="themeToggleBtn">
                        <span class="theme-option-dark">
                            <i class="bi bi-moon-stars fs-5" aria-hidden="true"></i>
                            <span class="visually-hidden">Switch to dark theme</span>
                        </span>
                        <span class="theme-option-light">
                            <i class="bi bi-sun fs-5" aria-hidden="true"></i>
                            <span class="visually-hidden">Switch to light theme</span>
                        </span>
                    </button>
                </li>

                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link text-dark position-relative" href="#" id="navbarDropdownNotif" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span id="notifBadge" class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle <?php echo (isset($unreadCount) && $unreadCount > 0) ? '' : 'd-none'; ?>">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0" id="navbarNotifDropdown" aria-labelledby="navbarDropdownNotif" style="width: 320px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center border-bottom py-2">
                            <span class="fw-bold text-dark">Notifications</span>
                            <span id="notifBadgeCount" class="badge bg-primary rounded-pill"><?php echo (int)($unreadCount ?? 0); ?> unread</span>
                        </div>
                        <div id="navbarNotifList">
                        <?php
                        // Fetch 3 most recent notifications if DB is available
                        $notifs = [];
                        if (isset($pdo)) {
                            $nstmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
                            $nstmt->execute([$_SESSION['user_id']]);
                            $notifs = $nstmt->fetchAll();
                        }
                        
                        if (count($notifs) > 0):
                            foreach ($notifs as $n):
                                $bg = $n['is_read'] ? '' : 'bg-light';
                        ?>
                            <a class="dropdown-item py-2 border-bottom notif-dropdown-item <?php echo $bg; ?>" data-notif-id="<?php echo $n['id']; ?>" href="<?php echo BASE_URL; ?>notifications/index.php">
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="fw-bold text-primary"><?php echo htmlspecialchars($n['title']); ?></small>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($n['created_at'])); ?></small>
                                </div>
                                <small class="text-wrap text-muted d-block"><?php echo htmlspecialchars(substr($n['message'], 0, 50)) . '...'; ?></small>
                            </a>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div id="notifEmptyPlaceholder" class="dropdown-item text-muted text-center py-3">No new notifications</div>
                        <?php endif; ?>
                        </div>
                        <a class="dropdown-item text-center fw-bold text-primary py-2 bg-white" href="<?php echo BASE_URL; ?>notifications/index.php">View All Notifications</a>
                    </div>
                </li>
                
                <!-- User Profile (Avatar Only) -->
                <li class="nav-item dropdown ms-1">
                    <a class="nav-link d-flex align-items-center p-0" id="navbarDropdownUser" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Account menu for <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>">
                        <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-semibold" style="width: 36px; height: 36px;">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="navbarDropdownUser">
                        <div class="dropdown-header border-bottom py-2">
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                            <small class="text-muted"><?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'Administrator' : 'Vehicle Owner'; ?></small>
                        </div>
                        <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>settings/profile.php"><i class="bi bi-person me-2 text-muted"></i> Profile</a>
                        <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>settings/password.php"><i class="bi bi-key me-2 text-muted"></i> Change Password</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
