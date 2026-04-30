/**
 * WebSocket Connection Management Module
 * Handles WebSocket connection, reconnection, heartbeat, and status updates
 */

let socket = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 10;
let heartbeatInterval = null;
let reconnectTimeout = null;
let isManualClose = false;

/**
 * Initialize WebSocket connection
 * @param {string} websocketUrl - WebSocket server URL
 */
export function initWebSocket(websocketUrl) {
    if (reconnectTimeout) {
        clearTimeout(reconnectTimeout);
        reconnectTimeout = null;
    }
    
    if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) {
        return;
    }
    
    try {
        socket = new WebSocket(websocketUrl);
        
        socket.onopen = () => {
            reconnectAttempts = 0;
            isManualClose = false;
            updateConnectionStatus(true, 'Connected');
            startHeartbeat();
        };

        socket.onerror = (error) => {
            console.error("❌ WebSocket error:", error);
        };

        socket.onclose = (event) => {
            stopHeartbeat();
            
            if (!isManualClose) {
                updateConnectionStatus(false, `Reconnecting... (${reconnectAttempts + 1}/${MAX_RECONNECT_ATTEMPTS})`);
            }
            
            if (isManualClose) {
                updateConnectionStatus(false, 'Disconnected');
                return;
            }
            
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
                reconnectTimeout = setTimeout(() => {
                    updateConnectionStatus(false, `Connecting... (Attempt ${reconnectAttempts})`);
                    initWebSocket(websocketUrl);
                }, delay);
            } else {
                updateConnectionStatus(false, 'Connection lost - Retrying in 30s...');
                reconnectTimeout = setTimeout(() => {
                    reconnectAttempts = 0;
                    updateConnectionStatus(false, 'Reconnecting...');
                    initWebSocket(websocketUrl);
                }, 30000);
            }
        };
    } catch (error) {
        console.error("❌ Failed to initialize WebSocket:", error);
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            reconnectAttempts++;
            const delay = Math.min(2000 * reconnectAttempts, 10000);
            reconnectTimeout = setTimeout(() => {
                initWebSocket(websocketUrl);
            }, delay);
        }
    }
}

/**
 * Heartbeat function to keep connection alive
 */
function startHeartbeat() {
    stopHeartbeat();
    
    heartbeatInterval = setInterval(() => {
        if (socket && socket.readyState === WebSocket.OPEN) {
            try {
                socket.send(JSON.stringify({ type: 'ping', timestamp: Date.now() }));
            } catch (error) {
                console.error("❌ Error sending heartbeat:", error);
                if (socket) {
                    socket.close();
                }
            }
        } else {
            stopHeartbeat();
        }
    }, 30000);
}

/**
 * Stop heartbeat interval
 */
function stopHeartbeat() {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
}

/**
 * Update connection status indicator in the UI
 * @param {boolean} isConnected - Connection status
 * @param {string} text - Status text to display
 */
function updateConnectionStatus(isConnected, text) {
    const indicator = document.getElementById('ws-status-indicator');
    const statusText = document.getElementById('ws-status-text');
    
    if (indicator && statusText) {
        if (isConnected) {
            indicator.className = 'inline-block w-3 h-3 rounded-full bg-green-500 animate-pulse';
            statusText.textContent = text || 'Connected';
            statusText.className = 'text-green-600 font-medium';
        } else {
            indicator.className = 'inline-block w-3 h-3 rounded-full bg-red-500';
            statusText.textContent = text || 'Disconnected';
            statusText.className = 'text-red-600';
        }
    }
}

/**
 * Cleanup WebSocket connection
 */
export function cleanupWebSocket() {
    isManualClose = true;
    stopHeartbeat();
    if (reconnectTimeout) {
        clearTimeout(reconnectTimeout);
    }
    if (socket) {
        socket.close(1000, 'Page unloading');
    }
}
