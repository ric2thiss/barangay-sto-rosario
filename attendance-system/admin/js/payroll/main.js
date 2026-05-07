/**
 * Payroll Page Main Entry Point
 * Initializes all modules for the payroll page
 */
import { PayrunProcessor } from './payrunProcessor.js';
import { CardUpdater } from './cardUpdater.js';
import { TableRenderer } from './tableRenderer.js';
import { PayrollModal } from './modal.js';
import { PayrollUtils } from './utils.js';
import { initSidebar } from '../shared/sidebar.js';

// Initialize modules
let payrunProcessor;
let cardUpdater;
let tableRenderer;
let modal;

/**
 * Handle process payrun button click
 */
async function processNewPayrun() {
    const processButton = document.getElementById('processPayrunButton');
    const originalText = processButton.innerHTML;
    
    // Disable button and show loading
    processButton.disabled = true;
    processButton.innerHTML = `
        <svg class="animate-spin h-5 w-5 inline mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;

    try {
        // Process payrun via API
        const newPayrunData = await payrunProcessor.processPayrun();

        // Reload stats and payruns
        await cardUpdater.loadStats();
        await tableRenderer.loadPayruns();

        // Show success modal
        const message = `Payroll for ${newPayrunData.periodCovered} (Net: ${PayrollUtils.formatCurrency(newPayrunData.netTotal)}) processed successfully.`;
        modal.show(message);
    } catch (error) {
        console.error('Error processing payrun:', error);
        alert('Error processing payrun: ' + (error.message || 'Unknown error'));
    } finally {
        // Re-enable button
        processButton.disabled = false;
        processButton.innerHTML = originalText;
    }
}

/**
 * Initialize all modules
 */
async function init() {
    // Initialize modules
    payrunProcessor = new PayrunProcessor();
    cardUpdater = new CardUpdater();
    tableRenderer = new TableRenderer();
    modal = new PayrollModal();
    modal.init();

    // Initialize sidebar toggle
    initSidebar();

    // Load initial data
    await cardUpdater.loadStats();
    await tableRenderer.loadPayruns();

    // Event listener for process payrun button
    const processPayrunButton = document.getElementById('processPayrunButton');
    if (processPayrunButton) {
        processPayrunButton.addEventListener('click', processNewPayrun);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM is already ready
    init();
}
