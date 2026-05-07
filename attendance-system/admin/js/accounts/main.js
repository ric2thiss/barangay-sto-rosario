/**
 * Accounts Page Main Entry Point
 * - Filter modal open/close
 * - Edit account modal submit (update)
 * - Lock/Unlock + Delete actions
 * - Add New Account button routes to create page
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

function qs(id) {
  return document.getElementById(id);
}

function show(el) {
  if (el) el.classList.remove('hidden');
}

function hide(el) {
  if (el) el.classList.add('hidden');
}

function jsonOrText(res) {
  const contentType = res.headers.get('content-type') || '';
  if (contentType.includes('application/json')) return res.json();
  return res.text().then((t) => ({ success: res.ok, message: t }));
}

async function apiRequest(path, { method = 'GET', body } = {}) {
  const baseUrl = getBaseUrl();
  const res = await fetch(`${baseUrl}${path}`, {
    method,
    headers: body ? { 'Content-Type': 'application/json' } : undefined,
    body: body ? JSON.stringify(body) : undefined,
    credentials: 'same-origin',
  });
  const payload = await jsonOrText(res);
  if (!res.ok || payload?.success === false) {
    const msg = payload?.error || payload?.message || 'Request failed';
    throw new Error(msg);
  }
  return payload;
}

function initFilterModal() {
  const filterButton = qs('filterButton');
  const filterModal = qs('filterModal');
  const closeFilterModal = qs('closeFilterModal');
  const backdrop = qs('filterModalBackdrop');

  if (!filterButton || !filterModal) return;

  filterButton.addEventListener('click', () => show(filterModal));
  closeFilterModal?.addEventListener('click', () => hide(filterModal));
  backdrop?.addEventListener('click', () => hide(filterModal));

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') hide(filterModal);
  });
}

function initAddAccountButton() {
  const addBtn = qs('addAccountBtn');
  if (!addBtn) return;
  // Keep href as fallback; JS just ensures consistent route.
  addBtn.addEventListener('click', (e) => {
    // If it is already an <a>, let it navigate naturally.
    // This handler mainly protects if markup changes back to <button>.
    const tag = addBtn.tagName.toLowerCase();
    if (tag === 'a') return;
    e.preventDefault();
    window.location.href = 'account-create.php';
  });
}

function initEditModal() {
  const modal = qs('accountModal');
  const closeBtn = qs('closeAccountModal');
  const cancelBtn = qs('cancelAccountBtn');
  const form = qs('accountForm');
  const title = qs('account-modal-title');
  const password = qs('password');
  const passwordHint = qs('passwordHint');

  if (!modal || !form) return;

  function openEdit(data) {
    title.textContent = 'Edit Account';
    qs('accountId').value = data.id || '';
    qs('username').value = data.username || '';
    qs('email').value = data.email || '';
    qs('full_name').value = data.fullName || '';
    qs('role').value = data.role || 'staff';
    // is_active checkbox: checked means active
    qs('is_active').checked = String(data.isActive) === '1' || data.isActive === 1 || data.isActive === true;

    // In edit mode, password is optional
    password.required = false;
    password.value = '';
    if (passwordHint) passwordHint.classList.remove('hidden');

    show(modal);
  }

  function close() {
    hide(modal);
  }

  closeBtn?.addEventListener('click', close);
  cancelBtn?.addEventListener('click', close);

  // Click outside to close
  modal.addEventListener('click', (e) => {
    if (e.target === modal) close();
  });

  // Wire edit buttons
  document.querySelectorAll('.editBtn').forEach((btn) => {
    btn.addEventListener('click', () => {
      openEdit({
        id: btn.dataset.id,
        username: btn.dataset.username,
        email: btn.dataset.email,
        fullName: btn.dataset.fullName,
        role: btn.dataset.role,
        isActive: btn.dataset.isActive,
      });
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = qs('accountId').value;
    if (!id) {
      // For safety: creation happens on the separate page.
      window.location.href = 'account-create.php';
      return;
    }

    const payload = {
      username: qs('username').value.trim(),
      email: qs('email').value.trim(),
      full_name: qs('full_name').value.trim(),
      role: qs('role').value,
      is_active: qs('is_active').checked ? 1 : 0,
    };

    const pwd = password.value;
    if (pwd && pwd.trim().length > 0) payload.password = pwd;

    const submitBtn = qs('submitAccountBtn');
    const original = submitBtn?.textContent;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
    }

    try {
      await apiRequest(`/api/accounts/update.php?id=${encodeURIComponent(id)}`, {
        method: 'POST',
        body: payload,
      });
      close();
      // Refresh current view (preserve querystring)
      window.location.reload();
    } catch (err) {
      alert(err.message || 'Failed to update account');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = original || 'Save Account';
      }
    }
  });
}

function initDeleteModal() {
  const modal = qs('deleteAccountModal');
  const cancelBtn = qs('cancelDeleteBtn');
  const confirmBtn = qs('confirmDeleteBtn');
  const usernameEl = qs('delete-account-username');

  if (!modal || !confirmBtn) return;

  let pendingId = null;

  function open(id, username) {
    pendingId = id;
    if (usernameEl) usernameEl.textContent = username || '';
    show(modal);
  }

  function close() {
    pendingId = null;
    hide(modal);
  }

  cancelBtn?.addEventListener('click', close);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) close();
  });

  document.querySelectorAll('.deleteBtn').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
      open(btn.dataset.id, btn.dataset.username);
    });
  });

  confirmBtn.addEventListener('click', async () => {
    if (!pendingId) return;
    confirmBtn.disabled = true;
    const original = confirmBtn.textContent;
    confirmBtn.textContent = 'Deleting...';

    try {
      await apiRequest(`/api/accounts/delete.php?id=${encodeURIComponent(pendingId)}`, {
        method: 'POST',
      });
      close();
      window.location.reload();
    } catch (err) {
      alert(err.message || 'Failed to delete account');
    } finally {
      confirmBtn.disabled = false;
      confirmBtn.textContent = original || 'Delete Account';
    }
  });
}

function initLockButtons() {
  document.querySelectorAll('.lockBtn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (btn.disabled) return;

      const id = btn.dataset.id;
      const isActive = btn.dataset.isActive === '1' || btn.dataset.isActive === 1;
      const lock = isActive ? 1 : 0;

      btn.disabled = true;
      const original = btn.textContent;
      btn.textContent = isActive ? 'Locking...' : 'Unlocking...';

      try {
        await apiRequest(`/api/accounts/lock.php?id=${encodeURIComponent(id)}&lock=${lock}`, {
          method: 'POST',
        });
        window.location.reload();
      } catch (err) {
        alert(err.message || 'Failed to update status');
        btn.disabled = false;
        btn.textContent = original;
      }
    });
  });
}

function initOfficialPortalButtons() {
  document.querySelectorAll('.officialPortalBtn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (btn.disabled) return;

      const id = btn.dataset.id;
      const allow = btn.dataset.allow === '1' || btn.dataset.allow === 1;
      const username = btn.dataset.username || '';

      if (!allow) {
        const ok = window.confirm(
          `Revoke system access for ${username}? They will not be able to use the portal while Inactive.`
        );
        if (!ok) return;
      }

      btn.disabled = true;
      const original = btn.innerHTML;

      try {
        await apiRequest(
          `/api/accounts/official-access.php?id=${encodeURIComponent(id)}&allow=${allow ? 1 : 0}`,
          { method: 'POST' }
        );
        window.location.reload();
      } catch (err) {
        alert(err.message || 'Failed to update access');
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });
}

async function init() {
  initSidebar();
  setCurrentDate();
  initFilterModal();
  initAddAccountButton();
  initEditModal();
  initDeleteModal();
  initLockButtons();
  initOfficialPortalButtons();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

