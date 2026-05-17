<?php
// frontend/pages/private/minutes.php
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Minutes of Meeting</h5>
      <div class="text-muted">Upload minutes files, generate structured JSON using Gemini, and manage records.</div>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnOpenMinutesCamera" title="Take a photo">
        <i class="bi bi-camera"></i>
      </button>
      <button class="btn btn-theme btn-sm" id="btnOpenMinutesUpload">
        <i class="bi bi-upload me-1"></i> Upload
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-6 col-md-4">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="minutesSearch" placeholder="Search by title...">
        </div>

        <div class="col-sm-6 col-md-3">
          <label class="form-label mb-1">Filter by date</label>
          <input type="date" class="form-control form-control-sm" id="minutesDate">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnClearMinutesFilters">Clear</button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="minutesTable">
          <thead class="table-light">
            <tr>
              <th style="width:38%;">Title</th>
              <th style="width:14%;">Status</th>
              <th style="width:12%;">Files</th>
              <th style="width:21%;">Created</th>
              <th style="width:15%;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="5" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Upload Modal -->
<div class="modal fade" id="minutesUploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title">Upload Minutes</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="minutesUploadForm" autocomplete="off">
        <div class="modal-body">

          <div id="minutesUploadMsg" class="mb-2"></div>

          <div class="mb-3">
            <label class="form-label">Title (optional)</label>
            <input type="text" class="form-control" id="minutesTitle" maxlength="200"
                   placeholder="e.g., Regular Session - Committee Meeting">
            <div class="form-text">If empty, it will default to “Minutes of Meeting”.</div>
          </div>

          <!-- Input mode tabs -->
          <ul class="nav nav-tabs mb-3" id="minutesInputTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tabUploadBtn" data-bs-toggle="tab"
                      data-bs-target="#paneUpload" type="button" role="tab">
                <i class="bi bi-upload me-1"></i>Upload File
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tabCameraBtn" data-bs-toggle="tab"
                      data-bs-target="#paneCamera" type="button" role="tab">
                <i class="bi bi-camera me-1"></i>Take Photo
              </button>
            </li>
          </ul>

          <div class="tab-content">

            <!-- Upload tab -->
            <div class="tab-pane fade show active" id="paneUpload" role="tabpanel">
              <div class="mb-3">
                <label class="form-label">Upload files (multiple allowed)</label>
                <input type="file" class="form-control" id="minutesFiles"
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                       multiple>
                <div class="form-text">Supported: JPG/PNG/PDF/DOC/DOCX</div>
              </div>
              <div class="border rounded p-2 bg-light">
                <div class="small text-muted mb-1">Selected files</div>
                <ul class="mb-0 small" id="minutesSelectedFiles">
                  <li class="text-muted">No files selected.</li>
                </ul>
              </div>
            </div>

            <!-- Camera tab -->
            <div class="tab-pane fade" id="paneCamera" role="tabpanel">
              <div class="position-relative mb-2">
                <video id="minutesCameraStream" autoplay playsinline muted
                       class="w-100 rounded" style="max-height:280px;background:#000;object-fit:cover;"></video>
                <span id="cameraStatusMsg" class="position-absolute top-0 start-50 translate-middle-x mt-2
                      badge bg-dark opacity-75 d-none">Starting camera…</span>
              </div>
              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSwitchCamera" title="Flip camera">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm flex-grow-1" id="btnCapturePhoto">
                  <i class="bi bi-camera me-1"></i>Capture Photo
                </button>
              </div>
              <canvas id="minutesCaptureCanvas" class="d-none"></canvas>
              <div id="capturedPhotosPreview" class="d-flex flex-wrap gap-2 mt-1"></div>
              <div class="small text-muted mt-1">
                Captured photos: <span id="capturedCount">0</span>
              </div>
            </div>

          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-theme" id="btnUploadMinutes">
            <i class="bi bi-cloud-arrow-up me-1"></i> Upload
          </button>
        </div>
      </form>

    </div>
  </div>
</div>



