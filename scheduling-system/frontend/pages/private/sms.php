<?php // frontend/pages/private/sms.php ?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">SMS Outbox</h5>
      <div class="text-muted">View and manage sent SMS notifications.</div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <!-- Filters -->
      <div class="row g-2 align-items-end mb-3">

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">From</label>
          <input type="date" class="form-control form-control-sm" id="smsDateFrom">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">To</label>
          <input type="date" class="form-control form-control-sm" id="smsDateTo">
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">Status</label>
          <select class="form-select form-select-sm" id="smsStatus">
            <option value="">All</option>
            <option value="sent">Sent</option>
            <option value="failed">Failed</option>
            <option value="queued">Queued</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="col-sm-6 col-md-2">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="smsSearch"
                 placeholder="Mobile, event, message...">
        </div>

        <div class="col-md-2 d-flex gap-2 align-items-end">
          <button class="btn btn-primary btn-sm flex-grow-1" id="btnApplySmsFilters">
            <i class="bi bi-search me-1"></i>Apply
          </button>
          <button class="btn btn-outline-secondary btn-sm" id="btnClearSmsFilters" title="Reset to today">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="smsTable">
          <thead class="table-light">
            <tr>
              <th style="width:13%;">Mobile</th>
              <th class="sms-col-sentby" style="width:12%;">Sent By</th>
              <th style="width:16%;">Event</th>
              <th style="width:26%;">Message</th>
              <th style="width:7%;">Type</th>
              <th style="width:9%;">Status</th>
              <th style="width:11%;">Queued At</th>
              <th style="width:6%;" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="8" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="small text-muted" id="smsMeta">—</div>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary" id="btnSmsPrev" type="button">Prev</button>
          <button class="btn btn-outline-secondary" id="btnSmsNext" type="button">Next</button>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- SMS Message Preview Modal -->
<div class="modal fade" id="smsPreviewModal" tabindex="-1" aria-labelledby="smsPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="smsPreviewModalLabel">SMS Message</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 d-flex gap-2 flex-wrap" id="smsPreviewMeta"></div>
        <hr class="my-2">
        <pre class="mb-0 small" id="smsPreviewBody"
             style="white-space: pre-wrap; word-break: break-word; font-family: inherit;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-primary" id="smsPreviewResend">
          <i class="bi bi-send me-1"></i>Resend
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  window.BPSS_SMS_LOGS_API_URL          = "backend/auth/get_sms_logs_api.php";
  window.BPSS_SMS_RESEND_SINGLE_API_URL = "backend/auth/resend_single_sms_api.php";
  window.BPSS_CURRENT_USER_ID           = <?php echo (int)$_SESSION['user_id']; ?>;
  window.BPSS_USER_ROLE                 = '<?php echo htmlspecialchars($_SESSION['role'] ?? 'staff', ENT_QUOTES); ?>';
</script>
