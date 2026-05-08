<?php
// frontend/includes/footer.php
?>
  </div> <!-- /.container-fluid -->
</main>

<footer class="bpss-footer text-center py-3 small text-muted border-top bg-white">
  © <?php echo date('Y'); ?> Barangay Planning & Scheduling System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="frontend/js/app.js"></script>


<!-- dashboard page -->
<?php if (isset($page) && $page === 'dashboard'): ?>
  <script src="frontend/js/dashboard.js"></script>
<?php endif; ?>

<!-- create_event page -->
<?php if (isset($page) && $page === 'create_event'): ?>


  <script src="frontend/js/schedule_preview.js"></script>
  <script src="frontend/js/schedule_agenda.js"></script>
  <script src="frontend/js/schedule_sms.js"></script>
  <script src="frontend/js/schedule_wizard.js"></script>

  
  <script src="frontend/js/get_schedule_events.js"></script>
  <script src="frontend/js/create_schedule_event.js"></script>
  <script src="frontend/js/update_schedule_event.js"></script> 
  <script src="frontend/js/schedule_event_get_recipient_groups.js"></script>
  <script src="frontend/js/schedule_event_get_recipient_contacts.js"></script>
  <script src="frontend/js/schedule_calendar.js"></script>
  <script src="frontend/js/schedule_event_modal.js"></script>

<?php endif; ?>

<!-- PDF Libraries -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>


<!-- minutes page -->
<?php if (isset($page) && $page === 'minutes'): ?>
  <?php include __DIR__ . '/modals/minutes_detail_modal.php'; ?>

  <script src="frontend/js/minutes_print.js"></script>

  <script src="frontend/js/minutes_list.js"></script>
  <script src="frontend/js/minutes_upload.js"></script>
  <script src="frontend/js/minutes_detail.js"></script>
  <script src="frontend/js/minutes_generate.js"></script>
  <script src="frontend/js/minutes_edit.js"></script>
  <script src="frontend/js/minutes_delete.js"></script>
<?php endif; ?>

<!-- logs page -->
<?php if (isset($page) && $page === 'logs'): ?>
  <script src="frontend/js/logs.js"></script>
<?php endif; ?>

<!-- sms page -->
<?php if (isset($page) && $page === 'sms'): ?>
  <script src="frontend/js/sms_logs.js"></script>
<?php endif; ?>

<!-- reports page -->
<?php if (isset($page) && $page === 'reports'): ?>
  <script src="frontend/js/reports.js"></script>
<?php endif; ?>

<!-- accounts page -->
<?php if (isset($page) && $page === 'accounts'): ?>
  <script src="frontend/js/accounts.js"></script>
<?php endif; ?>

<!-- profile page -->
<?php if (isset($page) && $page === 'profile'): ?>
  <script src="frontend/js/profile.js"></script>
<?php endif; ?>

<!-- settings page -->
<?php if (isset($page) && $page === 'settings'): ?>
  <script src="frontend/js/settings.js"></script>
<?php endif; ?>

  <?php require __DIR__ . '/modals/confirm_modal.php'; ?>
  <script src="frontend/js/confirm_modal.js"></script>
  <?php require __DIR__ . '/modals/toast.php'; ?>
  <script src="frontend/js/toast.js"></script>
  
</body>
</html>