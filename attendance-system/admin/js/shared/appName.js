/**
 * Standalone App Name Updater
 * Use this when you can't use ES6 modules
 * Include this script after the sidebar HTML
 */
(function() {
    'use strict';
    
    // Get base URL
    function getBaseUrl() {
        const path = window.location.pathname;
        const base = path.substring(0, path.indexOf('/attendance-system/') + '/attendance-system'.length);
        return window.location.origin + base;
    }
    
    // Fetch app name from API
    async function fetchAppName() {
        try {
            const timestamp = new Date().getTime();
            const response = await fetch(getBaseUrl() + '/api/settings/public.php?_=' + timestamp);
            
            if (!response.ok) {
                return 'Attendance System';
            }
            
            const data = await response.json();
            
            if (data.success && data.data && data.data.app_name) {
                return data.data.app_name.value || 'Attendance System';
            }
            
            return 'Attendance System';
        } catch (error) {
            console.error('Error fetching app name:', error);
            return 'Attendance System';
        }
    }
    
    // Update app name
    async function updateAppName() {
        const appNameElement = document.getElementById('app-name');
        
        if (!appNameElement) {
            return;
        }
        
        const appName = await fetchAppName();
        appNameElement.textContent = appName;
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateAppName);
    } else {
        updateAppName();
    }
})();
