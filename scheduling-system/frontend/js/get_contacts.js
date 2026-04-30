document.addEventListener("DOMContentLoaded", function () {

  const CONTACTS_URL = window.BPSS_CONTACTS_API_URL;
  const contactList = document.getElementById("contactList");
  const contactSearch = document.getElementById("contactSearch");
  const selectedCount = document.getElementById("selectedContactsCount");

  if (!CONTACTS_URL || !contactList) return;

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success:false }; }
  }

  function updateSelectedCount() {
    const checked = contactList.querySelectorAll(".notify-contact:checked");
    if (selectedCount) selectedCount.textContent = checked.length;
  }

  function renderContacts(data) {
    contactList.innerHTML = data.map(c => `
      <label class="d-flex align-items-center gap-2 py-1">
        <input class="form-check-input notify-contact"
               type="checkbox"
               name="notify_contacts[]"
               value="${c.id}">
        <span class="flex-grow-1">
          ${c.full_name}
          <small class="text-muted d-block">${c.mobile || ""}</small>
        </span>
      </label>
    `).join("");

    contactList.querySelectorAll(".notify-contact")
      .forEach(cb => cb.addEventListener("change", updateSelectedCount));
  }

  async function loadContacts(query = "") {
    const params = new URLSearchParams();
    if (query) params.set("q", query);

    const resp = await fetch(`${CONTACTS_URL}?${params.toString()}`, {
      method: "GET",
      headers: { "Accept": "application/json" }
    });

    const data = await safeJson(resp);
    if (data.success && Array.isArray(data.data)) {
      renderContacts(data.data);
    }
  }

  // initial load
  loadContacts();

  // search
  if (contactSearch) {
    contactSearch.addEventListener("input", function () {
      loadContacts(this.value.trim());
    });
  }

});