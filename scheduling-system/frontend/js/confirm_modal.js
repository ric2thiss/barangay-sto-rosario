/* global bootstrap */
(function () {
  let modal, modalEl, titleEl, msgEl, okBtn, cancelBtn;
  let onConfirm = null;
  let onCancel  = null;

  function toastError(message) {
    if (window.BPSSToast && typeof window.BPSSToast.error === "function") {
      window.BPSSToast.error(message);
    }
  }

  function init() {
    modalEl = document.getElementById("bpssConfirmModal");
    if (!modalEl) return;

    modal = new bootstrap.Modal(modalEl);

    titleEl   = document.getElementById("bpssConfirmTitle");
    msgEl     = document.getElementById("bpssConfirmMessage");
    okBtn     = document.getElementById("bpssConfirmOk");
    cancelBtn = document.getElementById("bpssConfirmCancel");

    okBtn.addEventListener("click", async () => {
      if (!onConfirm) {
        modal.hide();
        return;
      }

      okBtn.disabled = true;
      onCancel = null; // clear so hidden.bs.modal doesn't fire it after confirm

      try {
        await onConfirm();
        modal.hide();
      } catch (e) {
        toastError(e?.message || "Action failed.");
        // keep modal open so user can try again or cancel
      } finally {
        okBtn.disabled = false;
        onConfirm = null;
      }
    });

    modalEl.addEventListener("hidden.bs.modal", () => {
      // fire onCancel if modal was dismissed without confirming
      if (typeof onCancel === "function") {
        const fn = onCancel;
        onCancel = null;
        fn();
      }

      onConfirm = null;
      okBtn.disabled = false;

      // reset defaults
      titleEl.textContent = "Confirm";
      titleEl.className = "modal-title";
      msgEl.className = "alert alert-warning mb-0";
      msgEl.textContent = "Are you sure?";
      okBtn.className = "btn btn-danger";
      okBtn.textContent = "Confirm";
      if (cancelBtn) {
        cancelBtn.textContent = "Cancel";
        cancelBtn.classList.remove("d-none");
      }
    });
  }

  window.BPSSConfirm = {
    /**
     * open({
     *  title,
     *  message,
     *  messageClass,
     *  confirmText,
     *  confirmBtnClass,
     *  onConfirm: async () => { ... }
     * })
     */
    open(opts = {}) {
      if (!modalEl) init();
      if (!modalEl) return;

      const {
        title = "Confirm",
        titleHtml = "",
        titleClass = "modal-title",
        message = "Are you sure?",
        messageHtml = "",
        messageClass = "alert alert-warning mb-0",
        confirmText = "Confirm",
        confirmBtnClass = "btn-danger",
        cancelText = "Cancel",
        hideCancel = false,
        onConfirm: fn = null,
        onCancel:  cancelFn = null
      } = opts;

      titleEl.className = titleClass;
      if (titleHtml) titleEl.innerHTML = titleHtml;
      else titleEl.textContent = title;
      msgEl.className = messageClass;
      if (messageHtml) msgEl.innerHTML = messageHtml;
      else msgEl.textContent = message;

      okBtn.className = `btn ${confirmBtnClass}`;
      okBtn.textContent = confirmText;
      if (cancelBtn) {
        cancelBtn.textContent = cancelText;
        cancelBtn.classList.toggle("d-none", !!hideCancel);
      }

      onConfirm = fn;
      onCancel  = cancelFn;

      modal.show();
    }
  };

  document.addEventListener("DOMContentLoaded", init);
})();
