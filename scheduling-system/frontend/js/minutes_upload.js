/* global bootstrap */
(function () {
  const btnOpen       = document.getElementById("btnOpenMinutesUpload");
  const btnOpenCamera = document.getElementById("btnOpenMinutesCamera");
  const modalEl       = document.getElementById("minutesUploadModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  const form      = document.getElementById("minutesUploadForm");
  const msg       = document.getElementById("minutesUploadMsg");
  const titleEl   = document.getElementById("minutesTitle");
  const filesEl   = document.getElementById("minutesFiles");
  const listEl    = document.getElementById("minutesSelectedFiles");
  const btnUpload = document.getElementById("btnUploadMinutes");

  // Camera elements
  const tabCameraBtn    = document.getElementById("tabCameraBtn");
  const tabUploadBtn    = document.getElementById("tabUploadBtn");
  const videoEl         = document.getElementById("minutesCameraStream");
  const canvas          = document.getElementById("minutesCaptureCanvas");
  const btnCapture      = document.getElementById("btnCapturePhoto");
  const btnSwitch       = document.getElementById("btnSwitchCamera");
  const previewEl       = document.getElementById("capturedPhotosPreview");
  const capturedCountEl = document.getElementById("capturedCount");
  const cameraStatus    = document.getElementById("cameraStatusMsg");

  let stream         = null;
  let facingMode     = "environment";   // rear camera default
  let capturedFiles  = [];              // File objects from camera captures

  function setMsg(type, text) {
    msg.innerHTML = text ? `<div class="alert alert-${type} py-2 mb-0">${text}</div>` : "";
  }

  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  function renderSelected() {
    const files = Array.from(filesEl.files || []);
    if (!files.length) {
      listEl.innerHTML = `<li class="text-muted">No files selected.</li>`;
      return;
    }
    listEl.innerHTML = files.map(f =>
      `<li>${f.name} <span class="text-muted">(${f.type || "unknown"})</span></li>`
    ).join("");
  }

  // ── Camera helpers ────────────────────────────────────────────────────────

  async function startCamera() {
    stopCamera();
    cameraStatus.classList.remove("d-none");
    cameraStatus.textContent = "Starting camera…";
    try {
      // Use { ideal } so mobile browsers don't throw OverconstrainedError
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: facingMode }, width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
      });
    } catch (err) {
      // Fallback: try without facingMode/resolution constraints (covers locked-down devices)
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      } catch (fallbackErr) {
        cameraStatus.textContent = "Camera unavailable: " + (fallbackErr.message || fallbackErr.name);
        return;
      }
    }
    videoEl.srcObject = stream;
    // Explicit play() is required on many mobile browsers
    try { await videoEl.play(); } catch (_) { /* autoplay may handle it */ }
    cameraStatus.classList.add("d-none");
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
    videoEl.srcObject = null;
  }

  function capturePhoto() {
    if (!stream) return;
    canvas.width  = videoEl.videoWidth  || 1280;
    canvas.height = videoEl.videoHeight || 720;
    canvas.getContext("2d").drawImage(videoEl, 0, 0);
    canvas.toBlob(blob => {
      if (!blob) return;
      const idx  = capturedFiles.length + 1;
      const file = new File([blob], `photo_${Date.now()}_${idx}.jpg`, { type: "image/jpeg" });
      capturedFiles.push(file);

      // Thumbnail preview with remove button
      const url  = URL.createObjectURL(blob);
      const wrap = document.createElement("div");
      wrap.className = "position-relative";
      wrap.innerHTML = `
        <img src="${url}" class="rounded border" style="height:72px;width:72px;object-fit:cover;">
        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0
                p-0 lh-1" style="width:18px;height:18px;font-size:.6rem;"
                data-idx="${capturedFiles.length - 1}" title="Remove">×</button>`;
      wrap.querySelector("button").addEventListener("click", function () {
        const i = parseInt(this.dataset.idx, 10);
        capturedFiles[i] = null;          // null = removed
        URL.revokeObjectURL(url);
        wrap.remove();
        capturedCountEl.textContent = capturedFiles.filter(Boolean).length;
      });
      previewEl.appendChild(wrap);
      capturedCountEl.textContent = capturedFiles.filter(Boolean).length;
    }, "image/jpeg", 0.92);
  }

  // ── Tab switching ─────────────────────────────────────────────────────────

  tabCameraBtn?.addEventListener("shown.bs.tab", () => startCamera());
  tabCameraBtn?.addEventListener("hidden.bs.tab", () => stopCamera());

  btnCapture?.addEventListener("click", capturePhoto);

  btnSwitch?.addEventListener("click", () => {
    facingMode = facingMode === "environment" ? "user" : "environment";
    startCamera();
  });

  // ── Open modal helpers ────────────────────────────────────────────────────

  function openUploadTab() {
    modal.show();
    // Ensure upload tab is active
    bootstrap.Tab.getOrCreateInstance(tabUploadBtn).show();
  }

  function openCameraTab() {
    modal.show();
    bootstrap.Tab.getOrCreateInstance(tabCameraBtn).show();
  }

  btnOpen?.addEventListener("click", openUploadTab);
  btnOpenCamera?.addEventListener("click", openCameraTab);
  filesEl?.addEventListener("change", renderSelected);

  // ── Modal reset on close ──────────────────────────────────────────────────

  modalEl.addEventListener("hidden.bs.modal", () => {
    stopCamera();
    setMsg("", "");
    titleEl.value      = "";
    filesEl.value      = "";
    capturedFiles      = [];
    previewEl.innerHTML = "";
    capturedCountEl.textContent = "0";
    renderSelected();
    btnUpload.disabled = false;
    btnUpload.innerHTML = `<i class="bi bi-cloud-arrow-up me-1"></i> Upload`;
    // Reset to upload tab
    bootstrap.Tab.getOrCreateInstance(tabUploadBtn).show();
  });

  // ── Submit ────────────────────────────────────────────────────────────────

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    setMsg("", "");

    const uploadedFiles = Array.from(filesEl.files || []);
    const cameraFiles   = capturedFiles.filter(Boolean);
    const allFiles      = [...uploadedFiles, ...cameraFiles];

    if (!allFiles.length) {
      setMsg("warning", "Please select or capture at least one file.");
      return;
    }

    const fd = new FormData();
    fd.append("title", (titleEl.value || "").trim());
    for (const f of allFiles) fd.append("files[]", f);

    btnUpload.disabled  = true;
    btnUpload.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Uploading...`;

    try {
      const res  = await fetch("backend/auth/minutes_upload_api.php", { method: "POST", body: fd });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Upload failed.");

      window.BPSSToast?.success("Minutes uploaded successfully.");
      window.BPSS_Minutes?.refresh?.();
      setTimeout(() => modal.hide(), 450);
    } catch (err) {
      toastError(err?.message || "Upload failed.");
      setMsg("danger", err?.message || "Upload failed.");
    } finally {
      btnUpload.disabled  = false;
      btnUpload.innerHTML = `<i class="bi bi-cloud-arrow-up me-1"></i> Upload`;
    }
  });

  renderSelected();
})();