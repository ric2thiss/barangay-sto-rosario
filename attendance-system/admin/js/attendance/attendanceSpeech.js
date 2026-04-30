/**
 * Text-to-speech for attendance toasts (Web Speech API).
 * Success: resolved toast title + employee name (no window line — avoids duplicating UI detail).
 * Error: resolved toast title only (avoids repeating title text that also appears in the message).
 * Each new toast cancels any in-progress speech so alerts do not overlap.
 */

const STORAGE_KEY = 'attendanceSpeechMuted';

export function isSpeechMuted() {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * @param {boolean} muted
 */
export function setSpeechMuted(muted) {
    try {
        if (muted) {
            localStorage.setItem(STORAGE_KEY, '1');
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch {
        /* ignore quota / private mode */
    }
    syncSpeechToggleCheckbox();
}

function canSpeak() {
    return typeof window !== 'undefined'
        && window.speechSynthesis
        && typeof window.SpeechSynthesisUtterance !== 'undefined';
}

/**
 * @param {{ type: 'success' | 'error', title: string, employeeName?: string }} params
 */
export function speakAttendanceToast(params) {
    if (!canSpeak() || isSpeechMuted() || !params) {
        return;
    }

    const title = typeof params.title === 'string' ? params.title.trim() : '';
    let text = '';

    if (params.type === 'success') {
        const name = typeof params.employeeName === 'string' ? params.employeeName.trim() : '';
        if (title && name) {
            text = `${title} ${name}`;
        } else {
            text = title || name;
        }
    } else {
        text = title;
    }

    if (!text) {
        return;
    }

    try {
        window.speechSynthesis.cancel();
    } catch {
        /* ignore */
    }

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = document.documentElement.lang || 'en';

    try {
        window.speechSynthesis.speak(utterance);
    } catch {
        /* ignore */
    }
}

function syncSpeechToggleCheckbox() {
    const el = document.getElementById('attendance-speech-toggle');
    if (el && el instanceof HTMLInputElement) {
        el.checked = !isSpeechMuted();
    }
}

/**
 * Bind the Realtime Insight voice toggle (if present).
 */
export function initAttendanceSpeechControls() {
    const el = document.getElementById('attendance-speech-toggle');
    if (el && el instanceof HTMLInputElement) {
        el.checked = !isSpeechMuted();
        el.addEventListener('change', () => {
            setSpeechMuted(!el.checked);
        });
    }

    window.addEventListener('storage', (e) => {
        if (e.key === STORAGE_KEY) {
            syncSpeechToggleCheckbox();
            if (isSpeechMuted() && window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }
    });
}
