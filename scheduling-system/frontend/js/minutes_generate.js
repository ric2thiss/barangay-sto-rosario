/* minutes_generate.js
   - Calls backend/auth/minutes_generate_api.php
   - Saves extraction (backend does it)
   - Renders structured minutes into the print template via BPSS_MinutesPrint.setMinutes()
*/

/* global bootstrap */
(function () {
  const btn = document.getElementById("btnGenerateMinutesJson");
  if (!btn) return;

  const msgEl = document.getElementById("minutesDetailMsg");

  function setMsg(type, text) {
    msgEl.innerHTML = text
      ? `<div class="alert alert-${type} py-2 mb-0">${text}</div>`
      : "";
  }

  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  btn.addEventListener("click", async () => {
    const id = window.BPSS_Minutes?.activeId;
    if (!id) return;

    setMsg("", "");
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Generating...`;

    try {
      const res = await fetch("backend/auth/minutes_generate_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ minutes_document_id: id }),
      });

      // Handle non-2xx gracefully
      let data = null;
      try {
        data = await res.json();
      } catch {
        throw new Error("Server returned an invalid response.");
      }

      if (!res.ok) {
        throw new Error(data?.message || `Request failed (${res.status}).`);
      }

      if (!data.success) {
        throw new Error(data.message || "Generation failed.");
      }

      // Render into print template (no <pre> JSON box)
      let parsed = {};
      try {
        parsed = JSON.parse(data.extracted_json || "{}");
      } catch {
        parsed = {};
      }

      if (window.BPSS_MinutesPrint?.setMinutes) {
        window.BPSS_MinutesPrint.setMinutes(parsed);
      } else {
      }

      setMsg("success", "Extraction saved.");

      // Refresh list + reload detail (if your minutes_detail.js uses latest extraction)
      window.BPSS_Minutes?.refresh?.();
      window.BPSS_Minutes?.loadDetail?.(id);
    } catch (e) {
      toastError(e?.message || "Generation failed.");
      setMsg("danger", e?.message || "Generation failed.");
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-robot me-2"></i> Generate Minutes`;
    }
  });
})();