#!/usr/bin/env bash
# Start WebSocket Server for Vehicle Maintenance Tracking System
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "Starting WebSocket Server on port 8080..."
php "$DIR/websocket/server.php"

