/**
 * Shared Clock Module
 * Handles real-time clock and date updates for all pages
 * This ensures consistent date/time display across the entire application
 */

export class SharedClock {
    constructor() {
        this.intervalId = null;
    }

    /**
     * Update header date (upper right corner)
     * Format: September 28, 2025
     */
    updateHeaderDate() {
        const headerDateEl = document.getElementById('current-date');
        if (!headerDateEl) return;
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        headerDateEl.textContent = now.toLocaleDateString('en-US', options);
    }

    /**
     * Update realtime clock
     * Format: 10:28 : 40 AM
     */
    updateRealtimeClock() {
        const realtimeClockEl = document.getElementById('realtime-clock');
        if (!realtimeClockEl) return;

        const now = new Date();
        const timeOptions = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        };
        const raw = now.toLocaleTimeString('en-US', timeOptions);
        const m = raw.match(/^(.+?)\s+([AP]M)$/i);
        if (m) {
            const time = m[1];
            const ampm = m[2];
            realtimeClockEl.textContent = time.replace(/:/g, ' : ') + ' ' + ampm;
        } else {
            realtimeClockEl.textContent = raw;
        }
    }

    /**
     * Update today's date insight
     * Format: 28th September 2025
     */
    updateTodayDateInsight() {
        const todayDateInsightEl = document.getElementById('today-date-insight');
        if (!todayDateInsightEl) return;

        const now = new Date();
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

    /**
     * Update all date/time displays
     */
    updateAll() {
        this.updateHeaderDate();
        this.updateRealtimeClock();
        this.updateTodayDateInsight();
    }

    /**
     * Start the clock updates
     */
    start() {
        // Initial update
        this.updateAll();
        
        // Update every second (re-query DOM each tick so the clock survives node replacement)
        this.intervalId = setInterval(() => {
            try {
                this.updateRealtimeClock();
                const now = new Date();
                if (now.getSeconds() === 0) {
                    this.updateHeaderDate();
                    this.updateTodayDateInsight();
                }
            } catch (e) {
                console.warn('SharedClock tick error:', e);
            }
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

/**
 * Initialize clock automatically when DOM is ready
 * This can be called from any page
 */
export function initSharedClock() {
    const clock = new SharedClock();
    clock.start();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        clock.stop();
    });
    
    return clock;
}
