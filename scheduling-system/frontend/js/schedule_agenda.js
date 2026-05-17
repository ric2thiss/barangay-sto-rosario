document.addEventListener("DOMContentLoaded", function () {
  const agendaList  = document.getElementById("agendaList");
  const agendasJson = document.getElementById("agendasJson");
  const btnAddAgenda = document.getElementById("btnAddAgenda");

  if (!agendaList || !agendasJson || !btnAddAgenda) return;

  const STATUSES = [
    { value: "pending",   label: "Pending" },
    { value: "done",      label: "Done" },
    { value: "deferred",  label: "Deferred" },
    { value: "cancelled", label: "Cancelled" },
  ];

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function statusOptions(selected) {
    return STATUSES.map(s =>
      `<option value="${s.value}"${s.value === selected ? " selected" : ""}>${s.label}</option>`
    ).join("");
  }

  function agendaRowTemplate(item = {}) {
    const topic   = escapeHtml(item.topic   || "");
    const status  = item.status || "pending";
    const remarks = escapeHtml(item.remarks || "");

    const el = document.createElement("div");
    el.className = "agenda-item";

    el.innerHTML = `
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label mb-1">Topic</label>
          <input type="text" class="form-control agenda-topic" value="${topic}" placeholder="e.g., Clean-up drive">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Status</label>
          <select class="form-select agenda-status">
            ${statusOptions(status)}
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label mb-1">Remarks</label>
          <input type="text" class="form-control agenda-remarks" value="${remarks}" placeholder="Optional notes">
        </div>

        <div class="col-md-1 d-grid">
          <button type="button" class="btn btn-outline-danger btn-sm agenda-remove" title="Delete">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    `;
    return el;
  }

  function collectAgendas() {
    const items = Array.from(agendaList.querySelectorAll(".agenda-item")).map(row => {
      const topic   = row.querySelector(".agenda-topic")?.value.trim()   || "";
      const status  = row.querySelector(".agenda-status")?.value         || "pending";
      const remarks = row.querySelector(".agenda-remarks")?.value.trim() || "";
      return { topic, status, remarks };
    });

    return items.filter(x => x.topic);
  }

  function syncJson() {
    agendasJson.value = JSON.stringify(collectAgendas());
    window.BPSS_buildPreviewMessage?.();
  }

  function addAgenda(item = {}) {
    const row = agendaRowTemplate(item);
    agendaList.appendChild(row);
    syncJson();
    row.querySelector(".agenda-topic")?.focus();
  }

  btnAddAgenda.addEventListener("click", () => addAgenda());

  agendaList.addEventListener("click", (e) => {
    const btn = e.target.closest(".agenda-remove");
    if (!btn) return;
    btn.closest(".agenda-item")?.remove();
    syncJson();
  });

  // input covers text inputs; change covers selects
  agendaList.addEventListener("input", (e) => {
    if (e.target.matches(".agenda-topic, .agenda-remarks")) syncJson();
  });
  agendaList.addEventListener("change", (e) => {
    if (e.target.matches(".agenda-status")) syncJson();
  });

  window.BPSS_agendaSync = syncJson;

  window.BPSS_agendaReset = function () {
    agendaList.innerHTML = "";
    agendasJson.value = "[]";
  };

  // Restore agenda rows from DB data (used on edit)
  // items: array of { agenda_title, status, agenda_details }
  window.BPSS_agendaRestore = function (items) {
    agendaList.innerHTML = "";
    agendasJson.value = "[]";
    (items || []).forEach(item => {
      addAgenda({
        topic:   item.agenda_title  || "",
        status:  item.status        || "pending",
        remarks: item.agenda_details || "",
      });
    });
    syncJson();
  };

  agendasJson.value = agendasJson.value?.trim() ? agendasJson.value : "[]";
});
