/**
 * Print DTR Module
 * Handles printing DTR in Civil Service Form No. 48 format (pixel-accurate)
 */

let currentPrintData = null;

export function initPrintDTR() {
    /**
     * Local calendar date YYYY-MM-DD (do not use toISOString() — it shifts dates in UTC+ timezones).
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
     * Set data for printing
     * @param {Object} data 
     * @param {string} fromDate - Optional: start date from UI
     * @param {string} toDate - Optional: end date from UI
     */
    function setData(data, fromDate = null, toDate = null) {
        currentPrintData = {
            ...data,
            _fromDate: fromDate,
            _toDate: toDate
        };
    }

    /**
     * Generate a single DTR form (pixel-accurate)
     */
    function generateSingleDTR(data, attendanceMap, allDays, monthName) {
        // Generate rows for all days in the month
        const rows = [];
        let rowsWithData = 0;
        let monthTotalMinutes = 0;

        allDays.forEach(({ dayNum, date }) => {
            const dayData = attendanceMap[date] || null;
            
            // Debug first few days
            if (dayNum <= 3) {
                console.log(`Day ${dayNum} (${date}):`, {
                    has_data: !!dayData,
                    morning_in: dayData?.morning_in,
                    morning_out: dayData?.morning_out
                });
            }

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
                <tr>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: left; font-size: 5.5pt;">${dayNum}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${morningIn}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${morningOut}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${afternoonIn}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${afternoonOut}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${underH}</td>
                    <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;">${underM}</td>
                </tr>
            `);
        });

        const totalHoursCol = monthTotalMinutes > 0 ? String(Math.floor(monthTotalMinutes / 60)) : '';
        const totalMinutesCol = monthTotalMinutes > 0 ? String(monthTotalMinutes % 60) : '';

        console.log(`Generated ${rows.length} rows, ${rowsWithData} rows have attendance data`);

        return `
            <div style="font-family: 'Times New Roman', Times, serif; width: 48%; flex: 0 0 48%; page-break-inside: avoid; page-break-after: avoid; box-sizing: border-box; padding: 0 6px; margin: 0 3px; display: flex; flex-direction: column; height: 100%;">
                <!-- Header Section -->
                <div style="margin-bottom: 2px; flex-shrink: 0;">
                    <div style="font-size: 6.5pt; text-align: left; margin-bottom: 1px; padding-left: 1px;">Civil Service Form No. 48</div>
                    <div style="font-size: 8.5pt; font-weight: bold; text-align: center; margin-bottom: 1px; letter-spacing: 0.1px; line-height: 1.0;">DAILY TIME RECORD</div>
                    <div style="font-size: 6.5pt; text-align: center; margin-bottom: 2px; letter-spacing: 0.3px;">-----oOo-----</div>
                </div>

                <!-- Employee Name -->
                <div style="margin-bottom: 2px; flex-shrink: 0;">
                    <div style="border-bottom: 1px solid #000; text-align: center; padding-bottom: 1px; margin-bottom: 1px; min-height: 10px; line-height: 10px; font-size: 6pt;">
                        ${data.employee_name || ''}
                    </div>
                    <div style="text-align: center; font-size: 5.5pt; margin-top: 0px;">(Name)</div>
                </div>

                <!-- Month and Official Hours Section -->
                <div style="margin-bottom: 2px; flex-shrink: 0;">
                    <div style="margin-bottom: 2px;">
                        <span style="font-size: 6pt;">For the month of </span>
                        <span style="border-bottom: 1px solid #000; padding: 0 2px; display: inline-block; min-width: 60px; font-size: 6pt;">
                            ${monthName}
                        </span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 2px;">
                        <div style="flex: 1; margin-right: 4px;">
                            <div style="border-bottom: 1px solid #000; padding-bottom: 1px; min-height: 8px; margin-bottom: 1px;"></div>
                            <div style="font-size: 5.5pt;">Official hours for arrival and departure</div>
                        </div>
                        <div style="flex: 1;">
                            <div style="border-bottom: 1px solid #000; padding-bottom: 1px; min-height: 8px; margin-bottom: 2px;"></div>
                            <div style="font-size: 5.5pt; text-align: center;">Regular days</div>
                            <div style="border-bottom: 1px solid #000; padding-bottom: 1px; min-height: 8px; margin-top: 2px; margin-bottom: 1px;"></div>
                            <div style="font-size: 5.5pt; text-align: center;">Saturdays</div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Table -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2px; font-size: 5.5pt; border: 1.5px solid #000; table-layout: fixed; page-break-inside: avoid; flex: 1; min-height: 0;">
                    <thead style="display: table-header-group;">
                        <!-- First Header Row -->
                        <tr style="page-break-inside: avoid;">
                            <th rowspan="2" style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; width: 5%; vertical-align: middle; background: #fff; font-size: 5.5pt;">Day</th>
                            <th colspan="2" style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5.5pt; width: 18%;">A.M.</th>
                            <th colspan="2" style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5.5pt; width: 18%;">P.M.</th>
                            <th colspan="2" style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5.5pt; width: 18%;">Undertime</th>
                        </tr>
                        <!-- Second Header Row -->
                        <tr style="page-break-inside: avoid;">
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Arrival</th>
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Departure</th>
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Arrival</th>
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Departure</th>
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Hours</th>
                            <th style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5pt;">Minutes</th>
                        </tr>
                    </thead>
                    <tbody style="display: table-row-group;">
                        ${rows.join('')}
                        <!-- Total Row: label in Day column; Hours/Minutes = sum of daily totals -->
                        <tr>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: left; font-weight: bold; font-size: 5.5pt;">Total</td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;"></td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;"></td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;"></td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-size: 5.5pt;"></td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5.5pt;">${totalHoursCol}</td>
                            <td style="border: 1px solid #000; padding: 0px 1px; text-align: center; font-weight: bold; font-size: 5.5pt;">${totalMinutesCol}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Certification Section -->
                <div style="margin-top: 2px; margin-bottom: 2px; font-size: 5.5pt; text-align: justify; font-style: italic; line-height: 1.15; flex-shrink: 0;">
                    I certify on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                </div>

                <!-- Verification Section -->
                <div style="margin-top: 2px; margin-bottom: 1px; font-size: 5.5pt; flex-shrink: 0;">
                    VERIFIED as to the prescribed office hours:
                </div>
                <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 10px; margin-right: 4px; flex-shrink: 0;"></div>

                <!-- In Charge Section -->
                <div style="text-align: center; margin-top: 2px; flex-shrink: 0;">
                    <div style="border-bottom: 1px solid #000; margin-bottom: 1px; min-height: 10px; max-width: 80px; margin-left: auto; margin-right: auto;"></div>
                    <div style="font-size: 5.5pt;">In Charge</div>
                </div>
            </div>
        `;
    }

    /**
     * Generate DTR print content (side-by-side format)
     */
    function generatePrintContent() {
        if (!currentPrintData) {
            console.error('generatePrintContent: No currentPrintData');
            return '';
        }

        const data = currentPrintData;
        const attendanceData = data.attendance_data || [];
        
        console.log('Generating print content with data:', {
            employee_name: data.employee_name,
            attendance_count: attendanceData.length,
            fromDate: data._fromDate,
            toDate: data._toDate,
            has_attendance_data: Array.isArray(attendanceData) && attendanceData.length > 0
        });
        
        if (!Array.isArray(attendanceData) || attendanceData.length === 0) {
            console.warn('No attendance data available for printing');
            return '<div style="padding: 20px; text-align: center;">No attendance data available. Please load attendance data first.</div>';
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
            sample_dates: Object.keys(attendanceMap).slice(0, 5),
            sample_day_data: attendanceData.length > 0 ? {
                date: attendanceData[0].date,
                normalized_date: attendanceData[0].date?.split('T')[0],
                has_morning_in: !!attendanceData[0].morning_in,
                morning_in_type: typeof attendanceData[0].morning_in,
                morning_in_timestamp: attendanceData[0].morning_in?.timestamp,
                morning_in_keys: attendanceData[0].morning_in ? Object.keys(attendanceData[0].morning_in) : []
            } : null
        });

        // Get all days in the month dynamically (28-31 days based on month/year)
        const allDays = getAllDaysInMonth(fromDate, toDate);
        console.log('Days in month:', allDays.length, 'Sample dates:', allDays.slice(0, 5).map(d => d.date));

        if (allDays.length === 0) {
            console.error('No days generated for month');
            return '<div style="padding: 20px; text-align: center;">Error: Could not determine month. Please select a valid date range.</div>';
        }

        // Verify data mapping
        let matchedDays = 0;
        allDays.forEach(({ date }) => {
            if (attendanceMap[date]) {
                matchedDays++;
            }
        });
        console.log(`Data mapping: ${matchedDays} out of ${allDays.length} days have attendance data`);

        // Generate two identical forms side-by-side
        const form1 = generateSingleDTR(data, attendanceMap, allDays, monthName);
        const form2 = generateSingleDTR(data, attendanceMap, allDays, monthName);

        const html = `
            <div style="width: 100%; max-width: 100%; margin: 0; padding: 5px 8px; box-sizing: border-box; display: flex; justify-content: space-between; gap: 10px; page-break-inside: avoid;">
                ${form1}
                ${form2}
            </div>
        `;
        
        console.log('Generated HTML length:', html.length);
        return html;
    }

    /**
     * Print DTR
     */
    function print() {
        if (!currentPrintData) {
            alert('No data to print. Please load attendance data first.');
            return;
        }

        // Verify data structure
        if (!currentPrintData.attendance_data || !Array.isArray(currentPrintData.attendance_data)) {
            console.error('Invalid data structure:', currentPrintData);
            alert('Error: Invalid attendance data. Please load attendance data again.');
            return;
        }

        if (currentPrintData.attendance_data.length === 0) {
            alert('No attendance records found for the selected date range. Please select a different date range or employee.');
            return;
        }

        const printContent = document.getElementById('dtr-print-content');
        if (!printContent) {
            console.error('Print content container not found');
            alert('Error: Print container not found. Please refresh the page.');
            return;
        }

        console.log('Starting print generation...');
        const html = generatePrintContent();
        if (!html || html.trim() === '') {
            console.error('Generated print content is empty');
            console.log('Current print data:', currentPrintData);
            alert('Error: No content to print. Please check if attendance data is loaded.');
            return;
        }

        // Set the content
        printContent.innerHTML = html;
        
        // Verify content was inserted
        if (printContent.innerHTML.trim() === '') {
            console.error('Content was not inserted into print container');
            alert('Error: Content generation failed. Please check console for details.');
            return;
        }
        
        console.log('Print content inserted, length:', printContent.innerHTML.length);
        console.log('First 500 chars:', printContent.innerHTML.substring(0, 500));

        // Small delay to ensure DOM is updated and rendered
        setTimeout(() => {
            // Force a reflow to ensure content is rendered
            printContent.offsetHeight;
            window.print();
        }, 200);
    }

    return {
        setData,
        print
    };
}
