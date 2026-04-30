(function () {
  const apiGet    = window.BPSS_PROFILE_API;
  const apiUpdate = window.BPSS_PROFILE_UPDATE;

  // Elements
  const avatarWrap     = document.getElementById("profileAvatarWrap");
  const avatarImg      = document.getElementById("profileAvatarImg");
  const avatarInitials = document.getElementById("profileAvatarInitials");
  const avatarInput    = document.getElementById("avatarFileInput");
  const btnChangePhoto = document.getElementById("btnChangePhoto");
  const btnRemovePhoto = document.getElementById("btnRemovePhoto");

  const profileInfoForm  = document.getElementById("profileInfoForm");
  const profileFullName  = document.getElementById("profileFullName");
  const profileEmail     = document.getElementById("profileEmail");
  const profileMobile    = document.getElementById("profileMobile");
  const profileUsername  = document.getElementById("profileUsername");
  const profileDisplayName = document.getElementById("profileDisplayName");
  const infoMsg          = document.getElementById("infoMsg");
  const btnSaveInfo      = document.getElementById("btnSaveInfo");

  const profilePwForm  = document.getElementById("profilePwForm");
  const currentPassword = document.getElementById("currentPassword");
  const newPassword     = document.getElementById("newPassword");
  const confirmPassword = document.getElementById("confirmPassword");
  const pwMsg           = document.getElementById("pwMsg");
  const btnSavePw       = document.getElementById("btnSavePw");

  function setMsg(el, type, text) {
    el.innerHTML = text
      ? `<div class="alert alert-${type} py-2 mb-0 small">${text}</div>`
      : '';
  }

  function toast(type, msg) {
    if (window.BPSSToast?.[type]) window.BPSSToast[type](msg);
  }

  function getInitial(name) {
    return (name || 'U').trim().charAt(0).toUpperCase();
  }

  function showAvatar(path) {
    if (path) {
      if (avatarImg) {
        avatarImg.src = path;
      } else {
        // Replace initials span with img
        avatarWrap.innerHTML = `<img id="profileAvatarImg" src="${path}"
          style="width:100%;height:100%;object-fit:cover;" alt="Avatar">`;
      }
      btnRemovePhoto.classList.remove('d-none');
    } else {
      const name = profileDisplayName?.textContent || 'U';
      avatarWrap.innerHTML = `<span id="profileAvatarInitials">${getInitial(name)}</span>`;
      btnRemovePhoto.classList.add('d-none');
    }
  }

  // Show remove button if user already has a picture
  if (window.BPSS_HAS_PICTURE) btnRemovePhoto.classList.remove('d-none');

  // ── Load profile data ───────────────────────────────────────────────────
  async function loadProfile() {
    try {
      const res  = await fetch(apiGet);
      const data = await res.json();
      if (!data.success) return;
      const u = data.data;
      profileFullName.value = u.full_name  || '';
      profileEmail.value    = u.email      || '';
      profileMobile.value   = u.mobile     || '';
      profileUsername.value = u.username   || '';
    } catch (_) {}
  }

  loadProfile();

  // ── Photo change ────────────────────────────────────────────────────────
  btnChangePhoto.addEventListener('click', () => avatarInput.click());
  avatarWrap.addEventListener('click',    () => avatarInput.click());

  avatarInput.addEventListener('change', async () => {
    const file = avatarInput.files[0];
    if (!file) return;

    const fd = new FormData();
    fd.append('action', 'picture');
    fd.append('profile_picture', file);

    btnChangePhoto.disabled = true;
    btnChangePhoto.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Uploading…`;

    try {
      const res  = await fetch(apiUpdate, { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { toast('error', data.message); return; }

      showAvatar(data.path);
      // Update topbar avatar too
      updateTopbarAvatar(data.path, null);
      toast('success', 'Profile picture updated.');
    } catch (_) {
      toast('error', 'Upload failed. Please try again.');
    } finally {
      btnChangePhoto.disabled = false;
      btnChangePhoto.innerHTML = `<i class="bi bi-camera me-1"></i>Change Photo`;
      avatarInput.value = '';
    }
  });

  // ── Remove photo ────────────────────────────────────────────────────────
  btnRemovePhoto.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'remove_picture');
    try {
      const res  = await fetch(apiUpdate, { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { toast('error', data.message); return; }
      showAvatar(null);
      updateTopbarAvatar(null, profileFullName.value);
      toast('success', 'Profile picture removed.');
    } catch (_) {}
  });

  // ── Save info ────────────────────────────────────────────────────────────
  profileInfoForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg(infoMsg, '', '');

    const fd = new FormData();
    fd.append('action',    'info');
    fd.append('full_name', profileFullName.value.trim());
    fd.append('email',     profileEmail.value.trim());
    fd.append('mobile',    profileMobile.value.trim());

    btnSaveInfo.disabled = true;
    btnSaveInfo.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving…`;

    try {
      const res  = await fetch(apiUpdate, { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { setMsg(infoMsg, 'danger', data.message); return; }

      const newName = profileFullName.value.trim();
      profileDisplayName.textContent = newName;
      updateTopbarAvatar(null, newName); // refresh initials if no picture
      setMsg(infoMsg, 'success', 'Profile updated successfully.');
      toast('success', 'Profile updated.');
    } catch (_) {
      setMsg(infoMsg, 'danger', 'Failed to save. Please try again.');
    } finally {
      btnSaveInfo.disabled = false;
      btnSaveInfo.innerHTML = `<i class="bi bi-check-lg me-1"></i>Save Changes`;
    }
  });

  // ── Change password ──────────────────────────────────────────────────────
  profilePwForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    setMsg(pwMsg, '', '');

    const fd = new FormData();
    fd.append('action',           'password');
    fd.append('current_password', currentPassword.value);
    fd.append('new_password',     newPassword.value);
    fd.append('confirm_password', confirmPassword.value);

    btnSavePw.disabled = true;
    btnSavePw.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Updating…`;

    try {
      const res  = await fetch(apiUpdate, { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { setMsg(pwMsg, 'danger', data.message); return; }

      profilePwForm.reset();
      setMsg(pwMsg, 'success', 'Password changed successfully.');
      toast('success', 'Password changed.');
    } catch (_) {
      setMsg(pwMsg, 'danger', 'Failed to update password.');
    } finally {
      btnSavePw.disabled = false;
      btnSavePw.innerHTML = `<i class="bi bi-shield-lock me-1"></i>Update Password`;
    }
  });

  // ── Update topbar avatar without page reload ─────────────────────────────
  function updateTopbarAvatar(picPath, name) {
    const topAvatar = document.getElementById("topbarAvatar");
    if (!topAvatar) return;
    // Preserve the badge span
    const badge = topAvatar.querySelector('.bpss-avatar-badge');
    if (picPath) {
      // Remove old img/initial, keep badge
      const img = topAvatar.querySelector('img');
      const init = topAvatar.querySelector('#topbarAvatarInitial');
      if (img)  img.remove();
      if (init) init.remove();
      const el = document.createElement('img');
      el.id = 'topbarAvatarImg';
      el.src = picPath;
      el.alt = 'Avatar';
      el.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
      topAvatar.insertBefore(el, badge);
    } else if (name) {
      const initial = (name || 'U').trim().charAt(0).toUpperCase();
      const initEl = topAvatar.querySelector('#topbarAvatarInitial');
      if (initEl) {
        initEl.textContent = initial;
      } else {
        // Was showing img, switched to initials
        const img = topAvatar.querySelector('img');
        if (img) img.remove();
        const sp = document.createElement('span');
        sp.id = 'topbarAvatarInitial';
        sp.textContent = initial;
        topAvatar.insertBefore(sp, badge);
      }
    }
  }
})();
