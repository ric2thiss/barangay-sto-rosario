/**
 * Profile page: update info + change password (api/profile/index.php).
 */

import { initSidebar } from '../shared/sidebar.js';

function getBaseUrl() {
  if (typeof window.BASE_URL === 'string' && window.BASE_URL) {
    return window.BASE_URL.replace(/\/$/, '');
  }
  const meta = document.querySelector('meta[name="base-url"]');
  if (meta?.content) {
    return meta.content.replace(/\/$/, '');
  }
  const { protocol, host, pathname } = window.location;
  const m = pathname.match(/^(\/[^/]+)\/admin\//);
  if (m) return `${protocol}//${host}${m[1]}`;
  return `${protocol}//${host}`;
}

function showMessage(message, type = 'success') {
  const container = document.getElementById('message-container');
  if (!container) return;
  const bgColor =
    type === 'success'
      ? 'bg-green-50 border-green-200 text-green-800'
      : 'bg-red-50 border-red-200 text-red-800';

  container.className = `mb-4 p-4 rounded-lg border ${bgColor}`;
  container.innerHTML = `<p class="font-medium">${escapeHtml(message)}</p>`;
  container.classList.remove('hidden');

  window.setTimeout(() => {
    container.classList.add('hidden');
  }, 5000);
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

async function apiProfile(body) {
  const base = getBaseUrl();
  const res = await fetch(`${base}/api/profile/index.php`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    credentials: 'same-origin',
  });

  let data = {};
  const ct = res.headers.get('content-type') || '';
  if (ct.includes('application/json')) {
    try {
      data = await res.json();
    } catch {
      data = {};
    }
  }

  if (!res.ok || data.success === false) {
    const msg = data.message || data.error || `Request failed (${res.status})`;
    throw new Error(msg);
  }

  return data;
}

function setHeaderDate() {
  const el = document.getElementById('profile-header-date');
  if (!el) return;
  const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  el.textContent = new Date().toLocaleDateString('en-US', opts);
}

function init() {
  initSidebar();
  setHeaderDate();

  const profileForm = document.getElementById('profile-form');
  if (profileForm && !profileForm.dataset.readonly) {
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = profileForm.querySelector('[type="submit"]');
      const orig = btn?.textContent;
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
      }

      try {
        await apiProfile({
          username: document.getElementById('username').value.trim(),
          full_name: document.getElementById('full_name').value.trim(),
          email: document.getElementById('email').value.trim(),
        });
        showMessage('Profile updated successfully!', 'success');
        window.setTimeout(() => window.location.reload(), 900);
      } catch (err) {
        showMessage(err.message || 'Failed to update profile', 'error');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = orig || 'Update Profile';
        }
      }
    });
  }

  const passwordForm = document.getElementById('password-form');
  if (passwordForm && !passwordForm.dataset.readonly) {
    passwordForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (newPassword !== confirmPassword) {
        showMessage('New passwords do not match', 'error');
        return;
      }
      if (newPassword.length < 6) {
        showMessage('Password must be at least 6 characters', 'error');
        return;
      }

      const btn = passwordForm.querySelector('[type="submit"]');
      const orig = btn?.textContent;
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Updating...';
      }

      try {
        await apiProfile({
          action: 'change_password',
          current_password: document.getElementById('current_password').value,
          new_password: newPassword,
        });
        showMessage('Password changed successfully!', 'success');
        passwordForm.reset();
      } catch (err) {
        showMessage(err.message || 'Failed to change password', 'error');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = orig || 'Change Password';
        }
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
