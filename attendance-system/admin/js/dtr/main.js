/**
 * DTR Main Entry Point
 * Coordinates all DTR modules
 */

import { initDataFetcher } from './dataFetcher.js';
import { initTableRenderer } from './tableRenderer.js';
import { initAnomalyDetector } from './anomalyDetector.js';
import { initChartRenderer } from './chartRenderer.js';
import { initPrintDTR } from './printDTR.js';
import { initViewDTR } from './viewDTR.js';

let currentData = null;

/**
 * Initialize all DTR functionality
 */
function initDTR() {
    // Initialize data fetcher
    const dataFetcher = initDataFetcher();
    
    // Initialize table renderer
    const tableRenderer = initTableRenderer();
    
    // Initialize anomaly detector
    const anomalyDetector = initAnomalyDetector();
    
    // Initialize chart renderer
    const chartRenderer = initChartRenderer();
    
    // Initialize print DTR
    const printDTR = initPrintDTR();
    
    // Initialize view DTR
    const viewDTR = initViewDTR();

    // Load button handler
    const loadBtn = document.getElementById('load-data-btn');
    const employeeSelect = document.getElementById('employee-select');
    const fromDate = document.getElementById('from-date');
    const toDate = document.getElementById('to-date');
    const viewBtn = document.getElementById('view-dtr-btn');
    const printBtn = document.getElementById('print-dtr-btn');

    if (!loadBtn || !employeeSelect || !fromDate || !toDate || !viewBtn || !printBtn) {
        console.error('DTR init: missing required DOM elements');
        return;
    }

    function resetLoadedState() {
        currentData = null;
        viewBtn.disabled = true;
        printBtn.disabled = true;
        document.getElementById('employee-info-card')?.classList.add('hidden');
        document.getElementById('charts-section')?.classList.add('hidden');
        document.getElementById('attendance-table-section')?.classList.add('hidden');
        document.getElementById('anomalies-section')?.classList.add('hidden');
    }

    // If user changes employee or date filters, require re-load
    employeeSelect.addEventListener('change', resetLoadedState);
    fromDate.addEventListener('change', resetLoadedState);
    toDate.addEventListener('change', resetLoadedState);

    loadBtn.addEventListener('click', async () => {
        const employeeId = employeeSelect.value;
        const from = fromDate.value;
        const to = toDate.value;

        if (!employeeId) {
            alert('Please select an employee');
            return;
        }

        if (from && to && from > to) {
            alert('Invalid date range: "From Date" must be earlier than or equal to "To Date".');
            return;
        }

        loadBtn.disabled = true;
        loadBtn.textContent = 'Loading...';

        try {
            const data = await dataFetcher.fetchAttendanceData(employeeId, from, to);
            currentData = data;

            // Update employee info
            const employeeInfoCard = document.getElementById('employee-info-card');
            const employeeNameDisplay = document.getElementById('employee-name-display');
            const employeeIdDisplay = document.getElementById('employee-id-display');
            
            employeeInfoCard.classList.remove('hidden');
            employeeNameDisplay.textContent = data.employee_name || '(Unknown name)';
            employeeIdDisplay.textContent = `Employee ID: ${data.employee_id}`;

            // Render table
            tableRenderer.render(data.attendance_data);

            // Render anomalies
            anomalyDetector.render(data.anomalies);

            // Render charts
            chartRenderer.renderBarChart(data.attendance_data);
            chartRenderer.renderPieChart(data.statistics);

            // Show sections
            document.getElementById('charts-section').classList.remove('hidden');
            document.getElementById('attendance-table-section').classList.remove('hidden');
            document.getElementById('anomalies-section').classList.remove('hidden');

            // Enable view and print only when there is data to show/print
            const hasRows = Array.isArray(data.attendance_data) && data.attendance_data.length > 0;
            viewBtn.disabled = !hasRows;
            printBtn.disabled = !hasRows;
            viewDTR.setData(data, from, to);
            printDTR.setData(data, from, to);

        } catch (error) {
            console.error('Error loading attendance data:', error);
            alert('Failed to load attendance data: ' + (error.message || 'Unknown error'));
            resetLoadedState();
        } finally {
            loadBtn.disabled = false;
            loadBtn.textContent = 'Load Attendance Data';
        }
    });

    // View DTR button handler
    viewBtn.addEventListener('click', () => {
        if (currentData) {
            // Ensure data is set before viewing (in case it was cleared)
            const fromDate = document.getElementById('from-date').value;
            const toDate = document.getElementById('to-date').value;
            viewDTR.setData(currentData, fromDate, toDate);
            viewDTR.show();
        } else {
            alert('No data to view. Please load attendance data first.');
        }
    });

    // Print button handler
    printBtn.addEventListener('click', () => {
        if (currentData) {
            // Ensure data is set before printing (in case it was cleared)
            const fromDate = document.getElementById('from-date').value;
            const toDate = document.getElementById('to-date').value;
            printDTR.setData(currentData, fromDate, toDate);
            printDTR.print();
        } else {
            alert('No data to print. Please load attendance data first.');
        }
    });

    // Set default dates (current month) using local calendar dates — not toISOString() (UTC shift in PH)
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const toYmd = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    fromDate.value = toYmd(firstDay);
    toDate.value = toYmd(lastDay);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDTR);
} else {
    initDTR();
}
