<?php
// config/websocket.php
require_once __DIR__ . '/constants.php';

// WebSocket Server Configuration
if (!defined('WS_HOST')) {
    define('WS_HOST', '0.0.0.0');
}

if (!defined('WS_PORT')) {
    define('WS_PORT', 8080);
}

// Client connection settings (used by frontend JavaScript)
if (!defined('WS_CLIENT_HOST')) {
    define('WS_CLIENT_HOST', 'localhost');
}

if (!defined('WS_CLIENT_PORT')) {
    define('WS_CLIENT_PORT', 8080);
}

// Secret key for internal IPC/broadcaster authentication
if (!defined('WS_SECRET_KEY')) {
    define('WS_SECRET_KEY', 'vms_websocket_secure_token_' . md5(__DIR__));
}

// Polling interval in microseconds for the WebSocket server's DB event queue (200ms = 200000us)
if (!defined('WS_QUEUE_INTERVAL_US')) {
    define('WS_QUEUE_INTERVAL_US', 200000);
}

// Client fallback polling interval in milliseconds when WebSocket is disconnected
if (!defined('WS_FALLBACK_POLL_MS')) {
    define('WS_FALLBACK_POLL_MS', 15000);
}

