/**
 * Anomaly Detector Module
 * Handles rendering anomalies table
 */

export function initAnomalyDetector() {
    let currentAnomalies = [];
    let currentPage = 1;
    let pageSize = 5;

    const paginationEl = document.getElementById('anomalies-pagination');
    const rangeEl = document.getElementById('anomalies-range');
    const pageInfoEl = document.getElementById('anomalies-page-info');
    const prevBtn = document.getElementById('anomalies-prev');
    const nextBtn = document.getElementById('anomalies-next');
    const pageSizeSelect = document.getElementById('anomalies-page-size');

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
     * Render anomalies table
     * @param {Array} anomalies 
     */
    function render(anomalies) {
        const tbody = document.getElementById('anomalies-table-body');
        const section = document.getElementById('anomalies-section');

        currentAnomalies = Array.isArray(anomalies) ? anomalies : [];
        currentPage = 1;

        if (!currentAnomalies || currentAnomalies.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="2" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">No anomalies detected. All attendance records are complete.</p>
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

        const totalRecords = currentAnomalies.length;
        const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = Math.min(startIdx + pageSize, totalRecords);
        const pageSlice = currentAnomalies.slice(startIdx, endIdx);

        tbody.innerHTML = pageSlice.map(anomaly => {
            const date = formatDate(anomaly.date);
            const anomalyList = anomaly.anomalies.map(a => `<li class="mb-1">• ${a}</li>`).join('');

            return `
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${date}</td>
                    <td class="px-3 py-3 text-sm text-gray-700">
                        <ul class="list-none">
                            ${anomalyList}
                        </ul>
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
            render(currentAnomalies);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(currentAnomalies.length / pageSize));
            currentPage = Math.min(totalPages, currentPage + 1);
            render(currentAnomalies);
        });
    }
    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', () => {
            const parsed = parseInt(pageSizeSelect.value, 10);
            pageSize = Number.isFinite(parsed) && parsed > 0 ? parsed : pageSize;
            currentPage = 1;
            render(currentAnomalies);
        });
    }

    return {
        render
    };
}
