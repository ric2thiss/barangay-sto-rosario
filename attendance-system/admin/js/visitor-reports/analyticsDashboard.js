/**
 * Visitor analytics dashboard — Chart.js bar and pie only.
 */

import getBaseUrl from '../shared/baseUrl.js';

const API = `${getBaseUrl()}/api/reports/visitor-analytics.php`;

/**
 * Chart colors aligned with admin/js/attendance-analytics/main.js (Tailwind-aligned hex + cc/d0 alpha).
 */
const chartBlue = '#3b82f6';
const chartEmerald = '#10b981';
const chartAmber = '#f59e0b';
const chartOrange = '#ea580c';
const chartIndigo = '#6366f1';
const chartViolet = '#8b5cf6';
const chartRed = '#ef4444';
const chartCyan = '#06b6d4';
const chartPink = '#ec4899';
const chartTeal = '#14b8a6';
const chartGrayEmpty = '#e5e7eb';

/** Pie / multi-series: same order as attendance “position” pie */
const piePalette = [
    chartBlue,
    chartViolet,
    chartEmerald,
    chartAmber,
    chartRed,
    chartCyan,
    chartPink,
    chartTeal,
    chartOrange,
    chartIndigo,
];

/** Distinct bar color per widget (avoids every chart looking identical) */
const barChartColors = {
    trend: chartBlue,
    peakHours: chartIndigo,
    dayOfWeek: chartEmerald,
    ageGroups: chartViolet,
    purok: chartAmber,
    barangay: chartTeal,
};

const chartRefs = {};

function destroyAnalyticsCharts() {
    Object.keys(chartRefs).forEach((id) => {
        try {
            chartRefs[id]?.destroy();
        } catch (_) {
            /* ignore */
        }
        delete chartRefs[id];
    });
}

function barOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
            },
        },
    };
}

function pieOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            title: title ? { display: true, text: title } : { display: false },
            tooltip: { enabled: true },
        },
    };
}

function renderBar(canvasId, labels, values, datasetLabel = 'Visits', barColor = chartBlue) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (chartRefs[canvasId]) {
        chartRefs[canvasId].destroy();
    }
    const isPlaceholder =
        labels.length === 1 &&
        (labels[0] === 'No data' || labels[0] === '—') &&
        values.every((v) => Number(v) === 0);
    const fill = isPlaceholder ? `${chartGrayEmpty}` : `${barColor}cc`;
    const stroke = isPlaceholder ? chartGrayEmpty : barColor;
    chartRefs[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: datasetLabel,
                    data: values,
                    backgroundColor: fill,
                    borderColor: stroke,
                    borderWidth: 1,
                },
            ],
        },
        options: barOptions(),
    });
}

function renderPie(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (chartRefs[canvasId]) {
        chartRefs[canvasId].destroy();
    }
    const total = (values || []).reduce((a, b) => a + Number(b), 0);
    const isEmpty =
        total === 0 ||
        (labels.length === 1 && String(labels[0]).toLowerCase() === 'no data');
    const bg = isEmpty
        ? [chartGrayEmpty]
        : labels.map((_, i) => `${piePalette[i % piePalette.length]}cc`);
    chartRefs[canvasId] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: bg,
                    borderColor: '#ffffff',
                    borderWidth: 1,
                },
            ],
        },
        options: pieOptions(),
    });
}

function formatHour(h) {
    const hour = Number(h);
    if (hour === 0) return '12 AM';
    if (hour < 12) return `${hour} AM`;
    if (hour === 12) return '12 PM';
    return `${hour - 12} PM`;
}

function topNOther(rows, labelKey, countKey, n, otherLabel = 'Other') {
    if (!rows?.length) return { labels: [], values: [] };
    const sorted = [...rows].sort((a, b) => (b[countKey] || 0) - (a[countKey] || 0));
    if (sorted.length <= n) {
        return {
            labels: sorted.map((r) => r[labelKey]),
            values: sorted.map((r) => r[countKey]),
        };
    }
    const head = sorted.slice(0, n);
    const tailSum = sorted.slice(n).reduce((s, r) => s + (r[countKey] || 0), 0);
    return {
        labels: [...head.map((r) => r[labelKey]), otherLabel],
        values: [...head.map((r) => r[countKey]), tailSum],
    };
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function fillSelect(selectEl, options, current) {
    if (!selectEl) return;
    const cur = current != null ? String(current) : '';
    selectEl.innerHTML = '';
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All';
    selectEl.appendChild(all);
    (options || []).forEach((opt) => {
        if (opt === null || opt === undefined || opt === '') return;
        const o = document.createElement('option');
        o.value = String(opt);
        o.textContent = String(opt);
        selectEl.appendChild(o);
    });
    selectEl.value = cur;
}

export async function loadVisitorAnalytics(params) {
    const q = new URLSearchParams({
        from: params.from,
        to: params.to,
        trend: params.trend || 'day',
    });
    if (params.purpose) q.set('purpose', params.purpose);
    if (params.gender) q.set('gender', params.gender);
    if (params.purok) q.set('purok', params.purok);

    const res = await fetch(`${API}?${q.toString()}`);
    if (!res.ok) {
        throw new Error(`Analytics HTTP ${res.status}`);
    }
    const json = await res.json();
    if (!json.success) {
        throw new Error(json.message || json.error || 'Analytics failed');
    }
    return json;
}

export function renderVisitorAnalytics(data) {
    destroyAnalyticsCharts();

    const s = data.summary || {};
    setText('analyticsCardTotal', String(s.total_visits ?? 0));
    setText('analyticsCardUnique', String(s.unique_visitors ?? 0));
    setText('analyticsCardRepeat', String(s.repeat_visitor_people ?? 0));
    setText('analyticsCardAvgFreq', String(s.avg_visits_per_person ?? 0));

    const durEl = document.getElementById('insightDuration');
    if (durEl) {
        durEl.textContent = s.duration_available && s.avg_duration_minutes != null
            ? `${s.avg_duration_minutes} min`
            : 'Not available (no time-out recorded)';
    }

    const freqList = document.getElementById('insightFrequentList');
    if (freqList) {
        freqList.innerHTML = '';
        const list = data.frequent_visitors || [];
        if (!list.length) {
            freqList.innerHTML = '<li class="text-gray-500 text-sm">No visits in this range.</li>';
        } else {
            list.forEach((row, i) => {
                const li = document.createElement('li');
                li.className = 'flex justify-between text-sm py-1 border-b border-gray-100';
                li.innerHTML = `<span class="text-gray-700">${i + 1}. ${escapeHtml(row.name)}</span><span class="font-medium text-gray-900">${row.visits}</span>`;
                freqList.appendChild(li);
            });
        }
    }

    const trends = data.trends || [];
    if (trends.length) {
        renderBar(
            'chartVisitorTrend',
            trends.map((r) => r.label),
            trends.map((r) => r.count),
            'Visits',
            barChartColors.trend
        );
    } else {
        renderBar('chartVisitorTrend', ['No data'], [0], 'Visits', barChartColors.trend);
    }

    const hours = data.peak_hours || [];
    const hourLabels = [];
    const hourVals = [];
    for (let h = 0; h < 24; h++) {
        hourLabels.push(formatHour(h));
        const found = hours.find((x) => Number(x.hour) === h);
        hourVals.push(found ? found.count : 0);
    }
    renderBar('chartPeakHours', hourLabels, hourVals, 'Visits', barChartColors.peakHours);

    const dow = data.day_of_week || [];
    if (dow.length) {
        renderBar(
            'chartDayOfWeek',
            dow.map((r) => r.day),
            dow.map((r) => r.count),
            'Visits',
            barChartColors.dayOfWeek
        );
    } else {
        renderBar('chartDayOfWeek', ['—'], [0], 'Visits', barChartColors.dayOfWeek);
    }

    const purposes = data.purposes || [];
    const p1 = topNOther(purposes, 'label', 'count', 12);
    if (p1.values.reduce((a, b) => a + b, 0) === 0) {
        renderPie('chartPurposeTop', ['No data'], [1]);
    } else {
        renderPie('chartPurposeTop', p1.labels, p1.values);
    }

    const p2 = topNOther(purposes, 'label', 'count', 5);
    if (p2.values.reduce((a, b) => a + b, 0) === 0) {
        renderPie('chartPurposeDist', ['No data'], [1]);
    } else {
        renderPie('chartPurposeDist', p2.labels, p2.values);
    }

    const g = data.gender || [];
    if (g.length && g.some((x) => x.count > 0)) {
        renderPie(
            'chartGender',
            g.map((r) => r.label),
            g.map((r) => r.count)
        );
    } else {
        renderPie('chartGender', ['No data'], [1]);
    }

    const age = data.age_groups || [];
    if (age.length) {
        renderBar(
            'chartAgeGroups',
            age.map((r) => r.label),
            age.map((r) => r.count),
            'Visitors',
            barChartColors.ageGroups
        );
    } else {
        renderBar('chartAgeGroups', ['—'], [0], 'Visitors', barChartColors.ageGroups);
    }

    const pk = data.purok || [];
    if (pk.length) {
        renderBar(
            'chartPurok',
            pk.map((r) => r.label),
            pk.map((r) => r.count),
            'Visits',
            barChartColors.purok
        );
    } else {
        renderBar('chartPurok', ['—'], [0], 'Visits', barChartColors.purok);
    }

    const br = data.barangay || [];
    if (br.length) {
        renderBar(
            'chartBarangay',
            br.map((r) => r.label),
            br.map((r) => r.count),
            'Visits',
            barChartColors.barangay
        );
    } else {
        renderBar('chartBarangay', ['—'], [0], 'Visits', barChartColors.barangay);
    }

    const cv = data.civil_status || [];
    if (cv.length && cv.some((x) => x.count > 0)) {
        renderPie(
            'chartCivilStatus',
            cv.map((r) => r.label),
            cv.map((r) => r.count)
        );
    } else {
        renderPie('chartCivilStatus', ['No data'], [1]);
    }

    const fo = data.filter_options || {};
    fillSelect(document.getElementById('filterPurpose'), fo.purposes || [], data.filters?.purpose || '');
    fillSelect(document.getElementById('filterGender'), fo.genders || [], data.filters?.gender || '');
    fillSelect(document.getElementById('filterPurok'), fo.puroks || [], data.filters?.purok || '');
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

export function hideAnalyticsLoading() {
    const el = document.getElementById('analyticsLoading');
    if (el) el.classList.add('hidden');
}

export function showAnalyticsLoading() {
    const el = document.getElementById('analyticsLoading');
    if (el) el.classList.remove('hidden');
}
