<?php
// websocket/test_client.php
/**
 * Test script to broadcast a simulated notification or system alert
 * Usage:
 *   php websocket/test_client.php [user_id] [title] [message] [type]
 * Example:
 *   php websocket/test_client.php 1 "Oil Change Due" "Your Toyota Vios is due for an oil change!" "DUE"
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

$userId = isset($argv[1]) ? (int)$argv[1] : 1;
$title = isset($argv[2]) ? $argv[2] : "Live Maintenance Alert";
$message = isset($argv[3]) ? $argv[3] : "This is a real-time WebSocket test alert delivered to User #{$userId}.";
$type = isset($argv[4]) ? strtoupper($argv[4]) : "REMINDER";

echo "Dispatching real-time notification...\n";
echo "  Target User ID: {$userId}\n";
echo "  Title:          {$title}\n";
echo "  Message:        {$message}\n";
echo "  Type:           {$type}\n";

$result = WebSocketHelper::notifyUser($userId, $title, $message, $type, [
    'test' => true,
    'source' => 'cli_test_client'
]);

echo "Result: " . ($result ? "QUEUED / SENT SUCCESSFULLY" : "FAILED") . "\n";
echo "Check your browser window or WebSocket server console!\n";

