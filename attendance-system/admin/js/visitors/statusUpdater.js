/**
 * Status Updater Module
 * Handles recognition status UI updates
 */
export class StatusUpdater {
    constructor() {
        this.statusIcon = document.getElementById('status-icon');
        this.statusTitle = document.getElementById('status-title');
        this.statusMessage = document.getElementById('status-message');
        this.recognizedUserDiv = document.getElementById('recognized-user');
        this.userPhoto = document.getElementById('user-photo');
        this.userAction = document.getElementById('user-action');
        this.userDetails = document.getElementById('user-details');
        this.userTime = document.getElementById('user-time');
    }

    /**
     * Update status to loading
     */
    updateLoading(message) {
        if (this.statusTitle) this.statusTitle.textContent = 'Loading Models...';
        if (this.statusMessage) this.statusMessage.textContent = message || 'Please wait for the system to initialize.';
        if (this.statusIcon) {
            this.statusIcon.classList.remove('text-green-500', 'text-red-500');
            this.statusIcon.classList.add('text-yellow-500', 'animate-pulse');
        }
        if (this.recognizedUserDiv) {
            this.recognizedUserDiv.classList.add('hidden');
        }
    }

    /**
     * Update status to ready
     */
    updateReady() {
        if (this.statusTitle) this.statusTitle.textContent = 'READY TO SCAN';
        if (this.statusMessage) this.statusMessage.textContent = 'Scan your face to log your visit (Logbook).';
        if (this.statusIcon) {
            this.statusIcon.classList.remove('text-yellow-500', 'text-red-500', 'animate-pulse');
            this.statusIcon.classList.add('text-green-500');
        }
    }

    /**
     * Update status to error
     */
    updateError(title, message) {
        if (this.statusTitle) this.statusTitle.textContent = title || 'ERROR';
        if (this.statusMessage) this.statusMessage.textContent = message || 'An error occurred.';
        if (this.statusIcon) {
            this.statusIcon.classList.remove('text-yellow-500', 'text-green-500', 'animate-pulse');
            this.statusIcon.classList.add('text-red-500');
        }
    }

    /**
     * Update status when face is recognized
     */
    updateRecognized(name, personDetails) {
        if (!this.recognizedUserDiv) return;

        this.recognizedUserDiv.classList.remove('hidden');
        if (this.statusIcon) this.statusIcon.classList.add('hidden');
        
        if (this.statusTitle) this.statusTitle.textContent = 'MATCH FOUND!';
        if (this.statusMessage) this.statusMessage.textContent = 'Logging entry...';

        const now = new Date();
        const timeString = now.toLocaleTimeString();

        if (this.userPhoto) {
            this.userPhoto.src = personDetails ? personDetails.img : 'https://placehold.co/80x80/007bff/white?text=R';
        }
        if (this.userAction) this.userAction.textContent = 'LOG BOOK ENTRY';
        if (this.userDetails) this.userDetails.textContent = `Resident: ${name}`;
        if (this.userTime) this.userTime.textContent = `Time: ${timeString}`;
    }

    /**
     * Clear initial log item if it exists
     */
    clearInitialLogItem() {
        const logList = document.getElementById('logList');
        if (logList) {
            const initialLogItem = logList.querySelector('li');
            if (initialLogItem && initialLogItem.textContent.includes('No recent activity')) {
                initialLogItem.remove();
            }
        }
    }

    /**
     * Reset status to ready state - clear recognized user display
     */
    resetToReady() {
        if (this.recognizedUserDiv) {
            this.recognizedUserDiv.classList.add('hidden');
        }
        if (this.statusIcon) {
            this.statusIcon.classList.remove('hidden', 'text-red-500', 'text-yellow-500', 'animate-pulse');
            this.statusIcon.classList.add('text-green-500');
        }
        this.updateReady();
    }
}
