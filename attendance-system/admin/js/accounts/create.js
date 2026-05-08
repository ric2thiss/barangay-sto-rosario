/**
 * Create Account Page
 * Posts to /api/accounts/store.php then returns to accounts list.
 */

import { initSidebar } from '../shared/sidebar.js';
import { getBaseUrl } from '../shared/baseUrl.js';

function setCurrentDate() {
  const el = document.getElementById('current-date');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
}

function jsonOrText(res) {
  const contentType = res.headers.get('content-type') || '';
  if (contentType.includes('application/json')) return res.json();
  return res.text().then((t) => ({ success: res.ok, message: t }));
}

async function apiCreateAccount(payload) {
  const baseUrl = getBaseUrl();
  const res = await fetch(`${baseUrl}/api/accounts/store.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  });
  const data = await jsonOrText(res);
  if (!res.ok || data?.success === false) {
    throw new Error(data?.error || data?.message || 'Failed to create account');
  }
  return data;
}

async function init() {
  initSidebar();
  setCurrentDate();

  const form = document.getElementById('createAccountForm');
  const submitBtn = document.getElementById('submitBtn');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const payload = {
      username: document.getElementById('username').value.trim(),
      email: document.getElementById('email').value.trim(),
      full_name: document.getElementById('full_name').value.trim(),
      role: document.getElementById('role').value,
      password: document.getElementById('password').value,
      is_active: document.getElementById('is_active').checked ? 1 : 0,
    };

    const original = submitBtn?.textContent;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creating...';
    }

    try {
      await apiCreateAccount(payload);
      window.location.href = 'accounts.php';
    } catch (err) {
      alert(err.message || 'Failed to create account');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = original || 'Create Account';
      }
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

