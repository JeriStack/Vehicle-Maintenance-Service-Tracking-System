@echo off
title Vehicle Maintenance Tracking System - WebSocket Server
echo ====================================================================
echo Starting Real-Time WebSocket Server on port 8080...
echo Press Ctrl+C to stop the server at any time.
echo ====================================================================
php "%~dp0websocket\server.php"
pause

