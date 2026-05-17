document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("groupFilter")) {
    BPSS_loadGroups();
  }
});

async function BPSS_loadGroups(selectedIds = []) {
  const groupFilter = document.getElementById("groupFilter");
  const groupChecklist = document.getElementById("groupChecklist");
  if (!groupFilter || !groupChecklist) return;

  const resp = await fetch("backend/auth/get_contact_groups_api.php?action=list");
  const data = await resp.json();

  if (!data.success) {
    groupFilter.innerHTML = `<option value="">All groups</option>`;
    groupChecklist.innerHTML = `<div class="text-danger">Failed to load groups.</div>`;
    return;
  }

  groupFilter.innerHTML =
    `<option value="">All groups</option>` +
    data.data.map(g => `<option value="${g.id}">${g.group_name}</option>`).join("");

  const set = new Set((selectedIds || []).map(String));
  groupChecklist.innerHTML = data.data.map(g => `
    <div class="form-check">
      <input class="form-check-input group-check" type="checkbox" value="${g.id}"
             id="grp${g.id}" ${set.has(String(g.id)) ? "checked" : ""}>
      <label class="form-check-label" for="grp${g.id}">${g.group_name}</label>
    </div>
  `).join("") || `<div class="text-muted">No groups yet.</div>`;
}