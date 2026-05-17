/**
 * Card Updater Module
 * Handles summary card updates with real API data
 */
import { PayrollUtils } from './utils.js';

export class CardUpdater {
    constructor() {
        this.totalNetPayEl = document.getElementById('totalNetPay');
        this.employeesCountEl = document.getElementById('employeesCount');
        this.totalGrossPayEl = document.getElementById('totalGrossPay');
        this.grossPayChangeEl = document.getElementById('grossPayChange');
        this.totalDeductionsEl = document.getElementById('totalDeductions');
        this.deductionsChangeEl = document.getElementById('deductionsChange');
    }

    /**
     * Load and display payroll statistics from API
     */
    async loadStats() {
        // Import base URL utility
        const { getBaseUrl } = await import('../shared/baseUrl.js');
        const baseUrl = getBaseUrl();
        
        try {
            const response = await fetch(`${baseUrl}/api/payroll/stats.php`);
            const result = await response.json();
            
            if (result.success && result.data) {
                this.updateSummaryCards(result.data);
            } else {
                // No data available - show zeros
                this.updateSummaryCards({
                    total_gross_pay: 0,
                    total_deductions: 0,
                    total_net_pay: 0,
                    employees_count: 0,
                    gross_pay_change: 0,
                    deductions_change: 0
                });
            }
        } catch (error) {
            console.error('Error loading payroll stats:', error);
            // Show zeros on error
            this.updateSummaryCards({
                total_gross_pay: 0,
                total_deductions: 0,
                total_net_pay: 0,
                employees_count: 0,
                gross_pay_change: 0,
                deductions_change: 0
            });
        }
    }

    /**
     * Update summary cards with data
     */
    updateSummaryCards(data) {
        // Update Net Pay Card
        if (this.totalNetPayEl) {
            this.totalNetPayEl.textContent = PayrollUtils.formatCurrency(data.total_net_pay || 0);
        }
        if (this.employeesCountEl) {
            const count = data.employees_count || 0;
            this.employeesCountEl.textContent = count > 0 
                ? `For ${count} active employee${count !== 1 ? 's' : ''}`
                : 'No employees processed';
        }
        
        // Update Gross Pay Card
        if (this.totalGrossPayEl) {
            this.totalGrossPayEl.textContent = PayrollUtils.formatCurrency(data.total_gross_pay || 0);
        }
        if (this.grossPayChangeEl) {
            const change = data.gross_pay_change || 0;
            const changeText = change !== 0 
                ? `${change > 0 ? '+' : ''}${change.toFixed(1)}% vs. previous payrun`
                : 'No previous data';
            this.grossPayChangeEl.textContent = changeText;
            this.grossPayChangeEl.className = 'text-xs mt-1 ' + (change >= 0 ? 'text-green-500' : 'text-red-500');
        }

        // Update Deductions Card
        if (this.totalDeductionsEl) {
            this.totalDeductionsEl.textContent = PayrollUtils.formatCurrency(data.total_deductions || 0);
        }
        if (this.deductionsChangeEl) {
            const change = data.deductions_change || 0;
            const changeText = change !== 0 
                ? `${change > 0 ? '+' : ''}${change.toFixed(1)}% vs. previous payrun`
                : 'No previous data';
            this.deductionsChangeEl.textContent = changeText;
            this.deductionsChangeEl.className = 'text-xs mt-1 ' + (change <= 0 ? 'text-green-500' : 'text-red-500');
        }
    }
}
