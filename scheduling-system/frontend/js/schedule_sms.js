document.addEventListener("DOMContentLoaded", function () {
  const contactSearch = document.getElementById("contactSearch");
  const contactList = document.getElementById("contactList");
  const selectedContactsCount = document.getElementById("selectedContactsCount");

  // Auto notify compact UI
  const allowAutoNotify = document.getElementById("allowAutoNotify");
  const offsetMinutes = document.getElementById("offsetMinutes");

  // Hidden fields used by backend
  const allowAutoNotifyHidden = document.getElementById("allowAutoNotifyHidden");
  const offsetMinutesHidden = document.getElementById("offsetMinutesHidden");

  if (!contactList) return;

  function updateSelectedCount() {
    const selected = contactList.querySelectorAll(".notify-contact:checked").length;
    if (selectedContactsCount) selectedContactsCount.textContent = String(selected);
    window.BPSS_buildPreviewMessage?.();
  }

  function syncAutoNotify() {
    const enabled = !!allowAutoNotify?.checked;

    // ✅ enable offset input only if auto notify is checked
    if (offsetMinutes) offsetMinutes.disabled = !enabled;

    // sanitize offset value
    let offset = parseInt(offsetMinutes?.value || "60", 10);
    if (isNaN(offset) || offset < 1) offset = 60;
    if (offsetMinutes) offsetMinutes.value = String(offset);

    // write to hidden inputs for backend
    if (allowAutoNotifyHidden) allowAutoNotifyHidden.value = enabled ? "1" : "0";
    if (offsetMinutesHidden) offsetMinutesHidden.value = String(offset);

    window.BPSS_buildPreviewMessage?.();
  }

  // Contacts change
  contactList.addEventListener("change", (e) => {
    if (e.target && e.target.classList.contains("notify-contact")) {
      updateSelectedCount();
    }
  });

  // Search filter
  if (contactSearch) {
    contactSearch.addEventListener("input", () => {
      const q = contactSearch.value.trim().toLowerCase();
      const items = contactList.querySelectorAll(".contact-item");

      items.forEach(el => {
        const name = (el.getAttribute("data-name") || "").toLowerCase();
        const phone = (el.getAttribute("data-phone") || "").toLowerCase();
        const show = !q || name.includes(q) || phone.includes(q);
        el.classList.toggle("d-none", !show);
      });
    });
  }

  // Groups/SMS toggle updates preview
  document.addEventListener("change", (e) => {
    if (e.target?.classList?.contains("notify-group") || e.target?.id === "notifySms") {
      window.BPSS_buildPreviewMessage?.();
    }
  });

  // ✅ Auto notify toggle + offset typing
  if (allowAutoNotify) allowAutoNotify.addEventListener("change", syncAutoNotify);
  if (offsetMinutes) {
    offsetMinutes.addEventListener("input", syncAutoNotify);
    offsetMinutes.addEventListener("change", syncAutoNotify);
  }

  // init
  updateSelectedCount();
  syncAutoNotify(); // <- this sets disabled/enabled correctly on load
});