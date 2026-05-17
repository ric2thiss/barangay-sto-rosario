document.addEventListener("DOMContentLoaded", function () {
  const API_URL = window.BPSS_DASHBOARD_API_URL;

  // Today label
  const todayLabel = document.getElementById("dashToday");
  todayLabel.textContent = new Intl.DateTimeFormat("en-PH", {
    weekday: "long", year: "numeric", month: "long", day: "numeric"
  }).format(new Date());

  function esc(s) {
    return String(s ?? "")
      .replace(/&/g, "&amp;").replace(/</g, "&lt;")
      .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function fmtTime(str) {
    if (!str) return "";
    const d = new Date(str);
    if (isNaN(d)) return "";
    return new Intl.DateTimeFormat("en-PH", { hour: "numeric", minute: "2-digit" }).format(d);
  }

  function fmtShortDate(str) {
    if (!str) return "";
    const d = new Date(str);
    if (isNaN(d)) return "";
    return new Intl.DateTimeFormat("en-PH", { month: "short", day: "2-digit", weekday: "short" }).format(d);
  }

  function typeBadge(type) {
    const map = { meeting: "primary", event: "info", appointment: "warning", other: "secondary" };
    return `<span class="badge bg-${map[type] || "secondary"} bg-opacity-75">${esc(type || "—")}</span>`;
  }

  function statusDot(status) {
    const map = { pending: "secondary", done: "success", deferred: "warning", cancelled: "danger" };
    const color = map[status] || "secondary";
    return `<span class="badge bg-${color} rounded-pill" style="font-size:.65rem;">${esc(status || "pending")}</span>`;
  }

  function renderTodayEvents(events) {
    const el = document.getElementById("dashTodayEvents");

    if (!events.length) {
      el.innerHTML = `<div class="text-center py-4 text-muted small">No events scheduled for today.</div>`;
      return;
    }

    el.innerHTML = events.map(ev => {
      const timeRange = ev.end_datetime
        ? `${fmtTime(ev.start_datetime)} – ${fmtTime(ev.end_datetime)}`
        : fmtTime(ev.start_datetime);

      const agendaHtml = ev.agendas?.length
        ? `<ul class="mb-0 ps-3 mt-1" style="font-size:.8rem;">
            ${ev.agendas.map(a => `
              <li class="d-flex align-items-start gap-1 mb-1">
                <span class="flex-grow-1 text-muted">${esc(a.agenda_title || "—")}</span>
                ${statusDot(a.status)}
              </li>
            `).join("")}
           </ul>`
        : `<div class="small text-muted fst-italic mt-1">No agenda items.</div>`;

      return `
        <div class="border-bottom px-3 py-2">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <div class="fw-semibold text-truncate">${esc(ev.title)}</div>
            ${typeBadge(ev.schedule_type)}
          </div>
          <div class="small text-muted d-flex flex-wrap gap-3">
            ${timeRange ? `<span><i class="bi bi-clock me-1"></i>${esc(timeRange)}</span>` : ""}
            ${ev.location ? `<span><i class="bi bi-geo-alt me-1"></i>${esc(ev.location)}</span>` : ""}
          </div>
          ${agendaHtml}
        </div>
      `;
    }).join("");
  }

  function renderUpcoming(events) {
    const el = document.getElementById("dashUpcoming");

    if (!events.length) {
      el.innerHTML = `<div class="text-center py-3 text-muted small">No upcoming events this week.</div>`;
      return;
    }

    el.innerHTML = events.map(ev => `
      <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
        <div class="text-center flex-shrink-0" style="width:40px;">
          <div class="fw-bold lh-1 bpss-text-theme" style="font-size:1.1rem;">
            ${new Date(ev.start_datetime).getDate()}
          </div>
          <div class="small text-muted" style="font-size:.7rem;">
            ${new Intl.DateTimeFormat("en-PH", { month: "short" }).format(new Date(ev.start_datetime))}
          </div>
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <div class="fw-semibold text-truncate small">${esc(ev.title)}</div>
          <div class="text-muted" style="font-size:.75rem;">
            ${fmtTime(ev.start_datetime)}
            ${ev.location ? `· ${esc(ev.location)}` : ""}
          </div>
        </div>
        ${typeBadge(ev.schedule_type)}
      </div>
    `).join("");
  }

  function renderRecentSms(rows) {
    const el = document.getElementById("dashRecentSms");

    if (!rows.length) {
      el.innerHTML = `<div class="text-center py-3 text-muted small">No recent SMS activity.</div>`;
      return;
    }

    el.innerHTML = rows.map(r => {
      const isSent   = r.status === "sent";
      const icon     = isSent ? "bi-check-circle-fill text-success" : "bi-x-circle-fill text-danger";
      const sentTime = new Intl.DateTimeFormat("en-PH", {
        month: "short", day: "2-digit", hour: "numeric", minute: "2-digit"
      }).format(new Date(r.queued_at));

      return `
        <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
          <i class="bi ${icon} flex-shrink-0"></i>
          <div class="flex-grow-1 overflow-hidden">
            <div class="small fw-semibold text-truncate">${esc(r.to_mobile)}</div>
            <div class="text-muted" style="font-size:.75rem;">${r.event_title ? esc(r.event_title) : "—"}</div>
          </div>
          <div class="text-muted text-end flex-shrink-0" style="font-size:.72rem;">${sentTime}</div>
        </div>
      `;
    }).join("");
  }

  async function loadDashboard() {
    try {
      const resp = await fetch(API_URL);
      const json = await resp.json();

      if (!json.success) return;

      const { stats, today_events, upcoming, recent_sms } = json;

      // Stats
      document.getElementById("statEventsToday").textContent = stats.events_today;
      document.getElementById("statEventsWeek").textContent  = stats.events_week;
      document.getElementById("statContacts").textContent    = stats.total_contacts;
      document.getElementById("statSmsSent").textContent     = stats.sms_sent_today;

      renderTodayEvents(today_events || []);
      renderUpcoming(upcoming || []);
      renderRecentSms(recent_sms || []);

    } catch (err) {
      document.getElementById("dashTodayEvents").innerHTML =
        `<div class="text-center py-4 text-danger small">Failed to load dashboard.</div>`;
    }
  }

  loadDashboard();

  // SMS queue processor — fires queued auto-notify SMS whose send_at has passed
  const queueUrl = window.BPSS_SMS_QUEUE_API_URL;
  if (queueUrl) {
    async function processSmsQueue() {
      try { await fetch(queueUrl, { method: "POST" }); } catch (_) {}
    }
    processSmsQueue();
    setInterval(processSmsQueue, 60_000);
  }
});
