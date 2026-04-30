(function () {
  function toastError(message) {
    if (window.BPSSToast?.error) window.BPSSToast.error(message);
    else alert(message);
  }

  window.BPSS_Minutes = window.BPSS_Minutes || {};
  window.BPSS_Minutes.requestDelete = function (id) {
    if (!id) return;

    const run = () => doDelete(id);

    // Use your confirm modal if available
    if (window.BPSSConfirm?.open) {
      window.BPSSConfirm.open({
        title: "Delete Minutes",
        message: "Delete this minutes record? This will remove files and extractions.",
        okText: "Delete",
        okClass: "btn-danger",
        onConfirm: run
      });
      return;
    }

    // fallback
    if (confirm("Delete this minutes record? This will remove files and extractions.")) run();
  };

  async function doDelete(id) {
    try {
      const res = await fetch("backend/auth/minutes_delete_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
      });

      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Delete failed.");

      if (window.BPSSToast?.success) window.BPSSToast.success("Minutes deleted.");
      window.BPSS_Minutes?.refresh?.();
    } catch (e) {
      toastError(e?.message || "Delete failed.");
      throw e;
    }
  }
})();