document.addEventListener("DOMContentLoaded", function () {
  const GET_URL    = window.BPSS_ACCOUNTS_GET_API_URL;
  const CREATE_URL = window.BPSS_ACCOUNTS_CREATE_API_URL;
  const UPDATE_URL = window.BPSS_ACCOUNTS_UPDATE_API_URL;
  const DELETE_URL = window.BPSS_ACCOUNTS_DELETE_API_URL;
  const RESET_URL  = window.BPSS_ACCOUNTS_RESET_PW_API_URL;
  const TOGGLE_URL = window.BPSS_ACCOUNTS_TOGGLE_API_URL;
  const SELF_ID    = window.BPSS_CURRENT_USER_ID;

  const tbody       = document.querySelector("#accountsTable tbody");
  const searchInput = document.getElementById("accountSearch");
  const roleFilter  = document.getElementById("accountRoleFilter");
  const stFilter    = document.getElementById("accountStatusFilter");
  const btnClear    = document.getElementById("btnClearAccountFilters");
  const btnAdd      = document.getElementById("btnAddAccount");
  let requestSeq    = 0;

  // Main modal
  const accountModal = new bootstrap.Modal(document.getElementById("accountModal"));
  const form         = document.getElementById("accountForm");
  const modalTitle   = document.getElementById("accountModalTitle");
  const msgEl        = document.getElementById("accountMsg");
  const idInput      = document.getElementById("accountId");
  const pwSection    = document.getElementById("accountPasswordSection");
  const pwNote       = document.getElementById("accountPasswordNote");
  const pwReq        = document.getElementById("accountPwRequired");
  const confReq      = document.getElementById("accountConfPwRequired");

  // Reset PW modal
  const resetPwModal = new bootstrap.Modal(document.getElementById("resetPwModal"));
  const resetForm    = document.getElementById("resetPwForm");
  const resetMsg     = document.getElementById("resetPwMsg");
  const resetIdInput = document.getElementById("resetPwId");
  const resetNameEl  = document.getElementById("resetPwName");

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;").replace(/</g, "&lt;")
      .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function showMsg(el, msg, type = "danger") {
    el.innerHTML = `<div class="alert alert-${type} alert-sm py-1 px-2 mb-0 small">${esc(msg)}</div>`;
  }
  function clearMsg(el) { el.innerHTML = ""; }

  // ── Load table ────────────────────────────────────────────────────────────

  async function loadAccounts() {
    const requestId = ++requestSeq;
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>`;
    const params = new URLSearchParams();
    const q  = searchInput.value.trim();
    const r  = roleFilter.value;
    const st = stFilter.value;
    if (q)  params.set("q",      q);
    if (r)  params.set("role",   r);
    if (st) params.set("status", st);

    try {
      const resp = await fetch(`${GET_URL}?${params}`);
      const json = await resp.json();
      if (requestId !== requestSeq) return;
      if (!json.success) { tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load.</td></tr>`; return; }

      const rows = json.data || [];
      if (!rows.length) { tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No accounts found.</td></tr>`; return; }

      tbody.innerHTML = rows.map(u => {
        const isSelf   = parseInt(u.id, 10) === SELF_ID;
        const isActive = parseInt(u.is_active, 10) === 1;
        const roleBadge = u.role === "admin"
          ? `<span class="badge bg-danger">Admin</span>`
          : `<span class="badge bg-secondary">Staff</span>`;
        const statusBadge = isActive
          ? `<span class="badge bg-success">Active</span>`
          : `<span class="badge bg-secondary">Disabled</span>`;
        const toggleLabel = isActive ? "Disable" : "Enable";
        const toggleIcon  = isActive ? "bi-person-dash" : "bi-person-check";
        const toggleClass = isActive ? "btn-outline-warning" : "btn-outline-success";

        return `
          <tr data-id="${esc(u.id)}" data-active="${u.is_active}">
            <td class="fw-semibold">${esc(u.full_name)}${isSelf ? ' <span class="badge bg-primary bg-opacity-75 ms-1" style="font-size:.65rem;">You</span>' : ""}</td>
            <td class="text-muted small">${esc(u.username)}</td>
            <td>${roleBadge}</td>
            <td class="small text-muted">${esc(u.email || "—")}</td>
            <td class="small text-muted">${esc(u.mobile || "—")}</td>
            <td>${statusBadge}</td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <button class="btn btn-sm btn-outline-primary btn-edit-account"
                        data-id="${esc(u.id)}" title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary btn-reset-pw"
                        data-id="${esc(u.id)}" data-name="${esc(u.full_name)}" title="Reset Password">
                  <i class="bi bi-key"></i>
                </button>
                <button class="btn btn-sm ${toggleClass} btn-toggle-status"
                        data-id="${esc(u.id)}" title="${toggleLabel}"
                        ${isSelf ? "disabled" : ""}>
                  <i class="bi ${toggleIcon}"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete-account"
                        data-id="${esc(u.id)}" data-name="${esc(u.full_name)}" title="Delete"
                        ${isSelf ? "disabled" : ""}>
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join("");

    } catch {
      if (requestId !== requestSeq) return;
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error loading data.</td></tr>`;
    }
  }

  // ── Open create modal ─────────────────────────────────────────────────────

  function openCreateModal() {
    form.reset();
    clearMsg(msgEl);
    idInput.value          = "";
    modalTitle.textContent = "Add Account";
    pwNote.textContent     = "Set a password for this account.";
    pwReq.style.display    = "";
    confReq.style.display  = "";
    document.getElementById("accountPassword").required        = true;
    document.getElementById("accountConfirmPassword").required = true;
    accountModal.show();
  }

  // ── Open edit modal ───────────────────────────────────────────────────────

  function openEditModal(tr) {
    const id = tr.dataset.id;
    const cells = tr.querySelectorAll("td");

    form.reset();
    clearMsg(msgEl);
    modalTitle.textContent = "Edit Account";
    idInput.value          = id;
    pwNote.textContent     = "Leave password fields blank to keep current password.";
    pwReq.style.display    = "none";
    confReq.style.display  = "none";
    document.getElementById("accountPassword").required        = false;
    document.getElementById("accountConfirmPassword").required = false;

    // Prefill from row's data attributes (we'll fetch live for accuracy)
    fetch(`${GET_URL}?q=${encodeURIComponent(tr.querySelector("td:first-child").textContent.replace("You", "").trim())}`)
      .then(r => r.json())
      .then(json => {
        const user = (json.data || []).find(u => String(u.id) === String(id));
        if (!user) return;
        document.getElementById("accountFullName").value = user.full_name || "";
        document.getElementById("accountUsername").value = user.username  || "";
        document.getElementById("accountRole").value     = user.role      || "staff";
        document.getElementById("accountEmail").value    = user.email     || "";
        document.getElementById("accountMobile").value   = user.mobile    || "";
      });

    accountModal.show();
  }

  // ── Save handler ──────────────────────────────────────────────────────────

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    clearMsg(msgEl);

    const id       = idInput.value;
    const isEdit   = id !== "";
    const payload  = {
      id:               isEdit ? parseInt(id, 10) : undefined,
      full_name:        document.getElementById("accountFullName").value.trim(),
      username:         document.getElementById("accountUsername").value.trim(),
      role:             document.getElementById("accountRole").value,
      email:            document.getElementById("accountEmail").value.trim(),
      mobile:           document.getElementById("accountMobile").value.trim(),
      password:         document.getElementById("accountPassword").value,
      confirm_password: document.getElementById("accountConfirmPassword").value,
    };

    const btn = document.getElementById("btnSaveAccount");
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving...`;

    try {
      const url  = isEdit ? UPDATE_URL : CREATE_URL;
      const resp = await fetch(url, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload),
      });
      const json = await resp.json();

      if (!json.success) { showMsg(msgEl, json.message || "Failed to save."); return; }

      accountModal.hide();
      window.BPSS_showToast?.(json.message || "Saved.", "success");
      loadAccounts();
    } catch {
      window.BPSS_showToast?.("Network error.", "danger");
    } finally {
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }
  });

  // ── Reset password ────────────────────────────────────────────────────────

  function openResetPwModal(id, name) {
    resetForm.reset();
    clearMsg(resetMsg);
    resetIdInput.value   = id;
    resetNameEl.textContent = `Resetting password for: ${name}`;
    resetPwModal.show();
  }

  resetForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    clearMsg(resetMsg);

    const payload = {
      id:               parseInt(resetIdInput.value, 10),
      password:         document.getElementById("resetPwNew").value,
      confirm_password: document.getElementById("resetPwConfirm").value,
    };

    const btn = document.getElementById("btnResetPw");
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Resetting...`;

    try {
      const resp = await fetch(RESET_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify(payload),
      });
      const json = await resp.json();
      if (!json.success) { showMsg(resetMsg, json.message || "Failed."); return; }
      resetPwModal.hide();
      window.BPSS_showToast?.(json.message || "Password reset.", "success");
    } catch {
      window.BPSS_showToast?.("Network error.", "danger");
    } finally {
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }
  });

  // ── Toggle status ─────────────────────────────────────────────────────────

  async function toggleStatus(id) {
    try {
      const resp = await fetch(TOGGLE_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ id: parseInt(id, 10) }),
      });
      const json = await resp.json();
      if (!json.success) { window.BPSS_showToast?.(json.message || "Failed.", "danger"); return; }
      window.BPSS_showToast?.(json.message || "Done.", "success");
      loadAccounts();
    } catch {
      window.BPSS_showToast?.("Network error.", "danger");
    }
  }

  // ── Delete ────────────────────────────────────────────────────────────────

  function deleteAccount(id, name) {
    window.BPSS_confirm?.(`Delete account "<strong>${esc(name)}</strong>"? This cannot be undone.`, async () => {
      try {
        const resp = await fetch(DELETE_URL, {
          method:  "POST",
          headers: { "Content-Type": "application/json" },
          body:    JSON.stringify({ id: parseInt(id, 10) }),
        });
        const json = await resp.json();
        if (!json.success) { window.BPSS_showToast?.(json.message || "Failed.", "danger"); return; }
        window.BPSS_showToast?.(json.message || "Deleted.", "success");
        loadAccounts();
      } catch {
        window.BPSS_showToast?.("Network error.", "danger");
      }
    });
  }

  // ── Event delegation ──────────────────────────────────────────────────────

  tbody.addEventListener("click", function (e) {
    const tr = e.target.closest("tr");
    if (!tr) return;

    if (e.target.closest(".btn-edit-account")) {
      openEditModal(tr);
    } else if (e.target.closest(".btn-reset-pw")) {
      const btn = e.target.closest(".btn-reset-pw");
      openResetPwModal(btn.dataset.id, btn.dataset.name);
    } else if (e.target.closest(".btn-toggle-status")) {
      const btn = e.target.closest(".btn-toggle-status");
      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
      toggleStatus(btn.dataset.id).finally(() => { btn.disabled = false; btn.innerHTML = origHtml; });
    } else if (e.target.closest(".btn-delete-account")) {
      const btn = e.target.closest(".btn-delete-account");
      deleteAccount(btn.dataset.id, btn.dataset.name);
    }
  });

  btnAdd.addEventListener("click", openCreateModal);

  // Filters
  let searchTimer;
  searchInput.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadAccounts, 350);
  });
  roleFilter.addEventListener("change", loadAccounts);
  stFilter.addEventListener("change",   loadAccounts);
  btnClear.addEventListener("click",    () => {
    searchInput.value = ""; roleFilter.value = ""; stFilter.value = "";
    loadAccounts();
  });

  loadAccounts();
});
