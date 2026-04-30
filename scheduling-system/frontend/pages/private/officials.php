<?php
// frontend/pages/private/officials.php
?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Barangay Officials</h5>
      <div class="text-muted">Manage officials list and e-signatures (1 per official).</div>
    </div>

    <button class="btn btn-theme btn-sm" id="btnAddOfficial">
      <i class="bi bi-plus-circle me-1"></i> Add Official
    </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
          <label class="form-label mb-1">Status</label>
          <select class="form-select form-select-sm" id="statusFilter">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>

        <div class="col-sm-8 col-md-6">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="searchBox"
                 placeholder="Search name, title, committee...">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm mt-sm-4 w-100" id="btnClearFilters">
            Clear
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="officialsTable">
          <thead class="table-light">
            <tr>
              <th style="width:30%;">Official</th>
              <th style="width:25%;">Title / Committee</th>
              <th style="width:15%;">Term</th>
              <th style="width:20%;">E-signature</th>
              <th style="width:10%;" class="text-end">Actions</th>
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

<?php require __DIR__ . '/../../includes/modals/official_modal.php'; ?>

<link rel="stylesheet" href="frontend/css/officials.css">

<script src="frontend/js/get_contacts_dropdown.js"></script>
<script src="frontend/js/get_officials_table.js"></script>
<script src="frontend/js/create_official.js"></script>
<script src="frontend/js/delete_official.js"></script>