/**
 * Total hours / attendance summary reports (from legacy admin/reports.php).
 * Uses api/reports/index.php — same-origin credentials for session auth.
 */

import getBaseUrl from '../shared/baseUrl.js';

function reportsApiUrl() {
    const base = getBaseUrl().replace(/\/$/, '');
    return `${base}/api/reports/index.php`;
}

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
            row.avg_hours_per_employee.toFixed(1),
        ],
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
            row.avg_hours_per_employee.toFixed(1),
        ],
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
            row.total_hours.toLocaleString(),
        ],
    },
    'attendance-daily': {
        title: 'Daily Attendance Summary',
        chartLabel: 'Total Hours',
        tableHeaders: ['Date', 'Employee Name', 'Position', 'Chairmanship', 'Time In', 'Time Out', 'Total Hours'],
        getChartValue: (row) => row.total_hours,
        getChartLabel: (row) => `${row.date} - ${row.employee_name.substring(0, 15)}`,
        getTableRow: (row) => [
            row.date,
            row.employee_name,
            row.position,
            row.chairmanship,
            row.time_in,
            row.time_out,
            row.total_hours.toFixed(2),
        ],
    },
};

/**
 * @param {{ autoRun?: boolean }} opts
 */
export function initHoursReports(opts = {}) {
    const { autoRun = true } = opts;

    const reportTypeSelect = document.getElementById('hours-report-type');
    const startDateInput = document.getElementById('hours-report-start');
    const endDateInput = document.getElementById('hours-report-end');
    const runReportBtn = document.getElementById('hours-report-run');
    const exportReportBtn = document.getElementById('hours-report-export');
    const loadingIndicator = document.getElementById('hours-report-loading');
    const chartSection = document.getElementById('hours-report-chart-section');
    const chartContainer = document.getElementById('hours-report-chart-container');
    const chartTitle = document.getElementById('hours-report-chart-title');
    const tableSection = document.getElementById('hours-report-table-section');
    const tableHeader = document.getElementById('hours-report-table-header');
    const tableBody = document.getElementById('hours-report-table-body');

    if (!reportTypeSelect || !startDateInput || !endDateInput || !chartContainer) {
        return;
    }

    let currentData = null;
    const api = reportsApiUrl();

    async function fetchReportData(type, fromDate, toDate) {
        const url = `${api}?type=${encodeURIComponent(type)}&from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`;
        const response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    }

    function renderChart(data, config) {
        if (typeof d3 === 'undefined') {
            chartContainer.innerHTML = '<p class="text-center text-amber-600 py-8 text-sm">Chart library failed to load.</p>';
            return;
        }

        d3.select('#hours-report-chart-container').selectAll('*').remove();

        if (!data || data.length === 0) {
            chartContainer.innerHTML = '<p class="text-center text-gray-500 py-8">No data available for the selected period.</p>';
            return;
        }

        const container = chartContainer;
        const margin = { top: 20, right: 30, bottom: 60, left: 70 };
        let containerWidth = container.clientWidth;
        if (containerWidth === 0) {
            const parent = container.parentElement;
            if (parent) {
                containerWidth = parent.clientWidth - 48;
            }
            if (containerWidth === 0) {
                containerWidth = 800;
            }
        }

        const containerHeight = 400;
        const width = containerWidth - margin.left - margin.right;
        const height = containerHeight - margin.top - margin.bottom;

        const svg = d3
            .select('#hours-report-chart-container')
            .append('svg')
            .attr('width', containerWidth)
            .attr('height', containerHeight)
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3
            .scaleBand()
            .domain(data.map((d) => config.getChartLabel(d)))
            .range([0, width])
            .padding(0.2);

        const maxValue = d3.max(data, (d) => config.getChartValue(d));
        const y = d3
            .scaleLinear()
            .domain([0, maxValue * 1.1])
            .range([height, 0]);

        svg
            .append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll('text')
            .attr('transform', 'translate(-10,0)rotate(-45)')
            .style('text-anchor', 'end');

        svg.append('g').call(d3.axisLeft(y).ticks(5));

        svg
            .selectAll('bar')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', (d) => x(config.getChartLabel(d)))
            .attr('y', (d) => y(config.getChartValue(d)))
            .attr('width', x.bandwidth())
            .attr('height', (d) => height - y(config.getChartValue(d)))
            .attr('fill', '#3b82f6')
            .attr('rx', 4)
            .transition()
            .duration(800)
            .attr('height', (d) => height - y(config.getChartValue(d)));

        svg
            .append('text')
            .attr('transform', 'rotate(-90)')
            .attr('y', 0 - margin.left)
            .attr('x', 0 - height / 2)
            .attr('dy', '1em')
            .style('text-anchor', 'middle')
            .style('font-size', '14px')
            .text(config.chartLabel);
    }

    function renderTable(data, config) {
        if (!tableHeader || !tableBody) return;
        tableHeader.innerHTML = '';
        tableBody.innerHTML = '';

        if (!data || data.length === 0) {
            tableBody.innerHTML = `
            <tr>
                <td colspan="${config.tableHeaders.length}" class="px-6 py-8 text-center text-gray-500">
                    <p class="text-sm">No data available for the selected period.</p>
                </td>
            </tr>`;
            return;
        }

        config.tableHeaders.forEach((header) => {
            const th = document.createElement('th');
            th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
            th.textContent = header;
            tableHeader.appendChild(th);
        });

        data.forEach((row) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            config.getTableRow(row).forEach((cell) => {
                const td = document.createElement('td');
                td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
                td.textContent = cell;
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }

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

        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        if (chartSection) chartSection.classList.add('hidden');
        if (tableSection) tableSection.classList.add('hidden');

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

            const fromDateStr = new Date(fromDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const toDateStr = new Date(toDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            if (chartTitle) {
                chartTitle.textContent = `${config.title} (${fromDateStr} - ${toDateStr})`;
            }

            if (chartSection) chartSection.classList.remove('hidden');
            if (tableSection) tableSection.classList.remove('hidden');

            renderTable(response.data, config);

            requestAnimationFrame(() => {
                renderChart(response.data, config);
            });
        } catch (error) {
            console.error('Error updating report:', error);
            alert(`Error loading report data: ${error.message}`);
            if (chartSection) chartSection.classList.remove('hidden');
            if (tableSection) tableSection.classList.remove('hidden');
            chartContainer.innerHTML = `<p class="text-center text-red-500 py-8">Error: ${error.message}</p>`;
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">Error: ${error.message}</td></tr>`;
            }
        } finally {
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
        }
    }

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

        const headers = config.tableHeaders.join(',');
        const rows = currentData.map((row) =>
            config
                .getTableRow(row)
                .map((cell) => `"${cell}"`)
                .join(',')
        );
        const csv = [headers, ...rows].join('\n');

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

    runReportBtn?.addEventListener('click', updateReport);
    exportReportBtn?.addEventListener('click', exportReport);

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

    if (autoRun && startDateInput.value && endDateInput.value) {
        updateReport();
    }
}
