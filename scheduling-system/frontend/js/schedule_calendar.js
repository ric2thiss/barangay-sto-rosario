document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("bpssCalendar");
  if (!calendarEl || typeof FullCalendar === "undefined") return;

  function fmtTime(isoOrDate) {
    const d = (isoOrDate instanceof Date) ? isoOrDate : new Date(isoOrDate);
    if (isNaN(d.getTime())) return "";
    return new Intl.DateTimeFormat("en-PH", {
      hour: "numeric",
      minute: "2-digit"
    }).format(d);
  }

  const cal = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    height: "auto",
    selectable: true,
    nowIndicator: true,
    dayMaxEvents: true,

    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay"
    },

    // We will render our own overview text
    displayEventTime: false,
    eventDisplay: "block",

    // ✅ Overview text inside the day cell:
    // Title + Start time
    eventContent: function (arg) {
      const title = arg.event.title || "";
      const startTime = arg.event.start ? fmtTime(arg.event.start) : "";

      const wrap = document.createElement("div");
      wrap.className = "bpss-cal-event";

      const t = document.createElement("div");
      t.className = "bpss-cal-subject";
      t.textContent = title;

      const s = document.createElement("div");
      s.className = "bpss-cal-time";
      s.textContent = startTime ? `Start: ${startTime}` : "";

      wrap.appendChild(t);
      if (startTime) wrap.appendChild(s);

      return { domNodes: [wrap] };
    },

    dateClick: function (info) {
      const ymd = info.dateStr;
      const start = `${ymd}T09:00`;
      const end = `${ymd}T10:00`;

      if (typeof window.BPSS_openModalNewEvent === "function") {
        window.BPSS_openModalNewEvent({ start, end });
      }
    },

    // ✅ Click an event => open modal for editing
    eventClick: function (info) {
      const ev = info.event;

      // your API already set these
      const location = ev.extendedProps?.location || "";
      const status = ev.extendedProps?.status || "";

      if (typeof window.BPSS_openModalEditEvent === "function") {
        window.BPSS_openModalEditEvent({
          id: ev.id,
          title: ev.title,
          start: ev.start ? ev.start.toISOString() : "",
          end: ev.end ? ev.end.toISOString() : "",
          location,
          status
        });
      }
    },

    events: async function (info, successCallback, failureCallback) {
      try {
        if (typeof window.BPSS_getScheduleEvents !== "function") {
          throw new Error("BPSS_getScheduleEvents is not loaded. Check footer script order.");
        }
        const events = await window.BPSS_getScheduleEvents(info);
        successCallback(events);
      } catch (e) {
        failureCallback(e);
      }
    }
  });

  cal.render();
  window.BPSS_calendar = cal;

  // Auto-notify queue processor — poll every 60 seconds for queued SMS whose send_at has passed
  const queueUrl = window.BPSS_SMS_QUEUE_API_URL;
  if (queueUrl) {
    async function processSmsQueue() {
      try {
        await fetch(queueUrl, { method: "POST" });
      } catch (_) { /* non-fatal */ }
    }

    // Run once on load, then every 60 seconds
    processSmsQueue();
    setInterval(processSmsQueue, 60_000);
  }
});