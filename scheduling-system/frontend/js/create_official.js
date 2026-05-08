/* global bootstrap */
document.addEventListener("DOMContentLoaded", () => {
  const btnAdd = document.getElementById("btnAddOfficial");
  const modalEl = document.getElementById("officialModal");
  const form = document.getElementById("officialForm");
  if (!btnAdd || !modalEl || !form) return;

  const modal = new bootstrap.Modal(modalEl);

  const msgBox = document.getElementById("officialMsg");
  const titleEl = document.getElementById("officialModalTitle");

  const idEl = document.getElementById("officialId");
  const contactSelect = document.getElementById("contactSelect");

  const sigWrap = document.getElementById("signaturePreviewWrap");
  const sigPreview = document.getElementById("signaturePreview");
  const sigNamePreview = document.getElementById("signatureNamePreview");

  const signatureName = document.getElementById("signatureName");
  const signatureFile = document.getElementById("signatureFile");

  const btnRemoveBg = document.getElementById("btnRemoveSigBg");
  const sigBgHint = document.getElementById("sigBgHint");

  const showMsg = (type, text) => {
    msgBox.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${text}</div>`;
  };
  const clearMsg = () => { msgBox.innerHTML = ""; };

  const resetPreview = () => {
    if (!sigWrap) return;
    sigWrap.style.display = "none";
    if (sigPreview) sigPreview.src = "";
    if (sigNamePreview) {
      sigNamePreview.style.display = "none";
      sigNamePreview.textContent = "";
    }
  };

  const openAdd = () => {
    clearMsg();
    form.reset();
    idEl.value = "";
    if (contactSelect) contactSelect.value = "";
    resetPreview();
    titleEl.textContent = "Add Official";
    updateRemoveBgState();
    modal.show();
  };

  const openEdit = (o) => {
    clearMsg();
    form.reset();
    resetPreview();

    titleEl.textContent = "Edit Official";
    idEl.value = o.id || "";

    if (contactSelect) contactSelect.value = o.contact_id || "";

    document.getElementById("fullName").value = o.full_name || "";
    document.getElementById("officialTitle").value = o.official_title || "";
    document.getElementById("committee").value = o.committee || "";
    document.getElementById("termStart").value = o.term_start || "";
    document.getElementById("termEnd").value = o.term_end || "";
    document.getElementById("displayOrder").value = o.display_order || 1;
    document.getElementById("isActive").value = String(o.is_active ?? 1);

    signatureName.value = o.signature_name || "";

    if (o.signature_url && sigWrap && sigPreview) {
      sigWrap.style.display = "block";
      sigPreview.src = o.signature_url;

      if (o.signature_name && sigNamePreview) {
        sigNamePreview.style.display = "block";
        sigNamePreview.textContent = o.signature_name;
      }
    }

    updateRemoveBgState();
    modal.show();
  };

  btnAdd.addEventListener("click", openAdd);

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-edit");
    if (!btn) return;
    try {
      const raw = btn.getAttribute("data-official") || "{}";
      openEdit(JSON.parse(raw));
    } catch (err) {
    }
  });

  signatureName?.addEventListener("input", () => {
    const val = signatureName.value.trim();
    if (!sigWrap || sigWrap.style.display === "none") return;

    if (!val) {
      if (sigNamePreview) {
        sigNamePreview.style.display = "none";
        sigNamePreview.textContent = "";
      }
    } else {
      if (sigNamePreview) {
        sigNamePreview.style.display = "block";
        sigNamePreview.textContent = val;
      }
    }
  });

  signatureFile?.addEventListener("change", () => {
    const file = signatureFile.files?.[0];
    if (!file) {
      updateRemoveBgState();
      return;
    }

    if (sigWrap) sigWrap.style.display = "block";
    if (sigPreview) sigPreview.src = URL.createObjectURL(file);

    const val = signatureName.value.trim();
    if (sigNamePreview) {
      if (val) {
        sigNamePreview.style.display = "block";
        sigNamePreview.textContent = val;
      } else {
        sigNamePreview.style.display = "none";
        sigNamePreview.textContent = "";
      }
    }

    updateRemoveBgState();
  });

  // ===========================
  // Remove BG (remove.bg proxy)
  // ===========================
  function base64ToBlob(base64, mime) {
    const bin = atob(base64);
    const len = bin.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) bytes[i] = bin.charCodeAt(i);
    return new Blob([bytes], { type: mime });
  }

  function setInputFile(input, file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    input.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function updateRemoveBgState() {
    const f = signatureFile?.files?.[0];
    const ok = !!f && /^image\/(png|jpeg|jpg|webp)$/i.test(f.type);
    if (btnRemoveBg) btnRemoveBg.disabled = !ok;
    if (sigBgHint) sigBgHint.textContent = ok ? "Ready to remove background." : "Select an image first.";
  }

  updateRemoveBgState();

  btnRemoveBg?.addEventListener("click", async () => {
    const f = signatureFile?.files?.[0];
    if (!f) return;

    const oldHtml = btnRemoveBg.innerHTML;
    btnRemoveBg.disabled = true;
    btnRemoveBg.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Removing...`;
    if (sigBgHint) sigBgHint.textContent = "Processing...";

    try {
      const fd = new FormData();
      fd.append("image", f);

      const res = await fetch("backend/auth/remove_bg_api.php", {
        method: "POST",
        body: fd
      });

      const ct = res.headers.get("content-type") || "";
      if (!ct.includes("application/json")) {
        const t = await res.text();
        throw new Error(t.slice(0, 200) || "Server returned invalid response.");
      }

      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data?.message || `Failed (${res.status}).`);
      }

      const blob = base64ToBlob(data.data_base64, data.mime_type || "image/png");
      const newFile = new File([blob], data.filename || "signature_nobg.png", {
        type: data.mime_type || "image/png"
      });

      setInputFile(signatureFile, newFile);

      window.BPSSToast?.success("Background removed. Click Save to upload.");
    } catch (err) {
      window.BPSSToast?.error(err?.message || "Background removal failed.");
      updateRemoveBgState();
    } finally {
      btnRemoveBg.innerHTML = oldHtml;
      updateRemoveBgState();
    }
  });

  // ===========================
  // Submit (existing)
  // ===========================
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearMsg();

    const fd = new FormData(form);

    if (!fd.get("full_name")?.toString().trim()) {
      showMsg("danger", "Full Name is required.");
      return;
    }
    if (!fd.get("official_title")?.toString().trim()) {
      showMsg("danger", "Official Title is required.");
      return;
    }

    const submitBtn = form.querySelector('[type="submit"]');
    const origHtml  = submitBtn?.innerHTML;
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving...`; }

    try {
      const res = await fetch("backend/auth/create_official_api.php", {
        method: "POST",
        body: fd
      });

      const ct = res.headers.get("content-type") || "";
      const text = await res.text();

      if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`);
      if (!ct.includes("application/json")) throw new Error(text.slice(0, 200));

      const data = JSON.parse(text);
      if (!data.success) {
        window.BPSSToast?.error(data.message || "Failed to save.");
        return;
      }

      window.BPSSToast?.success(data.message || "Saved.");
      if (window.BPSS_loadOfficials) window.BPSS_loadOfficials();
      setTimeout(() => modal.hide(), 450);
    } catch (err) {
      window.BPSSToast?.error("Server error. Check Console/Network.");
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
    }
  });
});