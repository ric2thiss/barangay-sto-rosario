/**
 * WebSocket Module
 * Handles WebSocket connection, heartbeat, and reconnection logic
 */
import { ConnectionStatus } from './connectionStatus.js';

export class WebSocketManager {
    constructor(websocketUrl, connectionStatus, attendanceHandler, maxReconnectAttempts = 10) {
        this.websocketUrl = websocketUrl;
        this.connectionStatus = connectionStatus;
        this.attendanceHandler = attendanceHandler;
        this.maxReconnectAttempts = maxReconnectAttempts;
        
        this.socket = null;
        this.reconnectAttempts = 0;
        this.heartbeatInterval = null;
        this.reconnectTimeout = null;
        this.isManualClose = false;
    }

    /**
     * Initialize WebSocket connection
     */
    init() {
        // Clear any existing reconnection timeout
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
            this.reconnectTimeout = null;
        }
        
        // Don't reconnect if we're already connected or connecting
        if (this.socket && (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING)) {
            return;
        }
        
        try {
            this.socket = new WebSocket(this.websocketUrl);
            
            this.socket.onopen = () => {
                this.reconnectAttempts = 0;
                this.isManualClose = false;
                
                // Update connection status indicator
                this.connectionStatus.update(true, 'Connected');
                
                // Start heartbeat to keep connection alive
                this.startHeartbeat();
            };

            this.socket.onmessage = async (event) => {
                try {
                    // Handle binary ping/pong (native WebSocket protocol)
                    if (typeof event.data === 'string') {
                        const data = JSON.parse(event.data);
                        
                        // Handle pong (server response to our ping)
                        if (data && data.type === 'pong') {
                            return;
                        }
                        
                        // Handle "attendance_error" message from C# application
                        if (data && data.type === 'attendance_error') {
                            const errorMessage = data.message || 'An error occurred';
                            const errorType = data.error_type || 'error';
                            
                            // Format error title based on error type
                            let errorTitle = 'Attendance Error';
                            if (errorType === 'no_active_window') {
                                errorTitle = 'No Active Window';
                            } else if (errorType === 'already_logged') {
                                errorTitle = 'Already Logged';
                            } else if (errorType === 'no_match') {
                                errorTitle = 'No Match Found';
                            } else if (errorType === 'server_error') {
                                errorTitle = 'Server Error';
                            } else if (errorType === 'exception') {
                                errorTitle = 'Error';
                            }
                            
                            // Use attendance handler's toast for error
                            this.attendanceHandler.showError(errorTitle, errorMessage);
                            return;
                        }
                        
                        // Handle "attendance_update" message from C# application (contains full data)
                        if (data && data.type === 'attendance_update' && data.data) {
                            // Set flag to force update (bypass duplicate check)
                            this.attendanceHandler.handleUpdate(data.data, true);
                            return;
                        }
                        // Handle standard API response format (from server broadcast or initial load)
                        else if (data && data.lastAttendee) {
                            // Standard attendance data structure from API
                            this.attendanceHandler.handleUpdate(data);
                        } else if (data && Array.isArray(data.attendances) && data.attendances.length > 0) {
                            // Full API response with attendances array - use lastAttendee
                            if (data.lastAttendee) {
                                this.attendanceHandler.handleUpdate(data);
                            }
                        } else if (data && typeof data === 'object' && data.lastAttendee) {
                            this.attendanceHandler.handleUpdate(data);
                        }
                    }
                } catch (error) {
                    console.error("❌ Error parsing WebSocket message:", error);
                }
            };

            this.socket.onerror = (error) => {
                console.error("❌ WebSocket error:", error);
            };

            this.socket.onclose = (event) => {
                // Stop heartbeat when connection closes
                this.stopHeartbeat();
                
                // Update connection status indicator
                if (!this.isManualClose) {
                    this.connectionStatus.update(false, `Reconnecting... (${this.reconnectAttempts + 1}/${this.maxReconnectAttempts})`);
                }
                
                // Don't reconnect if we manually closed the connection (e.g., page unload)
                if (this.isManualClose) {
                    this.connectionStatus.update(false, 'Disconnected');
                    return;
                }
                
                // Always attempt to reconnect unless we've exceeded max attempts
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 10000); // Exponential backoff, max 10s
                    this.reconnectTimeout = setTimeout(() => {
                        this.connectionStatus.update(false, `Connecting... (Attempt ${this.reconnectAttempts})`);
                        this.init();
                    }, delay);
                } else {
                    this.connectionStatus.update(false, 'Connection lost - Retrying in 30s...');
                    // Reset attempts after 30 seconds and try again
                    this.reconnectTimeout = setTimeout(() => {
                        this.reconnectAttempts = 0;
                        this.connectionStatus.update(false, 'Reconnecting...');
                        this.init();
                    }, 30000);
                }
            };
        } catch (error) {
            console.error("❌ Failed to initialize WebSocket:", error);
            // Retry connection on error
            if (this.reconnectAttempts < this.maxReconnectAttempts) {
                this.reconnectAttempts++;
                const delay = Math.min(2000 * this.reconnectAttempts, 10000);
                this.reconnectTimeout = setTimeout(() => {
                    this.init();
                }, delay);
            }
        }
    }
    
    /**
     * Start heartbeat to keep connection alive
     */
    startHeartbeat() {
        // Clear existing heartbeat if any
        this.stopHeartbeat();
        
        // Send ping every 30 seconds to keep connection alive
        this.heartbeatInterval = setInterval(() => {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                try {
                    // Send a ping message (some servers support ping/pong)
                    this.socket.send(JSON.stringify({ type: 'ping', timestamp: Date.now() }));
                } catch (error) {
                    console.error("❌ Error sending heartbeat:", error);
                    // If heartbeat fails, connection might be dead, try reconnecting
                    if (this.socket) {
                        this.socket.close();
                    }
                }
            } else {
                this.stopHeartbeat();
            }
        }, 30000); // Every 30 seconds
    }
    
    /**
     * Stop heartbeat
     */
    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    /**
     * Check if connection is alive
     */
    isConnected() {
        return this.socket && this.socket.readyState === WebSocket.OPEN;
    }

    /**
     * Close WebSocket connection
     */
    close() {
        this.isManualClose = true;
        this.stopHeartbeat();
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
            this.reconnectTimeout = null;
        }
        if (this.socket) {
            this.socket.close(1000, 'Page unloading');
        }
    }

    /**
     * Reconnect if connection is lost
     */
    reconnectIfNeeded() {
        if (this.socket && this.socket.readyState !== WebSocket.OPEN && this.socket.readyState !== WebSocket.CONNECTING) {
            this.reconnectAttempts = 0;
            this.init();
        }
    }
}
