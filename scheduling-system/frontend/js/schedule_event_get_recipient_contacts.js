document.addEventListener("DOMContentLoaded", function () {

  const CONTACTS_URL = window.BPSS_SCHED_EVENT_RECIP_CONTACTS_API_URL;

  const contactList = document.getElementById("contactList");
  const contactSearch = document.getElementById("contactSearch");
  const selectedCount = document.getElementById("selectedContactsCount");

  if (!CONTACTS_URL || !contactList) return;

  // ✅ persistent selection set (used for edit restore)
  window.BPSS_schedEvent_selectedContactIds = window.BPSS_schedEvent_selectedContactIds || new Set();
  window.BPSS_schedEvent_excludedContactIds = window.BPSS_schedEvent_excludedContactIds || new Set();

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success:false, message:"Invalid response" }; }
  }

  function updateSelectedCount() {
    const checked = contactList.querySelectorAll(".notify-contact:checked");
    if (selectedCount) selectedCount.textContent = checked.length;
  }

  function renderContacts(data) {
    const set = window.BPSS_schedEvent_selectedContactIds;
    const excluded = window.BPSS_schedEvent_excludedContactIds;

    contactList.innerHTML = (data || []).map(c => {
      const idStr = String(c.id);
      const checked = set.has(idStr) && !excluded.has(idStr) ? "checked" : "";
      return `
        <label class="d-flex align-items-center gap-2 py-1 contact-item"
               data-name="${String(c.full_name || "").replace(/"/g, "&quot;")}"
               data-phone="${String(c.mobile || "").replace(/"/g, "&quot;")}">
          <input class="form-check-input notify-contact"
                 type="checkbox"
                 name="notify_contacts[]"
                 value="${c.id}"
                 ${checked}>
          <span class="flex-grow-1">
            ${c.full_name}
            <small class="text-muted d-block">${c.mobile || ""}</small>
          </span>
        </label>
      `;
    }).join("");

    contactList.querySelectorAll(".notify-contact")
      .forEach(cb => cb.addEventListener("change", () => {
        const set = window.BPSS_schedEvent_selectedContactIds;
        const excluded = window.BPSS_schedEvent_excludedContactIds;
        const idStr = String(cb.value);
        if (cb.checked) {
          set.add(idStr);
          excluded.delete(idStr);
        } else {
          set.delete(idStr);
        }

        updateSelectedCount();
        window.BPSS_buildPreviewMessage?.(false);
      }));

    updateSelectedCount();
  }

  async function loadContacts() {
    const params = new URLSearchParams();

    // search
    const q = (contactSearch?.value || "").trim();
    if (q) params.set("q", q);

    // group names selected
    const groups = window.BPSS_schedEvent_getSelectedGroupNames
      ? window.BPSS_schedEvent_getSelectedGroupNames()
      : [];

    groups.forEach(name => params.append("groups[]", name));

    const resp = await fetch(`${CONTACTS_URL}?${params.toString()}`, {
      method: "GET",
      headers: { "Accept": "application/json" }
    });

    const data = await safeJson(resp);

    if (data.success && Array.isArray(data.data)) renderContacts(data.data);
    else renderContacts([]);
  }

  // expose so groups file can reload contacts
  window.BPSS_schedEvent_reloadContacts = loadContacts;

  // ✅ edit helper: restore selected contacts from sms_outbox
  window.BPSS_schedEvent_applySelectedContacts = async function (eventId) {
    try {
      const resp = await fetch(`backend/auth/schedule_event_get_selected_contacts_api.php?event_id=${encodeURIComponent(eventId)}`, {
        headers: { "Accept": "application/json" }
      });
      const data = await safeJson(resp);

      if (data.success && Array.isArray(data.data)) {
        window.BPSS_schedEvent_selectedContactIds = new Set(data.data.map(String));
        window.BPSS_schedEvent_excludedContactIds = new Set();
      } else {
        window.BPSS_schedEvent_selectedContactIds = new Set();
        window.BPSS_schedEvent_excludedContactIds = new Set();
      }

      // reload list so checked states apply
      await loadContacts();

      // rebuild preview
      window.BPSS_buildPreviewMessage?.(true);

    } catch (e) {
      // ignore
    }
  };

  // initial load
  loadContacts();

  // search
  if (contactSearch) contactSearch.addEventListener("input", loadContacts);
});
