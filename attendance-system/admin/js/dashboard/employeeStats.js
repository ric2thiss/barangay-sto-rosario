/**
 * Employee Attendance Statistics Module
 * Handles fetching and updating employee attendance statistics based on filter
 */

/**
 * Update employee attendance counts based on filter
 * @param {string} filter - Filter type: 'today', 'week', 'month', 'year'
 */
export async function updateEmployeeAttendanceCounts(filter) {
    try {
        // Using modular endpoint: ../api/attendance/stats.php?filter=${filter}
        const response = await fetch(`../api/attendance/stats.php?filter=${filter}`);
        const data = await response.json();

        if (data.success) {
            // Keep the main Total Employees count unchanged (it's set by PHP)
            // Only update the detailed attendance counts below
            
            // Update detailed counts
            document.getElementById('total-present-count').textContent = data.total_present || 0;
            document.getElementById('total-absent-count').textContent = data.total_absent || 0;
            document.getElementById('total-late-count').textContent = data.total_late || 0;
            document.getElementById('total-overtime-count').textContent = data.total_overtime || 0;
        } else {
            console.error('Error fetching employee attendance stats:', data.error);
        }
    } catch (error) {
        console.error('Error updating employee attendance counts:', error);
    }
}

/**
 * Initialize employee attendance statistics with event listener
 */
export function initEmployeeStats() {
    const employeeAttendanceFilterDropdown = document.getElementById('employee-attendance-filter-dropdown');
    if (employeeAttendanceFilterDropdown) {
        employeeAttendanceFilterDropdown.addEventListener('change', (e) => {
            updateEmployeeAttendanceCounts(e.target.value);
        });
        // Initialize with default filter
        updateEmployeeAttendanceCounts('month');
    }
}
