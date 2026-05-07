document.addEventListener("DOMContentLoaded", function () {
  const URL = window.BPSS_EVENT_UPDATE_API_URL;
  if (!URL) return;

  window.BPSS_updateScheduleEvent = async function (formData) {
    const resp = await fetch(URL, {
      method: "POST",
      body: formData
    });

    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success: false, message: "Invalid server response." }; }
  };
});