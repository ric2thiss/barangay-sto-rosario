/**
 * Table Renderer Module
 * Handles attendance table rendering and updates
 */
export class TableRenderer {
    constructor() {
        this.tbody = document.getElementById("attendance-table-body");
    }

    /**
     * Helper function to get window text
     */
    getWindowText(window, windowLabel = null) {
        // Prefer window_label if available, otherwise use window
        return windowLabel || window || "";
    }

    /**
     * Add new attendance row to table
     */
    updateTable(attendance, resident) {
        if (!this.tbody || !attendance) return;

        // Remove "no records" row if it exists
        const noRecordsRow = document.getElementById("no-records-row");
        if (noRecordsRow) {
            noRecordsRow.remove();
        }

        // Format date/time for table
        const dateStr = attendance.created_at?.replace(" ", "T") || attendance.timestamp?.replace(" ", "T");
        const date = new Date(dateStr);
        const formattedDate = date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        const formattedTime = date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        const dateTimeStr = `${formattedDate} ${formattedTime}`;

        // Get employee name
        const firstName = resident?.first_name || "";
        const lastName = resident?.last_name || "";
        const fullName = `${firstName} ${lastName}`.trim() || "Unnamed";

        // Get window text (prefer window_label if available)
        const windowText = this.getWindowText(attendance.window, attendance.window_label);
        const activityLabel = attendance.activity_name ? String(attendance.activity_name) : "—";

        // Create new row with data attribute for duplicate detection
        const newRow = document.createElement("tr");
        newRow.className = "hover:bg-gray-50 transition duration-150";
        newRow.setAttribute('data-attendance-id', attendance.id);
        newRow.innerHTML = `
            <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${attendance.employee_id || 'Unknown'}</td>
            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">${fullName}</td>
            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">${dateTimeStr}</td>
            <td class="px-3 py-3 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${windowText}</span>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600">${activityLabel}</td>
        `;

        // Check if row already exists (prevent duplicates)
        const existingRow = this.tbody.querySelector(`tr[data-attendance-id="${attendance.id}"]`);
        if (!existingRow) {
            // Insert at the top of the table
            this.tbody.insertBefore(newRow, this.tbody.firstChild);
        }
    }

    /**
     * Export window text helper for other modules
     */
    static getWindowText(window, windowLabel = null) {
        // Prefer window_label if available, otherwise use window
        return windowLabel || window || "";
    }
}
