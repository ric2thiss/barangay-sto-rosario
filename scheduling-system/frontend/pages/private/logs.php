<?php
// frontend/pages/private/logs.php
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Activity Logs</h5>
      <div class="text-muted">Track system actions and changes across modules.</div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-6 col-md-5">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="logSearch"
                 placeholder="Search action, description, name, entity...">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label mb-1">Filter by date</label>
          <input type="date" class="form-control form-control-sm" id="logDate">
        </div>

        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnClearLogFilters">Clear</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="logsTable">
          <thead class="table-light">
            <tr>
              <th style="width:22%;">Actor</th>
              <th style="width:18%;">Action</th>
              <th style="width:22%;">Entity</th>
              <th style="width:26%;">Description</th>
              <th style="width:12%;">When</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="small text-muted" id="logsMeta">—</div>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" id="btnLogsPrev" type="button">Prev</button>
          <button class="btn btn-outline-secondary" id="btnLogsNext" type="button">Next</button>
        </div>
      </div>

    </div>
  </div>
</div>

