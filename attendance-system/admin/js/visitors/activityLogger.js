/**
 * Activity Logger Module
 * Manages activity log UI display
 */
export class ActivityLogger {
    constructor(logListId = 'logList') {
        this.logListId = logListId;
        this.logList = document.getElementById(logListId);
    }

    /**
     * Add log entry
     */
    addLogEntry(name, action = 'Check-in') {
        // Re-acquire element in case module initialized before DOM was fully ready
        if (!this.logList) {
            this.logList = document.getElementById(this.logListId);
        }
        if (!this.logList) return;

        // Remove placeholder if present
        const firstLi = this.logList.querySelector('li');
        if (firstLi && firstLi.textContent && firstLi.textContent.includes('No recent activity')) {
            firstLi.remove();
        }

        const li = document.createElement("li");
        const now = new Date();
        const timeString = now.toLocaleTimeString();

        li.innerHTML = `<span class="font-medium">${timeString}:</span> ${name} (${action}) <span class="text-green-500 float-right">✅</span>`;
        
        // Prepend new log item to the top of the list
        this.logList.prepend(li);
        
        // Keep the list tidy (limit the number of entries)
        if (this.logList.children.length > 5) {
            this.logList.removeChild(this.logList.lastChild);
        }
    }

    /**
     * Clear all log entries
     */
    clear() {
        if (this.logList) {
            this.logList.innerHTML = '';
        }
    }
}
