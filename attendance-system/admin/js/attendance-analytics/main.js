/**
 * Attendance Analytics — consumes /api/attendance/analytics.php
 */
import { initSidebar } from '../shared/sidebar.js';
import { initHoursReports } from './hoursReports.js';

const API = window.ATTENDANCE_ANALYTICS_API || '';

const state = {
    page: 1,
};

const attentionDetailState = {
    type: null,
    page: 1,
    perPage: 50,
};

const chartInstances = {};

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

/**
 * Info icon with hover/focus popover (insights panel). Pass uniqueId to avoid duplicate aria ids across re-renders.
 */
function insightInfoPopover(infoTitle, infoBody, uniqueId) {
    const uid = String(uniqueId ?? 'x');
    const bodyId = `help-pop-desc-js-${uid}`;
    return `<span class="help-popover-anchor">
            <button type="button" class="help-popover-btn" aria-describedby="${bodyId}" aria-label="More information: ${esc(
        infoTitle
    )}">i</button>
            <div class="help-popover-panel" role="tooltip">
                <p class="help-popover-panel-title">${esc(infoTitle)}</p>
                <p id="${bodyId}" class="help-popover-panel-body">${esc(infoBody)}</p>
            </div>
        </span>`;
}

function badgeClass(status) {
    const t = String(status).toLowerCase();
    if (t === 'incomplete' || t === 'absent') return 'bg-red-100 text-red-900';
    if (t === 'late' || t === 'undertime') return 'bg-amber-100 text-amber-900';
    if (t === 'overtime') return 'bg-indigo-100 text-indigo-900';
    if (t === 'complete') return 'bg-emerald-100 text-emerald-900';
    return 'bg-gray-100 text-gray-800';
}

function rowBgClass(row) {
    if (!row.is_complete && !row.is_absent_day) return 'bg-red-50';
    if (row.is_absent_day) return 'bg-red-50/80';
    if (row.late || row.undertime) return 'bg-amber-50';
    return 'bg-white';
}

function setDefaultDatesFromRange(range) {
    const fromEl = document.getElementById('filter-from');
    const toEl = document.getElementById('filter-to');
    if (fromEl && range?.from) fromEl.value = range.from;
    if (toEl && range?.to) toEl.value = range.to;
}

/**
 * Build query string; include activity_id=0 when uncategorized is selected.
 */
function buildAnalyticsQuery(params) {
    const u = new URL(API, window.location.origin);
    Object.entries(params).forEach(([k, v]) => {
        if (v === undefined || v === null) return;
        if (k === 'activity_id' && v === '') return;
        if (v === '') return;
        u.searchParams.set(k, String(v));
    });
    return u.toString();
}

async function fetchAnalytics(params) {
    const res = await fetch(buildAnalyticsQuery(params), { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

function getFilterParamsForApi() {
    const period = document.getElementById('filter-period')?.value || 'monthly';
    const from = document.getElementById('filter-from')?.value || '';
    const to = document.getElementById('filter-to')?.value || '';
    const employeeId = document.getElementById('filter-employee')?.value || '';
    const activityRaw = document.getElementById('filter-activity')?.value ?? '';
    const params = { filter: period };
    if (from && to) {
        params.from = from;
        params.to = to;
    }
    if (employeeId) params.employee_id = employeeId;
    if (activityRaw !== '') params.activity_id = activityRaw;
    return params;
}

function destroyCharts() {
    Object.keys(chartInstances).forEach((k) => {
        try {
            chartInstances[k]?.destroy();
        } catch (_) {
            /* ignore */
        }
        delete chartInstances[k];
    });
}

function renderCharts(charts) {
    destroyCharts();
    if (!charts || typeof Chart === 'undefined') return;

    const blue = '#3b82f6';
    const emerald = '#10b981';
    const amber = '#f59e0b';
    const orange = '#ea580c';
    const indigo = '#6366f1';

    const comp = charts.compliance_by_employee;
    const ctxComp = document.getElementById('chart-analytics-compliance')?.getContext('2d');
    if (ctxComp && comp?.labels?.length) {
        chartInstances.compliance = new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: comp.labels,
                datasets: [{ label: '% clean days', data: comp.values, backgroundColor: `${blue}cc` }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `${c.parsed.y}%` } } },
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: (v) => `${v}%` } },
                },
            },
        });
    }

    const issues = charts.issue_day_counts;
    const issueTotal = (issues?.values || []).reduce((a, b) => a + Number(b), 0);
    const ctxIssues = document.getElementById('chart-analytics-issues-pie')?.getContext('2d');
    if (ctxIssues) {
        if (issueTotal === 0) {
            chartInstances.issues = new Chart(ctxIssues, {
                type: 'pie',
                data: {
                    labels: ['No flagged days in range'],
                    datasets: [{ data: [1], backgroundColor: ['#e5e7eb'] }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        } else {
            chartInstances.issues = new Chart(ctxIssues, {
                type: 'pie',
                data: {
                    labels: issues.labels,
                    datasets: [
                        {
                            data: issues.values,
                            backgroundColor: [`${amber}d0`, `${orange}d0`, `${indigo}d0`],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right' } },
                },
            });
        }
    }

    const pv = charts.perfect_vs_attention;
    const ctxPv = document.getElementById('chart-analytics-perfect-bar')?.getContext('2d');
    if (ctxPv && pv?.labels?.length) {
        chartInstances.perfect = new Chart(ctxPv, {
            type: 'bar',
            data: {
                labels: pv.labels,
                datasets: [
                    {
                        label: 'Employees',
                        data: pv.values,
                        backgroundColor: [`${emerald}cc`, `${amber}cc`],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    const demo = charts.demographic_position;
    const ctxDemo = document.getElementById('chart-analytics-position-pie')?.getContext('2d');
    if (ctxDemo) {
        if (!demo?.labels?.length) {
            chartInstances.position = new Chart(ctxDemo, {
                type: 'pie',
                data: {
                    labels: ['No roster / position data'],
                    datasets: [{ data: [1], backgroundColor: ['#e5e7eb'] }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        } else {
            const palette = [blue, '#8b5cf6', emerald, amber, '#ef4444', '#06b6d4', '#ec4899', '#14b8a6'];
            const bg = demo.labels.map((_, i) => `${palette[i % palette.length]}cc`);
            chartInstances.position = new Chart(ctxDemo, {
                type: 'pie',
                data: {
                    labels: demo.labels,
                    datasets: [{ data: demo.values, backgroundColor: bg }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'right' } },
                },
            });
        }
    }
}

function renderSummary(summary) {
    const el = document.getElementById('summary-cards');
    if (!el) return;
    const cards = [
        { k: 'total_employees_present', label: 'Present (period)', tone: 'text-blue-700' },
        { k: 'late_employees', label: 'Late', tone: 'text-amber-700' },
        { k: 'undertime_employees', label: 'Undertime', tone: 'text-amber-800' },
        { k: 'absent_employees', label: 'Absent (any day)', tone: 'text-red-700' },
        { k: 'overtime_employees', label: 'Overtime', tone: 'text-indigo-700' },
        { k: 'perfect_attendance_employees', label: 'Perfect attendance', tone: 'text-emerald-700' },
    ];
    el.innerHTML = cards
        .map(
            (c) => `
        <div class="bg-white rounded-xl border border-gray-100 shadow p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">${esc(c.label)}</p>
            <p class="text-2xl font-bold mt-1 ${c.tone}">${summary?.[c.k] ?? 0}</p>
        </div>`
        )
        .join('');
}

const DETAIL_ICON_SVG =
    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>';

/**
 * Small table for “Needs attention” with clear column headers. Long captions go in the ⓘ popover.
 * @param {string|null} detailType — when set, shows a button to open the verification modal (late | undertime | overtime).
 * @param {{ title: string, body: string }} info — popover copy (hover / focus the i icon).
 * @param {string|number} infoUid — unique suffix for aria-describedby.
 */
function attentionTable(title, countColumnLabel, items, emptyMsg, detailType = null, info = null, infoUid = '') {
    const infoBtn = info ? insightInfoPopover(info.title, info.body, `tbl-${infoUid}`) : '';
    const detailBtn =
        detailType != null
            ? `<button type="button" class="attention-detail-open shrink-0 p-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 hover:text-gray-900" data-detail="${esc(
                  detailType
              )}" title="Open detailed breakdown" aria-label="Details: ${esc(title)}">${DETAIL_ICON_SVG}</button>`
            : '';
    if (!items?.length) {
        return `<div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50 flex flex-col min-h-[5.5rem] insights-card-shell">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                    <h4 class="font-semibold text-gray-800 text-sm">${esc(title)}</h4>
                    ${infoBtn}
                </div>
                ${detailBtn}
            </div>
            <p class="text-xs text-gray-500 mt-1 flex-1 flex items-center">${esc(emptyMsg)}</p>
        </div>`;
    }
    return `<div class="border border-gray-200 rounded-lg bg-white flex flex-col min-h-[5.5rem] insights-card-shell">
        <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1 flex items-center gap-2 flex-wrap">
                <h4 class="font-semibold text-gray-800 text-sm">${esc(title)}</h4>
                ${infoBtn}
            </div>
            ${detailBtn}
        </div>
        <div class="insights-card-clip">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wide">
                        <th class="px-3 py-2">Employee</th>
                        <th class="px-3 py-2 text-right whitespace-nowrap">${esc(countColumnLabel)}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    ${items
                        .map(
                            (x) => `<tr>
                        <td class="px-3 py-2 text-gray-800">${esc(x.full_name)}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-700 tabular-nums">${esc(x.count)}</td>
                    </tr>`
                        )
                        .join('')}
                </tbody>
            </table>
        </div>
    </div>`;
}

function renderInsights(insights) {
    const el = document.getElementById('insights-panel');
    if (!el || !insights) return;

    let infoId = 0;
    const pop = (t, b) => insightInfoPopover(t, b, `ins-${++infoId}`);

    const eop = insights.employee_of_period;
    const topPerformersInfoBody =
        'Employee of the Period is chosen as the first employee alphabetically by name among everyone who had perfect attendance for every calendar day in the selected range. Use the Perfect attendance info icon for how "perfect" is defined. The full perfect list is shown below.';
    const eopHtml = eop
        ? `<div class="min-h-[4.5rem] flex items-center"><div class="flex items-center gap-2 flex-wrap">
             <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-900">${esc(eop.badge || 'Top performer')}</span>
             <span class="font-medium text-gray-800">${esc(eop.full_name)}</span>
           </div></div>`
        : '<div class="min-h-[4.5rem] flex items-center"><p class="text-sm text-gray-500">No qualifying perfect streak for this period.</p></div>';

    const perfect = (insights.perfect_attendance || []).slice(0, 8);
    const perfectInfoBody =
        'Employees listed here had every calendar day in the range marked complete (all required attendance windows filled), on time for every required IN (within grace), and no undertime. Overtime is ignored for this list—it does not remove someone from perfect attendance.';
    const perfectHtml = perfect.length
        ? `<table class="min-w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
            <thead><tr class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase"><th class="px-3 py-2">Employee</th></tr></thead>
            <tbody class="divide-y divide-gray-100">${perfect.map((x) => `<tr><td class="px-3 py-2">${esc(x.full_name)}</td></tr>`).join('')}</tbody>
           </table>`
        : '<div class="min-h-[4.5rem] flex items-center border border-gray-200 rounded-lg bg-gray-50/50 px-3"><p class="text-sm text-gray-500">None in this period.</p></div>';

    const capLate =
        'Days with at least one late required IN (after grace, per master-list windows). Best-attendance scoring counts each late IN separately (e.g. morning and afternoon both late = 2).';
    const capUnd =
        'How many days they were marked undertime (worked minutes below expected, from configured in/out windows).';
    const capInc =
        'Days with at least one required attendance window missing (still had some logs that day).';
    const capAbs = 'Days with no attendance logs at all in the selected filter.';
    const capOt = 'Days with overtime (e.g. clock-out after the window end time).';

    const bestScoreInfoTitle = 'Attendance score & ranking';
    const bestScoreInfoBody =
        'Attendance score = 1 − (A + L + U + I) / T, where T is the number of calendar days in the selected range. A = absent days (no logs that day). L = late IN occurrences—each required IN window that is late after grace counts separately. U = undertime days. I = incomplete days (some logs but missing required windows). Employees with A = L = U = I = 0 are ranked first as perfect; everyone else is sorted by higher score, then more complete days, then fewer incomplete days.';

    const needsAttentionInfoBody =
        'These rankings use the same date range and activity filter as the rest of the dashboard. All numbers are counts of employee-days (how many days matched the condition), not counts of distinct employees. Use the list icon where available to open a detailed breakdown.';

    const bestRank = insights.best_attendance_rank || [];
    const bestTable =
        bestRank.length === 0
            ? '<p class="text-sm text-gray-500">No data.</p>'
            : `<div class="border border-gray-200 rounded-lg insights-card-shell">
            <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-600">
                <span class="font-medium text-gray-700">Top scores in range</span>
            </div>
            <div class="overflow-x-auto max-h-48 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase">
                        <th class="px-3 py-2">Employee</th>
                        <th class="px-3 py-2 text-right">Score</th>
                        <th class="px-3 py-2 text-left">Tier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    ${bestRank
                        .map(
                            (x) => `<tr>
                        <td class="px-3 py-2">${esc(x.full_name)}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">${esc(x.attendance_score != null ? x.attendance_score : '—')}</td>
                        <td class="px-3 py-2">${x.perfect_attendance ? '<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-900">Perfect</span>' : '—'}</td>
                    </tr>`
                        )
                        .join('')}
                </tbody>
            </table></div></div>`;

    el.innerHTML = `
        <div class="bg-white rounded-xl border border-gray-100 shadow p-5 space-y-4 flex flex-col">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-semibold text-gray-800">Top performers</h3>
                ${pop('Top performers', topPerformersInfoBody)}
            </div>
            ${eopHtml}
            <div class="pt-2 border-t border-gray-100 flex-1 flex flex-col">
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <h4 class="font-medium text-gray-700">Perfect attendance</h4>
                    ${pop('Perfect attendance', perfectInfoBody)}
                </div>
                ${perfectHtml}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow p-5 space-y-4">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-semibold text-gray-800">Needs attention</h3>
                ${pop('Needs attention', needsAttentionInfoBody)}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${attentionTable('Most late', 'Days late', insights.most_late, 'None recorded', 'late', { title: 'Most late', body: capLate }, 'late')}
                ${attentionTable('Most undertime', 'Days undertime', insights.most_undertime, 'None recorded', 'undertime', { title: 'Most undertime', body: capUnd }, 'und')}
                ${attentionTable('Incomplete days', 'Days incomplete', insights.most_incomplete, 'None', null, { title: 'Incomplete days', body: capInc }, 'inc')}
                ${attentionTable('Most absent days', 'Days absent', insights.most_absences, 'None', null, { title: 'Most absent days', body: capAbs }, 'abs')}
                ${attentionTable('Most overtime', 'Days overtime', insights.most_overtime, 'None', 'overtime', { title: 'Most overtime', body: capOt }, 'ot')}
            </div>
            <div class="pt-2 border-t border-gray-100">
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <h4 class="font-medium text-gray-700">Best attendance (score)</h4>
                    ${pop(bestScoreInfoTitle, bestScoreInfoBody)}
                </div>
                ${bestTable}
            </div>
        </div>`;
}

function renderTable(rows) {
    const tbody = document.getElementById('analytics-tbody');
    if (!tbody) return;
    if (!rows?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No rows for this filter.</td></tr>';
        return;
    }

    tbody.innerHTML = rows
        .map((row) => {
            const wins = (row.windows_summary || []).join('<br>');
            const logs = (row.logged_summary || []).join('<br>');
            const statuses = (row.statuses || [])
                .map((s) => `<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium mr-1 mb-1 ${badgeClass(s)}">${esc(s)}</span>`)
                .join('');
            const gaps = (row.logs_by_window || []).filter((w) => w.can_fill);
            const gapBtn =
                gaps.length > 0
                    ? `<button type="button" class="text-blue-600 hover:text-blue-800 text-xs font-medium fill-gap-btn" data-eid="${encodeURIComponent(row.employee_id)}" data-date="${encodeURIComponent(row.date)}">Fill gap</button>`
                    : '<span class="text-gray-300 text-xs">—</span>';
            return `<tr class="${rowBgClass(row)}">
                <td class="px-3 py-2 align-top font-medium text-gray-900">${esc(row.employee_name)}</td>
                <td class="px-3 py-2 align-top text-gray-700">${esc(row.shift_reference || row.date)}</td>
                <td class="px-3 py-2 align-top text-gray-600 text-xs">${wins}</td>
                <td class="px-3 py-2 align-top text-xs font-mono text-gray-800">${logs}</td>
                <td class="px-3 py-2 align-top">${statuses}</td>
                <td class="px-3 py-2 align-top">${gapBtn}</td>
            </tr>`;
        })
        .join('');

    tbody.querySelectorAll('.fill-gap-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const eid = decodeURIComponent(btn.getAttribute('data-eid') || '');
            const date = decodeURIComponent(btn.getAttribute('data-date') || '');
            openGapPicker(eid, date, rows);
        });
    });
}

function renderPagination(p) {
    const el = document.getElementById('analytics-pagination');
    if (!el || !p) {
        if (el) el.innerHTML = '';
        return;
    }
    const total = p.total_rows ?? 0;
    const page = p.current_page ?? 1;
    const pages = p.total_pages ?? 1;
    const fromR = p.from_row ?? 0;
    const toR = p.to_row ?? 0;
    if (total === 0) {
        el.innerHTML = '<span>No rows to display.</span>';
        return;
    }
    el.innerHTML = `
        <span>Showing <strong>${fromR}</strong>–<strong>${toR}</strong> of <strong>${total}</strong></span>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm" data-page-nav="prev" ${page <= 1 ? 'disabled' : ''}>Previous</button>
            <span class="text-sm">Page ${page} / ${pages}</span>
            <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm" data-page-nav="next" ${page >= pages ? 'disabled' : ''}>Next</button>
        </div>`;
    el.querySelector('[data-page-nav="prev"]')?.addEventListener('click', () => {
        if (page > 1) {
            state.page = page - 1;
            load();
        }
    });
    el.querySelector('[data-page-nav="next"]')?.addEventListener('click', () => {
        if (page < pages) {
            state.page = page + 1;
            load();
        }
    });
}

function openGapPicker(employeeId, date, rows) {
    const row = rows.find((r) => r.employee_id === employeeId && r.date === date);
    if (!row) return;
    const gaps = (row.logs_by_window || []).filter((w) => w.can_fill);
    if (!gaps.length) return;

    const modal = document.getElementById('gap-modal');
    const desc = document.getElementById('gap-modal-desc');
    desc.replaceChildren();
    const intro = document.createElement('p');
    intro.className = 'text-sm text-gray-600 mb-2';
    intro.textContent = `Select a missing window for ${row.employee_name} on ${date}.`;
    const sel = document.createElement('select');
    sel.id = 'gap-window-select';
    sel.className = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-1';
    gaps.forEach((g) => {
        const o = document.createElement('option');
        o.value = g.label;
        o.textContent = `${g.display_label} (${String(g.start || '').slice(0, 5)}–${String(g.end || '').slice(0, 5)})`;
        sel.appendChild(o);
    });
    desc.appendChild(intro);
    desc.appendChild(sel);

    document.getElementById('gap-employee-id').value = employeeId;
    document.getElementById('gap-date').value = date;
    document.getElementById('gap-window').value = sel.value;
    sel.onchange = () => {
        document.getElementById('gap-window').value = sel.value;
    };
    document.getElementById('gap-time').value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeGapModal() {
    const modal = document.getElementById('gap-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function getActivityPayloadForGap() {
    const v = document.getElementById('filter-activity')?.value ?? '';
    if (v === '' || v === '0') return {};
    const n = parseInt(v, 10);
    if (n > 0) return { activity_id: n };
    return {};
}

async function submitGap(ev) {
    ev.preventDefault();
    const employeeId = document.getElementById('gap-employee-id').value;
    const date = document.getElementById('gap-date').value;
    const winSel = document.getElementById('gap-window-select');
    const windowVal = winSel ? winSel.value : document.getElementById('gap-window').value;
    const time = document.getElementById('gap-time').value;
    if (!time) return;

    const body = { employee_id: employeeId, date, window: windowVal, time, ...getActivityPayloadForGap() };

    const res = await fetch(API, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
        alert(data.error || data.details || 'Save failed');
        return;
    }
    closeGapModal();
    load();
}

function populateActivityOptions(options, preserveValue) {
    const sel = document.getElementById('filter-activity');
    if (!sel) return;
    const prev = preserveValue !== undefined ? preserveValue : sel.value;
    sel.replaceChildren();
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All activities';
    sel.appendChild(all);
    (options || []).forEach((o) => {
        const opt = document.createElement('option');
        opt.value = String(o.id);
        const d = o.activity_date ? ` · ${o.activity_date}` : '';
        opt.textContent = (o.name || `Activity ${o.id}`) + d;
        sel.appendChild(opt);
    });
    const keys = new Set((options || []).map((x) => String(x.id)));
    if (prev !== '' && (prev === '0' || keys.has(prev))) sel.value = prev;
}

function populateEmployeeOptions(list) {
    const sel = document.getElementById('filter-employee');
    if (!sel) return;
    const current = sel.value;
    sel.replaceChildren();
    const all = document.createElement('option');
    all.value = '';
    all.textContent = 'All employees';
    sel.appendChild(all);
    (list || []).forEach((o) => {
        const opt = document.createElement('option');
        opt.value = o.employee_id;
        opt.textContent = o.full_name || o.employee_id;
        sel.appendChild(opt);
    });
    if (current && [...sel.options].some((o) => o.value === current)) sel.value = current;
}

function resetToFirstPage() {
    state.page = 1;
}

function closeAttentionDetailModal() {
    const modal = document.getElementById('attention-detail-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function attentionDetailMeta(type) {
    const map = {
        late: {
            title: 'Most late — detailed rows',
            sub: 'Employee-days with late clock-in (after grace) per master-list windows.',
            head: ['Employee', 'Date', 'Late (minutes)'],
            row: (r) => [r.employee_name, r.date, r.late_minutes ?? 0],
        },
        undertime: {
            title: 'Most undertime — detailed rows',
            sub: 'Employee-days below expected minutes worked (after tolerance).',
            head: ['Employee', 'Date', 'Shortfall (minutes)'],
            row: (r) => [r.employee_name, r.date, r.shortfall_minutes ?? 0],
        },
        overtime: {
            title: 'Most overtime — detailed rows',
            sub: 'Employee-days with clock-out after window end.',
            head: ['Employee', 'Date', 'Overtime (minutes)'],
            row: (r) => [r.employee_name, r.date, r.overtime_minutes ?? 0],
        },
        incomplete: {
            title: 'Incomplete days — detailed rows',
            sub: 'Days missing at least one required window (with some logs that day).',
            head: ['Employee', 'Date', 'Missing windows'],
            row: (r) => [r.employee_name, r.date, r.missing_windows ?? '—'],
        },
        absences: {
            title: 'Absent days — detailed rows',
            sub: 'Days with no attendance logs.',
            head: ['Employee', 'Date'],
            row: (r) => [r.employee_name, r.date],
        },
    };
    return map[type] || map.late;
}

function renderAttentionDetailTable(type, rows) {
    const meta = attentionDetailMeta(type);
    const thead = document.getElementById('attention-detail-thead');
    const tbody = document.getElementById('attention-detail-tbody');
    if (!thead || !tbody) return;
    thead.innerHTML = `<tr>${meta.head.map((h) => `<th class="px-3 py-2">${esc(h)}</th>`).join('')}</tr>`;
    if (!rows?.length) {
        tbody.innerHTML = `<tr><td colspan="${meta.head.length}" class="px-3 py-6 text-center text-gray-500">No rows for this filter.</td></tr>`;
        return;
    }
    tbody.innerHTML = rows
        .map((r) => {
            const cells = meta.row(r).map((c) => `<td class="px-3 py-2 text-gray-800">${esc(c)}</td>`).join('');
            return `<tr>${cells}</tr>`;
        })
        .join('');
}

function renderAttentionDetailPagination(p) {
    const el = document.getElementById('attention-detail-pagination');
    if (!el || !p) {
        if (el) el.innerHTML = '';
        return;
    }
    const total = p.total_rows ?? 0;
    const page = p.current_page ?? 1;
    const pages = p.total_pages ?? 1;
    const fromR = p.from_row ?? 0;
    const toR = p.to_row ?? 0;
    if (total === 0) {
        el.innerHTML = '';
        return;
    }
    el.innerHTML = `
        <span>Rows <strong>${fromR}</strong>–<strong>${toR}</strong> of <strong>${total}</strong></span>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 text-sm attention-detail-prev" ${page <= 1 ? 'disabled' : ''}>Previous</button>
            <span class="text-sm">Page ${page} / ${pages}</span>
            <button type="button" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-40 text-sm attention-detail-next" ${page >= pages ? 'disabled' : ''}>Next</button>
        </div>`;
    el.querySelector('.attention-detail-prev')?.addEventListener('click', () => {
        if (page > 1) {
            attentionDetailState.page = page - 1;
            loadAttentionDetailPage();
        }
    });
    el.querySelector('.attention-detail-next')?.addEventListener('click', () => {
        if (page < pages) {
            attentionDetailState.page = page + 1;
            loadAttentionDetailPage();
        }
    });
}

async function loadAttentionDetailPage() {
    const type = attentionDetailState.type;
    if (!type) return;
    const meta = attentionDetailMeta(type);
    document.getElementById('attention-detail-title').textContent = meta.title;
    document.getElementById('attention-detail-sub').textContent = meta.sub;

    const params = {
        ...getFilterParamsForApi(),
        detail: type,
        detail_page: attentionDetailState.page,
        detail_per_page: attentionDetailState.perPage,
    };

    const tbody = document.getElementById('attention-detail-tbody');
    const colCount = meta.head.length;
    if (tbody) tbody.innerHTML = `<tr><td colspan="${colCount}" class="px-3 py-6 text-center text-gray-500">Loading…</td></tr>`;

    try {
        const data = await fetchAnalytics(params);
        if (!data.success) throw new Error(data.error || 'Failed');
        renderAttentionDetailTable(type, data.rows || []);
        renderAttentionDetailPagination(data.pagination);
    } catch (e) {
        console.error(e);
        if (tbody) tbody.innerHTML = `<tr><td colspan="${colCount}" class="px-3 py-6 text-center text-red-600">${esc(e.message)}</td></tr>`;
        document.getElementById('attention-detail-pagination')?.replaceChildren();
    }
}

function openAttentionDetail(type) {
    attentionDetailState.type = type;
    attentionDetailState.page = 1;
    const modal = document.getElementById('attention-detail-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    loadAttentionDetailPage();
}

async function load() {
    const status = document.getElementById('filter-status')?.value || 'all';
    const perPage = parseInt(document.getElementById('filter-per-page')?.value || '25', 10) || 25;

    const params = {
        ...getFilterParamsForApi(),
        status,
        page: state.page,
        per_page: Math.min(100, Math.max(1, perPage)),
    };

    const tbody = document.getElementById('analytics-tbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading…</td></tr>';

    try {
        const data = await fetchAnalytics(params);
        if (!data.success) throw new Error(data.error || 'Failed');
        setDefaultDatesFromRange(data.range);
        const rl = document.getElementById('range-label');
        if (rl && data.range) {
            rl.textContent = `Range: ${data.range.from} → ${data.range.to} (${data.range.filter})`;
        }
        renderSummary(data.summary);
        renderInsights(data.insights);
        renderCharts(data.charts);
        const activityRaw = document.getElementById('filter-activity')?.value ?? '';
        populateActivityOptions(data.activity_filter_options, activityRaw);
        populateEmployeeOptions(data.employee_filter_options);
        renderTable(data.rows || []);
        renderPagination(data.pagination);
    } catch (e) {
        console.error(e);
        destroyCharts();
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-red-600">${esc(e.message)}</td></tr>`;
        document.getElementById('analytics-pagination')?.replaceChildren();
    }
}

function init() {
    initSidebar();
    document.getElementById('btn-refresh')?.addEventListener('click', () => {
        resetToFirstPage();
        load();
    });
    document.getElementById('filter-period')?.addEventListener('change', () => {
        document.getElementById('filter-from').value = '';
        document.getElementById('filter-to').value = '';
        resetToFirstPage();
        load();
    });
    ['filter-from', 'filter-to', 'filter-employee', 'filter-status', 'filter-activity', 'filter-per-page'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => {
            resetToFirstPage();
            load();
        });
    });
    document.getElementById('gap-cancel')?.addEventListener('click', closeGapModal);
    document.getElementById('gap-form')?.addEventListener('submit', submitGap);
    document.getElementById('gap-modal')?.addEventListener('click', (ev) => {
        if (ev.target.id === 'gap-modal') closeGapModal();
    });

    document.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.attention-detail-open');
        if (btn) {
            const t = btn.getAttribute('data-detail');
            if (t) openAttentionDetail(t);
        }
    });
    document.getElementById('attention-detail-close')?.addEventListener('click', closeAttentionDetailModal);
    document.getElementById('attention-detail-modal')?.addEventListener('click', (ev) => {
        if (ev.target.id === 'attention-detail-modal') closeAttentionDetailModal();
    });

    initHoursReports({ autoRun: true });

    load();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
