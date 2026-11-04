/**
 * assets/js/notifications.js
 * Vehicle Maintenance Service Tracking System - Real-time WebSocket Client
 * 
 * Features:
 * - Real-time push notifications over WebSocket (RFC 6455)
 * - Automatic connection health monitoring & exponential backoff reconnect
 * - Dual-layer fallback polling (guarantees alerts even if WebSocket daemon is stopped)
 * - Interactive Bootstrap 5 Floating Toast alerts
 * - Live navbar notification badge incrementing and dropdown updating
 * - Live update on Notifications and Dashboard pages
 * - Zero-asset Web Audio API chime
 */

(function () {
    'use strict';

    // Verify config is present and user is logged in
    const config = window.VMS_CONFIG || {};
    if (!config.userId) {
        return; // Guest user or login screen; no realtime needed
    }

    let socket = null;
    let isConnected = false;
    let reconnectAttempts = 0;
    let reconnectTimeout = null;
    let fallbackPollInterval = null;
    let lastKnownUnreadCount = null;
    let knownNotificationIds = new Set();

    // Configuration values
    const WS_HOST = config.wsHost || window.location.hostname || 'localhost';
    const WS_PORT = config.wsPort || 8080;
    const PROTOCOL = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const WS_URL = `${PROTOCOL}//${WS_HOST}:${WS_PORT}/?user_id=${config.userId}&role=${config.userRole}`;
    const BASE_URL = config.baseUrl || '/';
    const FALLBACK_INTERVAL = config.wsFallbackPoll || 15000;

    /**
     * Synthesize a gentle notification chime using Web Audio API
     */
    function playNotificationChime() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();

            // Note 1: 587.33 Hz (D5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, ctx.currentTime);
            gain1.gain.setValueAtTime(0.08, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(ctx.currentTime);
            osc1.stop(ctx.currentTime + 0.25);

            // Note 2: 880.00 Hz (A5)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, ctx.currentTime + 0.1);
            gain2.gain.setValueAtTime(0.12, ctx.currentTime + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.1);
            osc2.stop(ctx.currentTime + 0.45);
        } catch (e) {
            // Audio context policy might require user gesture; safe to ignore
        }
    }

    /**
     * Show a modern Bootstrap 5 floating toast alert
     */
    function showNotificationToast(notification) {
        const container = document.getElementById('vms-toast-container');
        if (!container) return;

        const title = escapeHtml(notification.title || 'Maintenance Alert');
        const message = escapeHtml(notification.message || '');
        const type = (notification.type || 'REMINDER').toUpperCase();
        const notifId = notification.id || '';
        const targetUrl = `${BASE_URL}notifications/index.php`;

        // Pick icon and badge color based on alert type
        let iconHtml = '<i class="bi bi-info-circle-fill text-info me-2"></i>';
        let badgeClass = 'bg-info';
        if (type === 'OVERDUE') {
            iconHtml = '<i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>';
            badgeClass = 'bg-danger';
        } else if (type === 'DUE') {
            iconHtml = '<i class="bi bi-clock-fill text-warning me-2"></i>';
            badgeClass = 'bg-warning text-dark';
        } else if (type === 'REMINDER') {
            iconHtml = '<i class="bi bi-bell-fill text-primary me-2"></i>';
            badgeClass = 'bg-primary';
        }

        const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        const toastHtml = `
            <div id="${toastId}" class="toast shadow-lg border-0 mb-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="8000">
                <div class="toast-header bg-white border-bottom">
                    ${iconHtml}
                    <strong class="me-auto text-dark">${title}</strong>
                    <span class="badge ${badgeClass} me-2">${type}</span>
                    <small class="text-muted">Just now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body bg-light">
                    <p class="mb-2 text-dark small">${message}</p>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="${targetUrl}" class="btn btn-sm btn-primary py-0 px-2 small">
                            <i class="bi bi-arrow-right-circle me-1"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        if (toastEl && window.bootstrap && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toastEl);
            bsToast.show();
            playNotificationChime();

            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        }
    }

    /**
     * Update the bell icon badge and count indicator
     */
    function updateBadges(unreadCount) {
        lastKnownUnreadCount = unreadCount;
        const badge = document.getElementById('notifBadge');
        const badgeCount = document.getElementById('notifBadgeCount');

        if (badge) {
            if (unreadCount > 0) {
                badge.classList.remove('d-none');
                badge.classList.add('animate__animated', 'animate__pulse');
            } else {
                badge.classList.add('d-none');
            }
        }

        if (badgeCount) {
            badgeCount.textContent = `${unreadCount} unread`;
            badgeCount.className = unreadCount > 0 ? 'badge bg-danger rounded-pill' : 'badge bg-secondary rounded-pill';
        }
    }

    /**
     * Prepend a new notification item into the navbar dropdown list
     */
    function prependDropdownItem(notification) {
        const list = document.getElementById('navbarNotifList');
        if (!list) return;

        // Remove "no notifications" empty placeholder if visible
        const placeholder = document.getElementById('notifEmptyPlaceholder');
        if (placeholder) {
            placeholder.remove();
        }

        const title = escapeHtml(notification.title || 'Notification');
        const message = escapeHtml(notification.message || '');
        const id = notification.id || '';
        const shortMessage = message.length > 50 ? message.substring(0, 50) + '...' : message;

        const itemHtml = `
            <a class="dropdown-item py-2 border-bottom notif-dropdown-item bg-light animate__animated animate__fadeInDown" data-notif-id="${id}" href="${BASE_URL}notifications/index.php">
                <div class="d-flex w-100 justify-content-between">
                    <small class="fw-bold text-primary">${title}</small>
                    <small class="text-muted" style="font-size: 0.7rem;">Just now</small>
                </div>
                <small class="text-wrap text-muted d-block">${shortMessage}</small>
            </a>
        `;

        list.insertAdjacentHTML('afterbegin', itemHtml);

        // Keep dropdown to max 5 items
        const items = list.querySelectorAll('.notif-dropdown-item');
        if (items.length > 5) {
            items[items.length - 1].remove();
        }
    }

    /**
     * If on the notifications index page, dynamically inject the notification row
     */
    function injectNotificationInPage(notification) {
        const listGroup = document.querySelector('.list-group.list-group-flush');
        if (!listGroup) return;

        const title = escapeHtml(notification.title || '');
        const message = escapeHtml(notification.message || '');
        const type = (notification.type || 'REMINDER').toUpperCase();
        const id = notification.id || '';

        let icon = '<i class="bi bi-info-circle text-info me-2"></i>';
        if (type === 'OVERDUE') icon = '<i class="bi bi-exclamation-circle text-danger me-2"></i>';
        else if (type === 'REMINDER') icon = '<i class="bi bi-calendar-event text-warning me-2"></i>';

        const rowHtml = `
            <div class="list-group-item p-4 bg-light animate__animated animate__fadeInDown" id="notif-row-${id}">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1 fw-bold text-primary">
                            ${icon} ${title}
                        </h5>
                        <p class="mb-2 text-dark fs-6">${message}</p>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i> Just now (live)</small>
                    </div>
                    <div class="d-flex flex-column gap-2 text-end">
                        <a href="${BASE_URL}notifications/mark_read.php?id=${id}" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                            <i class="bi bi-check2"></i> Mark Read
                        </a>
                        <a href="${BASE_URL}notifications/delete.php?id=${id}" class="btn btn-sm btn-outline-danger px-3 rounded-pill" onclick="return confirm('Delete this notification?');">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        `;

        listGroup.insertAdjacentHTML('afterbegin', rowHtml);
    }

    /**
     * Handle incoming real-time events
     */
    function handleEvent(eventName, data) {
        console.log(`[VMS Realtime] Event received: ${eventName}`, data);

        switch (eventName) {
            case 'new_notification':
                // Prevent duplicate handling if already known
                if (data.id && knownNotificationIds.has(data.id)) {
                    return;
                }
                if (data.id) {
                    knownNotificationIds.add(data.id);
                }

                // Increment badge counter
                const currentCount = lastKnownUnreadCount !== null ? lastKnownUnreadCount + 1 : 1;
                updateBadges(currentCount);

                // Show UI elements
                showNotificationToast(data);
                prependDropdownItem(data);
                injectNotificationInPage(data);
                break;

            case 'notification_read':
                if (data.id === 'all') {
                    updateBadges(0);
                    document.querySelectorAll('.notif-dropdown-item').forEach(el => el.classList.remove('bg-light'));
                } else if (data.id) {
                    const newCount = Math.max(0, (lastKnownUnreadCount || 1) - 1);
                    updateBadges(newCount);
                    const item = document.querySelector(`.notif-dropdown-item[data-notif-id="${data.id}"]`);
                    if (item) item.classList.remove('bg-light');
                    const row = document.getElementById(`notif-row-${data.id}`);
                    if (row) row.classList.remove('bg-light');
                }
                break;

            case 'service_updated':
                showNotificationToast({
                    title: 'Service Record Updated',
                    message: `Service update recorded for vehicle #${data.vehicle_id || ''}.`,
                    type: 'REMINDER'
                });
                break;

            case 'vehicle_updated':
                showNotificationToast({
                    title: 'Vehicle Information Updated',
                    message: `Vehicle ${data.plate_number || ''} was ${data.action || 'updated'}.`,
                    type: 'REMINDER'
                });
                break;

            case 'system_alert':
                showNotificationToast({
                    title: data.title || 'System Alert',
                    message: data.message || '',
                    type: 'OVERDUE'
                });
                break;
        }
    }

    /**
     * Connect to the WebSocket Server
     */
    function connectWebSocket() {
        if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
            return;
        }

        try {
            console.log(`[VMS Realtime] Connecting to WebSocket: ${WS_URL}`);
            socket = new WebSocket(WS_URL);

            socket.onopen = function () {
                console.log('[VMS Realtime] WebSocket connection established successfully.');
                isConnected = true;
                reconnectAttempts = 0;

                // Stop fallback polling while WebSocket is active
                if (fallbackPollInterval) {
                    clearInterval(fallbackPollInterval);
                    fallbackPollInterval = null;
                }

                // Authenticate and subscribe
                socket.send(JSON.stringify({
                    action: 'auth',
                    user_id: config.userId,
                    role: config.userRole || 'user',
                    csrf_token: config.csrfToken
                }));
            };

            socket.onmessage = function (event) {
                try {
                    const packet = JSON.parse(event.data);
                    const eventName = packet.event || packet.type || '';
                    const data = packet.data || packet.payload || {};

                    if (eventName === 'connected') {
                        console.log('[VMS Realtime] Handshake confirmed by server:', data);
                    } else if (eventName === 'authenticated') {
                        console.log('[VMS Realtime] Authenticated on channels:', data.channels);
                    } else if (eventName === 'pong') {
                        // Heartbeat reply
                    } else {
                        handleEvent(eventName, data);
                    }
                } catch (err) {
                    console.error('[VMS Realtime] Failed to parse message frame:', err, event.data);
                }
            };

            socket.onclose = function (event) {
                isConnected = false;
                console.warn(`[VMS Realtime] WebSocket closed (Code: ${event.code}). Initiating reconnect...`);
                scheduleReconnect();
            };

            socket.onerror = function (error) {
                isConnected = false;
                console.warn('[VMS Realtime] WebSocket encountered an error or server is offline.');
                // onclose will trigger next
            };

        } catch (err) {
            console.warn('[VMS Realtime] WebSocket initialization exception:', err);
            scheduleReconnect();
        }
    }

    /**
     * Exponential backoff reconnect strategy
     */
    function scheduleReconnect() {
        if (reconnectTimeout) {
            clearTimeout(reconnectTimeout);
        }

        // Start fallback polling if not already running
        startFallbackPolling();

        // Calculate delay: 2s, 4s, 8s, up to 15s max
        const delay = Math.min(15000, Math.pow(2, Math.min(reconnectAttempts, 4)) * 1000);
        reconnectAttempts++;

        reconnectTimeout = setTimeout(function () {
            connectWebSocket();
        }, delay);
    }

    /**
     * Fallback polling engine when WebSocket daemon is stopped/restarting
     */
    function startFallbackPolling() {
        if (fallbackPollInterval) return;

        console.log(`[VMS Realtime] Fallback polling enabled (Interval: ${FALLBACK_INTERVAL}ms)`);
        fallbackPollInterval = setInterval(function () {
            if (isConnected) {
                clearInterval(fallbackPollInterval);
                fallbackPollInterval = null;
                return;
            }

            fetch(`${BASE_URL}actions/notification_action.php?action=check_unread`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.status === 'ok') {
                    if (lastKnownUnreadCount !== null && data.unread_count > lastKnownUnreadCount) {
                        // New notifications arrived while disconnected
                        const diff = data.unread_count - lastKnownUnreadCount;
                        if (Array.isArray(data.latest) && data.latest.length > 0) {
                            for (let i = 0; i < Math.min(diff, data.latest.length); i++) {
                                handleEvent('new_notification', data.latest[i]);
                            }
                        }
                    }
                    updateBadges(data.unread_count);
                }
            })
            .catch(() => {
                // Silently ignore network errors during poll
            });
        }, FALLBACK_INTERVAL);
    }

    /**
     * Utility: Escape HTML string
     */
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Initialize initial unread count from existing badge if present
    document.addEventListener('DOMContentLoaded', function () {
        const badgeCount = document.getElementById('notifBadgeCount');
        if (badgeCount) {
            const match = badgeCount.textContent.match(/\d+/);
            if (match) {
                lastKnownUnreadCount = parseInt(match[0], 10);
            }
        }

        // Connect WebSocket
        connectWebSocket();
    });

})();
