/**
 * Activities admin: list + create local + edit/delete local only.
 */
import { initSidebar } from '../shared/sidebar.js';
import getBaseUrl from '../shared/baseUrl.js';

initSidebar();

const base = getBaseUrl();
const API_LIST = `${base}/api/activities/index.php`;
const API_UPDATE = `${base}/api/activities/update.php`;
const API_DELETE = `${base}/api/activities/delete.php`;

const tbody = document.getElementById('activities-tbody');
const summary = document.getElementById('activities-summary');
const btnLoad = document.getElementById('btn-load-activities');
const filterFrom = document.getElementById('filter-from');
const filterTo = document.getElementById('filter-to');
const filterSearch = document.getElementById('filter-search');
const formCreate = document.getElementById('form-create-activity');
const createMsg = document.getElementById('create-activity-msg');
const actDate = document.getElementById('act-date');

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function rowToObject(row) {
    if (!row) return {};
    return typeof row === 'object' && !Array.isArray(row) ? JSON.parse(JSON.stringify(row)) : {};
}

async function fetchList(page = 1) {
    const params = new URLSearchParams({ page: String(page), per_page: '50' });
    if (filterFrom?.value) params.set('from', filterFrom.value);
    if (filterTo?.value) params.set('to', filterTo.value);
    if (filterSearch?.value.trim()) params.set('search', filterSearch.value.trim());

    const res = await fetch(`${API_LIST}?${params}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    return res.json();
}

function renderRows(activities) {
    if (!tbody) return;
    tbody.innerHTML = '';
    if (!activities || !activities.length) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="6" class="px-3 py-6 text-center text-gray-500">No activities found.</td>';
        tbody.appendChild(tr);
        return;
    }

    activities.forEach((raw) => {
        const a = rowToObject(raw);
        const id = Number(a.id);
        const source = String(a.source || '');
        const isLocal = source === 'LOCAL';
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        const logsHref = `attendance.php?activity_id=${encodeURIComponent(String(id))}`;
        const logsLink = `<a href="${logsHref}" class="text-gray-700 hover:underline mr-2">Attendance</a>`;
        const actions = isLocal
            ? `${logsLink}<button type="button" class="text-blue-600 hover:underline mr-2 btn-edit" data-id="${id}">Edit</button>
               <button type="button" class="text-red-600 hover:underline btn-del" data-id="${id}">Delete</button>`
            : `${logsLink}<span class="text-gray-400 text-xs">LGUMS import</span>`;

        tr.innerHTML = `
            <td class="px-3 py-2 font-mono text-xs">${id}</td>
            <td class="px-3 py-2">${escapeHtml(String(a.name || ''))}</td>
            <td class="px-3 py-2 whitespace-nowrap">${escapeHtml(String(a.activity_date || ''))}</td>
            <td class="px-3 py-2">${escapeHtml(source)}</td>
            <td class="px-3 py-2 font-mono text-xs">${escapeHtml(a.external_id != null ? String(a.external_id) : '—')}</td>
            <td class="px-3 py-2 whitespace-nowrap">${actions}</td>
        `;
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.btn-edit').forEach((btn) => {
        btn.addEventListener('click', () => onEdit(Number(btn.getAttribute('data-id'))));
    });
    tbody.querySelectorAll('.btn-del').forEach((btn) => {
        btn.addEventListener('click', () => onDelete(Number(btn.getAttribute('data-id'))));
    });
}

async function load() {
    if (summary) summary.textContent = 'Loading…';
    try {
        const data = await fetchList(1);
        if (!data.success) {
            if (summary) summary.textContent = data.error || 'Failed to load';
            return;
        }
        renderRows(data.activities || []);
        const p = data.pagination || {};
        if (summary) {
            summary.textContent = `Showing ${p.startRecord || 0}–${p.endRecord || 0} of ${p.totalRecords || 0}`;
        }
    } catch (e) {
        if (summary) summary.textContent = 'Request failed';
    }
}

function onEdit(id) {
    const raw = Array.from(tbody.querySelectorAll('tr')).find((tr) => {
        const first = tr.querySelector('td');
        return first && Number(first.textContent.trim()) === id;
    });
    if (!raw) return;
    const cells = raw.querySelectorAll('td');
    const name = cells[1].textContent.trim();
    const date = cells[2].textContent.trim();
    const newName = window.prompt('Activity name', name);
    if (newName === null) return;
    const newDate = window.prompt('Activity date (Y-m-d)', date);
    if (newDate === null) return;
    const newDesc = window.prompt('Description (optional, blank to clear)', '');

    (async () => {
        const body = {
            id,
            name: newName.trim(),
            activity_date: newDate.trim(),
            description: newDesc === '' ? '' : newDesc,
        };
        const res = await fetch(API_UPDATE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const out = await res.json();
        if (!out.success) {
            window.alert(out.error || 'Update failed');
            return;
        }
        await load();
    })();
}

function onDelete(id) {
    if (!window.confirm('Delete this local activity? Attendance rows will keep logging but lose this link (set to no event).')) {
        return;
    }
    (async () => {
        const res = await fetch(API_DELETE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ id }),
        });
        const out = await res.json();
        if (!out.success) {
            window.alert(out.error || 'Delete failed');
            return;
        }
        await load();
    })();
}

if (btnLoad) {
    btnLoad.addEventListener('click', () => load());
}

if (formCreate) {
    formCreate.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (createMsg) createMsg.textContent = '';
        const fd = new FormData(formCreate);
        const body = {
            name: String(fd.get('name') || '').trim(),
            activity_date: String(fd.get('activity_date') || '').trim(),
            description: String(fd.get('description') || '').trim(),
        };
        try {
            const res = await fetch(API_LIST, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(body),
            });
            const out = await res.json();
            if (!out.success) {
                if (createMsg) createMsg.textContent = out.error || 'Failed';
                return;
            }
            formCreate.reset();
            if (actDate) {
                actDate.value = new Date().toISOString().slice(0, 10);
            }
            if (createMsg) createMsg.textContent = 'Created.';
            await load();
        } catch {
            if (createMsg) createMsg.textContent = 'Request failed';
        }
    });
}

const today = new Date().toISOString().slice(0, 10);
if (actDate && !actDate.value) {
    actDate.value = today;
}

load();
