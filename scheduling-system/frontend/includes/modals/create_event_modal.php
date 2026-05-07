<?php
// frontend/includes/modals/create_event_modal.php
?>

<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header">
        <h6 class="modal-title">Add Schedule Event</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="eventForm" autocomplete="off">
        <div class="modal-body">

          <!-- Message Box -->
          <div id="eventMsg" class="mb-2"></div>

          <!-- Step Indicators -->
         <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- LEFT: Step Pills -->
            <div class="d-flex gap-2 flex-wrap">
              <span class="badge rounded-pill step-pill" data-step-pill="1">1. Memo</span>
              <span class="badge rounded-pill step-pill" data-step-pill="2">2. Agenda</span>
              <span class="badge rounded-pill step-pill" data-step-pill="3">3. To</span>
              <span class="badge rounded-pill step-pill" data-step-pill="4">4. Preview</span>
            </div>

            <!-- RIGHT: Step Text + Delete -->
            <div class="d-flex align-items-center gap-3">

              <small class="text-muted" id="stepHint">
                Step 1 of 4
              </small>

              <button type="button"
                      class="btn btn-outline-danger btn-sm d-none"
                      id="btnDeleteEvent">
                <i class="bi bi-trash me-1"></i>Delete
              </button>

            </div>

          </div>

          <!-- ============================= -->
          <!-- STEP 1: MEMO DETAILS -->
          <!-- ============================= -->
          <div class="step-pane" data-step="1">
            <div class="row g-3">

              <div class="col-12">
                <label class="form-label">Subject <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" id="evTitle" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">From (Date & Time) <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="start_datetime" id="evStart" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">To (Date & Time) <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="end_datetime" id="evEnd" required>
              </div>

              <div class="col-12">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" id="evLocation">
              </div>

              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="evDesc" rows="3"></textarea>
              </div>

            </div>
          </div>

          <!-- ============================= -->
          <!-- STEP 2: AGENDA + ATTACHMENTS -->
          <!-- ============================= -->
          <div class="step-pane d-none" data-step="2">

            <!-- Agenda -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-semibold">Agenda</div>
              <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddAgenda">
                <i class="bi bi-plus-circle me-1"></i>Add item
              </button>
            </div>

            <div id="agendaList" class="vstack gap-2"></div>
            <input type="hidden" name="agendas_json" id="agendasJson">

            <!-- Attachments -->
            <hr class="my-3">

            <div class="fw-semibold mb-2">Event Attachments</div>

            <!-- Existing files (shown on edit) -->
            <div id="existingAttachments" class="mb-2"></div>

            <input type="file"
                   class="form-control"
                   name="event_files[]"
                   id="eventFiles"
                   multiple
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
            <div class="form-text">Accepted: PDF, Word, Excel, images. Multiple files allowed.</div>

          </div>

          <!-- ============================= -->
          <!-- STEP 3: RECIPIENTS -->
          <!-- ============================= -->
          <div class="step-pane d-none" data-step="3">
            <div class="row g-3">

              <!-- SMS + Auto Notify -->
              <div class="col-12">
                <div class="border rounded p-2">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                    <div class="d-flex align-items-center gap-4 flex-wrap">
                      <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="notifySms" name="notify_sms" value="1" checked>
                        <label class="form-check-label" for="notifySms">SMS</label>
                      </div>

                      <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="allowAutoNotify">
                        <label class="form-check-label" for="allowAutoNotify">Auto notify</label>
                      </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                      <span class="text-muted small">Offset</span>
                      <input type="number"
                             class="form-control form-control-sm"
                             id="offsetMinutes"
                             min="1"
                             step="1"
                             value="60"
                             style="width: 90px;"
                             disabled>
                      <span class="text-muted small">min</span>
                    </div>

                  </div>
                </div>
              </div>

              <!-- Groups -->
              <div class="col-12">
                <label class="form-label d-flex justify-content-between">
                  <span>To (Groups)</span>
                  <small class="text-muted">
                    <span id="selectedGroupsCount">0</span> selected
                  </small>
                </label>

                <div class="border rounded p-2" style="max-height: 160px; overflow:auto;">
                  <div id="groupList" class="vstack gap-1">
                    <div class="text-muted">Loading groups...</div>
                  </div>
                </div>
              </div>

              <!-- Contacts -->
              <div class="col-12">
                <label class="form-label d-flex justify-content-between">
                  <span>To (Contacts)</span>
                  <small class="text-muted">
                    <span id="selectedContactsCount">0</span> selected
                  </small>
                </label>

                <input type="text" class="form-control" id="contactSearch" placeholder="Search name...">

                <div class="border rounded mt-2 p-2" style="max-height: 260px; overflow:auto;">
                  <div id="contactList" class="vstack gap-1">
                    <!-- Contacts will be dynamically loaded or replaced -->
                  </div>
                </div>
              </div>

              <!-- Hidden Fields -->
              <input type="hidden" id="notifyMessage" name="notify_message">
              <input type="hidden" name="send_sms_now" value="1">
              <input type="hidden" id="allowAutoNotifyHidden" name="allow_auto_notify" value="0">
              <input type="hidden" id="offsetMinutesHidden" name="notify_offset_minutes" value="60">
              <input type="hidden" name="event_id" id="eventId" value="">
              <input type="hidden" name="is_edit" id="isEdit" value="0">

            </div>
          </div>

          <!-- ============================= -->
          <!-- STEP 4: PREVIEW -->
          <!-- ============================= -->
          <div class="step-pane d-none" data-step="4">
            <div class="row g-3">

              <div class="col-12">
                <div class="border rounded p-2 bg-light">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                      <div class="fw-semibold">Message Preview</div>
                      <small class="text-muted">Review before saving.</small>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefreshPreview">
                      <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <textarea class="form-control"
                    id="smsMessagePreview"
                    name="sms_message"
                    rows="10"
                    placeholder="You can edit the message before saving..."></textarea>
              </div>

            </div>
          </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary" id="btnPrev">
            <i class="bi bi-chevron-left me-1"></i> Back
          </button>

          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-primary" id="btnNext">
              Next <i class="bi bi-chevron-right ms-1"></i>
            </button>

            <button type="submit" class="btn btn-success d-none" id="btnSave">
              <i class="bi bi-check2-circle me-1"></i> Save
            </button>
          </div>
        </div>

      </form>

    </div>
  </div>
</div>

