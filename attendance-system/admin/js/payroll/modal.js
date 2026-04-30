/**
 * Modal Module
 * Handles success modal display
 */
export class PayrollModal {
    constructor() {
        this.successModal = document.getElementById('successModal');
        this.closeModalButton = document.getElementById('closeModal');
        this.modalMessage = document.getElementById('modalMessage');
    }

    /**
     * Show success modal with message
     */
    show(message) {
        if (!this.successModal || !this.modalMessage) return;

        this.modalMessage.textContent = message;
        this.successModal.classList.remove('opacity-0', 'pointer-events-none');
        
        const modalContent = this.successModal.querySelector('div');
        if (modalContent) {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }
    }

    /**
     * Hide success modal
     */
    hide() {
        if (!this.successModal) return;

        this.successModal.classList.add('opacity-0', 'pointer-events-none');
        
        const modalContent = this.successModal.querySelector('div');
        if (modalContent) {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
        }
    }

    /**
     * Initialize modal event listeners
     */
    init() {
        if (this.closeModalButton) {
            this.closeModalButton.addEventListener('click', () => this.hide());
        }
    }
}
