/**
 * Attendance Handler Module
 * Handles attendance updates, duplicate prevention, and state management
 */
import { Toast } from './toast.js';
import { TableRenderer } from './tableRenderer.js';

export class AttendanceHandler {
    constructor(apiUrl) {
        this.apiUrl = apiUrl;
        this.toast = new Toast();
        this.tableRenderer = new TableRenderer();
        
        // Track processed attendance IDs
        this.lastProcessedAttendanceId = null;
        this.processedAttendanceIds = new Set();
        this.isInitialLoad = true;
        this.forceUpdateNext = false;
    }

    /**
     * Helper function to get window text
     */
    getWindowText(window, windowLabel = null) {
        return TableRenderer.getWindowText(window, windowLabel);
    }

    /**
     * Find corresponding attendance from the rendered table
     * This is a fallback when correspondingAttendance is not provided by the API
     */
    findCorrespondingAttendanceFromTable(currentAttendance) {
        if (!currentAttendance || !currentAttendance.employee_id) {
            console.log("🔍 findCorrespondingAttendanceFromTable: No currentAttendance or employee_id");
            return null;
        }
        
        try {
            const tbody = document.getElementById("attendance-table-body");
            if (!tbody) {
                console.log("🔍 findCorrespondingAttendanceFromTable: No tbody found");
                return null;
            }
            
            const currentWindow = currentAttendance.window || "";
            const employeeId = currentAttendance.employee_id;
            
            console.log("🔍 Searching table for corresponding attendance:", {
                currentWindow: currentWindow,
                employeeId: employeeId
            });
            
            // Determine what we're looking for
            let targetWindow = null;
            if (currentWindow.includes("_out")) {
                // If current is "out", look for corresponding "in"
                if (currentWindow === "morning_out") targetWindow = "morning_in";
                else if (currentWindow === "afternoon_out") targetWindow = "afternoon_in";
            } else if (currentWindow.includes("_in")) {
                // If current is "in", look for corresponding "out"
                if (currentWindow === "morning_in") targetWindow = "morning_out";
                else if (currentWindow === "afternoon_in") targetWindow = "afternoon_out";
            }
            
            if (!targetWindow) {
                console.log("🔍 No targetWindow determined");
                return null;
            }
            
            console.log("🔍 Looking for targetWindow:", targetWindow);
            
            // Get current attendance date
            const currentDate = new Date(currentAttendance.created_at?.replace(" ", "T") || currentAttendance.timestamp?.replace(" ", "T"));
            const currentDateStr = currentDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            
            console.log("🔍 Current date string:", currentDateStr);
            
            // Look through table rows to find matching attendance
            const rows = tbody.querySelectorAll("tr[data-attendance-id]");
            console.log("🔍 Found", rows.length, "rows in table");
            
            for (const row of rows) {
                const employeeIdCell = row.querySelector("td:first-child");
                const dateTimeCell = row.querySelector("td:nth-child(3)");
                const statusCell = row.querySelector("td:last-child span");
                
                if (!employeeIdCell || !dateTimeCell || !statusCell) {
                    console.log("🔍 Row missing cells, skipping");
                    continue;
                }
                
                const rowEmployeeId = employeeIdCell.textContent.trim();
                const rowStatusText = statusCell.textContent.trim();
                const rowDateTimeText = dateTimeCell.textContent.trim();
                
                console.log("🔍 Checking row:", {
                    employeeId: rowEmployeeId,
                    status: rowStatusText,
                    dateTime: rowDateTimeText
                });
                
                // Check if employee ID matches
                if (rowEmployeeId !== employeeId) {
                    console.log("🔍 Employee ID doesn't match, skipping");
                    continue;
                }
                
                // Check if window matches
                // The status cell might show "morning_in", "morning out", "Morning In", etc.
                const statusText = rowStatusText.toLowerCase();
                const windowText = targetWindow.toLowerCase().replace("_", " ");
                const targetWindowLower = targetWindow.toLowerCase();
                
                console.log("🔍 Checking window match:", {
                    statusText: statusText,
                    windowText: windowText,
                    targetWindowLower: targetWindowLower,
                    matches: statusText.includes(windowText) || statusText.includes(targetWindowLower)
                });
                
                // Check if status contains the window text (e.g., "morning in" or "morning_in")
                if (!statusText.includes(windowText) && !statusText.includes(targetWindowLower)) {
                    console.log("🔍 Window doesn't match, skipping");
                    continue;
                }
                
                // Check if date matches
                const rowDateStr = rowDateTimeText.split(" ").slice(0, 3).join(" ");
                console.log("🔍 Checking date match:", {
                    rowDateStr: rowDateStr,
                    currentDateStr: currentDateStr,
                    matches: rowDateStr === currentDateStr
                });
                
                if (rowDateStr !== currentDateStr) {
                    console.log("🔍 Date doesn't match, skipping");
                    continue;
                }
                
                // Extract time from the date/time cell
                const timeMatch = rowDateTimeText.match(/(\d{1,2}:\d{2}:\d{2}\s*(?:AM|PM))/i);
                if (timeMatch) {
                    console.log("✅ Found matching attendance in table:", {
                        window: targetWindow,
                        timestamp: rowDateTimeText,
                        time: timeMatch[1]
                    });
                    // Return a mock attendance object with the time
                    return {
                        window: targetWindow,
                        timestamp: rowDateTimeText,
                        found_in_table: true
                    };
                } else {
                    console.log("🔍 No time match found in dateTimeText");
                }
            }
            
            console.log("🔍 No matching attendance found in table");
        } catch (error) {
            console.warn("❌ Error finding corresponding attendance from table:", error);
        }
        
        return null;
    }

    /**
     * Update current attendee display section
     */
    updateCurrentAttendeeDisplay(attendance, employee, resident, correspondingAttendance = null) {
        try {
            const timeInEl = document.getElementById("time_in");
            const timeOutEl = document.getElementById("time_out");
            const roleEl = document.getElementById("role");
            const employeePhotoEl = document.getElementById("employee_photo");
            const empIdEl = document.getElementById("employee_id");
            const nameEl = document.getElementById("name");
            const windowEl = document.getElementById("window");

            // Reset all employee UI fields to default values before rendering new data
            // This prevents old employee data from persisting when a new attendance is logged
            if (timeInEl) timeInEl.textContent = "-";
            if (timeOutEl) timeOutEl.textContent = "-";
            if (roleEl) roleEl.textContent = "-";
            if (empIdEl) empIdEl.textContent = "-";
            if (nameEl) nameEl.textContent = "-";
            if (windowEl) windowEl.textContent = "-";
            if (employeePhotoEl) employeePhotoEl.src = "./logo.png";

            // If no attendance today, fields are already cleared above, just return
            if (!attendance) {
                return;
            }

            // Format time for current attendance
            const date = new Date(attendance.created_at?.replace(" ", "T") || attendance.timestamp?.replace(" ", "T"));
            const formattedTime = date.toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });

            // Format time for corresponding attendance if available
            let correspondingFormattedTime = null;
            let correspondingWindow = null;
            
            // First, try to use the correspondingAttendance from API
            if (correspondingAttendance) {
                try {
                    const timestamp = correspondingAttendance.created_at || correspondingAttendance.timestamp;
                    if (timestamp) {
                        const correspondingDate = new Date(timestamp.replace(" ", "T"));
                        // Check if date is valid
                        if (!isNaN(correspondingDate.getTime())) {
                            correspondingFormattedTime = correspondingDate.toLocaleTimeString([], {
                                hour: "2-digit",
                                minute: "2-digit",
                                second: "2-digit",
                            });
                            correspondingWindow = correspondingAttendance.window;
                        }
                    }
                } catch (e) {
                    console.warn("Error formatting corresponding attendance time:", e);
                }
            }
            
            // If not found from API, try to find it from the rendered table
            if (!correspondingFormattedTime) {
                console.log("🔍 correspondingFormattedTime not found from API, searching table...");
                const tableCorresponding = this.findCorrespondingAttendanceFromTable(attendance);
                console.log("🔍 Table search result:", tableCorresponding);
                
                if (tableCorresponding && tableCorresponding.timestamp) {
                    try {
                        // Extract time from the timestamp string (format: "Jan 14, 2026 07:24:52 AM")
                        // The table shows format like "Jan 14, 2026 07:24:52 AM"
                        const timeMatch = tableCorresponding.timestamp.match(/(\d{1,2}:\d{2}:\d{2}\s*(?:AM|PM))/i);
                        console.log("🔍 Time match from table:", timeMatch);
                        
                        if (timeMatch) {
                            // Use the time directly from the table (already formatted)
                            correspondingFormattedTime = timeMatch[1].trim();
                            correspondingWindow = tableCorresponding.window;
                            console.log("✅ Extracted time from table:", {
                                time: correspondingFormattedTime,
                                window: correspondingWindow
                            });
                        } else {
                            console.log("⚠️ No time match found in table timestamp");
                        }
                    } catch (e) {
                        console.warn("❌ Error parsing time from table:", e);
                    }
                } else {
                    console.log("⚠️ No corresponding attendance found in table");
                }
            } else {
                console.log("✅ correspondingFormattedTime found from API");
            }

            // Display Employee Info
            if (roleEl) roleEl.textContent = employee?.position_name || employee?.position || "N/A";
            if (empIdEl) empIdEl.textContent = attendance.employee_id || "Unknown";

            const firstName = resident?.first_name || "";
            const lastName = resident?.last_name || "";
            if (nameEl) nameEl.textContent = `${firstName} ${lastName}`.trim() || "Unnamed";
            
            // Handle profile photo - prefer photo_url (full URL from profiling-system) when available
            let photoSrc = "./logo.png"; // Default fallback
            if (resident?.photo_url && resident.photo_url.startsWith('http')) {
                // Backend provides full URL to profiling-system/officials/uploads/officials/
                photoSrc = resident.photo_url;
            } else if (resident?.photo_path) {
                try {
                    let photoPaths = resident.photo_path;
                    // Parse JSON if it's a string
                    if (typeof photoPaths === 'string') {
                        const trimmed = photoPaths.trim();
                        if (trimmed.startsWith('[')) {
                            photoPaths = JSON.parse(photoPaths);
                        } else {
                            // Plain string - build profiling-system URL if not already absolute
                            if (trimmed.startsWith('http')) {
                                photoSrc = trimmed;
                            } else {
                                const filename = trimmed.split('/').pop() || trimmed;
                                const origin = window.location.origin;
                                photoSrc = `${origin}/profiling-system/officials/uploads/officials/${encodeURIComponent(filename)}`;
                            }
                        }
                    }
                    if (Array.isArray(photoPaths) && photoPaths.length > 0) {
                        const firstPhoto = photoPaths[0];
                        if (typeof firstPhoto === 'string' && firstPhoto.startsWith('http')) {
                            photoSrc = firstPhoto;
                        } else {
                            const filename = (firstPhoto || '').split('/').pop() || firstPhoto;
                            const origin = window.location.origin;
                            photoSrc = `${origin}/profiling-system/officials/uploads/officials/${encodeURIComponent(filename)}`;
                        }
                    }
                } catch (e) {
                    console.warn("Error parsing photo_path:", e);
                }
            }
            if (employeePhotoEl) {
                employeePhotoEl.src = photoSrc;
                employeePhotoEl.onerror = function() {
                    this.onerror = null;
                    this.src = "./logo.png";
                };
            }

            // Display Attendance Window (prefer window_label if available)
            const windowText = this.getWindowText(attendance.window, attendance.window_label);
            if (windowEl) windowEl.textContent = windowText;

            // Display Time In / Out (check original window value for logic, not display label)
            const windowValue = attendance.window || "";
            
            // Debug logging
            console.log("🕐 TIME IN/OUT Debug:", {
                window: windowValue,
                currentFormattedTime: formattedTime,
                correspondingFormattedTime: correspondingFormattedTime,
                correspondingWindow: correspondingWindow,
                correspondingAttendance: correspondingAttendance
            });
            
            // Check for "_out" first (more specific) to avoid false matches with "morning_out" containing "in"
            if (windowValue.includes("_out")) {
                // Current attendance is an "out" - display in TIME OUT
                if (timeOutEl) {
                    timeOutEl.textContent = formattedTime;
                    console.log("✅ TIME OUT set to:", formattedTime);
                }
                // If we have corresponding "in" attendance, display it in TIME IN
                if (timeInEl) {
                    // Check if we have corresponding "in" attendance (case-insensitive check)
                    const hasCorrespondingIn = correspondingFormattedTime && (
                        (correspondingWindow && correspondingWindow.toLowerCase().includes("_in")) ||
                        (correspondingAttendance && correspondingAttendance.window && correspondingAttendance.window.toLowerCase().includes("_in"))
                    );
                    
                    if (hasCorrespondingIn) {
                        timeInEl.textContent = correspondingFormattedTime;
                        console.log("✅ TIME IN set to (from corresponding):", correspondingFormattedTime);
                    } else {
                        // Keep as "-" (already reset at the start)
                        console.log("⚠️ TIME IN remains '-' (no corresponding data)", {
                            hasFormattedTime: !!correspondingFormattedTime,
                            hasWindow: !!correspondingWindow,
                            windowValue: correspondingWindow,
                            correspondingAttendanceWindow: correspondingAttendance?.window
                        });
                    }
                }
            } else if (windowValue.includes("_in")) {
                // Current attendance is an "in" - display in TIME IN
                if (timeInEl) {
                    timeInEl.textContent = formattedTime;
                    console.log("✅ TIME IN set to:", formattedTime);
                }
                // If we have corresponding "out" attendance, display it in TIME OUT
                if (timeOutEl) {
                    // Check if we have corresponding "out" attendance (case-insensitive check)
                    const hasCorrespondingOut = correspondingFormattedTime && (
                        (correspondingWindow && correspondingWindow.toLowerCase().includes("_out")) ||
                        (correspondingAttendance && correspondingAttendance.window && correspondingAttendance.window.toLowerCase().includes("_out"))
                    );
                    
                    if (hasCorrespondingOut) {
                        timeOutEl.textContent = correspondingFormattedTime;
                        console.log("✅ TIME OUT set to (from corresponding):", correspondingFormattedTime);
                    } else {
                        // Keep as "-" (already reset at the start)
                        console.log("⚠️ TIME OUT remains '-' (no corresponding data)");
                    }
                }
            } else if (windowValue.toLowerCase().includes("out")) {
                // Fallback for window values that contain "out" but don't have "_out"
                if (timeOutEl) {
                    timeOutEl.textContent = formattedTime;
                    console.log("✅ TIME OUT set to (fallback):", formattedTime);
                }
                if (timeInEl) {
                    if (correspondingFormattedTime && correspondingWindow && correspondingWindow.toLowerCase().includes("in")) {
                        timeInEl.textContent = correspondingFormattedTime;
                        console.log("✅ TIME IN set to (from corresponding, fallback):", correspondingFormattedTime);
                    } else {
                        // Keep as "-" (already reset at the start)
                        console.log("⚠️ TIME IN remains '-' (no corresponding data, fallback)");
                    }
                }
            } else if (windowValue.toLowerCase().includes("in")) {
                // Fallback for window values that contain "in" but don't have "_in"
                // Only check this after confirming it's not an "out" type
                if (timeInEl) {
                    timeInEl.textContent = formattedTime;
                    console.log("✅ TIME IN set to (fallback):", formattedTime);
                }
                if (timeOutEl) {
                    if (correspondingFormattedTime && correspondingWindow && correspondingWindow.toLowerCase().includes("out")) {
                        timeOutEl.textContent = correspondingFormattedTime;
                        console.log("✅ TIME OUT set to (from corresponding, fallback):", correspondingFormattedTime);
                    } else {
                        // Keep as "-" (already reset at the start)
                        console.log("⚠️ TIME OUT remains '-' (no corresponding data, fallback)");
                    }
                }
            } else {
                // Unknown window type - keep as "-" (already reset at the start)
                console.log("⚠️ Unknown window type, TIME IN/OUT remain '-'");
            }
            
            // Final state logging
            console.log("📊 Final TIME IN/OUT values:", {
                timeIn: timeInEl ? timeInEl.textContent : "N/A",
                timeOut: timeOutEl ? timeOutEl.textContent : "N/A"
            });
        } catch (error) {
            console.error("❌ Error updating current attendee display:", error);
        }
    }

    /**
     * Handle attendance updates (called from both WebSocket and polling)
     */
    handleUpdate(data, forceUpdate = false) {
        try {
            const { lastAttendee, lastAttendeeEmployee, lastAttendeeResident, correspondingAttendance } = data;
            
            // Update current attendee display (even if null, to clear the display when no attendance today)
            this.updateCurrentAttendeeDisplay(lastAttendee, lastAttendeeEmployee, lastAttendeeResident, correspondingAttendance);
            
            if (!lastAttendee) {
                return;
            }

            // Extract attendance ID - handle both object and array access
            const currentAttendanceId = lastAttendee.id || lastAttendee['id'];
            if (!currentAttendanceId) {
                return;
            }

            // On initial load, just mark as processed and don't show toast or update table
            if (this.isInitialLoad) {
                this.lastProcessedAttendanceId = currentAttendanceId;
                this.processedAttendanceIds.add(currentAttendanceId);
                this.isInitialLoad = false;
                return;
            }

            // Check if we should force update (from new_attendance message)
            const shouldForceUpdate = forceUpdate || this.forceUpdateNext;
            if (this.forceUpdateNext) {
                this.forceUpdateNext = false; // Reset flag
            }

            // Check if this is a duplicate (already processed) - skip unless forced
            if (!shouldForceUpdate && this.processedAttendanceIds.has(currentAttendanceId)) {
                return; // Skip duplicate
            }

            // Only update table and show toast if this is a NEW attendance OR if forced
            if (shouldForceUpdate || currentAttendanceId !== this.lastProcessedAttendanceId) {
                // Only add to table if it's a confirmed attendance (has id and employee_id)
                if (currentAttendanceId && lastAttendee.employee_id) {
                    // Remove from processed set if it was there (for force update)
                    if (shouldForceUpdate && this.processedAttendanceIds.has(currentAttendanceId)) {
                        this.processedAttendanceIds.delete(currentAttendanceId);
                    }
                    
                    // --- Update Attendance Table ---
                    this.tableRenderer.updateTable(lastAttendee, lastAttendeeResident);

                    // --- Show Success Notification ---
                    const firstName = lastAttendeeResident?.first_name || "";
                    const lastName = lastAttendeeResident?.last_name || "";
                    const employeeName = `${firstName} ${lastName}`.trim() || lastAttendee.employee_id || "Employee";
                    const windowText = this.getWindowText(lastAttendee.window, lastAttendee.window_label);
                    this.toast.show("Attendance Logged Successfully", `${employeeName} - ${windowText}`, 'success', { employeeName });

                    // Mark as processed
                    this.processedAttendanceIds.add(currentAttendanceId);
                }
            }

            // Update the last processed attendance ID
            this.lastProcessedAttendanceId = currentAttendanceId;

        } catch (error) {
            console.error("❌ Error handling attendance update:", error);
        }
    }

    /**
     * Show error toast notification
     */
    showError(title, message) {
        this.toast.show(title, message, 'error');
    }

    /**
     * Fetch latest attendance data from API (only for initial load)
     */
    async fetchLatestAttendance() {
        try {
            // Add cache-busting parameter to ensure fresh data
            const url = this.apiUrl + (this.apiUrl.includes('?') ? '&' : '?') + '_t=' + Date.now();
            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            if (!response.ok) {
                console.error("❌ Failed to fetch attendance data:", response.status);
                return null;
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error("❌ Error fetching attendance data:", error);
            return null;
        }
    }

    /**
     * Initialize with current attendance data on page load (one-time fetch)
     */
    async initialize() {
        try {
            const data = await this.fetchLatestAttendance();
            // Always call handleUpdate to ensure display is properly initialized
            // This clears the display if lastAttendee is null (no attendance today)
            if (data) {
                this.handleUpdate(data);
            }
        } catch (error) {
            console.error("❌ Error in initial fetch:", error);
        }
    }
}
