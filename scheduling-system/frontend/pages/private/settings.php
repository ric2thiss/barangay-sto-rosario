<?php // frontend/pages/private/settings.php ?>
<div class="container-fluid py-3">

  <div class="mb-4">
    <h5 class="mb-1 bpss-text-theme">Settings</h5>
    <div class="text-muted small">Customize your experience.</div>
  </div>

  <div class="row g-3">

    <!-- Appearance -->
    <div class="col-md-6 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header py-2">
          <span class="fw-semibold">
            <i class="bi bi-palette me-2 bpss-text-theme"></i>Appearance
          </span>
        </div>
        <div class="card-body">

          <!-- Dark Mode Toggle -->
          <div class="d-flex align-items-center justify-content-between py-1">
            <div>
              <div class="fw-medium">Dark Mode</div>
              <div class="text-muted small">Switch to a darker interface.</div>
            </div>
            <div class="form-check form-switch mb-0 ms-3">
              <input class="form-check-input bpss-toggle-lg"
                     type="checkbox"
                     role="switch"
                     id="darkModeToggle">
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>

</div>
