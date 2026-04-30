/**
 * Attendance reports: sidebar, charts, fines API, PDF download.
 */
import { initSidebar } from '../shared/sidebar.js';

const finesApi = window.ATTENDANCE_REPORTS_FINES_API || '';

/**
 * @param {string|number|undefined} preselectActivityId Optional activities.id to select in the modal
 */
function openFinesModal(preselectActivityId) {
    const m = document.getElementById('fines-modal');
    const sel = document.getElementById('fines-activity-id');
    if (preselectActivityId != null && preselectActivityId !== '' && sel) {
        const v = String(preselectActivityId);
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === v) {
                sel.selectedIndex = i;
                break;
            }
        }
    }
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
        loadFineForSelectedActivity();
    }
}

function closeFinesModal() {
    const m = document.getElementById('fines-modal');
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
}

async function loadFineForSelectedActivity() {
    const sel = document.getElementById('fines-activity-id');
    const amt = document.getElementById('fines-amount');
    if (!sel || !amt || !finesApi) return;
    const id = sel.value;
    if (!id) return;
    try {
        const res = await fetch(`${finesApi}?activity_id=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
        const data = await res.json();
        if (data.success) {
            amt.value = data.fine_amount != null ? String(data.fine_amount) : '0';
        }
    } catch (e) {
        console.error(e);
    }
}

async function saveFine() {
    const sel = document.getElementById('fines-activity-id');
    const amt = document.getElementById('fines-amount');
    if (!sel || !amt || !finesApi) return;
    const activity_id = parseInt(sel.value, 10);
    const fine_amount = parseFloat(amt.value);
    if (!activity_id || Number.isNaN(fine_amount) || fine_amount < 0) {
        alert('Select an activity and enter a valid amount (0 or more).');
        return;
    }
    try {
        const res = await fetch(finesApi, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ activity_id, fine_amount }),
        });
        const data = await res.json();
        if (!data.success) {
            alert(data.error || 'Save failed');
            return;
        }
        alert('Fine saved.');
        closeFinesModal();
        window.location.reload();
    } catch (e) {
        alert(e.message || 'Save failed');
    }
}

let chartPieInst = null;
let chartBarInst = null;

function renderCharts() {
    const pieData = window.ATTENDANCE_REPORTS_CHART_PIE;
    const barData = window.ATTENDANCE_REPORTS_CHART_BAR;
    if (typeof Chart === 'undefined') return;

    if (chartPieInst) {
        chartPieInst.destroy();
        chartPieInst = null;
    }
    if (chartBarInst) {
        chartBarInst.destroy();
        chartBarInst = null;
    }

    const pieEl = document.getElementById('chart-ar-pie');
    if (pieEl && pieData?.labels?.length && pieData.values.some((v) => v > 0)) {
        chartPieInst = new Chart(pieEl.getContext('2d'), {
            type: 'pie',
            data: {
                labels: pieData.labels,
                datasets: [
                    {
                        data: pieData.values,
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    }

    const barEl = document.getElementById('chart-ar-bar');
    if (barEl && barData?.labels?.length) {
        chartBarInst = new Chart(barEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: barData.labels,
                datasets: [
                    {
                        label: 'Fine (PHP)',
                        data: barData.values,
                        backgroundColor: '#3b82f6aa',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    }
}

function downloadPdf() {
    const el = document.getElementById('pdf-source');
    if (!el || typeof html2pdf === 'undefined') {
        window.print();
        return;
    }
    const opt = {
        margin: [8, 8, 8, 8],
        filename: `attendance_report_${new Date().toISOString().slice(0, 10)}.pdf`,
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
    };
    html2pdf().set(opt).from(el).save().catch(() => {
        alert('PDF generation failed. Use Print / PDF view instead.');
    });
}

function initFinesOverviewSearch() {
    const input = document.getElementById('fines-overview-search');
    const table = document.getElementById('fines-overview-table');
    if (!input || !table) return;
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr[data-search]').forEach((tr) => {
            const hay = (tr.getAttribute('data-search') || '').toLowerCase();
            tr.style.display = !q || hay.includes(q) ? '' : 'none';
        });
    });
}

initSidebar();

document.getElementById('btn-fines-modal')?.addEventListener('click', () => openFinesModal());
document.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.js-open-fines-modal');
    if (btn && btn.dataset.activityId) {
        openFinesModal(btn.dataset.activityId);
    }
});
document.getElementById('fines-cancel')?.addEventListener('click', closeFinesModal);
document.getElementById('fines-modal')?.addEventListener('click', (ev) => {
    if (ev.target.id === 'fines-modal') closeFinesModal();
});
document.getElementById('fines-activity-id')?.addEventListener('change', loadFineForSelectedActivity);
document.getElementById('fines-save')?.addEventListener('click', saveFine);
document.getElementById('btn-pdf-download')?.addEventListener('click', downloadPdf);

initFinesOverviewSearch();
renderCharts();
