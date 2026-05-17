/**
 * Base URL Utility
 * Dynamically detects the base URL of the application
 * Works regardless of folder name or domain
 */

/**
 * Get the base URL of the application
 * @returns {string} Base URL (e.g., "http://localhost/attendance" or "http://localhost/attendance-system")
 */
export function getBaseUrl() {
    // Check if BASE_URL is already set (injected by PHP)
    if (window.BASE_URL) {
        return window.BASE_URL;
    }
    
    // Check meta tag
    const metaBaseUrl = document.querySelector('meta[name="base-url"]');
    if (metaBaseUrl) {
        return metaBaseUrl.content;
    }
    
    // Auto-detect from current location
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;
    
    // If we're in admin/js/..., go up to project root
    // admin/js/payroll/main.js -> /attendance-system/admin/js/payroll/main.js
    // We want: /attendance-system
    const adminJsMatch = pathname.match(/^(\/[^\/]+)\/admin\/js\//);
    if (adminJsMatch) {
        return protocol + '//' + host + adminJsMatch[1];
    }
    
    // If we're in admin/..., go up one level
    const adminMatch = pathname.match(/^(\/[^\/]+)\/admin\//);
    if (adminMatch) {
        return protocol + '//' + host + adminMatch[1];
    }
    
    // If we're in api/..., go up one level
    const apiMatch = pathname.match(/^(\/[^\/]+)\/api\//);
    if (apiMatch) {
        return protocol + '//' + host + apiMatch[1];
    }
    
    // Fallback: use current origin
    return protocol + '//' + host;
}

// Export as default for convenience
export default getBaseUrl;
