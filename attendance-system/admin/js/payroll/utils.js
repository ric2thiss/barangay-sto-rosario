/**
 * Utilities Module
 * Currency and date formatting utilities
 */
export class PayrollUtils {
    /**
     * Format currency amount
     */
    static formatCurrency(amount) {
        if (typeof amount !== 'number') {
            amount = parseFloat(amount) || 0;
        }
        return '₱ ' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Get today's date in 'Mon DD, YYYY' format
     */
    static getFormattedDate() {
        const date = new Date();
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    /**
     * Format date string (YYYY-MM-DD) to 'Mon DD, YYYY' format
     */
    static formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
}
