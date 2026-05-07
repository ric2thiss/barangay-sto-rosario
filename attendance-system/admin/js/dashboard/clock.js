/**
 * Clock and Date Management Module
 * Handles real-time clock updates and date formatting
 */

/**
 * Get day suffix for ordinal numbers (st, nd, rd, th)
 * @param {number} day - Day of the month
 * @returns {string} Day suffix
 */
function getDaySuffix(day) {
    if (day > 3 && day < 21) return 'th';
    switch (day % 10) {
        case 1:  return "st";
        case 2:  return "nd";
        case 3:  return "rd";
        default: return "th";
    }
}

/**
 * Update clock and dates in the UI
 */
export function updateDatesAndClock() {
    const now = new Date();
    
    // Header Date (e.g., September 28, 2025)
    const headerDateElement = document.getElementById('current-date');
    if (headerDateElement) {
        headerDateElement.textContent = now.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    // Realtime Clock (e.g., 10:28 : 40 AM)
    const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit', 
        hour12: true 
    };
    const clockElement = document.getElementById('realtime-clock');
    const timeParts = now.toLocaleTimeString('en-US', timeOptions).split(' ');
    const time = timeParts[0];
    const ampm = timeParts[1];
    // Format to HH:MM : SS AM/PM
    const formattedTime = time.replace(/:/g, ' : ') + ' ' + ampm; 

    if (clockElement) {
        clockElement.textContent = formattedTime;
    }

    // Today's Date Insight (e.g., 28th September 2025)
    const day = now.getDate();
    const daySuffix = getDaySuffix(day);
    const monthYear = now.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    const dateString = day + daySuffix + ' ' + monthYear;
    
    const dateInsightElement = document.getElementById('today-date-insight');
    if (dateInsightElement) {
        dateInsightElement.textContent = dateString;
    }
}

/**
 * Initialize clock with automatic updates
 */
export function initClock() {
    // Update the clock every second
    setInterval(updateDatesAndClock, 1000);
    // Initial call
    updateDatesAndClock();
}
