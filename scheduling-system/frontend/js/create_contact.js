/* global bootstrap */
document.addEventListener("DOMContentLoaded", function () {

  const btnAdd = document.getElementById("btnAddContact");
  const modalEl = document.getElementById("contactModal");
  const form = document.getElementById("contactForm");
  const msgBox = document.getElementById("contactMsg");

  if (!btnAdd || !modalEl || !form) return;

  const modal = new bootstrap.Modal(modalEl);

  function showMsg(type, text) {
    msgBox.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${text}</div>`;
  }
  function clearMsg(){ msgBox.innerHTML = ""; }

  function getSelectedGroupIds() {
    return Array.from(document.querySelectorAll(".group-check:checked")).map(x => x.value);
  }

  btnAdd.addEventListener("click", async () => {
    clearMsg();
    document.getElementById("contactModalTitle").textContent = "Add Contact";
    form.reset();
    document.getElementById("contactId").value = "";
    await BPSS_loadGroups([]);
    modal.show();
  });

  window.BPSS_editContact = async function (id) {
    clearMsg();
    const resp = await fetch(`backend/auth/create_contact_api.php?action=get&id=${encodeURIComponent(id)}`);
    const data = await resp.json();

    if (!data.success) { window.BPSS_showToast?.(data.message || "Failed to load contact.", "danger"); return; }

    document.getElementById("contactModalTitle").textContent = "Edit Contact";
    document.getElementById("contactId").value = data.contact.id;
    document.getElementById("fullName").value = data.contact.full_name || "";
    document.getElementById("mobile").value = data.contact.mobile || "";
    document.getElementById("address").value = data.contact.address || "";

    await BPSS_loadGroups(data.group_ids || []);
    modal.show();
  };

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearMsg();

    const groupIds = getSelectedGroupIds();
    if (!groupIds.length) { showMsg("danger", "Please select at least 1 group."); return; }

    const submitBtn = form.querySelector('[type="submit"]');
    const origHtml  = submitBtn?.innerHTML;
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Saving...`; }

    try {
      const fd = new FormData(form);
      groupIds.forEach(gid => fd.append("group_ids[]", gid));

      const id = (document.getElementById("contactId").value || "").trim();
      const action = id ? "update" : "create";

      const resp = await fetch(`backend/auth/create_contact_api.php?action=${action}`, {
        method: "POST",
        body: fd
      });
      const data = await resp.json();

      if (!data.success) { showMsg("danger", data.message || "Save failed."); return; }

      modal.hide();
      window.BPSS_showToast?.(action === "create" ? "Contact created." : "Contact updated.", "success");
      window.BPSS_reloadContactsTable?.();
    } catch {
      showMsg("danger", "Network error. Please try again.");
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
    }
  });

  const btnQuickAdd = document.getElementById("btnQuickAddGroup");
  btnQuickAdd.addEventListener("click", async () => {
    clearMsg();
    const name = prompt("Enter new group name:");
    if (!name) return;

    const origHtml = btnQuickAdd.innerHTML;
    btnQuickAdd.disabled = true;
    btnQuickAdd.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Adding...`;

    try {
      const fd = new FormData();
      fd.append("group_name", name.trim());

      const resp = await fetch("backend/auth/get_contact_groups_api.php?action=create", {
        method: "POST",
        body: fd
      });
      const data = await resp.json();

      if (!data.success) { showMsg("danger", data.message || "Failed to add group."); window.BPSS_showToast?.(data.message || "Failed to add group.", "danger"); return; }

      await BPSS_loadGroups([data.id]);
      window.BPSS_showToast?.("Group added.", "success");
    } catch {
      showMsg("danger", "Network error.");
    } finally {
      btnQuickAdd.disabled = false;
      btnQuickAdd.innerHTML = origHtml;
    }
  });

});
