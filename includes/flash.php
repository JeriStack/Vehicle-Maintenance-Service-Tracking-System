<?php
// includes/flash.php

function displayFlashMessages() {
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        $session_key = 'flash_' . $type;
        if (isset($_SESSION[$session_key])) {
            $alertClass = $type === 'error' ? 'danger' : $type;
            echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION[$session_key], ENT_QUOTES, 'UTF-8');
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION[$session_key]);
        }
    }
}
