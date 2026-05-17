/**
 * Table Renderer Module
 * Handles table updates for payrun records with real API data
 */
import { PayrollUtils } from './utils.js';

export class TableRenderer {
    constructor() {
        this.tableBody = document.getElementById('payrunsTableBody');
    }

    /**
     * Load and display payruns from API
     */
    async loadPayruns() {
        // Import base URL utility
        const { getBaseUrl } = await import('../shared/baseUrl.js');
        const baseUrl = getBaseUrl();
        
        try {
            const response = await fetch(`${baseUrl}/api/payroll/payruns.php?limit=10`);
            const result = await response.json();
            
            if (result.success && result.data && result.data.length > 0) {
                this.renderPayruns(result.data);
            } else {
                // No payruns - show empty state
                this.showEmptyState();
            }
        } catch (error) {
            console.error('Error loading payruns:', error);
            this.showEmptyState();
        }
    }

    /**
     * Render payruns in table
     */
    renderPayruns(payruns) {
        if (!this.tableBody) return;

        // Clear existing rows (except pending row which will be removed)
        const pendingRow = document.getElementById('pending-row');
        this.tableBody.innerHTML = '';

        payruns.forEach(payrun => {
            const statusClass = payrun.status === 'completed' 
                ? 'bg-green-100 text-green-800'
                : payrun.status === 'processing'
                ? 'bg-yellow-100 text-yellow-800'
                : 'bg-gray-100 text-gray-800';
            
            const statusText = payrun.status.charAt(0).toUpperCase() + payrun.status.slice(1);

            const rowHTML = `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${payrun.payrun_date_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${payrun.period_covered}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${payrun.employees_count}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${PayrollUtils.formatCurrency(payrun.total_net_pay)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="payroll.php?action=view&payrun_id=${payrun.payrun_id}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <a href="#" class="text-gray-600 hover:text-gray-900" onclick="exportPayrun(${payrun.payrun_id}); return false;">Export</a>
                    </td>
                </tr>
            `;
            this.tableBody.insertAdjacentHTML('beforeend', rowHTML);
        });
    }

    /**
     * Show empty state when no payruns exist
     */
    showEmptyState() {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v1a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1h12a1 1 0 011 1v2"></path>
                    </svg>
                    <p class="text-sm">No payruns found. Click "Process New Payrun" to create your first payroll.</p>
                </td>
            </tr>
        `;
    }

    /**
     * Add new row to payrun table (after processing)
     */
    addNewRow(data) {
        if (!this.tableBody) return;

        const newRowHTML = `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${data.payrunDate}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.periodCovered}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.employeesPaid}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${PayrollUtils.formatCurrency(data.netTotal)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="payroll.php?action=view&payrun_id=${data.payrunId}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Export</a>
                </td>
            </tr>
        `;

        // Prepend the new row to the table body
        this.tableBody.insertAdjacentHTML('afterbegin', newRowHTML);

        // Remove the 'Pending' row if it exists
        const pendingRow = document.getElementById('pending-row');
        if (pendingRow) {
            pendingRow.remove();
        }
    }
}
