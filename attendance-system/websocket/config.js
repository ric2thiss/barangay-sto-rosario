/**
 * WebSocket Server Configuration
 * This file contains all configuration settings for the WebSocket server
 */

// Dynamically detect base URL from environment variable or file system
function getBaseUrl() {
    // Check environment variable first (for production/deployment)
    if (process.env.BASE_URL) {
        return process.env.BASE_URL;
    }
    
    // Check for folder name from current working directory
    // If running from websocket/ directory, go up one level to get project folder
    const path = require('path');
    const fs = require('fs');
    
    // Get the project root directory (parent of websocket/)
    const projectRoot = path.resolve(__dirname, '..');
    const projectFolderName = path.basename(projectRoot);
    
    // Default to localhost, but allow override via environment
    const host = process.env.HOST || 'localhost';
    const protocol = process.env.PROTOCOL || 'http';
    
    // Build base URL
    return `${protocol}://${host}/${projectFolderName}`;
}

module.exports = {
    // WebSocket Server Configuration
    websocket: {
        port: 8081,
        host: 'localhost'
    },

    // API Configuration
    api: {
        baseUrl: getBaseUrl(),
        endpoints: {
            // OLD ENDPOINTS (backward compatible):
            // attendances: '/api/services.php?resource=attendances',
            // templates: '/api/services.php?resource=templates',
            // attendanceWindows: '/api/services.php?resource=attendance-windows',
            // employees: '/api/services.php?resource=employees'
            // NEW ENDPOINTS:
            attendances: '/api/attendance/index.php',
            templates: '/api/templates/index.php',
            attendanceWindows: '/api/attendance/windows.php',
            employees: '/api/employees/index.php'
        }
    },

    // Server Settings
    server: {
        // Timeout settings (in milliseconds)
        heartbeatInterval: 30000, // 30 seconds
        reconnectDelay: 5000 // 5 seconds
    },

    // Logging
    logging: {
        enabled: true,
        level: 'info' // 'debug', 'info', 'warn', 'error'
    }
};
