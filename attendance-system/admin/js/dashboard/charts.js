/**
 * Chart Initialization and Management Module
 * Handles Chart.js initialization and updates for Employee Attendance and Visitor Traffic charts
 */

import { getChartData } from './chartData.js';

let employeeAttendanceChart = null;
let visitorTrafficChart = null;

/**
 * Initialize Employee Attendance Chart
 * @param {string} filterType - Filter type: 'today', 'week', 'month', 'year'
 */
export async function initializeEmployeeAttendanceChart(filterType = 'month') {
    const ctx = document.getElementById('employeeAttendanceChart');
    if (!ctx) return;

    const chartData = await getChartData(filterType, 'attendance');
    
    // Destroy existing chart if it exists
    if (employeeAttendanceChart) {
        employeeAttendanceChart.destroy();
    }

    employeeAttendanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Present',
                    data: chartData.presentData,
                    backgroundColor: 'rgba(59, 130, 246, 0.78)',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Absent',
                    data: chartData.absentData,
                    backgroundColor: 'rgba(239, 68, 68, 0.75)',
                    borderColor: '#dc2626',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                    },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
            interaction: {
                mode: 'index',
                axis: 'x',
                intersect: false,
            },
        },
    });
}

/**
 * Initialize Visitor Traffic Chart
 * @param {string} filterType - Filter type: 'today', 'week', 'month', 'year'
 */
export async function initializeVisitorTrafficChart(filterType = 'month') {
    const ctx = document.getElementById('visitorTrafficChart');
    if (!ctx) return;

    const chartData = await getChartData(filterType, 'visitor');
    
    // Destroy existing chart if it exists
    if (visitorTrafficChart) {
        visitorTrafficChart.destroy();
    }

    visitorTrafficChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Total Visitors',
                    data: chartData.visitorData,
                    backgroundColor: 'rgba(34, 197, 94, 0.78)',
                    borderColor: '#16a34a',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                    },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
            interaction: {
                mode: 'index',
                axis: 'x',
                intersect: false,
            },
        },
    });
}

/**
 * Initialize all charts with event listeners for filter dropdowns
 */
export async function initializeCharts() {
    const defaultFilter = 'month';
    await initializeEmployeeAttendanceChart(defaultFilter);
    await initializeVisitorTrafficChart(defaultFilter);

    // Add event listeners for filter dropdowns
    const attendanceFilter = document.getElementById('attendance-filter');
    const visitorFilter = document.getElementById('visitor-filter');

    if (attendanceFilter) {
        attendanceFilter.addEventListener('change', async (e) => {
            await initializeEmployeeAttendanceChart(e.target.value);
        });
    }

    if (visitorFilter) {
        visitorFilter.addEventListener('change', async (e) => {
            await initializeVisitorTrafficChart(e.target.value);
        });
    }
}
