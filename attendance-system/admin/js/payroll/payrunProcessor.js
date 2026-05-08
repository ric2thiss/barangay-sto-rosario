/**
 * Payrun Processor Module
 * Handles payrun processing via API
 */
import { PayrollUtils } from './utils.js';

export class PayrunProcessor {
    /**
     * Process a new payrun via API
     * @param {string} periodStart - Start date (YYYY-MM-DD)
     * @param {string} periodEnd - End date (YYYY-MM-DD)
     */
    async processPayrun(periodStart = null, periodEnd = null) {
        // Default to current month if not provided
        if (!periodStart) {
            const now = new Date();
            periodStart = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        }
        if (!periodEnd) {
            periodEnd = new Date().toISOString().split('T')[0];
        }

        // Import base URL utility
        const { getBaseUrl } = await import('../shared/baseUrl.js');
        const baseUrl = getBaseUrl();
        
        try {
            const response = await fetch(`${baseUrl}/api/payroll/process.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    period_start: periodStart,
                    period_end: periodEnd
                })
            });

            const result = await response.json();

            if (result.success && result.data) {
                const data = result.data;
                return {
                    payrunId: data.payrun_id,
                    payrunDate: PayrollUtils.formatDate(data.payrun_date),
                    periodCovered: PayrollUtils.formatDate(data.period_start) + ' - ' + PayrollUtils.formatDate(data.period_end),
                    employeesPaid: data.employees_count,
                    netTotal: data.total_net_pay,
                    grossTotal: data.total_gross_pay,
                    deductionsTotal: data.total_deductions,
                    status: 'Completed'
                };
            } else {
                throw new Error(result.message || 'Failed to process payrun');
            }
        } catch (error) {
            console.error('Error processing payrun:', error);
            throw error;
        }
    }
}
