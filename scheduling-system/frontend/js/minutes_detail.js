/* global bootstrap */
(function () {
  const modalEl = document.getElementById("minutesDetailModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  const titleEl = document.getElementById("minutesDetailTitle");
  const metaEl  = document.getElementById("minutesDetailMeta");
  const msgEl   = document.getElementById("minutesDetailMsg");
  const filesEl = document.getElementById("minutesFilesList");

  function setMsg(type, text) {
    if (!msgEl) return; // ✅ prevent null crash
    msgEl.innerHTML = text
      ? `<div class="alert alert-${type} py-2 mb-0">${text}</div>`
      : "";
  }

  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function fmtDate(dt) {
    if (!dt) return "";
    const d = new Date(String(dt).replace(" ", "T"));
    return isNaN(d) ? String(dt) : d.toLocaleString("en-PH");
  }

  function renderFiles(files) {
    if (!filesEl) return; // ✅ prevent null crash

    const arr = Array.isArray(files) ? files : [];
    if (!arr.length) {
      filesEl.innerHTML = `<div class="text-muted">No files.</div>`;
      return;
    }

    filesEl.innerHTML = arr
      .map(
        (f) => `
        <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
          <div class="me-2">
            <div class="fw-semibold">${escapeHtml(f.original_name || "")}</div>
            <div class="small text-muted">
              ${escapeHtml(f.mime_type || "unknown")} • ${escapeHtml(f.file_size || 0)} bytes
            </div>
          </div>
          <a class="btn btn-outline-secondary btn-sm"
             href="${escapeHtml(f.public_url || "#")}"
             target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i>
          </a>
        </div>
      `
      )
      .join("");
  }

  function renderMinutesFromJson(jsonText) {
    let parsed = {};
    try {
      parsed = JSON.parse(jsonText || "{}");
    } catch {
      parsed = {};
    }

    if (window.BPSS_MinutesPrint?.setMinutes) {
      window.BPSS_MinutesPrint.setMinutes(parsed);
    } else {
    }
  }

  window.BPSS_Minutes = window.BPSS_Minutes || {};
  window.BPSS_Minutes.loadDetail = async function (id) {
    if (!id) return;

    window.BPSS_Minutes.activeId = id;

    setMsg("", "");
    if (titleEl) titleEl.textContent = "Minutes Details";
    if (metaEl) metaEl.textContent = "";
    if (filesEl) filesEl.innerHTML = `<div class="text-muted">Loading...</div>`;

    // show blank template first
    renderMinutesFromJson("{}");

    modal.show();

    try {
      const res = await fetch(
        `backend/auth/minutes_detail_api.php?id=${encodeURIComponent(id)}`,
        { cache: "no-store" }
      );

      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Failed to load details.");

      const doc = data.doc || {};
      if (titleEl) titleEl.textContent = doc.title || "Minutes Details";
      if (metaEl) metaEl.textContent = `Status: ${doc.status || ""} • Created: ${fmtDate(doc.created_at)}`;

      renderFiles(data.files);

      const jsonText =
        data.latest_extraction_json ||
        data.latest_extraction ||
        data.extracted_json ||
        "";

      if (jsonText) {
        renderMinutesFromJson(jsonText);
      } else {
        renderMinutesFromJson("{}");
        setMsg("secondary", "No extraction yet. Click Generate Minutes.");
      }
    } catch (e) {
      toastError(e?.message || "Failed to load details.");
      setMsg("danger", e?.message || "Failed to load details.");
      if (filesEl) filesEl.innerHTML = `<div class="text-danger">Failed to load.</div>`;
      renderMinutesFromJson("{}");
    }
  };
})();