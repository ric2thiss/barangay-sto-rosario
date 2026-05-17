/**
 * WebSocket Server Configuration
 * This file contains all configuration settings for the WebSocket server
 */

const fs = require('fs');
const path = require('path');

// Load environment variables from .env file
const envPath = path.resolve(__dirname, '..', '.env');
const envVars = {};

if (fs.existsSync(envPath)) {
    const envFile = fs.readFileSync(envPath, 'utf8');
    envFile.split('\n').forEach(line => {
        const match = line.match(/^\s*([\w.-]+)\s*=\s*(.*)?\s*$/);
        if (match) {
            let key = match[1];
            let value = match[2] || '';
            // Remove surrounding quotes
            value = value.replace(/(^['"]|['"]$)/g, '').trim();
            envVars[key] = value;
        }
    });
}

// Dynamically detect base URL from environment variable or file system
function getBaseUrl() {
    // Check .env file or environment variable first
    if (envVars.BASE_URL) return envVars.BASE_URL;
    if (process.env.BASE_URL) return process.env.BASE_URL;
    
    // Check for folder name from current working directory
    const projectRoot = path.resolve(__dirname, '..');
    const projectFolderName = path.basename(projectRoot);
    
    const host = envVars.DB_HOST || process.env.HOST || 'localhost';
    const protocol = process.env.PROTOCOL || 'http';
    
    return `${protocol}://${host}/${projectFolderName}`;
}

module.exports = {
    // WebSocket Server Configuration
    websocket: {
        port: parseInt(envVars.WEBSOCKET_PORT || process.env.PORT || '8081', 10),
        host: envVars.WEBSOCKET_HOST || process.env.HOST || 'localhost'
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
