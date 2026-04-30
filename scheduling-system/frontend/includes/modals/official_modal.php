<?php
// frontend/includes/modals/official_modal.php
?>
<div class="modal fade" id="officialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title" id="officialModalTitle">Add Official</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="officialForm" autocomplete="off" enctype="multipart/form-data">
        <div class="modal-body">

          <div id="officialMsg" class="mb-2"></div>
          <input type="hidden" id="officialId" name="id" value="">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Reference Contact Number (optional)</label>
              <select class="form-select" id="contactSelect" name="contact_id">
                <option value="">— None —</option>
              </select>
              <div class="form-text">This only links a contact record. It does not auto-fill the official name.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="fullName" name="full_name" maxlength="150" required
                     style="text-transform:uppercase;"
                     oninput="this.value=this.value.toUpperCase()">
            </div>

            <div class="col-md-6">
              <label class="form-label">Official Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="officialTitle" name="official_title" maxlength="150" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Committee</label>
              <input type="text" class="form-control" id="committee" name="committee" maxlength="150">
            </div>

            <div class="col-md-3">
              <label class="form-label">Term Start</label>
              <input type="date" class="form-control" id="termStart" name="term_start">
            </div>

            <div class="col-md-3">
              <label class="form-label">Term End</label>
              <input type="date" class="form-control" id="termEnd" name="term_end">
            </div>

            <div class="col-md-3">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control" id="displayOrder" name="display_order" min="1" value="1">
            </div>

            <div class="col-md-3">
              <label class="form-label">Active</label>
              <select class="form-select" id="isActive" name="is_active">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="col-12"><hr class="my-2"></div>

            <div class="col-md-6">
              <label class="form-label">Signature Name (optional)</label>
              <input type="text" class="form-control" id="signatureName" name="signature_name" maxlength="120"
                     placeholder="Name printed above signature">
            </div>

            <div class="col-md-6">
              <label class="form-label">Signature Image (optional)</label>
              <input type="file" class="form-control" id="signatureFile" name="signature" accept="image/*">
              <div class="form-text">PNG preferred. Uploading a new file replaces the existing signature.</div>

              <!-- ✅ Remove background button -->
              <div class="d-flex gap-2 align-items-center mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRemoveSigBg" disabled>
                  <i class="bi bi-magic me-1"></i> Remove Background
                </button>
                <small class="text-muted" id="sigBgHint">Select an image first.</small>
              </div>
            </div>

            <div class="col-12">
              <div class="mt-2" id="signaturePreviewWrap" style="display:none;">
                <div class="border rounded p-2 bg-light">
                  <div class="small fw-semibold" id="signatureNamePreview" style="display:none;"></div>
                  <img id="signaturePreview" src="" alt=""
                       style="max-width:100%;height:64px;object-fit:contain;">
                </div>
                <small class="text-muted">Current signature</small>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-theme" id="btnSaveOfficial">
            <i class="bi bi-save2 me-1"></i> Save
          </button>
        </div>

      </form>

    </div>
  </div>
</div>