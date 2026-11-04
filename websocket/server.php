<?php
// websocket/server.php
/**
 * Vehicle Maintenance Service Tracking System - Real-time WebSocket Server
 * Native RFC 6455 WebSocket Implementation in PHP (No external dependencies)
 *
 * Usage:
 *   php websocket/server.php
 * or run start_websocket.bat on Windows
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);
ob_implicit_flush();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/websocket.php';

class WebSocketServer {
    private $host;
    private $port;
    private $masterSocket;
    private $clients = [];        // Socket ID => resource
    private $clientInfo = [];     // Socket ID => array metadata (handshake, channels, user_id, last_ping)
    private $channelSubscribers = []; // Channel name => array of Socket IDs
    private $dbPdo = null;
    private $lastQueueCheck = 0;
    private $lastHeartbeat = 0;
    private $logFile;

    public function __construct($host = WS_HOST, $port = WS_PORT) {
        $this->host = $host;
        $this->port = (int)$port;
        $this->logFile = __DIR__ . '/../logs/websocket.log';
    }

    public function run() {
        $context = stream_context_create([
            'socket' => [
                'so_reuseport' => 1,
                'backlog'      => 128
            ]
        ]);

        $bindAddress = "tcp://{$this->host}:{$this->port}";
        $this->masterSocket = @stream_socket_server(
            $bindAddress,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        if (!$this->masterSocket) {
            $this->log("FATAL: Failed to bind to {$bindAddress}: [{$errno}] {$errstr}", "ERROR");
            exit(1);
        }

        stream_set_blocking($this->masterSocket, false);

        $this->log("====================================================================");
        $this->log(" Vehicle Maintenance Service Tracking System - WebSocket Server");
        $this->log(" Listening on: {$bindAddress}");
        $this->log(" Process ID:   " . getmypid());
        $this->log(" Timezone:     " . date_default_timezone_get());
        $this->log(" Status:       Ready for incoming connections and realtime events");
        $this->log("====================================================================");

        $this->initDatabase();

        while (true) {
            $read = array_merge([$this->masterSocket], array_values($this->clients));
            $write = null;
            $except = null;

            // Wait up to 100ms for network events
            $changed = @stream_select($read, $write, $except, 0, 100000);

            if ($changed === false) {
                $this->log("stream_select interrupted or failed", "WARN");
                continue;
            }

            // Check for new incoming connection
            if (in_array($this->masterSocket, $read)) {
                $newSocket = @stream_socket_accept($this->masterSocket, 0);
                if ($newSocket) {
                    $this->handleNewConnection($newSocket);
                }
                $masterKey = array_search($this->masterSocket, $read);
                unset($read[$masterKey]);
            }

            // Read data from active clients
            foreach ($read as $socket) {
                $socketId = (int)$socket;
                if (!isset($this->clients[$socketId])) {
                    continue;
                }

                $data = @fread($socket, 8192);
                if ($data === false || strlen($data) === 0) {
                    $this->disconnect($socketId, "Remote client closed socket");
                    continue;
                }

                if (!$this->clientInfo[$socketId]['handshake']) {
                    $this->performHandshake($socketId, $socket, $data);
                } else {
                    $this->handleWebSocketFrame($socketId, $socket, $data);
                }
            }

            // Periodic task 1: Check database event queue
            $now = microtime(true);
            if ($now - $this->lastQueueCheck >= (WS_QUEUE_INTERVAL_US / 1000000)) {
                $this->processDatabaseEventQueue();
                $this->lastQueueCheck = $now;
            }

            // Periodic task 2: Heartbeat ping every 30 seconds
            if ($now - $this->lastHeartbeat >= 30) {
                $this->sendHeartbeats();
                $this->lastHeartbeat = $now;
            }

            // Tiny sleep to avoid 100% CPU on empty loops
            usleep(10000); // 10ms
        }
    }

    private function handleNewConnection($socket) {
        stream_set_blocking($socket, false);
        $socketId = (int)$socket;
        $peerName = stream_socket_get_name($socket, true) ?: 'unknown';

        $this->clients[$socketId] = $socket;
        $this->clientInfo[$socketId] = [
            'peer'      => $peerName,
            'handshake' => false,
            'channels'  => ['all'],
            'user_id'   => null,
            'role'      => 'guest',
            'connected' => time(),
            'last_ping' => time()
        ];

        // Subscribe to public channel by default
        $this->channelSubscribers['all'][$socketId] = $socketId;

        $this->log("Client #{$socketId} connected from {$peerName}");
    }

    private function performHandshake($socketId, $socket, $buffer) {
        // Check if this is an internal direct IPC broadcast request (plain JSON)
        $trimmed = trim($buffer);
        if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
            $this->handleDirectIpcBroadcast($socketId, $socket, $trimmed);
            return;
        }

        // Standard HTTP WebSocket Handshake
        if (preg_match("/GET (.*) HTTP\/1.1/i", $buffer, $matchPath) &&
            preg_match("/Sec-WebSocket-Key: (.*)\r\n/i", $buffer, $matchKey)) {

            $requestUri = trim($matchPath[1]);
            $clientKey = trim($matchKey[1]);
            $acceptKey = base64_encode(sha1($clientKey . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));

            $headers = "HTTP/1.1 101 Switching Protocols\r\n" .
                       "Upgrade: websocket\r\n" .
                       "Connection: Upgrade\r\n" .
                       "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

            @fwrite($socket, $headers);
            $this->clientInfo[$socketId]['handshake'] = true;

            $this->log("Client #{$socketId} completed WebSocket handshake (URI: {$requestUri})");

            // Extract potential query params from URI for instant auth (e.g. ?user_id=1&role=user)
            $parsedUrl = parse_url($requestUri);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (!empty($queryParams['user_id'])) {
                    $this->authenticateClient(
                        $socketId,
                        (int)$queryParams['user_id'],
                        $queryParams['role'] ?? 'user'
                    );
                }
            }

            // Send welcoming packet
            $this->sendToSocket($socketId, json_encode([
                'event' => 'connected',
                'data'  => [
                    'socket_id' => $socketId,
                    'time'      => date('Y-m-d H:i:s'),
                    'app'       => APP_NAME
                ]
            ]));
        } else {
            // Bad handshake request
            $this->disconnect($socketId, "Invalid WebSocket handshake header");
        }
    }

    private function handleDirectIpcBroadcast($socketId, $socket, $jsonStr) {
        $payload = json_decode($jsonStr, true);
        if (!$payload || !isset($payload['secret']) || $payload['secret'] !== WS_SECRET_KEY) {
            @fwrite($socket, json_encode(['status' => 'error', 'message' => 'Unauthorized']) . "\n");
            $this->disconnect($socketId, "Unauthorized IPC attempt");
            return;
        }

        $channel = $payload['channel'] ?? 'all';
        $event = $payload['event'] ?? 'message';
        $data = $payload['data'] ?? [];

        $recipients = $this->broadcastToChannel($channel, $event, $data);

        @fwrite($socket, json_encode(['status' => 'ok', 'recipients' => $recipients]) . "\n");
        $this->disconnect($socketId, "IPC request finished");
    }

    private function handleWebSocketFrame($socketId, $socket, $data) {
        $frame = $this->decodeFrame($data);
        if (!$frame) {
            return;
        }

        $opcode = $frame['opcode'];
        $payload = $frame['payload'];

        switch ($opcode) {
            case 1: // Text frame
                $this->handleClientMessage($socketId, $payload);
                break;

            case 2: // Binary frame (not used here)
                break;

            case 8: // Connection Close
                $this->disconnect($socketId, "Received close frame from client");
                break;

            case 9: // Ping
                $pongFrame = $this->encodeFrame($payload, 'pong');
                @fwrite($socket, $pongFrame);
                $this->clientInfo[$socketId]['last_ping'] = time();
                break;

            case 10: // Pong
                $this->clientInfo[$socketId]['last_ping'] = time();
                break;
        }
    }

    private function handleClientMessage($socketId, $message) {
        $data = json_decode($message, true);
        if (!$data) {
            return;
        }

        $action = $data['action'] ?? $data['type'] ?? '';

        if ($action === 'auth' || $action === 'subscribe') {
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
            $role = $data['role'] ?? 'user';
            if ($userId) {
                $this->authenticateClient($socketId, $userId, $role);
            }
        } elseif ($action === 'ping') {
            $this->sendToSocket($socketId, json_encode(['event' => 'pong', 'time' => time()]));
            $this->clientInfo[$socketId]['last_ping'] = time();
        }
    }

    private function authenticateClient($socketId, $userId, $role = 'user') {
        if (!isset($this->clientInfo[$socketId])) {
            return;
        }

        $this->clientInfo[$socketId]['user_id'] = $userId;
        $this->clientInfo[$socketId]['role'] = $role;

        $userChannel = "user_{$userId}";
        $this->subscribeToChannel($socketId, $userChannel);

        if ($role === 'admin') {
            $this->subscribeToChannel($socketId, 'admin');
        }

        $channelsList = implode(', ', $this->clientInfo[$socketId]['channels']);
        $this->log("Client #{$socketId} authenticated as User ID: {$userId} (Role: {$role}) [Channels: {$channelsList}]", "AUTH");

        $this->sendToSocket($socketId, json_encode([
            'event' => 'authenticated',
            'data'  => [
                'user_id'  => $userId,
                'role'     => $role,
                'channels' => $this->clientInfo[$socketId]['channels']
            ]
        ]));
    }

    private function subscribeToChannel($socketId, $channel) {
        if (!isset($this->channelSubscribers[$channel])) {
            $this->channelSubscribers[$channel] = [];
        }
        $this->channelSubscribers[$channel][$socketId] = $socketId;

        if (!in_array($channel, $this->clientInfo[$socketId]['channels'])) {
            $this->clientInfo[$socketId]['channels'][] = $channel;
        }
    }

    public function broadcastToChannel($channel, $event, $data) {
        $message = json_encode([
            'event'     => $event,
            'channel'   => $channel,
            'data'      => $data,
            'timestamp' => time()
        ]);

        $frame = $this->encodeFrame($message, 'text');
        $count = 0;

        if (!empty($this->channelSubscribers[$channel])) {
            foreach ($this->channelSubscribers[$channel] as $socketId) {
                if (isset($this->clients[$socketId]) && $this->clientInfo[$socketId]['handshake']) {
                    $written = @fwrite($this->clients[$socketId], $frame);
                    if ($written) {
                        $count++;
                    }
                }
            }
        }

        $this->log("Broadcast event '{$event}' to channel '{$channel}' ({$count} active clients)", "BROADCAST");
        return $count;
    }

    private function sendToSocket($socketId, $message) {
        if (isset($this->clients[$socketId])) {
            $frame = $this->encodeFrame($message, 'text');
            @fwrite($this->clients[$socketId], $frame);
        }
    }

    private function sendHeartbeats() {
        $now = time();
        $pingFrame = $this->encodeFrame("ping", 'ping');

        foreach ($this->clients as $socketId => $socket) {
            if ($this->clientInfo[$socketId]['handshake']) {
                // If client hasn't answered for over 90 seconds, drop connection
                if ($now - $this->clientInfo[$socketId]['last_ping'] > 90) {
                    $this->disconnect($socketId, "Client timed out (no ping/pong response for 90s)");
                    continue;
                }
                @fwrite($socket, $pingFrame);
            }
        }
    }

    private function disconnect($socketId, $reason = '') {
        if (!isset($this->clients[$socketId])) {
            return;
        }

        $socket = $this->clients[$socketId];
        $peer = $this->clientInfo[$socketId]['peer'] ?? 'unknown';

        // Clean up channel subscriptions
        if (!empty($this->clientInfo[$socketId]['channels'])) {
            foreach ($this->clientInfo[$socketId]['channels'] as $channel) {
                unset($this->channelSubscribers[$channel][$socketId]);
            }
        }

        unset($this->clients[$socketId]);
        unset($this->clientInfo[$socketId]);

        @fclose($socket);
        $this->log("Client #{$socketId} ({$peer}) disconnected. Reason: {$reason}");
    }

    private function initDatabase() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->dbPdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->log("Database queue connection established");
        } catch (\Throwable $e) {
            $this->log("Database connection warning: " . $e->getMessage() . " (Queue polling will retry)", "WARN");
            $this->dbPdo = null;
        }
    }

    private function processDatabaseEventQueue() {
        if (!$this->dbPdo) {
            // Attempt reconnect if lost
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->dbPdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (\Throwable $e) {
                return; // Still unavailable
            }
        }

        try {
            // Fetch up to 25 unprocessed events
            $stmt = $this->dbPdo->query("
                SELECT id, channel, event, payload 
                FROM realtime_events 
                WHERE processed_at IS NULL 
                ORDER BY id ASC 
                LIMIT 25
            ");
            $events = $stmt->fetchAll();

            if (empty($events)) {
                return;
            }

            $processedIds = [];
            foreach ($events as $row) {
                $channel = $row['channel'];
                $event = $row['event'];
                $payloadData = json_decode($row['payload'], true) ?: [];

                $this->broadcastToChannel($channel, $event, $payloadData);
                $processedIds[] = (int)$row['id'];
            }

            if (!empty($processedIds)) {
                $inQuery = implode(',', $processedIds);
                $this->dbPdo->exec("UPDATE realtime_events SET processed_at = NOW() WHERE id IN ({$inQuery})");
            }
        } catch (\Throwable $e) {
            $this->log("Error processing realtime_events queue: " . $e->getMessage(), "ERROR");
        }
    }

    private function encodeFrame($payload, $type = 'text') {
        $frameHead = [];
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129; // 0x81 (FIN + opcode 1)
                break;
            case 'close':
                $frameHead[0] = 136; // 0x88 (FIN + opcode 8)
                break;
            case 'ping':
                $frameHead[0] = 137; // 0x89 (FIN + opcode 9)
                break;
            case 'pong':
                $frameHead[0] = 138; // 0x8A (FIN + opcode 10)
                break;
            default:
                $frameHead[0] = 129;
        }

        if ($payloadLength <= 125) {
            $frameHead[1] = $payloadLength;
            $header = pack('CC', $frameHead[0], $frameHead[1]);
        } elseif ($payloadLength <= 65535) {
            $frameHead[1] = 126;
            $header = pack('CCn', $frameHead[0], $frameHead[1], $payloadLength);
        } else {
            $frameHead[1] = 127;
            $header = pack('CCNN', $frameHead[0], $frameHead[1], 0, $payloadLength);
        }

        return $header . $payload;
    }

    private function decodeFrame($data) {
        if (strlen($data) < 2) return null;

        $unmasked = ord($data[1]) & 128;
        $length = ord($data[1]) & 127;
        $offset = 2;

        if ($length === 126) {
            if (strlen($data) < 4) return null;
            $length = unpack('n', substr($data, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($data) < 10) return null;
            $length = unpack('J', substr($data, 2, 8))[1];
            $offset = 10;
        }

        $masks = '';
        if ($unmasked) {
            if (strlen($data) < $offset + 4) return null;
            $masks = substr($data, $offset, 4);
            $offset += 4;
        }

        if (strlen($data) < $offset + $length) return null;

        $payload = substr($data, $offset, $length);
        $text = '';
        if ($unmasked) {
            for ($i = 0; $i < $length; ++$i) {
                $text .= $payload[$i] ^ $masks[$i % 4];
            }
        } else {
            $text = $payload;
        }

        return [
            'opcode'  => ord($data[0]) & 15,
            'payload' => $text,
            'length'  => $offset + $length
        ];
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        echo $formatted;

        if ($this->logFile) {
            @file_put_contents($this->logFile, $formatted, FILE_APPEND);
        }
    }
}

// Start the server
$server = new WebSocketServer();
$server->run();

