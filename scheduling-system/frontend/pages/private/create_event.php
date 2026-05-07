<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h5 class="mb-0 bpss-text-theme">Create Schedule Event</h5>

  <div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1"
            id="btnAddEvent" type="button">
      <i class="bi bi-plus-circle"></i>
      <span>Add Event</span>
    </button>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div id="bpssCalendar"></div>
  </div>
</div>

<script>
  window.BPSS_USER_FULLNAME = <?php echo json_encode($_SESSION['full_name'] ?? ""); ?>;

  window.BPSS_EVENT_LIST_API_URL   = "backend/auth/get_schedule_events_api.php";
  window.BPSS_EVENT_CREATE_API_URL = "backend/auth/create_schedule_event_api.php";
  window.BPSS_EVENT_UPDATE_API_URL = "backend/auth/update_schedule_event_api.php";
  window.BPSS_EVENT_DELETE_API_URL = "backend/auth/delete_schedule_api.php";
  window.BPSS_SCHED_EVENT_RECIP_GROUPS_API_URL   = "backend/auth/schedule_event_get_recipient_groups_api.php";
  window.BPSS_SCHED_EVENT_RECIP_CONTACTS_API_URL = "backend/auth/schedule_event_get_recipient_contacts_api.php";
  window.BPSS_EVENT_RESEND_SMS_API_URL           = "backend/auth/resend_schedule_sms_api.php";
  window.BPSS_SMS_QUEUE_API_URL                  = "backend/auth/process_sms_queue_api.php";
</script>