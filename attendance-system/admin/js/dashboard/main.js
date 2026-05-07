/**
 * Dashboard Main Entry Point
 * Initializes all dashboard modules and coordinates their interactions
 */

import { initWebSocket, cleanupWebSocket } from './websocket.js';
import { initClock } from './clock.js';
import { initSharedClock } from '../shared/clock.js';
import { initWeather, fetchWeatherData } from './weather.js';
import { initializeCharts } from './charts.js';
import { initDashboardExtendedCharts } from './extendedCharts.js';
import { initVisitorStats } from './visitorStats.js';
import { initEmployeeStats } from './employeeStats.js';
import { initDashboardAnalyticsModals } from './analyticsModals.js';
import { initSidebar } from './sidebar.js';

// WebSocket URL is passed from PHP via a global variable
// This should be set before this module loads: window.WEBSOCKET_URL

/**
 * Initialize all dashboard functionality
 */
function initDashboard() {
    // Initialize WebSocket connection
    if (typeof window.WEBSOCKET_URL !== 'undefined') {
        initWebSocket(window.WEBSOCKET_URL);
        // Update initial status
        const statusText = document.getElementById('ws-status-text');
        if (statusText) {
            statusText.textContent = 'Connecting...';
        }
    }

    // Initialize clock (shared clock for consistent date display across all pages)
    initSharedClock();
    // Also initialize dashboard-specific clock for additional features if needed
    initClock();

    // Initialize weather
    initWeather();
    // Refresh weather every 30 minutes
    setInterval(() => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    fetchWeatherData(position.coords.latitude, position.coords.longitude);
                },
                () => {
                    // Use default location on error
                    fetchWeatherData(8.95, 125.53);
                },
                {
                    timeout: 5000,
                    enableHighAccuracy: false
                }
            );
        } else {
            fetchWeatherData(8.95, 125.53);
        }
    }, 30 * 60 * 1000);

    // Initialize charts when the window loads
    const startCharts = async () => {
        await initializeCharts();
        await initDashboardExtendedCharts();
    };
    if (document.readyState === 'loading') {
        window.addEventListener('load', () => {
            startCharts().catch((e) => console.error(e));
        });
    } else {
        startCharts().catch((e) => console.error(e));
    }

    // Initialize visitor statistics
    initVisitorStats();

    // Initialize employee statistics
    initEmployeeStats();

    // Visitor / attendance metric modals (lazy-loaded analytics)
    initDashboardAnalyticsModals();

    // Initialize sidebar
    initSidebar();

    // Cleanup WebSocket connection when page is unloaded
    window.addEventListener('beforeunload', () => {
        cleanupWebSocket();
    });
}

// Initialize dashboard when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}
