/**
 * Table Renderer Module
 * Handles rendering the attendance table
 */

export function initTableRenderer() {
    let currentAttendanceData = [];
    let currentPage = 1;
    let pageSize = 10;

    const paginationEl = document.getElementById('attendance-pagination');
    const rangeEl = document.getElementById('attendance-range');
    const pageInfoEl = document.getElementById('attendance-page-info');
    const prevBtn = document.getElementById('attendance-prev');
    const nextBtn = document.getElementById('attendance-next');
    const pageSizeSelect = document.getElementById('attendance-page-size');

    /**
     * Format time from timestamp
     * @param {string|null} timestamp 
     * @returns {string}
     */
    function formatTime(timestamp) {
        if (!timestamp) return '-';
        const date = new Date(timestamp);
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    /**
     * Format date
     * @param {string} dateStr 
     * @returns {string}
     */
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Format total hours
     * @param {number} hours 
     * @param {number} minutes 
     * @returns {string}
     */
    function formatTotalHours(hours, minutes) {
        if (hours === 0 && minutes === 0) return '-';
        return `${hours}h ${minutes}m`;
    }

    /**
     * Get status badge class
     * @param {string} status 
     * @returns {string}
     */
    function getStatusBadgeClass(status) {
        if (status === 'Complete') {
            return 'bg-green-100 text-green-800';
        }
        return 'bg-yellow-100 text-yellow-800';
    }

    /**
     * Render attendance table
     * @param {Array} attendanceData 
     */
    function render(attendanceData) {
        const tbody = document.getElementById('attendance-table-body');

        currentAttendanceData = Array.isArray(attendanceData) ? attendanceData : [];
        currentPage = 1;
        
        if (!currentAttendanceData || currentAttendanceData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">No attendance records found for the selected date range.</p>
                    </td>
                </tr>
            `;

            if (paginationEl) {
                paginationEl.classList.add('hidden');
            }
            return;
        }

        if (pageSizeSelect) {
            const parsed = parseInt(pageSizeSelect.value, 10);
            pageSize = Number.isFinite(parsed) && parsed > 0 ? parsed : pageSize;
        }

        const totalRecords = currentAttendanceData.length;
        const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = Math.min(startIdx + pageSize, totalRecords);
        const pageSlice = currentAttendanceData.slice(startIdx, endIdx);

        tbody.innerHTML = pageSlice.map(day => {
            const morningIn = day.morning_in ? formatTime(day.morning_in.timestamp) : '-';
            const morningOut = day.morning_out ? formatTime(day.morning_out.timestamp) : '-';
            const afternoonIn = day.afternoon_in ? formatTime(day.afternoon_in.timestamp) : '-';
            const afternoonOut = day.afternoon_out ? formatTime(day.afternoon_out.timestamp) : '-';
            const totalHours = formatTotalHours(day.total_hours || 0, day.total_minutes || 0);
            const status = day.status || 'Incomplete';
            const statusClass = getStatusBadgeClass(status);

            return `
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">${formatDate(day.date)}</td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">${morningIn}</td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">${morningOut}</td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">${afternoonIn}</td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">${afternoonOut}</td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 font-medium">${totalHours}</td>
                    <td class="px-3 py-3 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                            ${status}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        // Pagination UI
        if (paginationEl) {
            paginationEl.classList.remove('hidden');
        }
        if (rangeEl) {
            rangeEl.textContent = `Showing ${startIdx + 1}\u2013${endIdx} of ${totalRecords}`;
        }
        if (pageInfoEl) {
            pageInfoEl.textContent = `Page ${currentPage} / ${totalPages}`;
        }
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
    }

    // Wire pagination controls once
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentPage = Math.max(1, currentPage - 1);
            render(currentAttendanceData);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(currentAttendanceData.length / pageSize));
            currentPage = Math.min(totalPages, currentPage + 1);
            render(currentAttendanceData);
        });
    }
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', () => {
            const parsed = parseInt(pageSizeSelect.value, 10);
            pageSize = Number.isFinite(parsed) && parsed > 0 ? parsed : pageSize;
            currentPage = 1;
            render(currentAttendanceData);
        });
    }

    return {
        render
    };
}
