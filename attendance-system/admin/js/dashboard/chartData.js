/**
 * Chart Data Generation Module
 * Fetches chart data from API based on filter type (today, week, month, year)
 */

/**
 * Fetch chart data from API based on filter type
 * @param {string} filterType - Filter type: 'today', 'week', 'month', 'year'
 * @param {string} chartType - Chart type: 'attendance' or 'visitor'
 * @returns {Promise<Object>} Promise that resolves to object containing labels, presentData, absentData, and visitorData arrays
 */
export async function getChartData(filterType, chartType) {
    try {
        if (chartType === 'attendance') {
            // Fetch attendance chart data
            const response = await fetch(`../api/attendance/chart.php?filter=${filterType}`);
            const data = await response.json();
            
            if (data.success) {
                return {
                    labels: data.labels || [],
                    presentData: data.presentData || [],
                    absentData: data.absentData || [],
                    visitorData: []
                };
            } else {
                console.error('Error fetching attendance chart data:', data.error);
                return { labels: [], presentData: [], absentData: [], visitorData: [] };
            }
        } else if (chartType === 'visitor') {
            // Fetch visitor chart data
            const response = await fetch(`../api/visitors/chart.php?filter=${filterType}`);
            const data = await response.json();
            
            if (data.success) {
                return {
                    labels: data.labels || [],
                    presentData: [],
                    absentData: [],
                    visitorData: data.visitorData || []
                };
            } else {
                console.error('Error fetching visitor chart data:', data.error);
                return { labels: [], presentData: [], absentData: [], visitorData: [] };
            }
        }
    } catch (error) {
        console.error('Error fetching chart data:', error);
        return { labels: [], presentData: [], absentData: [], visitorData: [] };
    }
    
    return { labels: [], presentData: [], absentData: [], visitorData: [] };
}
