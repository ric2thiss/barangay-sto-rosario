document.addEventListener("DOMContentLoaded", function () {
  window.BPSS_deleteContact = function (id) {
    const run = async () => {
      const fd = new FormData();
      fd.append("id", id);

      try {
        const resp = await fetch("backend/auth/delete_contact_api.php", {
          method: "POST",
          body: fd
        });
        const data = await resp.json();

        if (!data.success) {
          window.BPSS_showToast?.(data.message || "Delete failed.", "danger");
          return;
        }
        window.BPSS_showToast?.("Contact deleted.", "success");
        window.BPSS_reloadContactsTable?.();
      } catch {
        window.BPSS_showToast?.("Network error.", "danger");
      }
    };

    if (window.BPSS_confirm) {
      window.BPSS_confirm("Delete this contact? This cannot be undone.", run);
    } else if (confirm("Delete this contact?")) {
      run();
    }
  };
});
