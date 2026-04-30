/**
 * View DTR Module
 * Handles displaying DTR in a modal (similar to print format but for viewing)
 */

let currentViewData = null;

export function initViewDTR() {
    /**
     * Local calendar date YYYY-MM-DD (avoid toISOString() — shifts dates in UTC+ timezones).
     * @param {Date} date
     * @returns {string}
     */
    function formatLocalDateYYYYMMDD(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /**
     * Format time from timestamp (24-hour format for DTR)
     * @param {string|null} timestamp 
     * @returns {string}
     */
    function formatTime(timestamp) {
        if (!timestamp) return '';
        let parsed;
        if (typeof timestamp === 'string') {
            const s = timestamp.trim();
            if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(s)) {
                parsed = new Date(s.replace(' ', 'T'));
            } else {
                parsed = new Date(s);
            }
        } else {
            parsed = new Date(timestamp);
        }
        if (Number.isNaN(parsed.getTime())) return '';
        const hours = parsed.getHours();
        const minutes = parsed.getMinutes();
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    /**
     * Format month name
     * @param {string} dateStr 
     * @returns {string}
     */
    function getMonthName(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    /**
     * Get number of days in month with leap year support
     * @param {number} year 
     * @param {number} month (0-11)
     * @returns {number}
     */
    function getDaysInMonth(year, month) {
        return new Date(year, month + 1, 0).getDate();
    }

    /**
     * Get all days in month dynamically (28-31 days based on month/year)
     * @param {string} fromDate 
     * @param {string} toDate 
     * @returns {Array}
     */
    function getAllDaysInMonth(fromDate, toDate) {
        if (!fromDate) {
            console.warn('getAllDaysInMonth: No fromDate provided');
            return [];
        }
        
        try {
            // Use the first date to determine the month
            // Handle both YYYY-MM-DD format and ensure proper parsing
            const dateStr = fromDate.includes('T') ? fromDate : fromDate + 'T00:00:00';
            const firstDate = new Date(dateStr);
            
            if (isNaN(firstDate.getTime())) {
                console.error('Invalid date:', fromDate);
                return [];
            }
            
            const year = firstDate.getFullYear();
            const month = firstDate.getMonth();
            
            // Get number of days in the month (respects leap years)
            const daysInMonth = getDaysInMonth(year, month);
            
            const days = [];
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                days.push({
                    dayNum: day,
                    date: formatLocalDateYYYYMMDD(date),
                });
            }
            
            return days;
        } catch (error) {
            console.error('Error in getAllDaysInMonth:', error);
            return [];
        }
    }

    /**
     * Set data for viewing
     * @param {Object} data 
     * @param {string} fromDate - Optional: start date from UI
     * @param {string} toDate - Optional: end date from UI
     */
    function setData(data, fromDate = null, toDate = null) {
        currentViewData = {
            ...data,
            _fromDate: fromDate,
            _toDate: toDate
        };
    }

    /**
     * Generate a single DTR form for viewing (similar to print but styled for modal)
     */
    function generateSingleDTR(data, attendanceMap, allDays, monthName) {
        // Generate rows for all days in the month
        const rows = [];
        let rowsWithData = 0;
        let monthTotalMinutes = 0;

        allDays.forEach(({ dayNum, date }) => {
            const dayData = attendanceMap[date] || null;

            // Extract time values from attendance data
            // API returns: morning_in, morning_out, afternoon_in, afternoon_out as objects with 'timestamp' property or null
            // Handle both object format (from API) and potential null values
            let morningIn = '';
            let morningOut = '';
            let afternoonIn = '';
            let afternoonOut = '';

            if (dayData) {
                // Handle morning_in
                if (dayData.morning_in) {
                    if (typeof dayData.morning_in === 'object' && dayData.morning_in.timestamp) {
                        morningIn = formatTime(dayData.morning_in.timestamp);
                    } else if (typeof dayData.morning_in === 'string') {
                        morningIn = formatTime(dayData.morning_in);
                    }
                }

                // Handle morning_out
                if (dayData.morning_out) {
                    if (typeof dayData.morning_out === 'object' && dayData.morning_out.timestamp) {
                        morningOut = formatTime(dayData.morning_out.timestamp);
                    } else if (typeof dayData.morning_out === 'string') {
                        morningOut = formatTime(dayData.morning_out);
                    }
                }

                // Handle afternoon_in
                if (dayData.afternoon_in) {
                    if (typeof dayData.afternoon_in === 'object' && dayData.afternoon_in.timestamp) {
                        afternoonIn = formatTime(dayData.afternoon_in.timestamp);
                    } else if (typeof dayData.afternoon_in === 'string') {
                        afternoonIn = formatTime(dayData.afternoon_in);
                    }
                }

                // Handle afternoon_out
                if (dayData.afternoon_out) {
                    if (typeof dayData.afternoon_out === 'object' && dayData.afternoon_out.timestamp) {
                        afternoonOut = formatTime(dayData.afternoon_out.timestamp);
                    } else if (typeof dayData.afternoon_out === 'string') {
                        afternoonOut = formatTime(dayData.afternoon_out);
                    }
                }
            }
            
            if (morningIn || morningOut || afternoonIn || afternoonOut) {
                rowsWithData++;
            }

            let underH = '';
            let underM = '';
            if (dayData && (morningIn || morningOut || afternoonIn || afternoonOut)) {
                const th = dayData.total_hours;
                const tm = dayData.total_minutes;
                underH = th !== undefined && th !== null ? String(th) : '0';
                underM = tm !== undefined && tm !== null ? String(tm) : '0';
                monthTotalMinutes +=
                    (Number(dayData.total_hours) || 0) * 60 + (Number(dayData.total_minutes) || 0);
            }

            rows.push(`
                <tr class="border-b border-gray-300">
                    <td class="px-2 py-1 text-xs text-left border-r border-gray-300">${dayNum}</td>
                    <td class="px-2 py-1 text-xs text-center border-r border-gray-300">${morningIn}</td>
                    <td class="px-2 py-1 text-xs text-center border-r border-gray-300">${morningOut}</td>
                    <td class="px-2 py-1 text-xs text-center border-r border-gray-300">${afternoonIn}</td>
                    <td class="px-2 py-1 text-xs text-center border-r border-gray-300">${afternoonOut}</td>
                    <td class="px-2 py-1 text-xs text-center border-r border-gray-300">${underH}</td>
                    <td class="px-2 py-1 text-xs text-center">${underM}</td>
                </tr>
            `);
        });

        const totalHoursCol = monthTotalMinutes > 0 ? String(Math.floor(monthTotalMinutes / 60)) : '';
        const totalMinutesCol = monthTotalMinutes > 0 ? String(monthTotalMinutes % 60) : '';

        console.log(`Generated ${rows.length} rows, ${rowsWithData} rows have attendance data`);

        return `
            <div class="border-2 border-gray-800 p-4 mb-4 bg-white" style="font-family: 'Times New Roman', Times, serif;">
                <!-- Header Section -->
                <div class="mb-2">
                    <div class="text-xs text-left mb-1">Civil Service Form No. 48</div>
                    <div class="text-sm font-bold text-center mb-1">DAILY TIME RECORD</div>
                    <div class="text-xs text-center mb-2">-----oOo-----</div>
                </div>

                <!-- Employee Name -->
                <div class="mb-2">
                    <div class="border-b border-gray-800 text-center pb-1 mb-1 min-h-[20px] text-xs">
                        ${data.employee_name || ''}
                    </div>
                    <div class="text-center text-xs mt-0">(Name)</div>
                </div>

                <!-- Month and Official Hours Section -->
                <div class="mb-2">
                    <div class="mb-2">
                        <span class="text-xs">For the month of </span>
                        <span class="border-b border-gray-800 px-1 inline-block min-w-[80px] text-xs">
                            ${monthName}
                        </span>
                    </div>
                    
                    <div class="flex justify-between mt-2">
                        <div class="flex-1 mr-2">
                            <div class="border-b border-gray-800 pb-1 min-h-[16px] mb-1"></div>
                            <div class="text-xs">Official hours for arrival and departure</div>
                        </div>
                        <div class="flex-1">
                            <div class="border-b border-gray-800 pb-1 min-h-[16px] mb-2"></div>
                            <div class="text-xs text-center">Regular days</div>
                            <div class="border-b border-gray-800 pb-1 min-h-[16px] mt-2 mb-1"></div>
                            <div class="text-xs text-center">Saturdays</div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <table class="w-full border-collapse border-2 border-gray-800 mb-2 text-xs" style="table-layout: fixed;">
                    <thead>
                        <!-- First Header Row -->
                        <tr>
                            <th rowspan="2" class="border border-gray-800 px-1 py-1 text-center font-bold w-[5%] align-middle bg-gray-100 text-xs">Day</th>
                            <th colspan="2" class="border border-gray-800 px-1 py-1 text-center font-bold text-xs w-[18%]">A.M.</th>
                            <th colspan="2" class="border border-gray-800 px-1 py-1 text-center font-bold text-xs w-[18%]">P.M.</th>
                            <th colspan="2" class="border border-gray-800 px-1 py-1 text-center font-bold text-xs w-[18%]">Undertime</th>
                        </tr>
                        <!-- Second Header Row -->
                        <tr>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Arrival</th>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Departure</th>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Arrival</th>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Departure</th>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Hours</th>
                            <th class="border border-gray-800 px-1 py-1 text-center font-bold text-xs">Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.join('')}
                        <!-- Total Row -->
                        <tr>
                            <td class="px-2 py-1 text-xs text-left font-bold border border-gray-800">Total</td>
                            <td class="px-2 py-1 text-xs text-center border border-gray-800"></td>
                            <td class="px-2 py-1 text-xs text-center border border-gray-800"></td>
                            <td class="px-2 py-1 text-xs text-center border border-gray-800"></td>
                            <td class="px-2 py-1 text-xs text-center border border-gray-800"></td>
                            <td class="px-2 py-1 text-xs text-center font-bold border border-gray-800">${totalHoursCol}</td>
                            <td class="px-2 py-1 text-xs text-center font-bold border border-gray-800">${totalMinutesCol}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Certification Section -->
                <div class="mt-2 mb-2 text-xs text-justify italic leading-tight">
                    I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                </div>

                <!-- Verification Section -->
                <div class="mt-2 mb-1 text-xs">
                    VERIFIED as to the prescribed office hours:
                </div>
                <div class="border-b border-gray-800 mb-2 min-h-[20px] mr-2"></div>

                <!-- In Charge Section -->
                <div class="text-center mt-2">
                    <div class="border-b border-gray-800 mb-1 min-h-[20px] max-w-[100px] mx-auto"></div>
                    <div class="text-xs">In Charge</div>
                </div>
            </div>
        `;
    }

    /**
     * Generate DTR view content (single form for modal display)
     */
    function generateViewContent() {
        if (!currentViewData) {
            console.error('generateViewContent: No currentViewData');
            return '';
        }

        const data = currentViewData;
        const attendanceData = data.attendance_data || [];
        
        console.log('Generating view content with data:', {
            employee_name: data.employee_name,
            attendance_count: attendanceData.length,
            fromDate: data._fromDate,
            toDate: data._toDate,
            has_attendance_data: Array.isArray(attendanceData) && attendanceData.length > 0
        });
        
        if (!Array.isArray(attendanceData) || attendanceData.length === 0) {
            console.warn('No attendance data available for viewing');
            return '<div class="p-5 text-center text-gray-500">No attendance data available. Please load attendance data first.</div>';
        }
        
        // Determine month from provided date range, attendance data, or current month
        let fromDate = data._fromDate || '';
        let toDate = data._toDate || '';
        
        // If no date range provided, try to infer from attendance data
        if (!fromDate && attendanceData.length > 0) {
            const dates = attendanceData.map(d => d.date).filter(Boolean).sort();
            if (dates.length > 0) {
                fromDate = dates[0];
                toDate = dates[dates.length - 1];
            }
        }
        
        // If still no date, use current month as fallback
        if (!fromDate) {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            fromDate = formatLocalDateYYYYMMDD(firstDay);
            toDate = formatLocalDateYYYYMMDD(lastDay);
        }
        
        console.log('Using date range:', { fromDate, toDate });
        
        const monthName = fromDate ? getMonthName(fromDate) : '';
        console.log('Month name:', monthName);

        // Create a map for quick lookup - ensure dates are normalized to YYYY-MM-DD format
        const attendanceMap = {};
        attendanceData.forEach(day => {
            if (day && day.date) {
                // Normalize date to YYYY-MM-DD format
                const normalizedDate = day.date.split('T')[0];
                attendanceMap[normalizedDate] = day;
            }
        });
        
        console.log('Attendance map created:', {
            total_days_in_data: attendanceData.length,
            total_days_in_map: Object.keys(attendanceMap).length,
            sample_dates: Object.keys(attendanceMap).slice(0, 5)
        });

        // Get all days in the month dynamically (28-31 days based on month/year)
        const allDays = getAllDaysInMonth(fromDate, toDate);
        console.log('Days in month:', allDays.length, 'Sample dates:', allDays.slice(0, 5).map(d => d.date));

        if (allDays.length === 0) {
            console.error('No days generated for month');
            return '<div class="p-5 text-center text-red-500">Error: Could not determine month. Please select a valid date range.</div>';
        }

        // Verify data mapping
        let matchedDays = 0;
        allDays.forEach(({ date }) => {
            if (attendanceMap[date]) {
                matchedDays++;
            }
        });
        console.log(`Data mapping: ${matchedDays} out of ${allDays.length} days have attendance data`);

        // Generate single form for modal view
        const form = generateSingleDTR(data, attendanceMap, allDays, monthName);

        return form;
    }

    /**
     * Show DTR in modal
     */
    function show() {
        if (!currentViewData) {
            alert('No data to view. Please load attendance data first.');
            return;
        }

        // Verify data structure
        if (!currentViewData.attendance_data || !Array.isArray(currentViewData.attendance_data)) {
            console.error('Invalid data structure:', currentViewData);
            alert('Error: Invalid attendance data. Please load attendance data again.');
            return;
        }

        if (currentViewData.attendance_data.length === 0) {
            alert('No attendance records found for the selected date range. Please select a different date range or employee.');
            return;
        }

        const viewContent = document.getElementById('view-dtr-content');
        if (!viewContent) {
            console.error('View content container not found');
            alert('Error: View container not found. Please refresh the page.');
            return;
        }

        console.log('Starting view generation...');
        const html = generateViewContent();
        if (!html || html.trim() === '') {
            console.error('Generated view content is empty');
            console.log('Current view data:', currentViewData);
            alert('Error: No content to view. Please check if attendance data is loaded.');
            return;
        }

        // Set the content
        viewContent.innerHTML = html;
        
        // Verify content was inserted
        if (viewContent.innerHTML.trim() === '') {
            console.error('Content was not inserted into view container');
            alert('Error: Content generation failed. Please check console for details.');
            return;
        }
        
        console.log('View content inserted, length:', viewContent.innerHTML.length);

        // Show modal
        const modal = document.getElementById('view-dtr-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    /**
     * Hide DTR modal
     */
    function hide() {
        const modal = document.getElementById('view-dtr-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Initialize modal close handlers
    function initModalHandlers() {
        const modal = document.getElementById('view-dtr-modal');
        const closeBtn = document.getElementById('close-view-dtr-modal');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', hide);
        }
        
        if (modal) {
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hide();
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    hide();
                }
            });
        }
    }

    // Initialize handlers when module loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalHandlers);
    } else {
        initModalHandlers();
    }

    return {
        setData,
        show,
        hide
    };
}
