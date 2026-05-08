(function () {
  const btn = document.getElementById("btnEditMinutesTitle");
  if (!btn) return;

  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  btn.addEventListener("click", async () => {
    const id = window.BPSS_Minutes?.activeId;
    if (!id) return;

    const newTitle = prompt("Enter new title:");
    if (newTitle === null) return;

    const title = newTitle.trim();
    if (!title) {
      toastError("Title cannot be empty.");
      return;
    }

    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving...`;

    try {
      const res = await fetch("backend/auth/minutes_update_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id, title })
      });

      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Update failed.");

      if (window.BPSSToast?.success) window.BPSSToast.success("Title updated.");
      window.BPSS_Minutes?.refresh?.();
      window.BPSS_Minutes?.loadDetail?.(id);
    } catch (e) {
      toastError(e?.message || "Update failed.");
    } finally {
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }
  });
})();