/**
 * Modal Handler Module
 * Handles ShowResidentModal open/close functionality
 */
export class ShowResidentModalHandler {
    constructor() {
        this.modal = document.getElementById('ShowResidentModal');
        this.backdrop = document.getElementById('showResidentModalBackdrop');
        this.closeButtons = [];
    }

    /**
     * Open modal
     */
    open() {
        if (this.modal) {
            this.modal.classList.remove('hidden');
        }
    }

    /**
     * Close modal
     */
    close() {
        if (this.modal) {
            this.modal.classList.add('hidden');
        }
    }

    /**
     * Initialize modal event listeners
     */
    init() {
        if (!this.modal) return;

        // Close on backdrop click
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => this.close());
        }

        // Close buttons
        const closeBtn = document.getElementById('closeShowResidentModal');
        const cancelBtn = document.getElementById('cancelShowResidentModal');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.close());
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                this.close();
            }
        });
    }
}
