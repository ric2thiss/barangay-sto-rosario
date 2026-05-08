document.addEventListener("DOMContentLoaded", function () {
  const API_URL = window.BPSS_REPORT_API_URL;

  const dateFrom   = document.getElementById("reportDateFrom");
  const dateTo     = document.getElementById("reportDateTo");
  const typeFilter = document.getElementById("reportType");
  const stFilter   = document.getElementById("reportStatus");
  const btnGen     = document.getElementById("btnGenerateReport");
  const btnClear   = document.getElementById("btnClearReportFilters");
  const btnPrint   = document.getElementById("btnPrintReport");
  const output     = document.getElementById("reportOutput");
  const summaryRow = document.getElementById("reportSummaryRow");

  // Summary cells
  const sumEvents  = document.getElementById("sumTotalEvents");
  const sumAgendas = document.getElementById("sumTotalAgendas");
  const sumDone    = document.getElementById("sumDoneRate");
  const sumSms     = document.getElementById("sumSmsSent");

  function pad2(value) {
    return String(value).padStart(2, "0");
  }

  function ymdLocal(date) {
    return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
  }

  // Default: current month
  const now = new Date();
  dateFrom.value = `${now.getFullYear()}-${pad2(now.getMonth() + 1)}-01`;
  dateTo.value   = ymdLocal(new Date(now.getFullYear(), now.getMonth() + 1, 0));

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;").replace(/</g, "&lt;")
      .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function fmtDate(str) {
    if (!str) return "—";
    const d = new Date(str);
    if (isNaN(d)) return esc(str);
    return new Intl.DateTimeFormat("en-PH", {
      month: "short", day: "2-digit", year: "numeric",
      hour: "numeric", minute: "2-digit"
    }).format(d);
  }

  function eventTypeBadge(type) {
    const map = { meeting: "primary", event: "info", appointment: "warning", other: "secondary" };
    const color = map[type] || "secondary";
    return `<span class="badge bg-${color}">${esc(type || "—")}</span>`;
  }

  function eventStatusBadge(status) {
    const map = { draft: "secondary", scheduled: "info", completed: "success", cancelled: "danger" };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color}">${esc(status || "—")}</span>`;
  }

  function agendaStatusBadge(status) {
    const map = { pending: "secondary", done: "success", deferred: "warning", cancelled: "danger" };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color} bg-opacity-75">${esc(status || "pending")}</span>`;
  }

  function minutesBadge(status) {
    if (!status) return `<span class="badge bg-light text-muted border">No minutes</span>`;
    const map = { draft: "warning", extracted: "info", final: "success" };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color}">Minutes: ${esc(status)}</span>`;
  }

  function buildAgendaTable(agendas) {
    if (!agendas || !agendas.length) {
      return `<div class="text-muted small fst-italic ps-1">No agenda items.</div>`;
    }
    const rows = agendas.map(a => `
      <tr>
        <td class="text-muted" style="width:3%;">${esc(a.agenda_no)}</td>
        <td style="width:35%;">${esc(a.agenda_title || "—")}</td>
        <td style="width:12%;">${agendaStatusBadge(a.status)}</td>
        <td class="text-muted small">${esc(a.agenda_details || "—")}</td>
      </tr>
    `).join("");

    return `
      <table class="table table-sm table-bordered mb-0" style="font-size:0.85rem;">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Agenda Topic</th>
            <th>Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    `;
  }

  function buildEventCard(ev, index) {
    const dateRange = ev.end_datetime
      ? `${fmtDate(ev.start_datetime)} — ${fmtDate(ev.end_datetime)}`
      : fmtDate(ev.start_datetime);

    const smsBadge = ev.sms_sent > 0
      ? `<span class="badge bg-primary">${ev.sms_sent} SMS sent</span>`
      : `<span class="badge bg-light text-muted border">No SMS</span>`;

    const failBadge = ev.sms_failed > 0
      ? `<span class="badge bg-danger">${ev.sms_failed} failed</span>`
      : "";

    return `
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-semibold">${index}. ${esc(ev.title)}</span>
            ${eventTypeBadge(ev.schedule_type)}
            ${eventStatusBadge(ev.status)}
            ${minutesBadge(ev.minutes_status)}
          </div>
          <div class="d-flex gap-1 flex-wrap">
            ${smsBadge}
            ${failBadge}
          </div>
        </div>
        <div class="card-body pb-2">

          <div class="row g-2 mb-2 small text-muted">
            <div class="col-sm-6">
              <i class="bi bi-calendar3 me-1"></i>${esc(dateRange)}
            </div>
            ${ev.location ? `<div class="col-sm-6"><i class="bi bi-geo-alt me-1"></i>${esc(ev.location)}</div>` : ""}
            ${ev.created_by ? `<div class="col-sm-6"><i class="bi bi-person me-1"></i>Created by ${esc(ev.created_by)}</div>` : ""}
            ${ev.description ? `<div class="col-12"><i class="bi bi-text-left me-1"></i>${esc(ev.description)}</div>` : ""}
          </div>

          <div class="fw-semibold small mb-1">Agenda</div>
          ${buildAgendaTable(ev.agendas)}

        </div>
      </div>
    `;
  }

  async function generateReport() {
    output.innerHTML = `<div class="text-center py-5 text-muted">
      <span class="spinner-border spinner-border-sm me-2"></span>Generating report...
    </div>`;
    summaryRow.style.setProperty("display", "none", "important");

    const params = new URLSearchParams({
      date_from: dateFrom.value,
      date_to:   dateTo.value,
    });
    if (typeFilter.value) params.set("type",   typeFilter.value);
    if (stFilter.value)   params.set("status", stFilter.value);

    try {
      const resp = await fetch(`${API_URL}?${params.toString()}`);
      const json = await resp.json();

      if (!json.success) {
        output.innerHTML = `<div class="alert alert-danger">${esc(json.message || "Failed to load report.")}</div>`;
        return;
      }

      const events  = json.events  || [];
      const summary = json.summary || {};

      // Populate summary cards
      sumEvents.textContent  = summary.total_events  || 0;
      sumAgendas.textContent = summary.total_agendas || 0;
      const pct = summary.total_agendas
        ? Math.round((summary.done_agendas / summary.total_agendas) * 100)
        : 0;
      sumDone.textContent = `${pct}%`;
      sumSms.textContent  = summary.total_sms_sent || 0;
      summaryRow.style.removeProperty("display");

      if (!events.length) {
        output.innerHTML = `<div class="text-center py-4 text-muted">No events found for the selected period.</div>`;
        return;
      }

      // Report header (visible when printing)
      const periodLabel = `${dateFrom.value} to ${dateTo.value}`;
      const header = `
        <div class="mb-3 d-none d-print-block">
          <h5 class="mb-0">Event Report</h5>
          <div class="small text-muted">Period: ${esc(periodLabel)}</div>
        </div>
      `;

      output.innerHTML = header + events.map((ev, i) => buildEventCard(ev, i + 1)).join("");

    } catch (err) {
      output.innerHTML = `<div class="alert alert-danger">Error loading report.</div>`;
    }
  }

  btnGen.addEventListener("click", generateReport);

  btnClear.addEventListener("click", () => {
    const n = new Date();
    dateFrom.value   = `${n.getFullYear()}-${pad2(n.getMonth() + 1)}-01`;
    dateTo.value     = ymdLocal(new Date(n.getFullYear(), n.getMonth() + 1, 0));
    typeFilter.value = "";
    stFilter.value   = "";
    generateReport();
  });

  btnPrint.addEventListener("click", () => window.print());

  // Auto-generate on load
  generateReport();
});
