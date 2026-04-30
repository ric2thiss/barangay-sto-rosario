/**
 * Chart Renderer Module
 * Handles rendering bar and pie charts using Chart.js
 */

let hoursChart = null;
let statusChart = null;

export function initChartRenderer() {
    /**
     * Format date for chart labels
     * @param {string} dateStr 
     * @returns {string}
     */
    function formatDateLabel(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Render bar chart for hours rendered per day
     * @param {Array} attendanceData 
     */
    function renderBarChart(attendanceData) {
        const ctx = document.getElementById('hours-chart');
        if (!ctx) return;

        // Destroy existing chart
        if (hoursChart) {
            hoursChart.destroy();
        }

        const labels = attendanceData.map(day => formatDateLabel(day.date));
        const data = attendanceData.map(day => day.total_hours_decimal || 0);

        hoursChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hours Rendered',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const day = attendanceData[context.dataIndex];
                                const hours = day.total_hours || 0;
                                const minutes = day.total_minutes || 0;
                                return `Hours: ${hours}h ${minutes}m`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + 'h';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    }

    /**
     * Render pie chart for attendance status breakdown
     * @param {Object} statistics 
     */
    function renderPieChart(statistics) {
        const ctx = document.getElementById('status-chart');
        if (!ctx) return;

        // Destroy existing chart
        if (statusChart) {
            statusChart.destroy();
        }

        const complete = statistics.complete || 0;
        const incomplete = statistics.incomplete || 0;
        const anomalous = statistics.anomalous || 0;

        statusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Complete', 'Incomplete', 'Anomalous'],
                datasets: [{
                    data: [complete, incomplete, anomalous],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.5)',
                        'rgba(234, 179, 8, 0.5)',
                        'rgba(239, 68, 68, 0.5)'
                    ],
                    borderColor: [
                        'rgba(34, 197, 94, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    return {
        renderBarChart,
        renderPieChart
    };
}
