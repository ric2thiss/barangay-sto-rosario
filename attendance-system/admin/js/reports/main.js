/**
 * Legacy entry for the old standalone Attendance Reports page.
 * The same UI is integrated in admin/attendance-analytics.php (section "Total hours & summaries").
 * The reports hub is admin/reports.php.
 */

import { initSidebar } from '../shared/sidebar.js';

// Initialize sidebar
initSidebar();

// API endpoint for reports (relative to admin/reports.php)
const API_ENDPOINT = '../api/reports/index.php';

// Get DOM elements
const reportTypeSelect = document.getElementById('reportType');
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const runReportBtn = document.getElementById('runReportBtn');
const exportReportBtn = document.getElementById('exportReportBtn');
const loadingIndicator = document.getElementById('loadingIndicator');
const chartSection = document.getElementById('chartSection');
const chartContainer = document.getElementById('chartContainer');
const chartTitle = document.getElementById('chartTitle');
const tableSection = document.getElementById('tableSection');
const tableHeader = document.getElementById('tableHeader');
const tableBody = document.getElementById('tableBody');

let currentData = null;

// Report type configuration
const reportConfigs = {
    'attendance-position': {
        title: 'Total Hours Worked by Position',
        chartLabel: 'Total Hours',
        tableHeaders: ['Position', 'Total Employees', 'Total Attendance', 'Total Hours (h)', 'Avg. Hours/Emp'],
        getChartValue: (row) => row.total_hours,
        getChartLabel: (row) => row.position,
        getTableRow: (row) => [
            row.position,
            row.total_employees,
            row.total_attendance,
            row.total_hours.toLocaleString(),
            row.avg_hours_per_employee.toFixed(1)
        ]
    },
    'attendance-chairmanship': {
        title: 'Total Hours Worked by Chairmanship',
        chartLabel: 'Total Hours',
        tableHeaders: ['Chairmanship', 'Total Employees', 'Total Attendance', 'Total Hours (h)', 'Avg. Hours/Emp'],
        getChartValue: (row) => row.total_hours,
        getChartLabel: (row) => row.chairmanship,
        getTableRow: (row) => [
            row.chairmanship,
            row.total_employees,
            row.total_attendance,
            row.total_hours.toLocaleString(),
            row.avg_hours_per_employee.toFixed(1)
        ]
    },
    'attendance-employee': {
        title: 'Total Hours Worked by Employee',
        chartLabel: 'Total Hours',
        tableHeaders: ['Employee Name', 'Position', 'Chairmanship', 'Total Attendance', 'Total Hours (h)'],
        getChartValue: (row) => row.total_hours,
        getChartLabel: (row) => row.employee_name,
        getTableRow: (row) => [
            row.employee_name,
            row.position,
            row.chairmanship,
            row.total_attendance,
            row.total_hours.toLocaleString()
        ]
    },
    'attendance-daily': {
        title: 'Daily Attendance Summary',
        chartLabel: 'Total Hours',
        tableHeaders: ['Date', 'Employee Name', 'Position', 'Chairmanship', 'Time In', 'Time Out', 'Total Hours'],
        getChartValue: (row) => row.total_hours,
        getChartLabel: (row) => row.date + ' - ' + row.employee_name.substring(0, 15),
        getTableRow: (row) => [
            row.date,
            row.employee_name,
            row.position,
            row.chairmanship,
            row.time_in,
            row.time_out,
            row.total_hours.toFixed(2)
        ]
    }
};

/**
 * Fetch report data from API
 */
async function fetchReportData(type, fromDate, toDate) {
    const url = `${API_ENDPOINT}?type=${encodeURIComponent(type)}&from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`;

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching report data:', error);
        throw error;
    }
}

/**
 * Render chart using D3.js
 */
function renderChart(data, config) {
    // Clear existing chart
    d3.select("#chartContainer").selectAll("*").remove();

    if (!data || data.length === 0) {
        chartContainer.innerHTML = '<p class="text-center text-gray-500 py-8">No data available for the selected period.</p>';
        return;
    }

    const container = document.getElementById('chartContainer');
    const margin = { top: 20, right: 30, bottom: 60, left: 70 };
    let containerWidth = container.clientWidth;
    
    // Fallback: if width is 0 (container hidden), use parent width or default
    if (containerWidth === 0) {
        const parent = container.parentElement;
        if (parent) {
            containerWidth = parent.clientWidth - 48; // Account for padding (p-6 = 24px each side)
        }
        // If still 0, use a default width
        if (containerWidth === 0) {
            containerWidth = 800; // Default width
        }
    }
    
    const containerHeight = 400;
    const width = containerWidth - margin.left - margin.right;
    const height = containerHeight - margin.top - margin.bottom;

    // Append SVG
    const svg = d3.select("#chartContainer")
        .append("svg")
        .attr("width", containerWidth)
        .attr("height", containerHeight)
        .append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // X scale (Labels - band scale)
    const x = d3.scaleBand()
        .domain(data.map(d => config.getChartLabel(d)))
        .range([0, width])
        .padding(0.2);

    // Y scale (Values - linear scale)
    const maxValue = d3.max(data, d => config.getChartValue(d));
    const y = d3.scaleLinear()
        .domain([0, maxValue * 1.1])
        .range([height, 0]);

    // Add X axis
    svg.append("g")
        .attr("transform", `translate(0,${height})`)
        .call(d3.axisBottom(x))
        .selectAll("text")
        .attr("transform", "translate(-10,0)rotate(-45)")
        .style("text-anchor", "end");

    // Add Y axis
    svg.append("g")
        .call(d3.axisLeft(y).ticks(5));

    // Add bars
    svg.selectAll("bar")
        .data(data)
        .enter()
        .append("rect")
        .attr("x", d => x(config.getChartLabel(d)))
        .attr("y", d => y(config.getChartValue(d)))
        .attr("width", x.bandwidth())
        .attr("height", d => height - y(config.getChartValue(d)))
        .attr("fill", "#3b82f6")
        .attr("rx", 4)
        .transition()
        .duration(800)
        .attr("height", d => height - y(config.getChartValue(d)));

    // Add Y axis label
    svg.append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 0 - margin.left)
        .attr("x", 0 - (height / 2))
        .attr("dy", "1em")
        .style("text-anchor", "middle")
        .style("font-size", "14px")
        .text(config.chartLabel);
}

/**
 * Render data table
 */
function renderTable(data, config) {
    // Clear existing table content
    tableHeader.innerHTML = '';
    tableBody.innerHTML = '';

    if (!data || data.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="${config.tableHeaders.length}" class="px-6 py-8 text-center text-gray-500">
                    <p class="text-sm">No data available for the selected period.</p>
                </td>
            </tr>
        `;
        return;
    }

    // Render headers
    config.tableHeaders.forEach(header => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = header;
        tableHeader.appendChild(th);
    });

    // Render rows
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        
        config.getTableRow(row).forEach(cell => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            td.textContent = cell;
            tr.appendChild(td);
        });

        tableBody.appendChild(tr);
    });
}

/**
 * Update report display
 */
async function updateReport() {
    const type = reportTypeSelect.value;
    const fromDate = startDateInput.value;
    const toDate = endDateInput.value;

    if (!fromDate || !toDate) {
        alert('Please select both start and end dates.');
        return;
    }

    if (new Date(fromDate) > new Date(toDate)) {
        alert('Start date must be before end date.');
        return;
    }

    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    chartSection.classList.add('hidden');
    tableSection.classList.add('hidden');

    try {
        const response = await fetchReportData(type, fromDate, toDate);
        
        if (!response.success || !response.data) {
            throw new Error(response.error || 'Failed to fetch report data');
        }

        currentData = response.data;
        const config = reportConfigs[type];

        if (!config) {
            throw new Error('Unknown report type');
        }

        // Update chart title with date range
        const fromDateStr = new Date(fromDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const toDateStr = new Date(toDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        chartTitle.textContent = `${config.title} (${fromDateStr} - ${toDateStr})`;

        // Show sections first (before rendering chart so container has width)
        chartSection.classList.remove('hidden');
        tableSection.classList.remove('hidden');

        // Render table immediately
        renderTable(response.data, config);

        // Render chart after a brief delay to ensure container has width
        requestAnimationFrame(() => {
            renderChart(response.data, config);
        });

    } catch (error) {
        console.error('Error updating report:', error);
        alert('Error loading report data: ' + error.message);
        chartContainer.innerHTML = `<p class="text-center text-red-500 py-8">Error: ${error.message}</p>`;
        tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">Error: ${error.message}</td></tr>`;
    } finally {
        loadingIndicator.classList.add('hidden');
    }
}

/**
 * Export report data
 */
function exportReport() {
    if (!currentData) {
        alert('Please generate a report first.');
        return;
    }

    const type = reportTypeSelect.value;
    const config = reportConfigs[type];
    
    if (!config) {
        alert('Unknown report type');
        return;
    }

    // Create CSV content
    const headers = config.tableHeaders.join(',');
    const rows = currentData.map(row => {
        return config.getTableRow(row).map(cell => `"${cell}"`).join(',');
    });
    const csv = [headers, ...rows].join('\n');

    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `report_${type}_${startDateInput.value}_${endDateInput.value}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Event listeners
if (runReportBtn) {
    runReportBtn.addEventListener('click', updateReport);
}

if (exportReportBtn) {
    exportReportBtn.addEventListener('click', exportReport);
}

// Auto-run report on page load if dates are set
document.addEventListener('DOMContentLoaded', () => {
    if (startDateInput.value && endDateInput.value) {
        updateReport();
    }
});

// Handle window resize for chart
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (currentData && reportTypeSelect.value) {
            const config = reportConfigs[reportTypeSelect.value];
            if (config) {
                renderChart(currentData, config);
            }
        }
    }, 250);
});
