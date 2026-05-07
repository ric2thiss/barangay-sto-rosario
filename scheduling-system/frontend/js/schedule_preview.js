document.addEventListener("DOMContentLoaded", function () {
  const evTitle = document.getElementById("evTitle");
  const evStart = document.getElementById("evStart");
  const evEnd = document.getElementById("evEnd");
  const evLocation = document.getElementById("evLocation");
  const evDesc = document.getElementById("evDesc");

  const agendasJson = document.getElementById("agendasJson");

  const smsPreview = document.getElementById("smsMessagePreview");
  const notifyMessageHidden = document.getElementById("notifyMessage");

  function setCustomMode(on) {
    if (!smsPreview) return;
    smsPreview.dataset.custom = on ? "1" : "0";
  }
  function isCustomMode() {
    return smsPreview && smsPreview.dataset.custom === "1";
  }

  function toDateObj(dtLocalValue) {
    if (!dtLocalValue) return null;
    const d = new Date(dtLocalValue);
    return isNaN(d.getTime()) ? null : d;
  }

  function fmtDate(d) {
    return new Intl.DateTimeFormat("en-PH", {
      year: "numeric", month: "short", day: "2-digit",
    }).format(d);
  }

  function fmtTime(d) {
    return new Intl.DateTimeFormat("en-PH", {
      hour: "numeric", minute: "2-digit"
    }).format(d);
  }

  function formatDateRange(startVal, endVal) {
    const s = toDateObj(startVal);
    const e = toDateObj(endVal);

    if (!s && !e) return "";
    if (s && !e) return fmtDate(s);
    if (!s && e) return fmtDate(e);

    const sameDate =
      s.getFullYear() === e.getFullYear() &&
      s.getMonth() === e.getMonth() &&
      s.getDate() === e.getDate();

    if (sameDate) return fmtDate(s);
    return `${fmtDate(s)} - ${fmtDate(e)}`;
  }

  function formatTimeRange(startVal, endVal) {
    const s = toDateObj(startVal);
    const e = toDateObj(endVal);

    if (!s && !e) return "";
    if (s && !e) return fmtTime(s);
    if (!s && e) return fmtTime(e);

    const sameDate =
      s.getFullYear() === e.getFullYear() &&
      s.getMonth() === e.getMonth() &&
      s.getDate() === e.getDate();

    if (sameDate) return `${fmtTime(s)} - ${fmtTime(e)}`;
    return `${fmtDate(s)} ${fmtTime(s)} - ${fmtDate(e)} ${fmtTime(e)}`;
  }

  // ✅ group values are group names now
  function getSelectedGroups() {
    // prefer helper (from groups script) if available
    if (window.BPSS_schedEvent_getSelectedGroupNames) {
      return window.BPSS_schedEvent_getSelectedGroupNames();
    }
    return Array.from(document.querySelectorAll(".notify-group:checked"))
      .map(x => String(x.value || "").trim())
      .filter(Boolean);
  }

  function getSelectedContactNames() {
    return Array.from(document.querySelectorAll(".notify-contact:checked"))
      .map(chk => {
        const item = chk.closest(".contact-item");
        const name = item?.getAttribute("data-name")?.trim();
        return name || "";
      })
      .filter(Boolean);
  }

  function parseAgendas() {
    try {
      const raw = agendasJson ? agendasJson.value.trim() : "";
      if (!raw) return [];
      const data = JSON.parse(raw);
      return Array.isArray(data) ? data : [];
    } catch {
      return [];
    }
  }

  function buildRecipientsLine() {
    const groups = getSelectedGroups();
    const names = getSelectedContactNames();

    const parts = [];
    if (groups.length) parts.push(groups.join(", "));
    if (names.length) parts.push(names.join(", "));

    return parts.join(", ");
  }

  function buildMessageText() {
    const title = (evTitle?.value || "").trim();
    const dateLine = formatDateRange(evStart?.value || "", evEnd?.value || "");
    const timeLine = formatTimeRange(evStart?.value || "", evEnd?.value || "");
    const location = (evLocation?.value || "").trim();
    const desc = (evDesc?.value || "").trim();

    const agendas = parseAgendas();
    const toLine = buildRecipientsLine();

    const sender = (window.BPSS_USER_FULLNAME && String(window.BPSS_USER_FULLNAME).trim())
      ? String(window.BPSS_USER_FULLNAME).trim()
      : "Brgy. PSS";

    const lines = [];

    if (title) lines.push(`Subject: ${title}`);
    if (toLine) lines.push(`To: ${toLine}`);
    if (dateLine) lines.push(`Date: ${dateLine}`);
    if (timeLine) lines.push(`Time: ${timeLine}`);
    if (location) lines.push(`Location: ${location}`);
    if (desc) lines.push(`Details: ${desc}`);

    if (agendas.length) {
      lines.push("");
      lines.push("Agenda:");
      agendas.forEach((a, idx) => {
        const topic   = (a.topic   || "").trim();
        const status  = (a.status  || "").trim();
        const remarks = (a.remarks || "").trim();

        let item = `${idx + 1}. ${topic || "Agenda item"}`;
        const extras = [];
        if (status && status !== "pending") extras.push(status);
        if (remarks) extras.push(remarks);
        if (extras.length) item += ` (${extras.join(" • ")})`;
        lines.push(item);
      });
    }

    lines.push("");
    lines.push(`- ${sender}`);

    return lines.join("\n");
  }

  function applyMessageToFields(message) {
    if (smsPreview) smsPreview.value = message;
    if (notifyMessageHidden) notifyMessageHidden.value = message;
  }

  window.BPSS_buildPreviewMessage = function (force = false) {
    if (!force && isCustomMode()) {
      if (smsPreview && notifyMessageHidden) notifyMessageHidden.value = smsPreview.value;
      return;
    }

    const msg = buildMessageText();
    applyMessageToFields(msg);
    setCustomMode(false);
  };

  if (smsPreview) {
    smsPreview.addEventListener("input", () => {
      setCustomMode(true);
      if (notifyMessageHidden) notifyMessageHidden.value = smsPreview.value;
    });
  }

  [evTitle, evStart, evEnd, evLocation, evDesc].forEach(el => {
    if (!el) return;
    el.addEventListener("input", () => window.BPSS_buildPreviewMessage(false));
    el.addEventListener("change", () => window.BPSS_buildPreviewMessage(false));
  });

  document.addEventListener("change", (e) => {
    if (e.target?.classList?.contains("notify-group") ||
        e.target?.classList?.contains("notify-contact") ||
        e.target?.id === "notifySms") {
      window.BPSS_buildPreviewMessage(false);
    }
  });

  window.BPSS_buildPreviewMessage(true);
});
