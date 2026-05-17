document.addEventListener("DOMContentLoaded", async () => {
  const sel = document.getElementById("contactSelect");
  if (!sel) return;

  try {
    const res = await fetch("backend/auth/get_contacts_dropdown_api.php");

    const ct = res.headers.get("content-type") || "";
    const text = await res.text();

    if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 200)}`);
    if (!ct.includes("application/json")) throw new Error(text.slice(0, 200));

    const data = JSON.parse(text);
    if (!data.success) throw new Error(data.message || "Failed to load contacts.");

    (data.contacts || []).forEach(c => {
      const opt = document.createElement("option");
      opt.value = String(c.id);
      opt.textContent = `${c.full_name}${c.mobile ? " — " + c.mobile : ""}`;
      sel.appendChild(opt);
    });
  } catch (e) {
  }
});