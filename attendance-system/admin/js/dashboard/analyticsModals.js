/**
 * Clickable dashboard metric rows → analytics modal (visitor / attendance).
 * Loads data lazily from api/dashboard/*-modal.php; Chart.js bar charts + tables.
 */

/** @type {any[]} */
const visitorModalCharts = [];
/** @type {any[]} */
const attendanceModalCharts = [];

const VISITOR_TITLES = {
    total: 'Total Visitors Analytics',
    resident: 'Resident Visitors Analytics',
    non_resident: 'Non-Resident Visitors Analytics',
    walkin: 'Walk-In Visitors Analytics',
    online: 'Online Appointment Visitors Analytics',
};

const ATTENDANCE_TITLES = {
    present: 'Present Employees Analytics',
    absent: 'Absent Employees Analytics',
    late: 'Late Arrivals Analytics',
    overtime: 'Overtime Analytics',
};

/** Visitor modal table page size (server max 200). */
const VISITOR_PAGE_SIZE = 50;

/** @type {{ filter: string, card: string }} */
let visitorModalPaging = { filter: 'month', card: 'total' };

function destroyChartList(list) {
    while (list.length) {
        const c = list.pop();
        if (c) {
            c.destroy();
        }
    }
}

function baseBarOptions(horizontal = false) {
    const opts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
            },
            x: { grid: { display: false } },
        },
    };
    if (horizontal) {
        opts.indexAxis = 'y';
        opts.scales = {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { grid: { display: false } },
        };
    }
    return opts;
}

function ensureChartData(labels, counts) {
    const lb = labels && labels.length ? labels : ['No data'];
    if (!counts || counts.length !== lb.length) {
        return { labels: lb, counts: lb.map(() => 0) };
    }
    return { labels: lb, counts };
}

function formatDateTime(value) {
    if (value == null || value === '') {
        return '—';
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return String(value);
    }
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** Attendance modal: API returns preformatted strings like "Apr 2, 2026 8:15 AM · morning_in". */
function formatAttendanceTimeCell(value) {
    if (value == null || value === '') {
        return '—';
    }
    if (typeof value === 'string' && (value.includes('·') || /_(in|out)\b/.test(value))) {
        return value;
    }
    return formatDateTime(value);
}

function setModalVisible(show) {
    const modal = document.getElementById('dashboard-analytics-modal');
    if (!modal) {
        return;
    }
    if (show) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function showModalState(phase, message) {
    const loading = document.getElementById('dashboard-analytics-modal-loading');
    const err = document.getElementById('dashboard-analytics-modal-error');
    const body = document.getElementById('dashboard-analytics-modal-body');
    if (!loading || !err || !body) {
        return;
    }
    loading.classList.add('hidden');
    err.classList.add('hidden');
    body.classList.add('hidden');
    err.textContent = '';
    if (phase === 'loading') {
        loading.classList.remove('hidden');
        if (message) {
            loading.textContent = message;
        }
    } else if (phase === 'error') {
        err.textContent = message || 'Something went wrong.';
        err.classList.remove('hidden');
    } else if (phase === 'content') {
        body.classList.remove('hidden');
    }
}

function renderVisitorTable(rows, totalRows, offset, limit) {
    const tbody = document.getElementById('dm-v-table-body');
    const note = document.getElementById('dm-v-table-note');
    if (!tbody) {
        return;
    }
    tbody.replaceChildren();
    (rows || []).forEach((r) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50/80';
        [
            r.full_name || '—',
            r.visitor_type || '—',
            r.purok_or_city || '—',
            r.service || '—',
            r.visit_type || '—',
            formatDateTime(r.created_at),
        ].forEach((text) => {
            const td = document.createElement('td');
            td.className = 'px-3 py-2 whitespace-nowrap';
            td.textContent = text;
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
    const total = typeof totalRows === 'number' ? totalRows : rows?.length ?? 0;
    const off = typeof offset === 'number' ? offset : 0;
    const lim = typeof limit === 'number' ? limit : VISITOR_PAGE_SIZE;
    if (note) {
        if (total === 0) {
            note.textContent = 'No rows for this filter.';
        } else if (!rows || rows.length === 0) {
            note.textContent = `No rows on this page (${total} total).`;
        } else {
            const from = off + 1;
            const to = off + rows.length;
            note.textContent = `Showing ${from}–${to} of ${total} visitor log(s). Charts load once; only the table reloads when you change pages.`;
        }
    }
}

function renderVisitorPagination(totalRows, offset, limit) {
    const el = document.getElementById('dm-v-table-pagination');
    if (!el) {
        return;
    }
    const total = totalRows || 0;
    const lim = limit || VISITOR_PAGE_SIZE;
    const off = offset || 0;
    if (total <= lim) {
        el.classList.add('hidden');
        el.replaceChildren();
        return;
    }
    el.classList.remove('hidden');
    const pages = Math.ceil(total / lim);
    const currentPage = Math.floor(off / lim) + 1;
    el.replaceChildren();
    const wrap = document.createElement('div');
    wrap.className = 'flex flex-wrap items-center gap-2';
    const prev = document.createElement('button');
    prev.type = 'button';
    prev.className =
        'px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed';
    prev.textContent = 'Previous';
    prev.disabled = currentPage <= 1;
    prev.addEventListener('click', () => {
        if (currentPage > 1) {
            loadVisitorModalPage(currentPage - 1);
        }
    });
    const next = document.createElement('button');
    next.type = 'button';
    next.className =
        'px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed';
    next.textContent = 'Next';
    next.disabled = currentPage >= pages;
    next.addEventListener('click', () => {
        if (currentPage < pages) {
            loadVisitorModalPage(currentPage + 1);
        }
    });
    const lab = document.createElement('span');
    lab.className = 'text-gray-600 tabular-nums';
    lab.textContent = `Page ${currentPage} of ${pages}`;
    wrap.appendChild(prev);
    wrap.appendChild(next);
    wrap.appendChild(lab);
    el.appendChild(wrap);
}

async function loadVisitorModalPage(page) {
    const { filter, card } = visitorModalPaging;
    const limit = VISITOR_PAGE_SIZE;
    const offset = (page - 1) * limit;
    const tbody = document.getElementById('dm-v-table-body');
    if (tbody) {
        tbody.classList.add('opacity-50', 'pointer-events-none');
    }
    try {
        const params = new URLSearchParams({
            filter,
            card,
            limit: String(limit),
            offset: String(offset),
            skip_charts: '1',
        });
        const res = await fetch(`../api/dashboard/visitor-modal.php?${params}`, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.error || 'Failed to load page');
        }
        renderVisitorTable(data.rows, data.total_rows, data.offset, data.limit);
        renderVisitorPagination(data.total_rows, data.offset, data.limit);
    } catch (e) {
        console.error(e);
    } finally {
        if (tbody) {
            tbody.classList.remove('opacity-50', 'pointer-events-none');
        }
    }
}

function renderAttendanceTable(rows) {
    const tbody = document.getElementById('dm-a-table-body');
    const note = document.getElementById('dm-a-table-note');
    if (!tbody) {
        return;
    }
    tbody.replaceChildren();
    (rows || []).forEach((r) => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50/80';
        [
            r.employee_name || '—',
            r.status || '—',
            formatAttendanceTimeCell(r.last_log != null ? r.last_log : r.time_in),
        ].forEach((text) => {
            const td = document.createElement('td');
            td.className = 'px-3 py-2';
            td.textContent = text;
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
    if (note) {
        const n = rows && rows.length ? rows.length : 0;
        note.textContent =
            n === 0
                ? 'No rows for this filter.'
                : `Showing ${n} employee row(s) (capped). Last log is the most recent attendance row in this period (window label shown).`;
    }
}

function buildVisitorModalCharts(payload) {
    if (typeof Chart === 'undefined') {
        return;
    }
    if (!payload || !payload.charts) {
        return;
    }
    destroyChartList(visitorModalCharts);
    const ch = payload.charts || {};

    const mk = (canvasId, labels, counts, colors, horizontal = false) => {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            return;
        }
        const { labels: lb, counts: ct } = ensureChartData(labels, counts);
        visitorModalCharts.push(
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: lb,
                    datasets: [
                        {
                            label: 'Count',
                            data: ct,
                            backgroundColor: colors.bg,
                            borderColor: colors.bd,
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: baseBarOptions(horizontal),
            })
        );
    };

    const t = ch.trend || {};
    mk('dm-v-chart-trend', t.labels, t.counts, { bg: 'rgba(147, 51, 234, 0.72)', bd: '#7c3aed' });

    const rn = ch.resident_vs_non || {};
    mk('dm-v-chart-resnon', rn.labels, rn.counts, { bg: 'rgba(249, 115, 22, 0.75)', bd: '#ea580c' });

    const pk = ch.purok || {};
    mk('dm-v-chart-purok', pk.labels, pk.counts, { bg: 'rgba(234, 179, 8, 0.75)', bd: '#ca8a04' }, true);

    const sv = ch.services || {};
    mk('dm-v-chart-services', sv.labels, sv.counts, { bg: 'rgba(59, 130, 246, 0.78)', bd: '#2563eb' }, true);

    const wo = ch.walkin_vs_online || {};
    mk('dm-v-chart-booking', wo.labels, wo.counts, { bg: 'rgba(236, 72, 153, 0.75)', bd: '#db2777' });

    const cy = ch.city_origin || {};
    mk('dm-v-chart-city', cy.labels, cy.counts, { bg: 'rgba(239, 68, 68, 0.72)', bd: '#dc2626' }, true);
}

function buildAttendanceModalCharts(payload) {
    if (typeof Chart === 'undefined') {
        return;
    }
    destroyChartList(attendanceModalCharts);
    const ch = payload.charts || {};
    const tr = ch.trend || {};

    const ctxTrend = document.getElementById('dm-a-chart-trend');
    if (ctxTrend) {
        const tl = tr.labels && tr.labels.length ? tr.labels : ['No data'];
        const tp = tr.present && tr.present.length === tl.length ? tr.present : tl.map(() => 0);
        attendanceModalCharts.push(
            new Chart(ctxTrend, {
                type: 'bar',
                data: {
                    labels: tl,
                    datasets: [
                        {
                            label: 'Present (distinct)',
                            data: tp,
                            backgroundColor: 'rgba(34, 197, 94, 0.75)',
                            borderColor: '#16a34a',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: baseBarOptions(false),
            })
        );
    }

    const pa = ch.present_vs_absent || {};
    const paLb = pa.labels || ['Present', 'Absent'];
    const paCt = pa.counts || [0, 0];
    const ctxPa = document.getElementById('dm-a-chart-presabs');
    if (ctxPa) {
        attendanceModalCharts.push(
            new Chart(ctxPa, {
                type: 'bar',
                data: {
                    labels: paLb,
                    datasets: [
                        {
                            label: 'Employees',
                            data: paCt,
                            backgroundColor: ['rgba(34, 197, 94, 0.78)', 'rgba(239, 68, 68, 0.75)'],
                            borderColor: ['#16a34a', '#dc2626'],
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: baseBarOptions(false),
            })
        );
    }

    const ctxLate = document.getElementById('dm-a-chart-late');
    if (ctxLate) {
        const tl = tr.labels && tr.labels.length ? tr.labels : ['No data'];
        const lateData = tr.late && tr.late.length === tl.length ? tr.late : tl.map(() => 0);
        attendanceModalCharts.push(
            new Chart(ctxLate, {
                type: 'bar',
                data: {
                    labels: tl,
                    datasets: [
                        {
                            label: 'Late events',
                            data: lateData,
                            backgroundColor: 'rgba(234, 179, 8, 0.8)',
                            borderColor: '#ca8a04',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: baseBarOptions(false),
            })
        );
    }

    const ctxOt = document.getElementById('dm-a-chart-ot');
    if (ctxOt) {
        const tl = tr.labels && tr.labels.length ? tr.labels : ['No data'];
        const otData = tr.overtime && tr.overtime.length === tl.length ? tr.overtime : tl.map(() => 0);
        attendanceModalCharts.push(
            new Chart(ctxOt, {
                type: 'bar',
                data: {
                    labels: tl,
                    datasets: [
                        {
                            label: 'Overtime events',
                            data: otData,
                            backgroundColor: 'rgba(99, 102, 241, 0.78)',
                            borderColor: '#4f46e5',
                            borderWidth: 1,
                            borderRadius: 4,
                        },
                    ],
                },
                options: baseBarOptions(false),
            })
        );
    }
}

async function openVisitorModal(card) {
    const filterEl = document.getElementById('visitor-filter-dropdown');
    const filter = filterEl ? filterEl.value : 'month';
    const title = document.getElementById('dashboard-analytics-modal-title');
    const sub = document.getElementById('dashboard-analytics-modal-sub');
    const panelV = document.getElementById('dashboard-analytics-visitor-panel');
    const panelA = document.getElementById('dashboard-analytics-attendance-panel');

    if (title) {
        title.textContent = VISITOR_TITLES[card] || VISITOR_TITLES.total;
    }
    if (sub) {
        sub.textContent = `Period: ${filter} · Card filter applied to list and charts.`;
    }
    if (panelV) {
        panelV.classList.remove('hidden');
    }
    if (panelA) {
        panelA.classList.add('hidden');
    }

    setModalVisible(true);
    showModalState('loading', 'Loading visitor analytics…');
    destroyChartList(visitorModalCharts);

    try {
        visitorModalPaging = { filter, card };
        const params = new URLSearchParams({
            filter,
            card,
            limit: String(VISITOR_PAGE_SIZE),
            offset: '0',
        });
        const url = `../api/dashboard/visitor-modal.php?${params}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) {
            showModalState('error', data.error || 'Failed to load data.');
            return;
        }
        buildVisitorModalCharts(data);
        renderVisitorTable(data.rows, data.total_rows, data.offset, data.limit);
        renderVisitorPagination(data.total_rows, data.offset, data.limit);
        const total = typeof data.total_rows === 'number' ? data.total_rows : (data.rows || []).length;
        if (sub) {
            sub.textContent = `Period: ${filter} · ${total} visitor log(s) match this card.`;
        }
        showModalState('content');
    } catch (e) {
        console.error(e);
        showModalState('error', 'Network error loading visitor analytics.');
    }
}

async function openAttendanceModal(card) {
    const filterEl = document.getElementById('employee-attendance-filter-dropdown');
    const filter = filterEl ? filterEl.value : 'month';
    const title = document.getElementById('dashboard-analytics-modal-title');
    const sub = document.getElementById('dashboard-analytics-modal-sub');
    const panelV = document.getElementById('dashboard-analytics-visitor-panel');
    const panelA = document.getElementById('dashboard-analytics-attendance-panel');

    if (title) {
        title.textContent = ATTENDANCE_TITLES[card] || ATTENDANCE_TITLES.present;
    }
    if (sub) {
        sub.textContent = `Period: ${filter} · Same rules as dashboard attendance stats.`;
    }
    if (panelV) {
        panelV.classList.add('hidden');
    }
    if (panelA) {
        panelA.classList.remove('hidden');
    }

    setModalVisible(true);
    showModalState('loading', 'Loading attendance analytics…');
    destroyChartList(attendanceModalCharts);

    try {
        const url = `../api/dashboard/attendance-modal.php?${new URLSearchParams({ filter, card })}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) {
            showModalState('error', data.error || 'Failed to load data.');
            return;
        }
        buildAttendanceModalCharts(data);
        renderAttendanceTable(data.rows);
        showModalState('content');
    } catch (e) {
        console.error(e);
        showModalState('error', 'Network error loading attendance analytics.');
    }
}

function closeModal() {
    destroyChartList(visitorModalCharts);
    destroyChartList(attendanceModalCharts);
    const vPag = document.getElementById('dm-v-table-pagination');
    if (vPag) {
        vPag.classList.add('hidden');
        vPag.replaceChildren();
    }
    const loading = document.getElementById('dashboard-analytics-modal-loading');
    const err = document.getElementById('dashboard-analytics-modal-error');
    const body = document.getElementById('dashboard-analytics-modal-body');
    if (loading) {
        loading.classList.add('hidden');
    }
    if (err) {
        err.classList.add('hidden');
        err.textContent = '';
    }
    if (body) {
        body.classList.add('hidden');
    }
    setModalVisible(false);
}

/**
 * Bind dashboard metric rows and modal close actions.
 */
export function initDashboardAnalyticsModals() {
    const modal = document.getElementById('dashboard-analytics-modal');
    const closeBtn = document.getElementById('dashboard-analytics-modal-close');
    if (!modal) {
        return;
    }

    document.querySelectorAll('[data-dashboard-analytics="visitor"]').forEach((el) => {
        const open = () => {
            const card = el.getAttribute('data-visitor-card') || 'total';
            openVisitorModal(card);
        };
        el.addEventListener('click', open);
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open();
            }
        });
    });

    document.querySelectorAll('[data-dashboard-analytics="attendance"]').forEach((el) => {
        const open = () => {
            const card = el.getAttribute('data-attendance-card') || 'present';
            openAttendanceModal(card);
        };
        el.addEventListener('click', open);
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                open();
            }
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
}
