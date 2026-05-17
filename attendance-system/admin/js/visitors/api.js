/**
 * API Module
 * Handles API calls for visitor logging
 */
export class VisitorAPI {
    constructor() {
        // Get base URL dynamically
        const protocol = window.location.protocol;
        const host = window.location.host;
        const path = window.location.pathname;
        const basePath = path.substring(0, path.indexOf('/admin')) || '';
        this.baseUrl = `${protocol}//${host}${basePath}`;
    }

    /**
     * Fetch residents with photos for face recognition
     */
    async fetchResidents() {
        try {
            const response = await fetch(`${this.baseUrl}/api/visitors/residents.php`);
            const data = await response.json();
            
            if (data.success) {
                return data.residents || [];
            } else {
                console.error("Error fetching residents:", data.error);
                return [];
            }
        } catch (err) {
            console.error("❌ Error fetching residents:", err);
            return [];
        }
    }

    /**
     * Check if resident has a booking
     */
    async checkBooking(residentId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/visitors/check-booking.php?resident_id=${residentId}`);
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                console.error("Error checking booking:", data.error);
                return { has_booking: false, booking: null, has_pending: false, pending_requests: [] };
            }
        } catch (err) {
            console.error("❌ Error checking booking:", err);
            return { has_booking: false, booking: null, has_pending: false, pending_requests: [] };
        }
    }

    /**
     * Name suggestions: profiling residents + previous non-resident logs
     */
    async lookupVisitorNames(query) {
        const q = (query || '').trim();
        if (q.length < 2) {
            return { success: true, profiling: [], previous_visitors: [] };
        }
        try {
            const response = await fetch(
                `${this.baseUrl}/api/visitors/lookup-name.php?q=${encodeURIComponent(q)}&limit=12`
            );
            const data = await response.json();
            if (data.success) {
                return data;
            }
            return { success: false, profiling: [], previous_visitors: [] };
        } catch (err) {
            console.error("❌ lookupVisitorNames:", err);
            return { success: false, profiling: [], previous_visitors: [] };
        }
    }

    /**
     * Latest non-resident log row for form prefill
     */
    async fetchLastNonResident() {
        try {
            const response = await fetch(`${this.baseUrl}/api/visitors/last-non-resident.php`);
            const data = await response.json();
            if (data.success && data.visitor) {
                return data.visitor;
            }
            return null;
        } catch (err) {
            console.error("❌ fetchLastNonResident:", err);
            return null;
        }
    }

    /**
     * Fetch available services
     * @param {boolean} isResident - Whether the visitor is a resident (default true). Non-residents only see allowed services.
     */
    async fetchServices(isResident = true) {
        try {
            const residentParam = isResident ? '1' : '0';
            const response = await fetch(`${this.baseUrl}/api/visitors/services.php?is_resident=${residentParam}`);
            const data = await response.json();
            
            if (data.success) {
                return data.services || [];
            } else {
                console.error("Error fetching services:", data.error);
                return [];
            }
        } catch (err) {
            console.error("❌ Error fetching services:", err);
            return [];
        }
    }

    /**
     * Fetch resident address
     */
    async fetchResidentAddress(residentId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/visitors/resident-address.php?resident_id=${residentId}`);
            
            // Check if response is OK
            if (!response.ok) {
                const text = await response.text();
                console.error("❌ Error response from address API:", response.status, text);
                return null;
            }
            
            // Get response as text first to check if it's valid JSON
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error("❌ Invalid JSON response from address API:", text.substring(0, 200));
                console.error("Parse error:", parseError);
                return null;
            }
            
            if (data.success) {
                return data.address;
            } else {
                console.error("Error fetching address:", data.error);
                return null;
            }
        } catch (err) {
            console.error("❌ Error fetching address:", err);
            return null;
        }
    }

    /**
     * Log visitor attendance
     * 
     * @param {Object} logData - Visitor log data
     * @param {number|null} logData.resident_id - Resident ID (null for non-residents)
     * @param {string} logData.first_name - First name
     * @param {string|null} logData.middle_name - Middle name (optional)
     * @param {string} logData.last_name - Last name
     * @param {string} logData.address - Full address string
     * @param {string} logData.purpose - Service name or purpose
     * @param {boolean} logData.is_resident - Is resident flag
     * @param {boolean} logData.had_booking - Had booking flag
     * @param {string|null} logData.booking_id - Booking ID (optional)
     * @param {string|null} logData.birthdate - Birthdate (required for non-residents)
     * @returns {Promise<Object>}
     */
    async logVisitor(logData) {
        try {
            // Log payload to console for debugging (as required)
            console.log('Visitor Log Payload:', JSON.stringify(logData, null, 2));
            
            const response = await fetch(`${this.baseUrl}/api/visitors/log.php`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(logData)
            });

            const data = await response.json();
            
            if (data.success) {
                console.log("✅ Visitor logged successfully:", data);
                return data;
            } else {
                console.error("❌ Error logging visitor:", data.error);
                throw new Error(data.error || 'Failed to log visitor');
            }
        } catch (err) {
            console.error("❌ Error saving visitor log:", err);
            throw err;
        }
    }

    /**
     * Fetch recent visitor logs
     */
    async fetchRecentLogs(limit = 10) {
        try {
            const response = await fetch(`${this.baseUrl}/api/visitors/recent-logs.php?limit=${limit}`);
            const data = await response.json();

            if (data.success) {
                return data.logs || [];
            } else {
                console.error("Error fetching recent logs:", data.error);
                return [];
            }
        } catch (err) {
            console.error("Error fetching recent logs:", err);
            return [];
        }
    }

    /**
     * Send service application data to external API
     * 
     * @param {Object} servicePayload - Service application payload
     * @param {string} externalApiUrl - External API URL
     * @returns {Promise<Object>}
     */
    async submitServiceApplication(servicePayload, externalApiUrl) {
        try {
            // Log payload to console for debugging (as required)
            console.log('External API Payload:', JSON.stringify(servicePayload, null, 2));
            
            const response = await fetch(externalApiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(servicePayload)
            });

            const data = await response.json();
            console.log("✅ Service application submitted:", data);
            return data;
        } catch (err) {
            console.error("❌ Error submitting service application:", err);
            throw err;
        }
    }
}
