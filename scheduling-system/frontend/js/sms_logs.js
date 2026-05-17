document.addEventListener("DOMContentLoaded", function () {
  const LOGS_URL   = window.BPSS_SMS_LOGS_API_URL;
  const RESEND_URL = window.BPSS_SMS_RESEND_SINGLE_API_URL;
  const SELF_ID    = window.BPSS_CURRENT_USER_ID;
  const isAdmin    = window.BPSS_USER_ROLE === "admin";
  const colCount   = isAdmin ? 8 : 7;

  // Hide "Sent By" column for staff
  if (!isAdmin) {
    document.querySelectorAll(".sms-col-sentby").forEach(el => {
      el.style.display = "none";
    });
  }

  // Preview modal
  const previewModal   = new bootstrap.Modal(document.getElementById("smsPreviewModal"));
  const previewBody    = document.getElementById("smsPreviewBody");
  const previewMeta    = document.getElementById("smsPreviewMeta");
  const previewResend  = document.getElementById("smsPreviewResend");
  let   previewOutboxId = null;

  const tbody       = document.querySelector("#smsTable tbody");
  const metaEl      = document.getElementById("smsMeta");
  const btnPrev     = document.getElementById("btnSmsPrev");
  const btnNext     = document.getElementById("btnSmsNext");
  const dateFrom    = document.getElementById("smsDateFrom");
  const dateTo      = document.getElementById("smsDateTo");
  const statusSel   = document.getElementById("smsStatus");
  const searchInput = document.getElementById("smsSearch");
  const btnApply    = document.getElementById("btnApplySmsFilters");
  const btnClear    = document.getElementById("btnClearSmsFilters");

  let currentPage = 1;
  let requestSeq = 0;

  function pad2(value) {
    return String(value).padStart(2, "0");
  }

  function todayStr() {
    const now = new Date();
    return `${now.getFullYear()}-${pad2(now.getMonth() + 1)}-${pad2(now.getDate())}`;
  }

  // Default date range to today
  dateFrom.value = todayStr();
  dateTo.value   = todayStr();

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function fmtDateTime(str) {
    if (!str) return "—";
    const d = new Date(str);
    if (isNaN(d)) return esc(str);
    return new Intl.DateTimeFormat("en-PH", {
      month: "short", day: "2-digit", year: "numeric",
      hour: "numeric", minute: "2-digit"
    }).format(d);
  }

  function statusBadge(status) {
    const map = {
      sent:      "success",
      failed:    "danger",
      queued:    "warning",
      cancelled: "secondary",
    };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color}">${esc(status)}</span>`;
  }

  function typeBadge(type) {
    if (type === "auto") return `<span class="badge bg-info text-dark">auto</span>`;
    return `<span class="badge bg-light text-dark border">now</span>`;
  }

  function truncate(str, len = 80) {
    const s = String(str ?? "");
    return s.length > len ? s.slice(0, len) + "…" : s;
  }

  function sentByCell(r) {
    if (!isAdmin) return "";
    const isSelf = parseInt(r.sent_by_id, 10) === SELF_ID;
    const label  = isSelf
      ? `<span class="badge bg-primary bg-opacity-75" style="font-size:.65rem;">You</span>`
      : esc(r.sent_by_name || "—");
    return `<td class="sms-col-sentby small">${label}</td>`;
  }

  async function loadLogs(page = 1) {
    const requestId = ++requestSeq;
    currentPage = page;
    tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center py-4 text-muted">Loading...</td></tr>`;

    const params  = new URLSearchParams({ page });
    const from    = dateFrom.value.trim();
    const to      = dateTo.value.trim();
    const status  = statusSel.value;
    const q       = searchInput.value.trim();

    if (from)   params.set("date_from", from);
    if (to)     params.set("date_to",   to);
    if (status) params.set("status",    status);
    if (q)      params.set("q",         q);

    try {
      const resp = await fetch(`${LOGS_URL}?${params.toString()}`);
      const json = await resp.json();
      if (requestId !== requestSeq) return;

      if (!json.success) {
        tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center py-4 text-danger">${esc(json.message || "Failed to load.")}</td></tr>`;
        return;
      }

      const rows  = json.data || [];
      const total = json.total || 0;
      const pages = json.pages || 1;

      metaEl.textContent = total
        ? `Showing page ${page} of ${pages} — ${total} record(s)`
        : "No records found.";

      btnPrev.disabled = page <= 1;
      btnNext.disabled = page >= pages;

      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center py-4 text-muted">No SMS records for this filter.</td></tr>`;
        return;
      }

      // Store row data for modal access (keyed by outbox id)
      window._smsRowData = {};
      rows.forEach(r => { window._smsRowData[r.id] = r; });

      tbody.innerHTML = rows.map(r => `
        <tr>
          <td class="fw-semibold">${esc(r.to_mobile)}</td>
          ${sentByCell(r)}
          <td class="small text-muted">${r.event_title ? esc(r.event_title) : '<span class="text-muted fst-italic">—</span>'}</td>
          <td class="small">
            <span class="btn-preview-msg text-truncate d-inline-block"
                  style="max-width:200px; cursor:pointer; text-decoration:underline dotted;"
                  data-id="${esc(r.id)}"
                  title="Click to preview full message">
              ${esc(truncate(r.message))}
            </span>
          </td>
          <td>${typeBadge(r.send_type)}</td>
          <td>${statusBadge(r.status)}</td>
          <td class="small text-muted">${fmtDateTime(r.queued_at)}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary btn-resend-sms"
                    data-id="${esc(r.id)}"
                    title="Resend this SMS">
              <i class="bi bi-send"></i>
            </button>
          </td>
        </tr>
      `).join("");

    } catch (err) {
      if (requestId !== requestSeq) return;
      tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center py-4 text-danger">Error loading data.</td></tr>`;
    }
  }

  // Message preview handler
  tbody.addEventListener("click", function (e) {
    const span = e.target.closest(".btn-preview-msg");
    if (!span) return;

    const id  = span.dataset.id;
    const row = window._smsRowData?.[id];
    if (!row) return;

    previewOutboxId = id;

    // Build meta badges
    const isSelf = parseInt(row.sent_by_id, 10) === SELF_ID;
    const sentByBadge = isAdmin
      ? (isSelf
          ? `<span class="badge bg-primary bg-opacity-75">You</span>`
          : `<span class="badge bg-light text-dark border">${esc(row.sent_by_name || "Unknown")}</span>`)
      : "";

    previewMeta.innerHTML = [
      `<span class="badge bg-secondary">${esc(row.to_mobile)}</span>`,
      row.event_title ? `<span class="badge bg-light text-dark border">${esc(row.event_title)}</span>` : "",
      sentByBadge,
      typeBadge(row.send_type),
      statusBadge(row.status),
      `<span class="small text-muted ms-1">${fmtDateTime(row.queued_at)}</span>`,
    ].filter(Boolean).join(" ");

    // Show full message with proper line breaks
    previewBody.textContent = row.message;

    previewModal.show();
  });

  // Resend handler
  tbody.addEventListener("click", async function (e) {
    const btn = e.target.closest(".btn-resend-sms");
    if (!btn) return;

    const outboxId = btn.dataset.id;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

    try {
      const resp = await fetch(RESEND_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ outbox_id: parseInt(outboxId, 10) }),
      });
      const json = await resp.json();

      if (json.success) {
        window.BPSS_showToast?.("SMS resent successfully.", "success");
        loadLogs(currentPage);
      } else {
        window.BPSS_showToast?.(json.message || "Failed to resend SMS.", "danger");
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-send"></i>`;
      }
    } catch {
      window.BPSS_showToast?.("Network error.", "danger");
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-send"></i>`;
    }
  });

  // Filter events
  btnApply.addEventListener("click", () => loadLogs(1));

  // Allow pressing Enter in search to apply
  searchInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadLogs(1);
  });

  statusSel.addEventListener("change", () => loadLogs(1));
  dateFrom.addEventListener("change", () => loadLogs(1));
  dateTo.addEventListener("change", () => loadLogs(1));

  btnClear.addEventListener("click", () => {
    dateFrom.value    = todayStr();
    dateTo.value      = todayStr();
    statusSel.value   = "";
    searchInput.value = "";
    loadLogs(1);
  });

  btnPrev.addEventListener("click", () => { if (currentPage > 1) loadLogs(currentPage - 1); });
  btnNext.addEventListener("click", () => loadLogs(currentPage + 1));

  // Modal resend button
  previewResend.addEventListener("click", async function () {
    if (!previewOutboxId) return;
    previewResend.disabled = true;
    previewResend.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Sending...`;

    try {
      const resp = await fetch(RESEND_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ outbox_id: parseInt(previewOutboxId, 10) }),
      });
      const json = await resp.json();

      previewModal.hide();
      if (json.success) {
        window.BPSS_showToast?.("SMS resent successfully.", "success");
        loadLogs(currentPage);
      } else {
        window.BPSS_showToast?.(json.message || "Failed to resend SMS.", "danger");
      }
    } catch {
      previewModal.hide();
      window.BPSS_showToast?.("Network error.", "danger");
    } finally {
      previewResend.disabled = false;
      previewResend.innerHTML = `<i class="bi bi-send me-1"></i>Resend`;
    }
  });

  // Initial load
  loadLogs(1);
});
