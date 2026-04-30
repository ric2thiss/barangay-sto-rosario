/**
 * Attendance Page Main Entry Point
 * Initializes all modules for the attendance page
 */
import { WebSocketManager } from './websocket.js';
import { AttendanceHandler } from './attendanceHandler.js';
import { ConnectionStatus } from './connectionStatus.js';
import { initSharedClock } from '../shared/clock.js';
import { Weather } from './weather.js';
import { initSidebar } from '../shared/sidebar.js';
import { initActivitySelect } from './activitySelect.js';
import { initAttendanceSpeechControls } from './attendanceSpeech.js';

// Get configuration from data attributes on the script tag or meta tags
// These values are injected by PHP in attendance.php
function getConfig() {
    // Try to get from current script tag's data attributes
    const currentScript = document.currentScript;
    let websocketUrl = currentScript?.dataset?.websocketUrl || '';
    let attendanceApiUrl = currentScript?.dataset?.attendanceApiUrl || '';
    let activitiesOptionsUrl = currentScript?.dataset?.activitiesOptionsUrl || '';
    let activitiesActiveUrl = currentScript?.dataset?.activitiesActiveUrl || '';
    
    // Fallback: try to get from meta tags if not in script data attributes
    if (!websocketUrl) {
        const metaWs = document.querySelector('meta[name="websocket-url"]');
        if (metaWs) websocketUrl = metaWs.content;
    }

    if (!attendanceApiUrl) {
        const metaApi = document.querySelector('meta[name="attendance-api-url"]');
        if (metaApi) attendanceApiUrl = metaApi.content;
    }

    if (!activitiesOptionsUrl) {
        const m = document.querySelector('meta[name="activities-options-url"]');
        if (m) activitiesOptionsUrl = m.content;
    }
    if (!activitiesActiveUrl) {
        const m = document.querySelector('meta[name="activities-active-url"]');
        if (m) activitiesActiveUrl = m.content;
    }
    
    return { websocketUrl, attendanceApiUrl, activitiesOptionsUrl, activitiesActiveUrl };
}

const config = getConfig();

// Initialize modules
let weather;
let connectionStatus;
let attendanceHandler;
let websocketManager;

/**
 * Initialize all modules
 */
function init() {
    // Initialize connection status
    connectionStatus = new ConnectionStatus();
    connectionStatus.update(false, 'Connecting...');

    // Initialize attendance handler
    attendanceHandler = new AttendanceHandler(config.attendanceApiUrl);

    // Initialize WebSocket manager
    if (config.websocketUrl) {
        websocketManager = new WebSocketManager(
            config.websocketUrl,
            connectionStatus,
            attendanceHandler,
            10 // max reconnect attempts
        );
        websocketManager.init();
    }

    // Single shared clock (re-queries DOM each tick — avoids stale nodes after dynamic updates)
    initSharedClock();

    // Initialize weather
    weather = new Weather(8.95, 125.53); // Default: Butuan City, Philippines
    weather.init();

    // Initialize sidebar toggle
    initSidebar();

    // Initialize attendance data on page load
    attendanceHandler.initialize();

    initActivitySelect(config.activitiesOptionsUrl, config.activitiesActiveUrl).catch(() => {});

    initAttendanceSpeechControls();

    // Handle page visibility changes (when tab loses/gains focus)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && websocketManager) {
            websocketManager.reconnectIfNeeded();
        }
    });
    
    // Handle focus events (when window loses/gains focus)
    window.addEventListener('focus', () => {
        if (websocketManager) {
            websocketManager.reconnectIfNeeded();
        }
    });

    // Cleanup WebSocket connection when page is unloaded
    window.addEventListener('beforeunload', () => {
        if (websocketManager) {
            websocketManager.close();
        }
        if (weather) {
            weather.stop();
        }
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM is already ready
    init();
}
