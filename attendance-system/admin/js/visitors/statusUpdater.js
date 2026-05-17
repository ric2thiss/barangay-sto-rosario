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

    /**
     * Update status when a visitor has already been logged for this request/purpose
     */
    updateAlreadyLogged(name, serviceName) {
        if (this.statusIcon) {
            this.statusIcon.classList.remove('hidden', 'text-green-500', 'text-red-500', 'animate-pulse');
            this.statusIcon.classList.add('text-yellow-500');
            // Change icon to a warning/info icon
            this.statusIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            `;
        }
        if (this.statusTitle) this.statusTitle.textContent = 'ALREADY LOGGED';
        if (this.statusMessage) this.statusMessage.textContent =
            `${name} has already been logged for "${serviceName}". No duplicate entry was created.`;

        if (this.recognizedUserDiv) {
            this.recognizedUserDiv.classList.add('hidden');
        }
    }
}
