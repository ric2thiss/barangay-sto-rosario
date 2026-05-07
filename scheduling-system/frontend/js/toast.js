/* global bootstrap */
(function () {
  let toastEl, msgEl;

  function init() {
    toastEl = document.getElementById("bpssToast");
    msgEl = document.getElementById("bpssToastMsg");
  }

  function show(message, variant = "primary", delay = 3000) {
    if (!toastEl || !msgEl) init();
    if (!toastEl || !msgEl) return;

    toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
    msgEl.textContent = message;

    const t = new bootstrap.Toast(toastEl, { delay });
    t.show();
  }

  window.BPSSToast = {
    show,
    success: (m, d) => show(m, "success", d),
    error: (m, d) => show(m, "danger", d),
    info: (m, d) => show(m, "info", d),
    warning: (m, d) => show(m, "warning", d),
  };

  // Alias used across the codebase: BPSS_showToast(message, type)
  window.BPSS_showToast = function (message, type) {
    const variant = type === "danger"  ? "danger"
                  : type === "warning" ? "warning"
                  : type === "success" ? "success"
                  : "info";
    show(message, variant);
  };

  // Show a flash stored in sessionStorage (e.g. post-login redirect)
  function showFlash() {
    try {
      const raw = sessionStorage.getItem("bpss_flash");
      if (!raw) return;
      sessionStorage.removeItem("bpss_flash");
      const { msg, type } = JSON.parse(raw);
      if (msg) show(msg, type || "info");
    } catch (_) {}
  }

  document.addEventListener("DOMContentLoaded", () => { init(); showFlash(); });
})();