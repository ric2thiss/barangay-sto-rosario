/**
 * Modal Module
 * Handles modal open/close functionality for employee forms
 */
export class EmployeeModal {
    constructor(modalId = 'addEmployeeModal') {
        this.modal = document.getElementById(modalId);
        this.openButton = document.getElementById('openAddEmployeeModal');
        this.closeButtons = this.modal?.querySelectorAll('[onclick*="hidden"]') || [];
    }

    /**
     * Open the modal
     */
    open() {
        if (!this.modal) return;
        this.modal.classList.remove('hidden');
        // Optional: Focus the first input for accessibility
        const firstInput = this.modal.querySelector('input, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    /**
     * Close the modal
     */
    close() {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
    }

    /**
     * Initialize modal event listeners
     */
    init() {
        // Open button
        if (this.openButton) {
            this.openButton.addEventListener('click', () => this.open());
        }

        // Close on backdrop click
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target.id === 'addEmployeeModal') {
                    this.close();
                }
            });
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                this.close();
            }
        });

        // Close buttons (inline onclick handlers)
        this.closeButtons.forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });
    }
}
