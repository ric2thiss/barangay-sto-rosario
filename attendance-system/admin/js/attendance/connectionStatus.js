/**
 * Connection Status Module
 * Handles WebSocket connection status indicator updates
 */
export class ConnectionStatus {
    constructor() {
        this.indicator = document.getElementById('ws-status-indicator');
        this.statusText = document.getElementById('ws-status-text');
    }

    /**
     * Update connection status indicator
     */
    update(isConnected, text) {
        if (!this.indicator || !this.statusText) return;
        
        if (isConnected) {
            this.indicator.className = 'inline-block w-3 h-3 rounded-full bg-green-500 animate-pulse';
            this.statusText.textContent = text || 'Connected';
            this.statusText.className = 'text-green-600 font-medium';
        } else {
            this.indicator.className = 'inline-block w-3 h-3 rounded-full bg-red-500';
            this.statusText.textContent = text || 'Disconnected';
            this.statusText.className = 'text-red-600';
        }
    }
}
