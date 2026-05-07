document.addEventListener("DOMContentLoaded", function () {

  const table = document.getElementById("contactsTable");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const groupFilter = document.getElementById("groupFilter");
  const searchBox = document.getElementById("searchBox");
  const btnClear = document.getElementById("btnClearFilters");
  let requestSeq = 0;
  let rowsCache = [];

  function esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, m => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[m]));
  }

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success:false, message:"Invalid JSON" }; }
  }

  function renderRows(rows) {
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">No contacts found.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(c => `
      <tr data-id="${c.id}">
        <td class="fw-semibold">${esc(c.full_name)}</td>
        <td>${esc(c.groups || "-")}</td>
        <td>${esc(c.mobile || "-")}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary me-1 btn-edit" title="Edit">
            <i class="bi bi-pencil-square"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>
    `).join("");
  }

  function applyFilters() {
    const gid = (groupFilter.value || "").trim();
    const q = (searchBox.value || "").trim().toLowerCase();

    const filtered = rowsCache.filter((row) => {
      const groupIds = String(row.group_ids || "")
        .split(",")
        .map((x) => x.trim())
        .filter(Boolean);

      const matchesGroup = !gid || groupIds.includes(gid);

      const haystack = [
        row.full_name,
        row.mobile,
        row.groups,
      ].map((x) => String(x || "").toLowerCase()).join(" ");
      const matchesQuery = !q || haystack.includes(q);

      return matchesGroup && matchesQuery;
    });

    renderRows(filtered);
  }

  async function loadContactsTable() {
    const requestId = ++requestSeq;
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">Loading...</td></tr>`;

    try {
      const resp = await fetch("backend/auth/get_contacts_table_api.php", { cache: "no-store" });
      const data = await safeJson(resp);

      if (requestId !== requestSeq) return;

      if (!data.success || !Array.isArray(data.data)) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">No contacts found.</td></tr>`;
        return;
      }

      rowsCache = data.data;
      applyFilters();
    } catch {
      if (requestId !== requestSeq) return;
      tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">Failed to load contacts.</td></tr>`;
    }
  }

  window.BPSS_reloadContactsTable = loadContactsTable;

  let searchTimer;
  groupFilter.addEventListener("change", applyFilters);
  searchBox.addEventListener("input", () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 150);
  });
  btnClear.addEventListener("click", () => {
    groupFilter.value = "";
    searchBox.value = "";
    applyFilters();
  });

  tbody.addEventListener("click", (e) => {
    const tr = e.target.closest("tr[data-id]");
    if (!tr) return;
    const id = tr.getAttribute("data-id");

    if (e.target.closest(".btn-delete")) window.BPSS_deleteContact?.(id);
    if (e.target.closest(".btn-edit")) window.BPSS_editContact?.(id);
  });

  loadContactsTable();
});
