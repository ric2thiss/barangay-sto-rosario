/**
 * Visitor Statistics Module
 * Handles fetching and updating visitor statistics based on filter
 */

/**
 * Update visitor counts based on filter
 * @param {string} filter - Filter type: 'today', 'week', 'month', 'year'
 */
export async function updateVisitorCounts(filter) {
    try {
        // Using modular endpoint: ../api/visitors/stats.php?filter=${filter}
        const apiUrl = `../api/visitors/stats.php?filter=${filter}`;
        console.log('Fetching visitor stats from:', apiUrl);
        const response = await fetch(apiUrl);
        
        if (!response.ok) {
            console.error('HTTP error fetching visitor stats:', response.status, response.statusText);
            return;
        }
        
        const data = await response.json();
        console.log('Visitor stats response:', data);

        if (data.success) {
            // Update detailed visitor counts (do not overwrite the residents count)
            const totalVisitorsEl = document.getElementById('total-visitors-count');
            const residentVisitorsEl = document.getElementById('resident-visitors-count');
            const nonResidentVisitorsEl = document.getElementById('non-resident-visitors-count');
            const walkinEl = document.getElementById('walkin-count');
            const onlineAppointmentEl = document.getElementById('online-appointment-count');
            
            if (totalVisitorsEl) {
                totalVisitorsEl.textContent = data.total_visitors || 0;
            }
            if (residentVisitorsEl) {
                residentVisitorsEl.textContent = data.resident_visitors || 0;
            }
            if (nonResidentVisitorsEl) {
                nonResidentVisitorsEl.textContent = data.non_resident_visitors || 0;
            }
            if (walkinEl) {
                walkinEl.textContent = data.walkin || 0;
            }
            if (onlineAppointmentEl) {
                onlineAppointmentEl.textContent = data.online_appointment || 0;
            }
        } else {
            console.error('Error fetching visitor stats:', data.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error updating visitor counts:', error);
    }
}

/**
 * Initialize visitor statistics with event listener
 */
export function initVisitorStats() {
    const visitorFilterDropdown = document.getElementById('visitor-filter-dropdown');
    if (visitorFilterDropdown) {
        visitorFilterDropdown.addEventListener('change', (e) => {
            updateVisitorCounts(e.target.value);
        });
        // Initialize with default filter
        updateVisitorCounts('month');
    }
}
