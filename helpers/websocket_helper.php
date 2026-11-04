<?php
// helpers/websocket_helper.php
require_once __DIR__ . '/../config/app.php';

class WebSocketHelper {
    
    /**
     * Broadcast an event to a given channel (e.g. 'user_1', 'admin', 'all')
     *
     * @param string $channel Target channel
     * @param string $event Event name
     * @param array $data Event data payload
     * @return bool True if queued or delivered, false on failure
     */
    public static function broadcast($channel, $event, array $data = []) {
        $payload = [
            'secret'    => WS_SECRET_KEY,
            'action'    => 'broadcast',
            'channel'   => $channel,
            'event'     => $event,
            'data'      => $data,
            'timestamp' => microtime(true)
        ];

        $deliveredDirectly = self::sendDirectTcp($payload);

        // Always record to the realtime_events database queue so background server
        // can pick it up if direct socket was offline or busy.
        self::queueInDatabase($channel, $event, $data, $deliveredDirectly);

        return true;
    }

    /**
     * Send a notification specifically to a user
     */
    public static function notifyUser($userId, $title, $message, $type = 'REMINDER', array $extra = []) {
        $data = array_merge([
            'user_id'    => (int)$userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'timestamp'  => time()
        ], $extra);

        return self::broadcast('user_' . (int)$userId, 'new_notification', $data);
    }

    /**
     * Broadcast an alert or event to all administrators
     */
    public static function notifyAdmin($event, array $data = []) {
        return self::broadcast('admin', $event, $data);
    }

    /**
     * Broadcast to all connected clients
     */
    public static function notifyAll($event, array $data = []) {
        return self::broadcast('all', $event, $data);
    }

    /**
     * Notify clients when notifications are marked as read
     */
    public static function notificationRead($userId, $notificationId = 'all') {
        return self::broadcast('user_' . (int)$userId, 'notification_read', [
            'id'      => $notificationId,
            'user_id' => (int)$userId
        ]);
    }

    /**
     * Notify clients of service record additions or modifications
     */
    public static function serviceUpdated($userId, array $serviceData) {
        return self::broadcast('user_' . (int)$userId, 'service_updated', $serviceData);
    }

    /**
     * Notify clients of vehicle additions or modifications
     */
    public static function vehicleUpdated($userId, array $vehicleData) {
        return self::broadcast('user_' . (int)$userId, 'vehicle_updated', $vehicleData);
    }

    /**
     * Attempt direct non-blocking TCP delivery to local WebSocket server
     */
    private static function sendDirectTcp(array $payload) {
        try {
            // Short connection timeout (300ms) to ensure zero impact on web page performance
            $socket = @stream_socket_client(
                'tcp://127.0.0.1:' . WS_PORT,
                $errno,
                $errstr,
                0.3,
                STREAM_CLIENT_CONNECT
            );

            if ($socket) {
                stream_set_timeout($socket, 0, 300000); // 300ms read/write timeout
                $json = json_encode($payload) . "\n";
                @fwrite($socket, $json);
                @fclose($socket);
                return true;
            }
        } catch (\Throwable $e) {
            // Silently fall back to DB queue
        }
        return false;
    }

    /**
     * Persist event to the database queue for resilient processing
     */
    private static function queueInDatabase($channel, $event, array $data, $alreadyDelivered = false) {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("
                INSERT INTO realtime_events (channel, event, payload, processed_at) 
                VALUES (?, ?, ?, ?)
            ");
            $processedAt = $alreadyDelivered ? date('Y-m-d H:i:s') : null;
            $stmt->execute([
                $channel,
                $event,
                json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $processedAt
            ]);
        } catch (\Throwable $e) {
            error_log("WebSocketHelper DB queue error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
        }
    }
}

