/**
 * Clock Module
 * Handles real-time clock and date updates
 */
export class Clock {
    constructor() {
        this.intervalId = null;
    }

    /**
     * Update header date
     */
    updateHeaderDate() {
        const headerDateEl = document.getElementById('current-date');
        if (!headerDateEl) return;
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        headerDateEl.textContent = now.toLocaleDateString('en-US', options);
    }

    /**
     * Update realtime clock and today's date
     */
    updateRealtimeClock() {
        const now = new Date();

        const realtimeClockEl = document.getElementById('realtime-clock');
        if (realtimeClockEl) {
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const raw = now.toLocaleTimeString('en-US', timeOptions);
            const m = raw.match(/^(.+?)\s+([AP]M)$/i);
            if (m) {
                realtimeClockEl.textContent = m[1].replace(/:/g, ' : ') + ' ' + m[2];
            } else {
                realtimeClockEl.textContent = raw;
            }
        }

        const todayDateInsightEl = document.getElementById('today-date-insight');
        if (todayDateInsightEl) {
            const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
            const day = now.getDate();
            let daySuffix;

            if (day > 3 && day < 21) {
                daySuffix = 'th';
            } else {
                switch (day % 10) {
                    case 1:  daySuffix = "st"; break;
                    case 2:  daySuffix = "nd"; break;
                    case 3:  daySuffix = "rd"; break;
                    default: daySuffix = "th";
                }
            }

            const monthYear = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const dateString = day + daySuffix + ' ' + monthYear;
            todayDateInsightEl.textContent = dateString;
        }
    }

    /**
     * Start the clock updates
     */
    start() {
        // Initial update
        this.updateHeaderDate();
        this.updateRealtimeClock();
        
        // Update every second
        this.intervalId = setInterval(() => {
            this.updateRealtimeClock();
        }, 1000);
    }

    /**
     * Stop the clock updates
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
}
