// ── SHOW / HIDE PASSWORD ──────────────────────────────────────────────────
const pwInput = document.getElementById('password');
const toggleBtn = document.getElementById('togglePw');
const showCb = document.getElementById('showPassword');

function setVisibility(show) {
    pwInput.type = show ? 'text' : 'password';
    toggleBtn.textContent = show ? '🙈' : '👁️';
    if (showCb.checked !== show) showCb.checked = show;
}

if (toggleBtn && showCb && pwInput) {
    toggleBtn.addEventListener('click', () => setVisibility(pwInput.type === 'password'));
    showCb.addEventListener('change', () => setVisibility(showCb.checked));
}

// ── COUNTDOWN TIMER ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    let remaining = window.remainingLockTime || 0;
    if (remaining <= 0) return;

    const countEl = document.getElementById('countdown');
    const loginBtn = document.getElementById('loginButton');
    const userIn = document.getElementById('username');
    const passIn = document.getElementById('password');
    const cbIn = document.getElementById('showPassword');

    if (loginBtn && userIn && passIn && cbIn) {
        loginBtn.disabled = userIn.disabled = passIn.disabled = cbIn.disabled = true;
    }

    const tick = setInterval(() => {
        remaining--;
        if (countEl) countEl.textContent = remaining;

        if (remaining <= 0) {
            clearInterval(tick);
            if (loginBtn && userIn && passIn && cbIn) {
                loginBtn.disabled = userIn.disabled = passIn.disabled = cbIn.disabled = false;
            }

            const errBox = document.getElementById('loginError');
            if (errBox) errBox.remove();
        }
    }, 1000);
});

// ── XSS PREVENTION ───────────────────────────────────────────────────────
document.querySelectorAll('input[type="text"]').forEach(f =>
    f.addEventListener('input', function() {
        this.value = this.value.replace(/[<>]/g, '');
    })
);
