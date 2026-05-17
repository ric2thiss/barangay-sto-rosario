<?php // frontend/pages/private/reports.php ?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Reports</h5>
      <div class="text-muted">Event summary with agenda status and SMS notifications.</div>
    </div>
    <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="btnPrintReport">
      <i class="bi bi-printer"></i>
      <span>Print</span>
    </button>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm mb-3" id="reportFiltersCard">
    <div class="card-body">
      <div class="row g-2 align-items-end">

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">From</label>
          <input type="date" class="form-control form-control-sm" id="reportDateFrom">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">To</label>
          <input type="date" class="form-control form-control-sm" id="reportDateTo">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">Type</label>
          <select class="form-select form-select-sm" id="reportType">
            <option value="">All types</option>
            <option value="meeting">Meeting</option>
            <option value="event">Event</option>
            <option value="appointment">Appointment</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">Status</label>
          <select class="form-select form-select-sm" id="reportStatus">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="scheduled">Scheduled</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-primary btn-sm w-100" id="btnGenerateReport">Generate</button>
          <button class="btn btn-outline-secondary btn-sm" id="btnClearReportFilters" title="Reset to current month">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="row g-3 mb-3" id="reportSummaryRow" style="display:none!important;">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold bpss-text-theme" id="sumTotalEvents">—</div>
          <div class="small text-muted">Total Events</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold bpss-text-theme" id="sumTotalAgendas">—</div>
          <div class="small text-muted">Total Agendas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-success" id="sumDoneRate">—</div>
          <div class="small text-muted">Agenda Completion</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm text-center h-100">
        <div class="card-body py-3">
          <div class="fs-3 fw-bold text-primary" id="sumSmsSent">—</div>
          <div class="small text-muted">SMS Sent</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Report output -->
  <div id="reportOutput"></div>

</div>

<!-- Print styles -->
<style>
@media print {
  #reportFiltersCard, #btnPrintReport, .bpss-sidebar, .bpss-topbar,
  nav, header, footer, .btn { display: none !important; }
  .card { border: 1px solid #ccc !important; box-shadow: none !important; break-inside: avoid; }
  #reportSummaryRow { display: flex !important; }
  body { font-size: 12px; }
}
</style>

<script>
  window.BPSS_REPORT_API_URL = "backend/auth/get_events_report_api.php";
</script>
