/**
 * Filter Handler Module
 * Handles filter modal open/close functionality
 */
export class FilterHandler {
    constructor() {
        this.filterModal = document.getElementById('filterModal');
        this.filterButton = document.getElementById('filterButton');
        this.backdrop = document.getElementById('filterModalBackdrop');
        this.closeButton = document.getElementById('closeFilterModal');
    }

    /**
     * Open filter modal
     */
    open() {
        if (this.filterModal) {
            this.filterModal.classList.remove('hidden');
        }
    }

    /**
     * Close filter modal
     */
    close() {
        if (this.filterModal) {
            this.filterModal.classList.add('hidden');
        }
    }

    /**
     * Initialize filter handlers
     */
    init() {
        // Handle filter button click
        if (this.filterButton) {
            this.filterButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        }

        // Close on backdrop click
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => {
                this.close();
            });
        }

        // Close button
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => {
                this.close();
            });
        }

        // Prevent modal from closing when clicking inside modal content
        const filterModalContent = document.getElementById('filterModalContent');
        if (filterModalContent) {
            filterModalContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // Close when clicking outside modal content
        if (this.filterModal) {
            this.filterModal.addEventListener('click', (e) => {
                if (e.target.id === 'filterModal') {
                    this.close();
                }
            });
        }

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.filterModal && !this.filterModal.classList.contains('hidden')) {
                this.close();
            }
        });
    }
}
