/* global bootstrap */
(function () {
  const table = document.getElementById("minutesTable");
  if (!table) return;

  const tbody = table.querySelector("tbody");

  const searchEl = document.getElementById("minutesSearch");
  const dateEl = document.getElementById("minutesDate");
  const btnClear = document.getElementById("btnClearMinutesFilters");

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

  let cache = [];

  function render(rows) {
    if (!rows.length) {
      tbody.innerHTML =
        `<tr><td colspan="5" class="text-center py-4 text-muted">No minutes found.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {
      const badge =
        r.status === "final" ? "success" :
        r.status === "extracted" ? "primary" :
        "secondary";

      return `
        <tr data-id="${escapeHtml(r.id)}">
          <td>
            <div class="fw-semibold">${escapeHtml(r.title)}</div>
            <div class="small text-muted">#${escapeHtml(r.id)}</div>
          </td>
          <td><span class="badge bg-${badge}">${escapeHtml(r.status)}</span></td>
          <td>${escapeHtml(r.file_count)}</td>
          <td class="small text-muted">${escapeHtml(fmtDate(r.created_at))}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary" data-action="view" data-id="${escapeHtml(r.id)}">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-outline-danger" data-action="delete" data-id="${escapeHtml(r.id)}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join("");
  }

  function applyFilters() {
    const q = (searchEl.value || "").trim().toLowerCase();
    const d = (dateEl.value || "").trim();

    const filtered = cache.filter(row => {
      const haystack = [
        row.title,
        row.status,
        row.id,
      ].map(x => String(x || "").toLowerCase()).join(" ");
      const okQ = !q || haystack.includes(q);
      const okD = !d || String(row.created_at || "").slice(0, 10) === d;
      return okQ && okD;
    });

    render(filtered);
  }

  async function load() {
    tbody.innerHTML =
      `<tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>`;

    try {
      const res = await fetch("backend/auth/minutes_list_api.php", { cache: "no-store" });

      const text = await res.text();

      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error("API did not return JSON. Response starts with: " + text.slice(0, 120));
      }

      if (!res.ok || !data.success)
        throw new Error(data.message || "Failed to load minutes.");

      cache = Array.isArray(data.rows) ? data.rows : [];
      applyFilters();

    } catch (e) {
      toastError(e?.message || "Failed to load minutes.");
      tbody.innerHTML =
        `<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load.</td></tr>`;
    }
  }

  window.BPSS_Minutes = window.BPSS_Minutes || {};
  window.BPSS_Minutes.refresh = load;
  window.BPSS_Minutes.openDetail = function (id) {
    window.BPSS_Minutes.activeId = id;
    window.BPSS_Minutes.loadDetail?.(id);
  };

  searchEl?.addEventListener("input", applyFilters);
  dateEl?.addEventListener("change", applyFilters);

  btnClear?.addEventListener("click", () => {
    searchEl.value = "";
    dateEl.value = "";
    applyFilters();
  });

  tbody.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;

    const action = btn.getAttribute("data-action");
    const id = btn.getAttribute("data-id");

    if (action === "view") window.BPSS_Minutes.openDetail(id);
    if (action === "delete") window.BPSS_Minutes.requestDelete?.(id);
  });

  load();
})();
