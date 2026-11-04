<?php
// helpers/auth_helper.php
// Proxy to core auth functions

require_once __DIR__ . '/../includes/auth.php';

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
