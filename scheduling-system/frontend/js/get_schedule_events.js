document.addEventListener("DOMContentLoaded", function () {
  const LIST_URL = window.BPSS_EVENT_LIST_API_URL;
  if (!LIST_URL) return;

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return []; }
  }

  window.BPSS_getScheduleEvents = async function (info) {
    const params = new URLSearchParams();
    if (info?.startStr) params.set("start", info.startStr);
    if (info?.endStr) params.set("end", info.endStr);

    const resp = await fetch(`${LIST_URL}?${params.toString()}`, {
      method: "GET",
      headers: { "Accept": "application/json" }
    });

    return await safeJson(resp); // already in FullCalendar format
  };
});