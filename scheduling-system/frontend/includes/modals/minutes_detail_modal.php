<?php
// frontend/includes/modals/minutes_detail_modal.php
?>
<!-- Detail Modal -->
<div class="modal fade" id="minutesDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h6 class="modal-title mb-0" id="minutesDetailTitle">Minutes Details</h6>
          <div class="small text-muted" id="minutesDetailMeta"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- MESSAGE -->
        <div id="minutesDetailMsg" class="mb-2"></div>

        <!-- TOOLBAR -->
        <div class="minutes-toolbar d-flex align-items-center mb-3 gap-2">

          <!-- ✅ GENERATE BUTTON (RESTORED) -->
          <button class="btn btn-success d-flex align-items-center gap-2"
                  id="btnGenerateMinutesJson"
                  type="button">
            <i class="bi bi-robot"></i>
            Generate Minutes
          </button>

          <select id="minutesPaperSize" class="form-select w-auto">
            <option value="legal">Legal (216 × 356 mm)</option>
            <option value="a4">A4 (210 × 297 mm)</option>
            <option value="letter">Letter (216 × 279 mm)</option>
          </select>

          <button class="btn btn-warning" id="btnMinutesToggleEdit" type="button">
            <i class="bi bi-pen me-2" id="minutesEditIcon"></i>
            <span id="minutesEditLabel">Edit Text</span>
          </button>

          <button class="btn btn-success d-flex align-items-center gap-2 d-none"
                  id="btnMinutesSaveEdits"
                  type="button">
            <span class="spinner-border spinner-border-sm d-none" id="minutesSaveSpinner"></span>
            <i class="bi bi-save" id="minutesSaveIcon"></i>
            <span id="minutesSaveLabel">Save Changes</span>
          </button>

          <button class="btn btn-primary d-flex align-items-center gap-2"
                  id="btnMinutesDownloadPdf"
                  type="button">
            <span class="spinner-border spinner-border-sm d-none" id="minutesPdfSpinner"></span>
            <i class="bi bi-filetype-pdf me-1" id="minutesPdfIcon"></i>
            <span id="minutesPdfLabel">Download PDF</span>
          </button>

        </div>

        <!-- FILES PANEL -->
        <div class="row g-3 mb-3">
          <div class="col-lg-4">
            <div class="border rounded p-2">
              <div class="fw-semibold mb-2">Uploaded files</div>
              <div id="minutesFilesList" class="small text-muted">Loading...</div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="border rounded p-2 bg-light">
              <div class="fw-semibold mb-2">Instructions</div>
              <div class="small text-muted">
                Click <strong>Edit Text</strong> to modify fields.
                Lock editing to enable <strong>Download PDF</strong>.
              </div>
            </div>
          </div>
        </div>

        <!-- DOCUMENT AREA -->
        <div class="minutes-print-wrapper">
          <div id="minutesDoc" class="minutes-paper legal">

            <!-- PAGE 1 -->
            <div class="minutes-pdf-page">
              <div class="minutes-page-inner">

                <div class="minutes-header">
                  <img src="frontend/assets/images/seal-nobg.png" class="minutes-logo" alt="Seal" />
                  <div class="minutes-header-center">
                    <div>Republic of the Philippines</div>
                    <div><strong>PROVINCE OF AGUSAN DEL NORTE</strong></div>
                    <div>Municipality of Magallanes</div>
                    <div><strong>BARANGAY STO. ROSARIO</strong></div>
                    <div><strong>OFFICE OF THE SANGGUNIANG BARANGAY</strong></div>
                  </div>
                  <img src="frontend/assets/images/logo2.png" class="minutes-logo" alt="Logo" />
                </div>

                <hr />

                <div class="minutes-title">
                  <div>ORDER AND CALENDAR OF BUSINESS</div>
                  <div>
                    <strong class="minutes-editable" data-key="session_title"></strong>
                  </div>
                  <div class="minutes-session-date minutes-editable" data-key="session_date_display"></div>
                </div>

                <ol class="minutes-roman">
                  <li>CALL TO ORDER</li>
                  <li>ROLL CALL</li>
                  <li>READING AND APPROVAL OF PREVIOUS MINUTES</li>
                  <li>INCLUSION, AMENDMENTS AND APPROVAL OF AGENDA</li>
                  <li>MATTERS FOR INFORMATION</li>

                  <li>
                    PRIVILEGE HOUR
                    <div class="minutes-sub-item">6.1 QUESTION HOUR</div>
                  </li>

                  <li>
                    FIRST READING AND REFERRAL
                    <div class="minutes-sub-item minutes-editable" data-key="first_reading_resolution"
                       data-inline-fallback="7.1 Reso. No. ___-2025: Requesting ______ Movant-SBM ______">
                      7.1 Reso. No. ___-2025: Requesting ______ Movant-SBM ______
                    </div>
                  </li>

                  <li>COMMITTEE REPORT</li>

                  <li>
                    CALENDAR OF BUSINESS
                    <div class="minutes-sub-item">9.1 UNFINISHED BUSINESS</div>
                    <div class="minutes-sub-item">9.2 BUSINESS OF THE DAY</div>
                    <div class="minutes-sub-item">9.3 UNASSIGNED BUSINESS</div>
                  </li>

                  <li>ORDINANCE AND RESOLUTION FOR THIRD READING</li>
                  <li>OTHER MATTERS</li>
                  <li>ANNOUNCEMENT</li>
                  <li>ADJOURNMENT</li>
                </ol>

                <div class="minutes-footer">
                  <span class="minutes-footer-title" data-key="session_title"></span> pg.1
                </div>

              </div>
            </div>

            <!-- PAGE 2 -->
            <div class="minutes-pdf-page">
              <div class="minutes-page-inner">

                <div class="minutes-header">
                  <img src="frontend/assets/images/seal-nobg.png" class="minutes-logo" alt="Seal" />
                  <div class="minutes-header-center">
                    <div>Republic of the Philippines</div>
                    <div><strong>PROVINCE OF AGUSAN DEL NORTE</strong></div>
                    <div>Municipality of Magallanes</div>
                    <div><strong>BARANGAY STO. ROSARIO</strong></div>
                    <div><strong>OFFICE OF THE SANGGUNIANG BARANGAY</strong></div>
                  </div>
                  <img src="frontend/assets/images/logo2.png" class="minutes-logo" alt="Logo" />
                </div>

                <hr />

                <div class="minutes-section-title">EXCERPT FROM THE MINUTES OF THE <span data-key="session_title" data-inline-fallback="None."></span> OF THE BARANGAY OF BRGY.STO. ROSARIO,
                  MAGALLANES, AGUSAN DEL NORTE HELD ON <span class="minutes-editable" data-key="session_date_display" data-inline-fallback="None."></span>, <span class="minutes-editable" data-key="session_time"></span> AT THE BARANGAY SESSION HALL.
                </div>

                <p class="minutes-justify minutes-editable" data-key="excerpt_page_1"></p>

                <p><strong>PRESENT:</strong></p>
                <ul class="minutes-list" id="minutesPresentList"></ul>
                <button type="button"
                        class="btn btn-sm btn-outline-primary mt-2 d-none"
                        id="btnMinutesAddPresent">
                  Add Present Member
                </button>

                <p class="mt-3"><strong>ABSENT:</strong></p>
                <ul class="minutes-list" id="minutesAbsentList"></ul>
                <button type="button"
                        class="btn btn-sm btn-outline-primary mt-2 d-none"
                        id="btnMinutesAddAbsent">
                  Add Absent Member
                </button>

                <p class="mt-3">
                  <strong>OTHERS PRESENT:</strong>
                  <span class="minutes-editable" data-key="others_present"></span>
                </p>

                <p class="minutes-section-subtitle">I. CALL TO ORDER:</p>
                <p class="minutes-justify minutes-editable" data-key="call_to_order"></p>

                <p class="minutes-section-subtitle">II. ROLL CALL:</p>
                <p class="minutes-justify minutes-editable" data-key="roll_call"></p>

                <p class="minutes-section-subtitle">III. READING AND APPROVAL OF PREVIOUS MINUTES:</p>
                <p class="minutes-justify minutes-editable" data-key="approval_of_minutes"></p>

                <p class="minutes-section-subtitle">IV. INCLUSION, AMENDMENTS, AND APPROVAL OF AGENDA:</p>
                <p class="minutes-justify minutes-editable" data-key="agenda"></p>

                <p class="minutes-section-subtitle">V. MATTERS FOR INFORMATION:</p>
                <p class="minutes-justify minutes-editable" data-key="matters_for_information"></p>

                <div class="minutes-footer">
                  <span class="minutes-footer-title" data-key="session_title"></span> pg.2
                </div>

              </div>
            </div>

            <!-- PAGE 3 -->
            <div class="minutes-pdf-page">
              <div class="minutes-page-inner">

                <p class="minutes-section-subtitle">VI. PRIVILEGE HOUR:</p>
                <p class="minutes-justify minutes-editable" data-key="privilege_hour"></p>

                <p class="minutes-section-subtitle">VII. FIRST READING & REFERRAL:</p>
                <p class="minutes-justify minutes-editable" data-key="first_reading_referral"></p>

                <p class="minutes-section-subtitle">VIII. COMMITTEE REPORT:</p>
                <p class="minutes-justify minutes-editable" data-key="committee_report"></p>

                <p class="minutes-section-subtitle">IX. CALENDAR OF BUSINESS:</p>
                <p class="minutes-justify minutes-editable" data-key="calendar_of_business"></p>

                <p class="minutes-section-subtitle">X. ORDINANCE AND RESOLUTION FOR THIRD READING:</p>
                <p class="minutes-justify minutes-editable" data-key="third_reading"></p>

                <p class="minutes-section-subtitle">XI. OTHER MATTERS:</p>
                <p class="minutes-justify minutes-editable" data-key="other_matters"></p>

                <p class="minutes-section-subtitle">XII. ANNOUNCEMENT:</p>
                <p class="minutes-justify minutes-editable" data-key="announcement"></p>

                <div class="minutes-footer">
                  <span class="minutes-footer-title" data-key="session_title"></span> pg.3
                </div>

              </div>
            </div>

            <!-- PAGE 4 -->
            <div class="minutes-pdf-page">
              <div class="minutes-page-inner">

                <p class="minutes-section-subtitle">XIII. ADJOURNMENT:</p>
                <p class="minutes-justify minutes-editable" data-key="adjournment"></p>

                <p style="margin-top:30px;">
                  I HEREBY CERTIFY to the correctness of the foregoing.
                </p>

                <div class="minutes-sig-grid" id="minutesSignatureGrid"></div>

                <div class="minutes-footer">
                  <span class="minutes-footer-title" data-key="session_title"></span> pg.4
                </div>

              </div>
            </div>

          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
