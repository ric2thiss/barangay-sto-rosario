const API_BASE = '../api/settings/index.php';
const LOG_EXPORT = '../api/settings/log-export.php';
const LOG_DELETE = '../api/settings/log-delete.php';
const DATABASE_BACKUP = '../api/settings/database-backup.php';
const LOGIN_LOGS = '../api/settings/login-logs.php';
const ACCESS_LOG = '../api/settings/access-log.php';

let currentSettings = {};
let loginLogsPage = 1;
const loginLogsPerPage = 20;

function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const bgColor = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
    container.className = `mb-4 p-4 rounded-lg border ${bgColor}`;
    container.innerHTML = `<p class="font-medium">${message}</p>`;
    container.classList.remove('hidden');
    setTimeout(() => container.classList.add('hidden'), 5000);
}

function applyUserAccessFromSettings() {
    const raw = currentSettings.user_access_control;
    let uac = {
        attendance_admins: true,
        profiling_admin: true,
        barangay_officials: true,
        residents: true,
    };
    if (raw && raw.value && typeof raw.value === 'object') {
        uac = { ...uac, ...raw.value };
    }
    document.getElementById('uac_attendance_admins').checked = !!uac.attendance_admins;
    document.getElementById('uac_profiling_admin').checked = !!uac.profiling_admin;
    document.getElementById('uac_barangay_officials').checked = !!uac.barangay_officials;
    document.getElementById('uac_residents').checked = !!uac.residents;
}

async function loadSettings() {
    try {
        const response = await fetch(API_BASE, { credentials: 'same-origin' });
        const data = await response.json();
        if (data.success && data.settings) {
            currentSettings = data.settings;
            if (currentSettings.app_name) {
                document.getElementById('app_name').value = currentSettings.app_name.value || '';
            }
            if (currentSettings.timezone) {
                const tz = currentSettings.timezone.value || 'Asia/Manila';
                const sel = document.getElementById('timezone');
                if (sel && [...sel.options].some((o) => o.value === tz)) {
                    sel.value = tz;
                }
            }
            if (currentSettings.maintenance_mode) {
                document.getElementById('maintenance_mode').checked = currentSettings.maintenance_mode.value || false;
            }
            if (currentSettings.maintenance_message) {
                document.getElementById('maintenance_message').value = currentSettings.maintenance_message.value || '';
            }
            if (currentSettings.apache_access_log_path) {
                document.getElementById('apache_access_log_path').value =
                    currentSettings.apache_access_log_path.value || '';
            }
            applyUserAccessFromSettings();
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showMessage('Failed to load settings', 'error');
    }
}

window.saveGeneralSettings = async function () {
    const settings = {
        app_name: document.getElementById('app_name').value,
        timezone: document.getElementById('timezone').value,
    };
    try {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings),
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (data.success) {
            showMessage('General settings saved successfully', 'success');
            loadSettings();
        } else {
            showMessage(data.message || 'Failed to save settings', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showMessage('Failed to save settings', 'error');
    }
};

window.saveSecuritySettings = async function () {
    const user_access_control = {
        attendance_admins: document.getElementById('uac_attendance_admins').checked,
        profiling_admin: document.getElementById('uac_profiling_admin').checked,
        barangay_officials: document.getElementById('uac_barangay_officials').checked,
        residents: document.getElementById('uac_residents').checked,
    };
    const settings = {
        user_access_control,
        apache_access_log_path: document.getElementById('apache_access_log_path').value.trim(),
    };
    try {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings),
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (data.success) {
            showMessage('Security settings saved', 'success');
            loadSettings();
        } else {
            showMessage(data.message || 'Failed to save', 'error');
        }
    } catch (e) {
        showMessage('Failed to save security settings', 'error');
    }
};

window.saveMaintenanceSettings = async function () {
    const settings = {
        maintenance_mode: document.getElementById('maintenance_mode').checked ? 1 : 0,
        maintenance_message: document.getElementById('maintenance_message').value,
    };
    try {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings),
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (data.success) {
            const mode = settings.maintenance_mode ? 'enabled' : 'disabled';
            showMessage(`Maintenance mode ${mode} successfully`, 'success');
            loadSettings();
        } else {
            showMessage(data.message || 'Failed to save maintenance settings', 'error');
        }
    } catch (error) {
        console.error('Error saving maintenance settings:', error);
        showMessage('Failed to save maintenance settings', 'error');
    }
};

window.onMaintenanceToggle = function () {
    const isEnabled = document.getElementById('maintenance_mode').checked;
    const message = isEnabled
        ? 'Maintenance will apply on save. Only Administrator, Admin, and Barangay Secretary roles keep access.'
        : 'Maintenance will be disabled when you save.';
    if (isEnabled) {
        if (!confirm(message + '\n\nContinue?')) {
            document.getElementById('maintenance_mode').checked = false;
        }
    }
};

async function loadAccessLogPreview() {
    const pre = document.getElementById('access_log_preview');
    const meta = document.getElementById('access_log_meta');
    pre.textContent = 'Loading…';
    try {
        const r = await fetch(`${ACCESS_LOG}?lines=250`);
        const d = await r.json();
        if (!d.success) {
            pre.textContent = d.message || 'Error';
            return;
        }
        meta.textContent = d.path ? `Source: ${d.path}` : d.error || '';
        pre.textContent = (d.lines || []).join('\n') || (d.error || 'No lines');
    } catch (e) {
        pre.textContent = 'Failed to load';
    }
}

async function loadLoginLogsPage() {
    const tbody = document.getElementById('login_logs_tbody');
    const info = document.getElementById('login_logs_page_info');
    tbody.innerHTML = '<tr><td colspan="6" class="p-2 text-gray-500">Loading…</td></tr>';
    try {
        const r = await fetch(`${LOGIN_LOGS}?page=${loginLogsPage}&per_page=${loginLogsPerPage}`);
        const d = await r.json();
        if (!d.success) {
            tbody.innerHTML = `<tr><td colspan="6" class="p-2 text-red-600">${d.message || 'Error'}</td></tr>`;
            return;
        }
        tbody.innerHTML = '';
        const rows = d.rows || [];
        if (rows.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="6" class="p-2 text-gray-500">No login attempts recorded yet. ' +
                'Sign-ins through the central login page are listed here after you open any protected page in this app ' +
                '(one entry per browser session). Failed attempts via this app\'s own login API are also logged.</td></tr>';
        }
        for (const row of rows) {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-100';
            const ok = row.success === 1 || row.success === true;
            tr.innerHTML = `
                <td class="p-2 whitespace-nowrap">${row.created_at || ''}</td>
                <td class="p-2">${escapeHtml(row.username || '')}</td>
                <td class="p-2">${ok ? 'Yes' : 'No'}</td>
                <td class="p-2">${escapeHtml(row.auth_source || '')}</td>
                <td class="p-2">${escapeHtml(row.role || '')}</td>
                <td class="p-2 text-xs">${escapeHtml(row.message || '')}</td>`;
            tbody.appendChild(tr);
        }
        const total = d.total || 0;
        const pages = Math.max(1, Math.ceil(total / loginLogsPerPage));
        info.textContent = `Page ${loginLogsPage} of ${pages} (${total} total)`;
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-2 text-red-600">Failed to load</td></tr>';
    }
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

async function downloadLogExport(logType, format) {
    const fromEl = logType === 'visitor' ? 'vis_export_from' : 'att_export_from';
    const toEl = logType === 'visitor' ? 'vis_export_to' : 'att_export_to';
    const from = document.getElementById(fromEl).value;
    const to = document.getElementById(toEl).value;
    const u = new URL(LOG_EXPORT, window.location.href);
    u.searchParams.set('log_type', logType);
    u.searchParams.set('format', format);
    if (from) {
        u.searchParams.set('date_from', from);
    }
    if (to) {
        u.searchParams.set('date_to', to);
    }
    if (format === 'zip') {
        u.searchParams.set('zip_formats', 'sql,pdf,docx,xlsx');
    }
    try {
        const r = await fetch(u.toString(), { credentials: 'same-origin' });
        if (!r.ok) {
            const t = await r.text();
            showMessage(t.slice(0, 200) || 'Export failed', 'error');
            return;
        }
        const cd = r.headers.get('Content-Disposition');
        let fname = 'export';
        if (cd && cd.includes('filename=')) {
            fname = cd.split('filename=')[1].replace(/["']/g, '').trim();
        } else {
            fname = `${logType}_export.${format === 'zip' ? 'zip' : format}`;
        }
        const blob = await r.blob();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = fname;
        a.click();
        URL.revokeObjectURL(a.href);
        showMessage('Download started', 'success');
    } catch (e) {
        showMessage('Export failed', 'error');
    }
}

async function postLogDelete(logType, userField, passField) {
    const username = document.getElementById(userField).value.trim();
    const password = document.getElementById(passField).value;
    const fromEl = logType === 'visitor' ? 'vis_del_from' : 'att_del_from';
    const toEl = logType === 'visitor' ? 'vis_del_to' : 'att_del_to';
    const date_from = document.getElementById(fromEl).value || null;
    const date_to = document.getElementById(toEl).value || null;
    if (!username || !password) {
        showMessage('Enter username and password', 'error');
        return;
    }
    if (!confirm('This will soft-delete matching logs. Continue?')) {
        return;
    }
    try {
        const r = await fetch(LOG_DELETE, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username,
                password,
                log_type: logType === 'visitor' ? 'visitor' : 'attendance',
                date_from,
                date_to,
            }),
        });
        const d = await r.json();
        if (d.success) {
            showMessage(d.message + (d.affected_rows != null ? ` (${d.affected_rows} rows)` : ''), 'success');
            document.getElementById(passField).value = '';
        } else {
            showMessage(d.message || 'Delete failed', 'error');
        }
    } catch (e) {
        showMessage('Delete request failed', 'error');
    }
}

async function downloadDatabaseBackup() {
    try {
        const r = await fetch(DATABASE_BACKUP, { credentials: 'same-origin' });
        if (!r.ok) {
            let msg = 'Backup failed';
            try {
                const j = await r.json();
                if (j.message) {
                    msg = j.message;
                }
            } catch {
                const t = await r.text();
                if (t) {
                    msg = t.slice(0, 200);
                }
            }
            showMessage(msg, 'error');
            return;
        }
        const cd = r.headers.get('Content-Disposition');
        let fname = 'attendance_system_backup.sql';
        if (cd && cd.includes('filename=')) {
            fname = cd.split('filename=')[1].replace(/["']/g, '').trim();
        }
        const blob = await r.blob();
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = fname;
        a.click();
        URL.revokeObjectURL(a.href);
        showMessage('Database backup download started', 'success');
    } catch (e) {
        showMessage('Backup failed', 'error');
    }
}

window.showTab = function (tabName) {
    document.querySelectorAll('.settings-tab-content').forEach((content) => content.classList.add('hidden'));
    document.querySelectorAll('.settings-tab').forEach((tab) => tab.classList.remove('settings-tab-active'));
    const activeContent = document.getElementById(tabName);
    if (activeContent) {
        activeContent.classList.remove('hidden');
    }
    const activeTab = document.querySelector(`.settings-tab[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('settings-tab-active');
    }
    if (tabName === 'security') {
        loadAccessLogPreview();
        loginLogsPage = 1;
        loadLoginLogsPage();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('-translate-x-full'));
    }

    document.querySelectorAll('[data-export-att]').forEach((btn) => {
        btn.addEventListener('click', () => downloadLogExport('attendance', btn.getAttribute('data-export-att')));
    });
    document.querySelectorAll('[data-export-vis]').forEach((btn) => {
        btn.addEventListener('click', () => downloadLogExport('visitor', btn.getAttribute('data-export-vis')));
    });
    document.getElementById('att_del_btn').addEventListener('click', () =>
        postLogDelete('attendance', 'att_del_user', 'att_del_pass')
    );
    document.getElementById('vis_del_btn').addEventListener('click', () =>
        postLogDelete('visitor', 'vis_del_user', 'vis_del_pass')
    );
    const backupBtn = document.getElementById('system_db_backup_btn');
    if (backupBtn) {
        backupBtn.addEventListener('click', downloadDatabaseBackup);
    }
    document.getElementById('reload_access_log').addEventListener('click', loadAccessLogPreview);
    document.getElementById('login_logs_prev').addEventListener('click', () => {
        if (loginLogsPage > 1) {
            loginLogsPage--;
            loadLoginLogsPage();
        }
    });
    document.getElementById('login_logs_next').addEventListener('click', () => {
        loginLogsPage++;
        loadLoginLogsPage();
    });

    loadSettings();
});
