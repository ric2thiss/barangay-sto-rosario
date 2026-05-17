/* global bootstrap */
(function () {
  const table = document.getElementById("logsTable");
  if (!table) return;

  const tbody = table.querySelector("tbody");

  const searchEl = document.getElementById("logSearch");
  const dateEl = document.getElementById("logDate");
  const btnClear = document.getElementById("btnClearLogFilters");

  const metaEl = document.getElementById("logsMeta");
  const btnPrev = document.getElementById("btnLogsPrev");
  const btnNext = document.getElementById("btnLogsNext");

  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function fmtDate(dt) {
    if (!dt) return "";
    const d = new Date(String(dt).replace(" ", "T"));
    return isNaN(d) ? String(dt) : d.toLocaleString();
  }

  function entityLabel(row) {
    const t = row.entity_type ? String(row.entity_type) : "-";
    const id = row.entity_id ? String(row.entity_id) : "";
    return id ? `${t} #${id}` : t;
  }

  let page = 1;
  const limit = 25;
  let lastTotal = 0;
  let requestSeq = 0;

  function render(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No logs found.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(r.actor_label || "-")}</div>
        </td>

        <td>
          <span class="badge bg-secondary">${escapeHtml(r.action)}</span>
        </td>

        <td class="bpss-log-entity">
          ${escapeHtml(entityLabel(r))}
        </td>

        <td>
          <div>${escapeHtml(r.description || "")}</div>
        </td>

        <td class="small text-muted">
          ${escapeHtml(fmtDate(r.created_at))}
        </td>
      </tr>
    `).join("");
  }

  function debounce(fn, wait = 250) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  async function load() {
    const requestId = ++requestSeq;
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>`;

    const q = (searchEl.value || "").trim();
    const d = (dateEl.value || "").trim(); // YYYY-MM-DD

    const params = new URLSearchParams();
    params.set("page", String(page));
    params.set("limit", String(limit));
    if (q) params.set("q", q);
    if (d) params.set("date", d);

    try {
      const res = await fetch(`backend/auth/activity_logs_list_api.php?${params.toString()}`, { cache: "no-store" });
      const data = await res.json();
      if (requestId !== requestSeq) return;
      if (!data.success) throw new Error(data.message || "Failed to load logs.");

      lastTotal = Number(data.total || 0);
      render(Array.isArray(data.rows) ? data.rows : []);

      const from = lastTotal ? ((page - 1) * limit + 1) : 0;
      const to = Math.min(page * limit, lastTotal);
      metaEl.textContent = `Showing ${from}-${to} of ${lastTotal}`;

      btnPrev.disabled = page <= 1;
      btnNext.disabled = page * limit >= lastTotal;
    } catch (e) {
      if (requestId !== requestSeq) return;
      toastError(e?.message || "Failed to load logs.");
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load.</td></tr>`;
      metaEl.textContent = "—";
    }
  }

  const loadDebounced = debounce(() => { page = 1; load(); }, 250);

  searchEl?.addEventListener("input", loadDebounced);
  dateEl?.addEventListener("change", () => { page = 1; load(); });

  btnClear?.addEventListener("click", () => {
    searchEl.value = "";
    dateEl.value = "";
    page = 1;
    load();
  });

  btnPrev?.addEventListener("click", () => {
    if (page > 1) page--;
    load();
  });

  btnNext?.addEventListener("click", () => {
    page++;
    load();
  });

  load();
})();
