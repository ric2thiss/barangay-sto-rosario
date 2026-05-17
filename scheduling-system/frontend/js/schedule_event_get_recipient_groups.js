document.addEventListener("DOMContentLoaded", function () {

  const GROUPS_URL = window.BPSS_SCHED_EVENT_RECIP_GROUPS_API_URL;

  const groupList = document.getElementById("groupList");
  const selectedGroupsCount = document.getElementById("selectedGroupsCount");

  if (!GROUPS_URL || !groupList) return;

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success:false, message:"Invalid response" }; }
  }

  function updateSelectedGroupsCount() {
    const checked = groupList.querySelectorAll(".notify-group:checked");
    if (selectedGroupsCount) selectedGroupsCount.textContent = checked.length;
  }

  window.BPSS_schedEvent_getSelectedGroupNames = function () {
    return Array.from(groupList.querySelectorAll(".notify-group:checked"))
      .map(x => String(x.value || "").trim())
      .filter(Boolean);
  };

  async function loadGroups() {
    const resp = await fetch(GROUPS_URL, {
      method: "GET",
      headers: { "Accept": "application/json" }
    });

    const data = await safeJson(resp);

    if (!data.success || !Array.isArray(data.data)) {
      groupList.innerHTML = `<div class="text-danger">Failed to load groups.</div>`;
      return;
    }

    if (data.data.length === 0) {
      groupList.innerHTML = `<div class="text-muted">No groups yet.</div>`;
      updateSelectedGroupsCount();
      return;
    }

    groupList.innerHTML = data.data.map(g => `
      <label class="d-flex align-items-center gap-2 py-1">
        <input class="form-check-input notify-group"
               type="checkbox"
               name="notify_groups[]"
               value="${String(g.group_name).replace(/"/g, "&quot;")}">
        <span class="flex-grow-1">${g.group_name}</span>
      </label>
    `).join("");

    groupList.querySelectorAll(".notify-group").forEach(cb => {
      cb.addEventListener("change", () => {
        updateSelectedGroupsCount();
        window.BPSS_schedEvent_reloadContacts?.();
        window.BPSS_buildPreviewMessage?.(false);
      });
    });

    updateSelectedGroupsCount();
  }

  loadGroups();
});