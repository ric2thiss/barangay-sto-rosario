<?php // frontend/pages/private/accounts.php ?>
<div class="container-fluid py-3">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h5 class="mb-1 bpss-text-theme">Accounts</h5>
      <div class="text-muted">Manage system user accounts.</div>
    </div>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" id="btnAddAccount">
      <i class="bi bi-person-plus"></i>
      <span>Add Account</span>
    </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">

      <!-- Filters -->
      <div class="row g-2 align-items-end mb-3">
        <div class="col-sm-6 col-md-4">
          <label class="form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm" id="accountSearch"
                 placeholder="Name, username, email...">
        </div>
        <div class="col-sm-4 col-md-2">
          <label class="form-label mb-1">Role</label>
          <select class="form-select form-select-sm" id="accountRoleFilter">
            <option value="">All roles</option>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
          </select>
        </div>
        <div class="col-sm-4 col-md-2">
          <label class="form-label mb-1">Status</label>
          <select class="form-select form-select-sm" id="accountStatusFilter">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Disabled</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary btn-sm w-100" id="btnClearAccountFilters">Clear</button>
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="accountsTable">
          <thead class="table-light">
            <tr>
              <th style="width:22%;">Full Name</th>
              <th style="width:16%;">Username</th>
              <th style="width:10%;">Role</th>
              <th style="width:18%;">Email</th>
              <th style="width:13%;">Mobile</th>
              <th style="width:9%;">Status</th>
              <th style="width:12%;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<script>
  window.BPSS_ACCOUNTS_GET_API_URL      = "backend/auth/get_accounts_api.php";
  window.BPSS_ACCOUNTS_CREATE_API_URL   = "backend/auth/create_account_api.php";
  window.BPSS_ACCOUNTS_UPDATE_API_URL   = "backend/auth/update_account_api.php";
  window.BPSS_ACCOUNTS_DELETE_API_URL   = "backend/auth/delete_account_api.php";
  window.BPSS_ACCOUNTS_RESET_PW_API_URL = "backend/auth/reset_account_password_api.php";
  window.BPSS_ACCOUNTS_TOGGLE_API_URL   = "backend/auth/toggle_account_status_api.php";
  window.BPSS_CURRENT_USER_ID           = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
</script>
