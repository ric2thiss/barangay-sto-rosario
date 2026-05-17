/**
 * Residents Page Main Entry Point
 * Initializes all modules for the residents page
 */
import { DeleteHandler } from "./deleteHandler.js";
import { ViewHandler } from "./viewHandler.js";
import { FilterHandler } from "./filterHandler.js";
import { ShowResidentModalHandler } from "./modalHandler.js";
import { initSidebar } from '../shared/sidebar.js';
import { initSharedClock } from '../shared/clock.js';

// Debug: Check if script is loaded
console.log("Residents.js script loaded!");

// Handle editEmployeeModal close buttons (if modal exists - unused but present in HTML)
function initEditEmployeeModalHandlers() {
    const modal = document.getElementById('editEmployeeModal');
    if (!modal) return;

    const closeBtn = document.getElementById('closeEditEmployeeModal');
    const cancelBtn = document.getElementById('cancelEditEmployeeModal');

    const closeModal = () => {
        modal.classList.add('hidden');
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }
}

// Initialize modules
let deleteHandler;
let viewHandler;
let filterHandler;
let showResidentModalHandler;

/**
 * Initialize all modules
 */
function init() {
    console.log("DOM loaded, setting up event listeners");
    
    // Initialize delete handler
    deleteHandler = new DeleteHandler();
    deleteHandler.init();
    
    // Initialize view handler
    viewHandler = new ViewHandler();
    viewHandler.init();
    
    // Initialize filter handler
    filterHandler = new FilterHandler();
    filterHandler.init();
    
    // Initialize show resident modal handler
    showResidentModalHandler = new ShowResidentModalHandler();
    showResidentModalHandler.init();
    
    // Initialize edit employee modal handlers (if modal exists)
    initEditEmployeeModalHandlers();
    
    // Initialize sidebar toggle
    initSidebar();
    
    // Initialize shared clock for consistent date display
    initSharedClock();
}

// Wait for DOM to be fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM is already ready
    init();
}
