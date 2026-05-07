/**
 * Data Fetcher Module
 * Handles fetching attendance data from API
 */

export function initDataFetcher() {
    const API_BASE = '../api/dtr/employee-attendance.php';

    /**
     * Fetch attendance data for an employee
     * @param {string} employeeId 
     * @param {string|null} fromDate 
     * @param {string|null} toDate 
     * @returns {Promise<Object>}
     */
    async function fetchAttendanceData(employeeId, fromDate = null, toDate = null) {
        const params = new URLSearchParams({
            employee_id: employeeId
        });

        if (fromDate) {
            params.append('from', fromDate);
        }
        if (toDate) {
            params.append('to', toDate);
        }

        const url = `${API_BASE}?${params.toString()}`;
        
        const response = await fetch(url);
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch attendance data');
        }

        return data;
    }

    return {
        fetchAttendanceData
    };
}
