document.addEventListener("DOMContentLoaded", function () {
  const URL = window.BPSS_EVENT_RESEND_SMS_API_URL;
  if (!URL) return;

  window.BPSS_resendScheduleSms = async function (eventId) {
    const resp = await fetch(URL, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json" },
      body: JSON.stringify({ event_id: eventId })
    });

    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success: false, message: "Invalid server response." }; }
  };
});