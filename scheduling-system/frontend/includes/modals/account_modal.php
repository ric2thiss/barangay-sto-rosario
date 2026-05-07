<?php // frontend/includes/modals/account_modal.php ?>

<!-- Create / Edit Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="accountModalTitle">Add Account</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="accountForm" autocomplete="off">
        <div class="modal-body">
          <div id="accountMsg" class="mb-2"></div>
          <input type="hidden" id="accountId" name="id" value="">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="accountFullName"
                     name="full_name" placeholder="e.g. Juan dela Cruz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm" id="accountUsername"
                     name="username" placeholder="e.g. jdelacruz" required autocomplete="off">
            </div>
            <div class="col-md-6">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" id="accountRole" name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control form-control-sm" id="accountEmail"
                     name="email" placeholder="email@example.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">Mobile</label>
              <input type="text" class="form-control form-control-sm" id="accountMobile"
                     name="mobile" placeholder="09XXXXXXXXX">
            </div>

            <!-- Password fields (required on create, optional on edit) -->
            <div class="col-12" id="accountPasswordSection">
              <hr class="my-1">
              <div class="small text-muted mb-2" id="accountPasswordNote">Set a password for this account.</div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Password <span class="text-danger" id="accountPwRequired">*</span></label>
                  <input type="password" class="form-control form-control-sm" id="accountPassword"
                         name="password" placeholder="Min. 6 characters" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirm Password <span class="text-danger" id="accountConfPwRequired">*</span></label>
                  <input type="password" class="form-control form-control-sm" id="accountConfirmPassword"
                         name="confirm_password" placeholder="Repeat password" autocomplete="new-password">
                </div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnSaveAccount">
            <i class="bi bi-save2 me-1"></i>Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Reset Password</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="resetPwForm" autocomplete="off">
        <div class="modal-body">
          <div id="resetPwMsg" class="mb-2"></div>
          <input type="hidden" id="resetPwId" name="id">
          <div class="small text-muted mb-3" id="resetPwName"></div>
          <div class="mb-2">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control form-control-sm" id="resetPwNew"
                   name="password" placeholder="Min. 6 characters" autocomplete="new-password" required>
          </div>
          <div>
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control form-control-sm" id="resetPwConfirm"
                   name="confirm_password" placeholder="Repeat password" autocomplete="new-password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm" id="btnResetPw">
            <i class="bi bi-key me-1"></i>Reset
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
