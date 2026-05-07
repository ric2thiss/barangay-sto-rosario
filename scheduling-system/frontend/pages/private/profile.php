<?php // frontend/pages/private/profile.php ?>

<div class="container-fluid py-3">

  <div class="mb-3 text-center">
    <h5 class="mb-1 bpss-text-theme">My Profile</h5>
    <div class="text-muted small">Manage your account information and security settings.</div>
  </div>

  <div class="row g-3" style="max-width:720px;margin-left:auto;margin-right:auto;">

    <!-- ── Avatar card ──────────────────────────────────────── -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body d-flex align-items-center gap-4 flex-wrap">

          <!-- Avatar circle -->
          <div class="position-relative" style="flex-shrink:0;">
            <div id="profileAvatarWrap"
                 style="width:90px;height:90px;border-radius:50%;overflow:hidden;
                        background:var(--bpss-primary);display:flex;align-items:center;
                        justify-content:center;font-size:2rem;font-weight:700;
                        color:#fff;cursor:pointer;border:3px solid #dee2e6;"
                 title="Click to change photo">
              <?php
                $pic = $_SESSION['profile_picture'] ?? '';
                if ($pic): ?>
                <img id="profileAvatarImg" src="<?= htmlspecialchars($pic) ?>"
                     style="width:100%;height:100%;object-fit:cover;" alt="Avatar">
              <?php else: ?>
                <span id="profileAvatarInitials">
                  <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                </span>
              <?php endif; ?>
            </div>
            <!-- hidden file input -->
            <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp"
                   class="d-none">
          </div>

          <div class="flex-grow-1">
            <div class="fw-semibold fs-5" id="profileDisplayName">
              <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
            </div>
            <div class="text-muted small text-capitalize"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></div>
            <div class="d-flex gap-2 mt-2 flex-wrap">
              <button class="btn btn-outline-secondary btn-sm" id="btnChangePhoto">
                <i class="bi bi-camera me-1"></i>Change Photo
              </button>
              <button class="btn btn-outline-danger btn-sm d-none" id="btnRemovePhoto">
                <i class="bi bi-trash me-1"></i>Remove
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Account info form ─────────────────────────────────── -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold small py-2">Account Information</div>
        <div class="card-body">
          <div id="infoMsg" class="mb-2"></div>
          <form id="profileInfoForm" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="profileFullName" maxlength="150" required>
            </div>
            <div class="row g-2">
              <div class="col-sm-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="profileEmail" maxlength="150">
              </div>
              <div class="col-sm-6">
                <label class="form-label">Mobile</label>
                <input type="text" class="form-control" id="profileMobile" maxlength="20" placeholder="e.g. 09171234567">
              </div>
            </div>
            <div class="mt-3">
              <label class="form-label">Username</label>
              <input type="text" class="form-control bg-light" id="profileUsername" readonly>
              <div class="form-text">Username cannot be changed.</div>
            </div>
            <div class="mt-3 d-flex justify-content-end">
              <button type="submit" class="btn btn-primary btn-sm" id="btnSaveInfo">
                <i class="bi bi-check-lg me-1"></i>Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ── Change password form ──────────────────────────────── -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold small py-2">Change Password</div>
        <div class="card-body">
          <div id="pwMsg" class="mb-2"></div>
          <form id="profilePwForm" autocomplete="off">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" class="form-control" id="currentPassword" autocomplete="current-password">
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control" id="newPassword" autocomplete="new-password">
              <div class="form-text">Minimum 6 characters.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" autocomplete="new-password">
            </div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-warning btn-sm" id="btnSavePw">
                <i class="bi bi-shield-lock me-1"></i>Update Password
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  window.BPSS_PROFILE_API    = "backend/auth/get_profile_api.php";
  window.BPSS_PROFILE_UPDATE = "backend/auth/update_profile_api.php";
  <?php
    $pic = $_SESSION['profile_picture'] ?? '';
    echo 'window.BPSS_HAS_PICTURE = ' . ($pic ? 'true' : 'false') . ';';
  ?>
</script>
