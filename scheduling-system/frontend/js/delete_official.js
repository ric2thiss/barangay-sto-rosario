document.addEventListener("DOMContentLoaded", () => {
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-delete");
    if (!btn) return;

    const id = Number(btn.getAttribute("data-id") || 0);
    const name = btn.getAttribute("data-name") || "this official";
    if (!id) return;

    if (!window.BPSSConfirm || !window.BPSSConfirm.open) {
      return;
    }

    window.BPSSConfirm.open({
      title: "Delete Official",
      message: `Delete ${name}? This will also delete the e-signature (if any).`,
      messageClass: "alert alert-danger mb-0",
      confirmText: "Delete",
      confirmBtnClass: "btn-danger",
      onConfirm: async () => {
        const res = await fetch("backend/auth/delete_official_api.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id })
        });

        const data = await res.json();

        if (!data.success) {
          window.BPSSToast?.error(data.message || "Delete failed.");
          // throw so confirm modal stays open (your confirm_modal.js behavior)
          throw new Error(data.message || "Delete failed.");
        }

        window.BPSSToast?.success("Official deleted.");
        window.BPSS_loadOfficials?.();
      }
    });
  });
});