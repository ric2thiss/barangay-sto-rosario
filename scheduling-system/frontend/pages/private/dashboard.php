<?php // frontend/pages/private/dashboard.php ?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Dashboard</h5>
      <div class="text-muted small" id="dashToday">—</div>
    </div>
    <a href="index.php?page=create_event" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-plus-circle"></i>
      <span>New Event</span>
    </a>
  </div>

  <!-- Stat cards -->
  <div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:46px;height:46px;background:rgba(31,58,147,.1);">
            <i class="bi bi-calendar-event fs-5 bpss-text-theme"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold lh-1" id="statEventsToday">—</div>
            <div class="small text-muted">Events Today</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:46px;height:46px;background:rgba(13,202,240,.1);">
            <i class="bi bi-calendar-week fs-5 text-info"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold lh-1" id="statEventsWeek">—</div>
            <div class="small text-muted">This Week</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:46px;height:46px;background:rgba(25,135,84,.1);">
            <i class="bi bi-people fs-5 text-success"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold lh-1" id="statContacts">—</div>
            <div class="small text-muted">Contacts</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
               style="width:46px;height:46px;background:rgba(13,110,253,.1);">
            <i class="bi bi-chat-dots fs-5 text-primary"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold lh-1" id="statSmsSent">—</div>
            <div class="small text-muted">SMS Sent Today</div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-3">

    <!-- Today's Events -->
    <div class="col-lg-7">
      <div class="card shadow-sm h-100">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <span class="fw-semibold">Today's Events</span>
          <a href="index.php?page=create_event" class="small text-decoration-none">View calendar →</a>
        </div>
        <div class="card-body p-0" id="dashTodayEvents">
          <div class="text-center py-4 text-muted small">
            <span class="spinner-border spinner-border-sm me-1"></span>Loading...
          </div>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-5 d-flex flex-column gap-3">

      <!-- Upcoming This Week -->
      <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <span class="fw-semibold">Upcoming This Week</span>
        </div>
        <div class="card-body p-0" id="dashUpcoming">
          <div class="text-center py-3 text-muted small">
            <span class="spinner-border spinner-border-sm me-1"></span>Loading...
          </div>
        </div>
      </div>

      <!-- Recent SMS -->
      <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <span class="fw-semibold">Recent SMS</span>
          <a href="index.php?page=sms" class="small text-decoration-none">View all →</a>
        </div>
        <div class="card-body p-0" id="dashRecentSms">
          <div class="text-center py-3 text-muted small">
            <span class="spinner-border spinner-border-sm me-1"></span>Loading...
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
  window.BPSS_DASHBOARD_API_URL = "backend/auth/get_dashboard_api.php";
  window.BPSS_SMS_QUEUE_API_URL = "backend/auth/process_sms_queue_api.php";
</script>
