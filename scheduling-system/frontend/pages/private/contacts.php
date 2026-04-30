<?php
// frontend/pages/private/contacts.php
?>
<div class="container-fluid py-3">

  <!-- Header row -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Contacts</h5>
      <div class="text-muted">Manage contacts and assign multiple groups.</div>
    </div>

    <button class="btn btn-theme btn-sm" id="btnAddContact">
      <i class="bi bi-plus-circle me-1"></i> Add Contact
    </button>
  </div>

  <!-- Filter + Table -->
  <div class="card shadow-sm">
    <div class="card-body">

      <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
          <label class="form-label mb-1">Filter by group</label>
          <select class="form-select form-select-sm" id="groupFilter">
            <option value="">All groups</option>
          </select>
        </div>

        <div class="col-sm-8 col-md-6">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="searchBox" placeholder="Search name or mobile...">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm mt-sm-4 w-100" id="btnClearFilters">
            Clear
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="contactsTable">
          <thead class="table-light">
            <tr>
              <th style="width:35%;">Name</th>
              <th style="width:35%;">Groups</th>
              <th style="width:15%;">Mobile</th>
              <th style="width:15%;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="4" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<!-- Add/Edit Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title" id="contactModalTitle">Add Contact</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="contactForm" autocomplete="off">
        <div class="modal-body">

          <div id="contactMsg" class="mb-2"></div>

          <input type="hidden" id="contactId" name="id" value="">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="fullName" name="full_name" maxlength="150" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Mobile</label>
              <input type="text" class="form-control" id="mobile" name="mobile" maxlength="20" placeholder="09XXXXXXXXX">
            </div>

            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" class="form-control" id="address" name="address" maxlength="255">
            </div>

            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between">
                <label class="form-label mb-1">Groups <span class="text-danger">*</span></label>

                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQuickAddGroup">
                  <i class="bi bi-plus-lg me-1"></i> New Group
                </button>
              </div>

              <div id="groupChecklist" class="border rounded p-2" style="max-height:180px; overflow:auto;">
                <div class="text-muted">Loading groups...</div>
              </div>

              <div class="form-text">Select one or more groups (e.g., Barangay Staff, Barangay Kagawad).</div>
            </div>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-theme" id="btnSaveContact">
            <i class="bi bi-save2 me-1"></i> Save
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<script src="frontend/js/get_contact_groups.js"></script>
<script src="frontend/js/get_contacts_table.js"></script>
<script src="frontend/js/create_contact.js"></script>
<script src="frontend/js/delete_contact.js"></script>