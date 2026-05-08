/**
 * Additional dashboard bar charts (read-only APIs). Does not replace existing metrics.
 */
import { getChartData } from './chartData.js';

let complianceChart = null;
let topEmployeesChart = null;
let visitorAgeChart = null;
let visitorResidentSplitChart = null;
let visitorVisitTypeChart = null;
let visitorServicesInsightChart = null;

function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
    return null;
}

function complianceFromAttendance(chartData) {
    const labels = chartData.labels || [];
    const present = chartData.presentData || [];
    const absent = chartData.absentData || [];
    return labels.map((label, i) => {
        const p = Number(present[i]) || 0;
        const a = Number(absent[i]) || 0;
        const denom = p + a;
        if (denom <= 0) {
            return { label, pct: 0 };
        }
        return { label, pct: Math.round((p / denom) * 1000) / 10 };
    });
}

async function fetchExtraCharts(filterType) {
    const res = await fetch(`../api/dashboard/extra-charts.php?filter=${encodeURIComponent(filterType)}`, {
        credentials: 'same-origin',
    });
    const data = await res.json();
    if (!data.success) {
        return {
            top_employees: [],
            visitor_age: { labels: [], counts: [] },
            visitor_resident_split: { labels: [], counts: [] },
            visitor_visit_type: { labels: [], counts: [] },
            visitor_services: { labels: [], counts: [] },
        };
    }
    return data;
}

export async function initDashboardExtendedCharts() {
    const filterEl = document.getElementById('dashboard-insights-filter');
    const complianceCtx = document.getElementById('dashboardComplianceChart');
    const topEmpCtx = document.getElementById('dashboardTopEmployeesChart');
    const ageCtx = document.getElementById('dashboardVisitorAgeChart');
    const residentSplitCtx = document.getElementById('dashboardVisitorResidentChart');
    const visitTypeCtx = document.getElementById('dashboardVisitorVisitTypeChart');
    const servicesInsightCtx = document.getElementById('dashboardVisitorServicesChart');
    if (!filterEl || !complianceCtx || !topEmpCtx || !ageCtx || typeof Chart === 'undefined') {
        return;
    }

    const render = async () => {
        const filterType = filterEl.value;
        const [attData, extra] = await Promise.all([
            getChartData(filterType, 'attendance'),
            fetchExtraCharts(filterType),
        ]);

        const complianceRows = complianceFromAttendance(attData);
        const compLabels = complianceRows.map((r) => r.label);
        const compValues = complianceRows.map((r) => r.pct);

        complianceChart = destroyChart(complianceChart);
        complianceChart = new Chart(complianceCtx, {
            type: 'bar',
            data: {
                labels: compLabels,
                datasets: [
                    {
                        label: 'Compliance (% distinct with logs)',
                        data: compValues,
                        backgroundColor: 'rgba(99, 102, 241, 0.75)',
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.parsed.y ?? 0}%`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback(v) {
                                return `${v}%`;
                            },
                        },
                    },
                    x: { grid: { display: false } },
                },
            },
        });

        const topList = extra.top_employees || [];
        const teLabels = topList.length
            ? topList.map((r) => {
                  const name = (r.full_name || r.employee_id || '').trim();
                  return name.length > 22 ? `${name.slice(0, 20)}…` : name;
              })
            : ['No data'];
        const teCounts = topList.length ? topList.map((r) => r.log_count || 0) : [0];

        topEmployeesChart = destroyChart(topEmployeesChart);
        topEmployeesChart = new Chart(topEmpCtx, {
            type: 'bar',
            data: {
                labels: teLabels,
                datasets: [
                    {
                        label: 'Attendance logs',
                        data: teCounts,
                        backgroundColor: 'rgba(14, 165, 233, 0.75)',
                        borderColor: '#0284c7',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                    y: {
                        grid: { display: false },
                    },
                },
            },
        });

        const va = extra.visitor_age || {};
        const ageLabels = va.labels && va.labels.length ? va.labels : ['No data'];
        const ageCounts = va.counts && va.counts.length ? va.counts : [0];

        visitorAgeChart = destroyChart(visitorAgeChart);
        visitorAgeChart = new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageLabels,
                datasets: [
                    {
                        label: 'Visitors (by age at visit)',
                        data: ageCounts,
                        backgroundColor: 'rgba(168, 85, 247, 0.72)',
                        borderColor: '#9333ea',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                    x: { grid: { display: false } },
                },
            },
        });

        const vrs = extra.visitor_resident_split || { labels: [], counts: [] };
        const vrsLabels = vrs.labels && vrs.labels.length ? vrs.labels : ['No data'];
        const vrsCounts = vrs.counts && vrs.counts.length ? vrs.counts : [0];
        visitorResidentSplitChart = destroyChart(visitorResidentSplitChart);
        if (residentSplitCtx) {
            visitorResidentSplitChart = new Chart(residentSplitCtx, {
                type: 'bar',
                data: {
                    labels: vrsLabels,
                    datasets: [
                        {
                            label: 'Visitors',
                            data: vrsCounts,
                            backgroundColor: 'rgba(249, 115, 22, 0.75)',
                            borderColor: '#ea580c',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } },
                    },
                },
            });
        }

        const vvt = extra.visitor_visit_type || { labels: [], counts: [] };
        const vvtLabels = vvt.labels && vvt.labels.length ? vvt.labels : ['No data'];
        const vvtCounts = vvt.counts && vvt.counts.length ? vvt.counts : [0];
        visitorVisitTypeChart = destroyChart(visitorVisitTypeChart);
        if (visitTypeCtx) {
            visitorVisitTypeChart = new Chart(visitTypeCtx, {
                type: 'bar',
                data: {
                    labels: vvtLabels,
                    datasets: [
                        {
                            label: 'Visitors',
                            data: vvtCounts,
                            backgroundColor: 'rgba(20, 184, 166, 0.75)',
                            borderColor: '#0d9488',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } },
                    },
                },
            });
        }

        const vs = extra.visitor_services || { labels: [], counts: [] };
        const svcLabels =
            vs.labels && vs.labels.length
                ? vs.labels.map((l) => (String(l).length > 24 ? `${String(l).slice(0, 22)}…` : l))
                : ['No data'];
        const svcCounts = vs.counts && vs.counts.length ? vs.counts : [0];
        visitorServicesInsightChart = destroyChart(visitorServicesInsightChart);
        if (servicesInsightCtx) {
            visitorServicesInsightChart = new Chart(servicesInsightCtx, {
                type: 'bar',
                data: {
                    labels: svcLabels,
                    datasets: [
                        {
                            label: 'Visits',
                            data: svcCounts,
                            backgroundColor: 'rgba(59, 130, 246, 0.78)',
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 } },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    };

    await render();
    filterEl.addEventListener('change', () => {
        render().catch((e) => console.error(e));
    });
}
