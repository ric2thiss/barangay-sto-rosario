/* global bootstrap */
document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("eventModal");
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);

  const form = document.getElementById("eventForm");
  const msgBox = document.getElementById("eventMsg");

  const btnAddEvent = document.getElementById("btnAddEvent");
  const btnToday = document.getElementById("btnToday");

  const evId = document.getElementById("eventId");
  const evTitle = document.getElementById("evTitle");
  const evStart = document.getElementById("evStart");
  const evEnd = document.getElementById("evEnd");

  const btnResendSms = document.getElementById("btnResendSms");

  // Delete button beside "Step 1 of 4"
  const btnDeleteEvent = document.getElementById("btnDeleteEvent");
  const isEditEl = document.getElementById("isEdit");

  // inline message helper (inside modal)
  window.BPSS_toastMsg = function (text, type = "info") {
    if (!msgBox) return;
    const cls =
      type === "danger"
        ? "alert-danger"
        : type === "success"
        ? "alert-success"
        : type === "warning"
        ? "alert-warning"
        : "alert-info";

    msgBox.innerHTML = `<div class="alert ${cls} py-2 mb-0">${text}</div>`;
  };

  function clearMsg() {
    if (msgBox) msgBox.innerHTML = "";
  }

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  async function autoRemoveConflictContacts(conflicts = []) {
    const ids = Array.from(new Set((conflicts || []).map((c) => String(c.contact_id || "")).filter(Boolean)));
    if (!ids.length) return;

    window.BPSS_schedEvent_selectedContactIds = window.BPSS_schedEvent_selectedContactIds || new Set();
    window.BPSS_schedEvent_excludedContactIds = window.BPSS_schedEvent_excludedContactIds || new Set();

    ids.forEach((id) => {
      window.BPSS_schedEvent_selectedContactIds.delete(id);
      window.BPSS_schedEvent_excludedContactIds.add(id);
    });

    if (typeof window.BPSS_schedEvent_reloadContacts === "function") {
      await window.BPSS_schedEvent_reloadContacts();
    }

    window.BPSS_buildPreviewMessage?.(true);
    window.BPSSToast?.warning("Conflicting contacts were removed from this schedule.");
  }

  function buildConflictMessageHtml(conflicts = []) {
    if (!Array.isArray(conflicts) || !conflicts.length) {
      return `<div>Cannot proceed because selected contacts already have overlapping schedules.</div>`;
    }

    const items = conflicts.map((conflict) => {
      const start = new Date(conflict.start_datetime || "");
      const end = new Date(conflict.end_datetime || "");
      const fmt = (d) => Number.isNaN(d.getTime())
        ? ""
        : new Intl.DateTimeFormat("en-PH", {
            month: "short",
            day: "2-digit",
            year: "numeric",
            hour: "numeric",
            minute: "2-digit",
          }).format(d);

      return `
        <li class="mb-2">
          <strong>${escapeHtml(conflict.contact_name || "Contact")}</strong><br>
          <span class="small">
            Already scheduled for <strong>${escapeHtml(conflict.event_title || "another event")}</strong><br>
            ${escapeHtml(fmt(start))} - ${escapeHtml(fmt(end))}
          </span>
        </li>
      `;
    }).join("");

    return `
      <div class="mb-2">Cannot proceed because these contacts already have overlapping schedules:</div>
      <ul class="ps-3 mb-0">${items}</ul>
    `;
  }

  function showConflictPopup(message, conflicts = []) {
    if (window.BPSSConfirm && typeof window.BPSSConfirm.open === "function") {
      window.BPSSConfirm.open({
        titleHtml: `<i class="bi bi-exclamation-triangle-fill me-2"></i>Schedule Conflict`,
        titleClass: "modal-title w-100 text-center text-danger",
        message,
        messageHtml: buildConflictMessageHtml(conflicts),
        messageClass: "alert alert-danger mb-0",
        confirmText: "Auto Remove Conflicts",
        confirmBtnClass: "btn-warning",
        cancelText: "Close",
        hideCancel: false,
        onConfirm: async () => autoRemoveConflictContacts(conflicts),
      });
      return;
    }

    window.BPSSToast?.error(message);
  }

  function validateModalBasics() {
    clearMsg();

    if (!evTitle || !evTitle.value.trim()) {
      window.BPSS_toastMsg?.("Subject is required.", "danger");
      evTitle?.focus();
      return false;
    }

    if (!evStart || !evStart.value) {
      window.BPSS_toastMsg?.("Start date/time is required.", "danger");
      evStart?.focus();
      return false;
    }

    if (!evEnd || !evEnd.value) {
      window.BPSS_toastMsg?.("End date/time is required.", "danger");
      evEnd?.focus();
      return false;
    }

    const s = new Date(evStart.value);
    const e = new Date(evEnd.value);

    if (isNaN(s.getTime()) || isNaN(e.getTime())) {
      window.BPSS_toastMsg?.("Please enter valid start and end date/time.", "danger");
      evEnd?.focus();
      return false;
    }

    if (e <= s) {
      window.BPSS_toastMsg?.("End date/time must be after start.", "danger");
      evEnd?.focus();
      return false;
    }

    return true;
  }

  function dtLocalNowPlus(minutes) {
    const d = new Date();
    d.setMinutes(d.getMinutes() + minutes);
    d.setSeconds(0);
    d.setMilliseconds(0);

    const pad = (n) => String(n).padStart(2, "0");
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth() + 1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const mi = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
  }

  function isoToLocalInput(iso) {
    if (!iso) return "";
    const d = new Date(iso);
    if (isNaN(d.getTime())) return "";

    const pad = (n) => String(n).padStart(2, "0");
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth() + 1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const mi = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
  }

  // toggles resend + delete, sync hidden isEdit field
  function setEditMode(isEdit) {
    if (btnResendSms) btnResendSms.classList.toggle("d-none", !isEdit);
    if (btnDeleteEvent) btnDeleteEvent.classList.toggle("d-none", !isEdit);
    if (isEditEl) isEditEl.value = isEdit ? "1" : "0";
  }

  async function resetRecipients() {
    // clear group checks (groups UI exists immediately)
    document.querySelectorAll(".notify-group").forEach((x) => (x.checked = false));

    // clear selected contact Set (important: contacts list may be virtual/reloaded)
    window.BPSS_schedEvent_selectedContactIds = new Set();
    window.BPSS_schedEvent_excludedContactIds = new Set();

    // reload contact list so checkboxes reflect cleared Set
    if (typeof window.BPSS_schedEvent_reloadContacts === "function") {
      await window.BPSS_schedEvent_reloadContacts();
    } else {
      // fallback if list is already in DOM
      document.querySelectorAll(".notify-contact").forEach((x) => (x.checked = false));
    }

    // refresh preview
    window.BPSS_buildPreviewMessage?.(true);
  }

  function escHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;");
  }

  function formatBytes(bytes) {
    if (!bytes) return "";
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / 1048576).toFixed(1) + " MB";
  }

  function showExistingAttachments(attachments) {
    const container = document.getElementById("existingAttachments");
    if (!container) return;
    if (!attachments || !attachments.length) {
      container.innerHTML = "";
      return;
    }
    const icons = { image: "bi-file-image", pdf: "bi-file-pdf", doc: "bi-file-word",
                    docx: "bi-file-word", xls: "bi-file-excel", xlsx: "bi-file-excel" };
    const rows = attachments.map(a => {
      const icon = icons[a.file_type] || "bi-file-earmark";
      const size = a.file_size ? ` <span class="text-muted">(${formatBytes(Number(a.file_size))})</span>` : "";
      return `<div class="d-flex align-items-center gap-2 py-1 border-bottom small">
        <i class="bi ${escHtml(icon)} text-secondary"></i>
        <a href="${escHtml(a.stored_path)}" target="_blank" class="text-truncate flex-grow-1">${escHtml(a.original_name)}</a>
        ${size}
      </div>`;
    }).join("");
    container.innerHTML = `<div class="small fw-semibold text-muted mb-1">Uploaded files:</div>${rows}`;
  }

  async function resetFormDefaults() {
    form.reset();
    clearMsg();

    if (evId) evId.value = "";
    setEditMode(false);

    // default times
    if (evStart) evStart.value = dtLocalNowPlus(10);
    if (evEnd) evEnd.value = dtLocalNowPlus(70);

    // clear agendas
    if (typeof window.BPSS_agendaReset === "function") {
      window.BPSS_agendaReset();
    }

    // clear existing attachments display
    showExistingAttachments([]);

    // clear recipients (groups + contacts)
    await resetRecipients();

    // step to 1
    if (typeof window.BPSS_showStep === "function") {
      window.BPSS_showStep(1);
    }

    // rebuild preview
    window.BPSS_buildPreviewMessage?.(true);

    setTimeout(() => evTitle?.focus(), 50);
  }

  async function openModalNewEvent(prefill = {}) {
    await resetFormDefaults();

    if (prefill.start && evStart) evStart.value = prefill.start;
    if (prefill.end && evEnd) evEnd.value = prefill.end;

    window.BPSS_buildPreviewMessage?.(true);
    modal.show();
  }

  // EDIT: restore contacts from sms_outbox
  async function openModalEditEvent(data = {}) {
    await resetFormDefaults();

    const id = data.id || "";
    if (!id) {
      window.BPSSToast?.error("Invalid event.");
      return;
    }

    if (evId) evId.value = id;
    if (evTitle) evTitle.value = data.title || "";

    if (evStart) evStart.value = isoToLocalInput(data.start);
    if (evEnd) evEnd.value = isoToLocalInput(data.end);

    // optional fields
    const locEl = document.getElementById("evLocation");
    const statusEl = document.getElementById("evStatus");
    if (locEl && data.location != null) locEl.value = data.location;
    if (statusEl && data.status != null) statusEl.value = data.status;

    setEditMode(true);

    // go to step 1
    window.BPSS_showStep?.(1);

    // restore checked contacts from sms_outbox
    if (typeof window.BPSS_schedEvent_applySelectedContacts === "function") {
      await window.BPSS_schedEvent_applySelectedContacts(id);
    } else {
      await window.BPSS_schedEvent_reloadContacts?.();
    }

    // fetch agendas + attachments from DB
    try {
      const detailUrl = window.BPSS_EVENT_DETAIL_API_URL ||
                        "backend/auth/get_event_detail_api.php";
      const res = await fetch(`${detailUrl}?event_id=${encodeURIComponent(id)}`);
      const detail = await res.json().catch(() => ({}));
      if (detail.success) {
        window.BPSS_agendaRestore?.(detail.agendas || []);
        showExistingAttachments(detail.attachments || []);
      }
    } catch (_) { /* non-fatal */ }

    // force preview rebuild after restore
    window.BPSS_buildPreviewMessage?.(true);

    modal.show();
  }

  if (btnAddEvent) btnAddEvent.addEventListener("click", () => openModalNewEvent());

  if (btnToday) {
    btnToday.addEventListener("click", () => {
      if (window.BPSS_calendar && typeof window.BPSS_calendar.today === "function") {
        window.BPSS_calendar.today();
      }
    });
  }

  // ✅ DELETE EVENT (Global Confirm Modal + Global Toast)
  if (btnDeleteEvent) {
    btnDeleteEvent.addEventListener("click", () => {
      clearMsg();

      const id = parseInt(evId?.value || "0", 10);
      if (!id) {
        window.BPSSToast?.warning("No event selected.");
        return;
      }

      if (!window.BPSSConfirm || typeof window.BPSSConfirm.open !== "function") {
        window.BPSSToast?.error("Confirm modal is not loaded.");
        return;
      }

      window.BPSSConfirm.open({
        title: "Delete Schedule Event",
        message: "Are you sure you want to delete this schedule event? This cannot be undone.",
        messageClass: "alert alert-warning mb-0",
        confirmText: "Delete",
        confirmBtnClass: "btn-danger",
        onConfirm: async () => {
          const origHtml = btnDeleteEvent.innerHTML;
          btnDeleteEvent.disabled = true;
          btnDeleteEvent.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Deleting...`;

          try {
            const url =
              window.BPSS_EVENT_DELETE_API_URL || "backend/auth/delete_schedule_api.php";

            const resp = await fetch(url, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ event_id: id }),
            });

            const data = await resp
              .json()
              .catch(() => ({ success: false, message: "Invalid response" }));

            if (!data || data.success !== true) {
              throw new Error(data?.message || "Failed to delete event.");
            }

            window.BPSSToast?.success("Event deleted successfully.");
            window.BPSS_calendar?.refetchEvents?.();
            modal.hide();
          } finally {
            btnDeleteEvent.disabled = false;
            btnDeleteEvent.innerHTML = origHtml;
          }
        },
      });
    });
  }

  // ✅ Submit: CREATE or UPDATE based on event_id
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearMsg();

    if (!validateModalBasics()) return;

    const notifySmsEl = document.getElementById("notifySms");
    const isEdit      = document.getElementById("isEdit")?.value === "1";

    // Inner save function — called after any confirmation
    const btnSave = document.getElementById("btnSave");
    async function doSave() {
      const origHtml = btnSave?.innerHTML;
      if (btnSave) { btnSave.disabled = true; btnSave.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving...`; }

      try {
        window.BPSS_agendaSync?.();
        window.BPSS_buildPreviewMessage?.(true);

        const fd = new FormData(form);
        const excludedIds = Array.from(window.BPSS_schedEvent_excludedContactIds || []);
        excludedIds.forEach((id) => fd.append("excluded_conflict_contacts[]", id));
        const id = fd.get("event_id");

        let res;
        if (id) {
          if (typeof window.BPSS_updateScheduleEvent !== "function") {
            throw new Error("BPSS_updateScheduleEvent is not loaded.");
          }
          res = await window.BPSS_updateScheduleEvent(fd);
        } else {
          if (typeof window.BPSS_createScheduleEvent !== "function") {
            throw new Error("BPSS_createScheduleEvent is not loaded.");
          }
          res = await window.BPSS_createScheduleEvent(fd);
        }

        if (!res || res.success !== true) {
          if (res?.message) {
            window.BPSS_toastMsg?.(res.message, "danger");
          }
          if (Array.isArray(res?.conflicts) && res.conflicts.length) {
            showConflictPopup(res.message || "Selected contacts have conflicting schedules.", res.conflicts);
          } else {
            window.BPSSToast?.error(res?.message || "Failed to save event.");
          }
          return;
        }

        window.BPSSToast?.success(id ? "Event updated successfully." : "Event saved successfully.");
        window.BPSS_calendar?.refetchEvents?.();
        setTimeout(() => modal.hide(), 350);
      } catch (err) {
        window.BPSSToast?.error(err?.message || "Unexpected error.");
      } finally {
        if (btnSave) { btnSave.disabled = false; btnSave.innerHTML = origHtml; }
      }
    }

    // Editing with SMS checked → ask before sending
    if (isEdit && notifySmsEl?.checked) {
      if (window.BPSSConfirm && typeof window.BPSSConfirm.open === "function") {
        window.BPSSConfirm.open({
          title: "Send SMS Update",
          message: "Are you sure you want to send the changes via SMS to recipients?",
          messageClass: "alert alert-info mb-0",
          confirmText: "Yes",
          confirmBtnClass: "btn-primary",
          cancelText: "No",
          onConfirm: async () => {
            await doSave();
          },
          onCancel: async () => {
            if (notifySmsEl) notifySmsEl.checked = false;
            await doSave();
          },
        });
      } else {
        // Fallback: native confirm
        const confirmed = confirm("Are you sure you want to send the changes via SMS to recipients?");
        if (!confirmed && notifySmsEl) notifySmsEl.checked = false;
        await doSave();
      }
      return;
    }

    await doSave();
  });

  // ✅ Resend SMS button
  if (btnResendSms) {
    btnResendSms.addEventListener("click", async () => {
      clearMsg();

      const origHtml = btnResendSms.innerHTML;
      btnResendSms.disabled = true;
      btnResendSms.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Sending...`;

      try {
        const id = parseInt(evId?.value || "0", 10);
        if (!id) {
          window.BPSSToast?.warning("No event selected.");
          return;
        }

        if (typeof window.BPSS_resendScheduleSms !== "function") {
          throw new Error("BPSS_resendScheduleSms is not loaded.");
        }

        const res = await window.BPSS_resendScheduleSms(id);

        if (!res || res.success !== true) {
          const msg = res && res.message ? res.message : "Failed to resend SMS.";
          window.BPSSToast?.error(msg);
          return;
        }

        window.BPSSToast?.success("SMS resent successfully.");
      } catch (err) {
        window.BPSSToast?.error(err?.message || "Unexpected error.");
      } finally {
        btnResendSms.disabled = false;
        btnResendSms.innerHTML = origHtml;
      }
    });
  }

  // expose open functions
  window.BPSS_openModalNewEvent = openModalNewEvent;
  window.BPSS_openModalEditEvent = openModalEditEvent;
});
