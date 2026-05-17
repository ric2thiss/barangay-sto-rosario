document.addEventListener("DOMContentLoaded", () => {
  const table = document.getElementById("officialsTable");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const statusFilter = document.getElementById("statusFilter");
  const searchBox = document.getElementById("searchBox");
  const btnClear = document.getElementById("btnClearFilters");
  let requestSeq = 0;

  const esc = (s) => String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

  async function loadOfficials() {
    const requestId = ++requestSeq;
    const params = new URLSearchParams();
    if (statusFilter?.value !== "") params.set("is_active", statusFilter.value);
    if (searchBox?.value.trim()) params.set("q", searchBox.value.trim());

    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>`;

    try {
      const res = await fetch(`backend/auth/get_officials_table_api.php?${params.toString()}`);

      // IMPORTANT: read text first to avoid "Unexpected token <"
      const ct = res.headers.get("content-type") || "";
      const text = await res.text();

      if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`);
      if (!ct.includes("application/json")) throw new Error(text.slice(0, 200));

      const data = JSON.parse(text);
      if (requestId !== requestSeq) return;
      if (!data.success) throw new Error(data.message || "Failed to load.");

      const rows = data.officials || [];
      if (!rows.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No officials found.</td></tr>`;
        return;
      }

      tbody.innerHTML = rows.map(o => {
        const term = `${esc(o.term_start || "")}${o.term_end ? " - " + esc(o.term_end) : ""}` || "—";

        const sigHtml = o.signature_url
          ? `
            <div class="text-center">
              ${o.signature_name ? `<div class="small fw-semibold">${esc(o.signature_name)}</div>` : ``}
              <img class="sig-thumb" src="${esc(o.signature_url)}" alt="signature">
            </div>
          `
          : `<span class="text-muted">—</span>`;

        const contactLine = o.contact_mobile
          ? `<div class="text-muted small">Ref: ${esc(o.contact_mobile)}</div>`
          : `<div class="text-muted small">Ref: —</div>`;

        return `
          <tr>
            <td>
              <div class="fw-semibold">${esc(o.full_name)}</div>
              ${contactLine}
            </td>
            <td>
              <div class="fw-semibold">${esc(o.official_title || "")}</div>
              <div class="text-muted small">${esc(o.committee || "—")}</div>
            </td>
            <td class="small">${term}</td>
            <td>${sigHtml}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1 btn-edit"
                      data-official="${esc(JSON.stringify(o))}">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger btn-delete"
                      data-id="${esc(o.id)}"
                      data-name="${esc(o.full_name)}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        `;
      }).join("");

    } catch (e) {
      if (requestId !== requestSeq) return;
      tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">
        Error loading officials. Check Console/Network for details.
      </td></tr>`;
    }
  }

  window.BPSS_loadOfficials = loadOfficials;

  statusFilter?.addEventListener("change", loadOfficials);
  searchBox?.addEventListener("input", () => {
    clearTimeout(window.__bpssOfficialsT);
    window.__bpssOfficialsT = setTimeout(loadOfficials, 250);
  });

  btnClear?.addEventListener("click", () => {
    if (statusFilter) statusFilter.value = "";
    if (searchBox) searchBox.value = "";
    loadOfficials();
  });

  loadOfficials();
});
