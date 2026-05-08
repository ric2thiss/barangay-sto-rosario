/**
 * Loads today's activities (LGUMS + local), sets default tagging for biometric/API logs.
 */
function todayYmdManila() {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());
    const y = parts.find((p) => p.type === 'year')?.value;
    const m = parts.find((p) => p.type === 'month')?.value;
    const d = parts.find((p) => p.type === 'day')?.value;
    if (y && m && d) {
        return `${y}-${m}-${d}`;
    }
    return new Date().toISOString().slice(0, 10);
}

async function postActive(activityId, activeUrl) {
    const body = { activity_id: activityId === '' || activityId === null ? null : Number(activityId) };
    const res = await fetch(activeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
    if (!res.ok) {
        const text = await res.text();
        throw new Error(text || `HTTP ${res.status}`);
    }
    return res.json();
}

export async function initActivitySelect(optionsUrl, activeUrl) {
    const sel = document.getElementById('attendance-activity-select');
    if (!sel || !optionsUrl || !activeUrl) {
        return;
    }

    const date = todayYmdManila();
    let data;
    try {
        const res = await fetch(`${optionsUrl}?date=${encodeURIComponent(date)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        data = await res.json();
    } catch (e) {
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Could not load activities';
        sel.appendChild(opt);
        return;
    }

    if (!data.success) {
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = data.error || 'Could not load activities';
        sel.appendChild(opt);
        return;
    }

    const opts = data.options || [];
    sel.innerHTML = '';

    const none = document.createElement('option');
    none.value = '';
    none.textContent = 'No event';
    sel.appendChild(none);

    opts.forEach((o) => {
        const opt = document.createElement('option');
        opt.value = String(o.id);
        const tag = o.source === 'LGUMS' ? 'LGUMS' : 'Local';
        opt.textContent = `${o.name} (${tag})`;
        sel.appendChild(opt);
    });

    let selected = data.current_active_activity_id;
    if (selected == null && data.suggested_activity_id != null) {
        selected = data.suggested_activity_id;
        try {
            await postActive(selected, activeUrl);
        } catch {
            /* keep UI selection only */
        }
    }

    if (selected != null && String(selected) !== '') {
        sel.value = String(selected);
        if (sel.value !== String(selected)) {
            sel.value = '';
        }
    } else {
        sel.value = '';
    }

    sel.addEventListener('change', async () => {
        try {
            await postActive(sel.value, activeUrl);
        } catch (e) {
            console.warn('Could not save default activity', e);
        }
    });
}
