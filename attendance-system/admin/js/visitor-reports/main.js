/**
 * Visitor Analytics + reports: Chart.js (bar and pie only for charts).
 */

import { initSidebar } from '../shared/sidebar.js';
import getBaseUrl from '../shared/baseUrl.js';
import {
    loadVisitorAnalytics,
    renderVisitorAnalytics,
    showAnalyticsLoading,
    hideAnalyticsLoading,
} from './analyticsDashboard.js';

initSidebar();

const API_ENDPOINT = `${getBaseUrl()}/api/reports/visitor-reports.php`;

const reportTypeSelect = document.getElementById('reportType');
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const filterTrend = document.getElementById('filterTrend');
const filterPurpose = document.getElementById('filterPurpose');
const filterGender = document.getElementById('filterGender');
const filterPurok = document.getElementById('filterPurok');
const runReportBtn = document.getElementById('runReportBtn');
const exportAnalyticsBtn = document.getElementById('exportAnalyticsBtn');
const exportTableBtn = document.getElementById('exportTableBtn');
const loadingIndicator = document.getElementById('loadingIndicator');
const chartSection = document.getElementById('chartSection');
const chartTitle = document.getElementById('chartTitle');
const tableSection = document.getElementById('tableSection');
const tableHeader = document.getElementById('tableHeader');
const tableBody = document.getElementById('tableBody');
const analyticsError = document.getElementById('analyticsError');

let currentChart = null;
let currentData = null;
let currentConfig = null;

/** First analytics request may use URL/bootstrap values before select options exist */
let analyticsBootstrap = true;

/** Last successful analytics API payload (for Export Analytics CSV) */
let lastAnalyticsData = null;

const colors = {
    primary: '#3b82f6',
    secondary: '#8b5cf6',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#06b6d4',
    purple: '#a855f7',
    pink: '#ec4899',
    teal: '#14b8a6',
    orange: '#f97316',
    gray: '#6b7280',
};

const colorPalette = [
    colors.primary,
    colors.secondary,
    colors.success,
    colors.warning,
    colors.danger,
    colors.info,
    colors.purple,
    colors.pink,
    colors.teal,
    colors.orange,
];

function getAnalyticsParams() {
    const i = window.__visitorAnalyticsInitial || {};
    const purpose =
        analyticsBootstrap && filterPurpose && filterPurpose.value === ''
            ? i.purpose || ''
            : filterPurpose?.value ?? '';
    const gender =
        analyticsBootstrap && filterGender && filterGender.value === ''
            ? i.gender || ''
            : filterGender?.value ?? '';
    const purok =
        analyticsBootstrap && filterPurok && filterPurok.value === ''
            ? i.purok || ''
            : filterPurok?.value ?? '';
    return {
        from: startDateInput.value,
        to: endDateInput.value,
        trend: filterTrend?.value || i.trend || 'day',
        purpose,
        gender,
        purok,
    };
}

function showAnalyticsError(msg) {
    if (!analyticsError) return;
    analyticsError.textContent = msg;
    analyticsError.classList.remove('hidden');
}

function hideAnalyticsError() {
    if (!analyticsError) return;
    analyticsError.classList.add('hidden');
    analyticsError.textContent = '';
}

async function refreshAnalytics() {
    hideAnalyticsError();
    showAnalyticsLoading();
    try {
        const data = await loadVisitorAnalytics(getAnalyticsParams());
        lastAnalyticsData = data;
        renderVisitorAnalytics(data);
        analyticsBootstrap = false;
        window.__visitorAnalyticsInitial = null;
    } catch (error) {
        console.error('Analytics error:', error);
        lastAnalyticsData = null;
        showAnalyticsError(error.message || 'Failed to load visitor analytics.');
    } finally {
        hideAnalyticsLoading();
    }
}

function ensureReportCanvas() {
    const container = document.getElementById('chartContainer');
    if (!container) return;
    let canvas = document.getElementById('reportChart');
    if (!canvas) {
        container.innerHTML =
            '<canvas id="reportChart"></canvas>';
        canvas = document.getElementById('reportChart');
    }
    return canvas;
}

const reportConfigs = {
    'total-visitors': {
        title: 'Total Visitors Over Time',
        chartType: 'bar',
        tableHeaders: ['Date', 'Total Visitors'],
        getChartData: (data) => ({
            labels: data.map((d) => {
                const date = new Date(d.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Visitors',
                    data: data.map((d) => d.count),
                    backgroundColor: colors.primary + 'AA',
                    borderColor: colors.primary,
                    borderWidth: 1,
                },
            ],
        }),
        getTableRow: (row) => [
            new Date(row.date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }),
            row.count,
        ],
    },
    'services-availed': {
        title: 'Services Availed by Visitors',
        chartType: 'bar',
        tableHeaders: ['Service', 'Count'],
        getChartData: (data) => ({
            labels: data.map((d) => d.service),
            datasets: [
                {
                    label: 'Visitors',
                    data: data.map((d) => d.count),
                    backgroundColor: colorPalette.slice(0, data.length),
                    borderColor: colorPalette.slice(0, data.length).map((c) => c + 'CC'),
                    borderWidth: 1,
                },
            ],
        }),
        getTableRow: (row) => [row.service, row.count],
    },
    'visitor-types': {
        title: 'Types of Visitors (Residents vs Non-Residents)',
        chartType: 'pie',
        tableHeaders: ['Visitor Type', 'Count'],
        getChartData: (data) => ({
            labels: data.map((d) => d.type),
            datasets: [
                {
                    data: data.map((d) => d.count),
                    backgroundColor: [colors.success, colors.warning],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                },
            ],
        }),
        getTableRow: (row) => [row.type, row.count],
    },
    'appointment-types': {
        title: 'Appointment Types (Online vs Walk-in)',
        chartType: 'pie',
        tableHeaders: ['Appointment Type', 'Count'],
        getChartData: (data) => ({
            labels: data.map((d) => d.type),
            datasets: [
                {
                    data: data.map((d) => d.count),
                    backgroundColor: [colors.purple, colors.pink],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                },
            ],
        }),
        getTableRow: (row) => [row.type, row.count],
    },
    'gender-distribution': {
        title: 'Gender Distribution of Visitors',
        chartType: 'bar',
        tableHeaders: ['Gender', 'Count'],
        getChartData: (data) => ({
            labels: data.map((d) => d.gender),
            datasets: [
                {
                    label: 'Visitors',
                    data: data.map((d) => d.count),
                    backgroundColor: colors.primary + '80',
                    borderColor: colors.primary,
                    borderWidth: 1,
                },
            ],
        }),
        getTableRow: (row) => [row.gender, row.count],
    },
    'age-services': {
        title: 'Age Groups & Services Availed',
        chartType: 'bar',
        tableHeaders: ['Age Group', 'Service', 'Count'],
        getChartData: (data) => {
            const allServices = new Set();
            data.forEach((group) => {
                group.services.forEach((s) => allServices.add(s.service));
            });
            const services = Array.from(allServices);
            const ageGroups = data.map((d) => d.age_group);
            const datasets = services.map((service, idx) => ({
                label: service,
                data: ageGroups.map((ageGroup) => {
                    const group = data.find((d) => d.age_group === ageGroup);
                    const serviceData = group?.services.find((s) => s.service === service);
                    return serviceData ? serviceData.count : 0;
                }),
                backgroundColor: colorPalette[idx % colorPalette.length] + '80',
                borderColor: colorPalette[idx % colorPalette.length],
                borderWidth: 1,
            }));
            return {
                labels: ageGroups,
                datasets,
            };
        },
        getTableRow: (row) => {
            const rows = [];
            row.services.forEach((service) => {
                rows.push([row.age_group, service.service, service.count]);
            });
            return rows;
        },
    },
};

async function fetchReportData(type, fromDate, toDate) {
    const url = `${API_ENDPOINT}?type=${encodeURIComponent(type)}&from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`;
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
}

function renderChart(data, config) {
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }

    const canvas = ensureReportCanvas();
    if (!canvas) {
        console.error('Report canvas missing');
        return;
    }

    if (!data || data.length === 0) {
        const container = document.getElementById('chartContainer');
        if (container) {
            container.innerHTML =
                '<p class="text-center text-gray-500 py-8 px-4">No data available for the selected period.</p>';
        }
        return;
    }

    const chartData = config.getChartData(data);
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                enabled: true,
            },
        },
        scales: {},
    };

    if (config.chartType === 'bar' && data[0]?.services) {
        chartOptions.scales = {
            x: { stacked: false },
            y: {
                beginAtZero: true,
                stacked: false,
                ticks: { stepSize: 1 },
            },
        };
    } else if (config.chartType === 'bar') {
        chartOptions.scales = {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
            },
        };
    } else if (config.chartType === 'pie') {
        chartOptions.scales = {};
    }

    currentChart = new Chart(canvas, {
        type: config.chartType,
        data: chartData,
        options: chartOptions,
    });
}

function renderTable(data, config) {
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

    config.tableHeaders.forEach((header) => {
        const th = document.createElement('th');
        th.className =
            'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = header;
        tableHeader.appendChild(th);
    });

    const reportType = reportTypeSelect.value;
    if (reportType === 'age-services') {
        data.forEach((group) => {
            group.services.forEach((service, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                const cells = [group.age_group, service.service, service.count];
                if (idx > 0) {
                    cells[0] = '';
                }
                cells.forEach((cell) => {
                    const td = document.createElement('td');
                    td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
                    td.textContent = cell;
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            });
        });
    } else {
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

    loadingIndicator.classList.remove('hidden');
    chartSection.classList.add('hidden');
    tableSection.classList.add('hidden');

    try {
        const response = await fetchReportData(type, fromDate, toDate);

        if (!response.success || !response.data) {
            throw new Error(response.error || 'Failed to fetch report data');
        }

        currentData = response.data;
        currentConfig = reportConfigs[type];

        if (!currentConfig) {
            throw new Error('Unknown report type');
        }

        const fromDateStr = new Date(fromDate).toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric',
        });
        const toDateStr = new Date(toDate).toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric',
        });
        chartTitle.textContent = `${currentConfig.title} (${fromDateStr} - ${toDateStr})`;

        chartSection.classList.remove('hidden');
        tableSection.classList.remove('hidden');

        const container = document.getElementById('chartContainer');
        if (container && !document.getElementById('reportChart')) {
            container.innerHTML = '<canvas id="reportChart"></canvas>';
        }

        renderTable(response.data, currentConfig);

        requestAnimationFrame(() => {
            renderChart(response.data, currentConfig);
        });
    } catch (error) {
        console.error('Error updating report:', error);
        alert('Error loading report data: ' + error.message);
        const container = document.getElementById('chartContainer');
        if (container) {
            container.innerHTML = `<p class="text-center text-red-500 py-8">Error: ${error.message}</p>`;
        }
        tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">Error: ${error.message}</td></tr>`;
        chartSection.classList.remove('hidden');
        tableSection.classList.remove('hidden');
    } finally {
        loadingIndicator.classList.add('hidden');
    }
}

async function runAll() {
    await Promise.all([refreshAnalytics(), updateReport()]);
}

function csvEscapeCell(value) {
    const s = value == null ? '' : String(value);
    return `"${s.replace(/"/g, '""')}"`;
}

function csvRow(cells) {
    return cells.map(csvEscapeCell).join(',');
}

/**
 * Build CSV matching the analytics dashboard: summary, filters, and each chart dataset.
 */
function buildAnalyticsCsv(d) {
    const lines = [];
    const pushSection = (title) => {
        lines.push('');
        lines.push(csvRow([title]));
    };

    lines.push(csvRow(['Visitor Analytics Export']));
    lines.push(csvRow(['Generated', new Date().toISOString()]));
    lines.push(csvRow(['Date range from', d.from || '']));
    lines.push(csvRow(['Date range to', d.to || '']));
    lines.push(csvRow(['Trend grouping', d.filters?.trend || '']));
    lines.push(csvRow(['Filter purpose', d.filters?.purpose || '']));
    lines.push(csvRow(['Filter gender', d.filters?.gender || '']));
    lines.push(csvRow(['Filter purok', d.filters?.purok || '']));

    const sum = d.summary || {};
    pushSection('Summary');
    lines.push(csvRow(['Metric', 'Value']));
    lines.push(csvRow(['Total visits', sum.total_visits ?? 0]));
    lines.push(csvRow(['Unique visitors', sum.unique_visitors ?? 0]));
    lines.push(csvRow(['Repeat visitors (people with >1 visit)', sum.repeat_visitor_people ?? 0]));
    lines.push(csvRow(['Average visits per person', sum.avg_visits_per_person ?? 0]));
    lines.push(
        csvRow([
            'Average visit duration',
            sum.duration_available && sum.avg_duration_minutes != null
                ? `${sum.avg_duration_minutes} minutes`
                : 'Not available',
        ])
    );

    pushSection('Visitors over time');
    lines.push(csvRow(['Period', 'Visit count']));
    (d.trends || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Peak hours');
    lines.push(csvRow(['Hour (0-23)', 'Visit count']));
    const byHour = new Map((d.peak_hours || []).map((x) => [Number(x.hour), x.count]));
    for (let h = 0; h < 24; h++) {
        lines.push(csvRow([h, byHour.get(h) ?? 0]));
    }

    pushSection('Visits by day of week');
    lines.push(csvRow(['Day', 'Visit count']));
    (d.day_of_week || []).forEach((r) => lines.push(csvRow([r.day, r.count])));

    pushSection('Purposes');
    lines.push(csvRow(['Purpose', 'Visit count']));
    (d.purposes || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Gender');
    lines.push(csvRow(['Gender', 'Visit count']));
    (d.gender || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Age groups');
    lines.push(csvRow(['Age group', 'Visit count']));
    (d.age_groups || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Purok');
    lines.push(csvRow(['Purok', 'Visit count']));
    (d.purok || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Barangay');
    lines.push(csvRow(['Barangay', 'Visit count']));
    (d.barangay || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Civil status');
    lines.push(csvRow(['Civil status', 'Visit count']));
    (d.civil_status || []).forEach((r) => lines.push(csvRow([r.label, r.count])));

    pushSection('Frequent visitors (top 10)');
    lines.push(csvRow(['Name', 'Visits']));
    (d.frequent_visitors || []).forEach((r) => lines.push(csvRow([r.name, r.visits])));

    return '\uFEFF' + lines.join('\r\n');
}

function exportAnalyticsCsv() {
    if (!lastAnalyticsData) {
        alert('Load analytics first (click Run / Refresh).');
        return;
    }
    const csv = buildAnalyticsCsv(lastAnalyticsData);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const f = lastAnalyticsData.from || startDateInput.value;
    const t = lastAnalyticsData.to || endDateInput.value;
    a.download = `visitor_analytics_${f}_${t}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function exportDetailedTableCsv() {
    if (!currentData || !currentConfig) {
        alert('Load the detailed report first (click Run / Refresh).');
        return;
    }

    const headers = currentConfig.tableHeaders.join(',');
    let rows = [];

    const reportType = reportTypeSelect.value;
    if (reportType === 'age-services') {
        currentData.forEach((group) => {
            group.services.forEach((service, idx) => {
                const row = [group.age_group, service.service, service.count];
                if (idx > 0) {
                    row[0] = '';
                }
                rows.push(row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','));
            });
        });
    } else {
        rows = currentData.map((row) =>
            currentConfig.getTableRow(row).map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')
        );
    }

    const csv = '\uFEFF' + [headers, ...rows].join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `visitor_report_${reportTypeSelect.value}_${startDateInput.value}_${endDateInput.value}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

if (runReportBtn) {
    runReportBtn.addEventListener('click', runAll);
}

if (exportAnalyticsBtn) {
    exportAnalyticsBtn.addEventListener('click', exportAnalyticsCsv);
}

if (exportTableBtn) {
    exportTableBtn.addEventListener('click', exportDetailedTableCsv);
}

if (reportTypeSelect) {
    reportTypeSelect.addEventListener('change', () => {
        updateReport();
    });
}

[filterPurpose, filterGender, filterPurok, filterTrend].forEach((el) => {
    if (el) {
        el.addEventListener('change', () => {
            refreshAnalytics();
        });
    }
});

let dateAnalyticsTimer;
function scheduleAnalyticsOnDateChange() {
    clearTimeout(dateAnalyticsTimer);
    dateAnalyticsTimer = setTimeout(() => {
        const from = startDateInput?.value;
        const to = endDateInput?.value;
        if (!from || !to || new Date(from) > new Date(to)) return;
        refreshAnalytics();
    }, 350);
}

[startDateInput, endDateInput].forEach((el) => {
    if (el) {
        el.addEventListener('change', scheduleAnalyticsOnDateChange);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    if (startDateInput.value && endDateInput.value) {
        runAll();
    }
});

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (currentData && currentChart && reportTypeSelect.value) {
            const config = reportConfigs[reportTypeSelect.value];
            if (config) {
                const container = document.getElementById('chartContainer');
                if (container?.querySelector('#reportChart')) {
                    renderChart(currentData, config);
                }
            }
        }
    }, 250);
});
