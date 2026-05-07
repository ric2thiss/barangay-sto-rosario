document.addEventListener("DOMContentLoaded", function () {

  const CREATE_URL = window.BPSS_EVENT_CREATE_API_URL;

  if (!CREATE_URL) return;

  async function safeJson(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch { return { success:false, message:"Invalid response" }; }
  }

  window.BPSS_createScheduleEvent = async function (formData) {

    const resp = await fetch(CREATE_URL, {
      method: "POST",
      body: formData
    });

    return await safeJson(resp);
  };

});

