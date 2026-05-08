/**
 * Sidebar Module v2.0
 * Handles sidebar toggle functionality on all screen sizes
 * Can be reused across all admin pages
 * Now with dynamic app name from database
 */

import getBaseUrl from './baseUrl.js';

/**
 * Fetch app name from settings API
 */
async function fetchAppName() {
    try {
        // Add cache busting parameter
        const timestamp = new Date().getTime();
        const response = await fetch(`${getBaseUrl()}/api/settings/public.php?_=${timestamp}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.app_name) {
            return data.data.app_name.value || 'Attendance System';
        }
        
        return 'Attendance System';
    } catch (error) {
        console.error('Error fetching app name:', error);
        return 'Attendance System'; // Fallback to default
    }
}

/**
 * Update app name in sidebar
 */
async function updateAppName() {
    const appNameElement = document.getElementById('app-name');
    
    if (!appNameElement) {
        return;
    }
    
    const appName = await fetchAppName();
    appNameElement.textContent = appName;
}

export function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const mainContent = document.querySelector('main');

    if (!sidebar || !toggleButton || !mainContent) {
        console.warn('Sidebar elements not found');
        return;
    }

    // Load app name from database
    updateAppName();

    // Function to toggle sidebar
    function toggleSidebar() {
        // Check if sidebar is visible by checking its position
        const rect = sidebar.getBoundingClientRect();
        const isVisible = rect.left >= 0;
        
        if (!isVisible) {
            // Show sidebar
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            sidebar.classList.add('md:translate-x-0');
            mainContent.classList.add('md:ml-64');
            // On mobile, dim the background when sidebar is open
            if (window.innerWidth < 768) {
                mainContent.classList.add('opacity-50', 'pointer-events-none');
            }
        } else {
            // Hide sidebar
            sidebar.classList.remove('translate-x-0', 'md:translate-x-0');
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('md:ml-64');
            mainContent.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    toggleButton.addEventListener('click', toggleSidebar);

    // Close sidebar if main content is clicked on mobile
    mainContent.addEventListener('click', () => {
        if (window.innerWidth < 768 && sidebar.classList.contains('translate-x-0')) {
            sidebar.classList.remove('translate-x-0', 'md:translate-x-0');
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('opacity-50', 'pointer-events-none');
        }
    });
}
