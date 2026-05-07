/* global bootstrap */
(function () {
  const state = {
    editMode: false,
    minutes: {},
    paperSize: "legal",

    signatureList: [
      { name: "HON. CLEOPATRA C. ROCES", title: "Punong Barangay" },
      { name: "JANE A. ESMA", title: "Barangay Secretary" },
      { name: "HON. ALLAN D. DURAN", title: "Sangguniang Barangay Member" },
      { name: "HON. RODRIGO C. ABAO, JR.", title: "Sangguniang Barangay Member" },
      { name: "HON. MARGIE M. GABATO", title: "Sangguniang Barangay Member" },
      { name: "HON. RUEL M. BUENO", title: "Sangguniang Barangay Member" },
      { name: "HON. ARNEL N. ORLANDEZ", title: "Sangguniang Barangay Member" },
      { name: "HON. LODOVICO C. SATO, JR.", title: "Sangguniang Barangay Member" },
      { name: "HON. LEONARDO L. CRUZA", title: "Sangguniang Barangay Member" },
      { name: "HON. CHARLES NIÑO REY JAMITO", title: "SK Chairperson" },
    ],

    officials: [],
    officialsLoaded: false,
  };

  function toast(type, msg) {
    if (window.BPSSToast?.[type]) window.BPSSToast[type](msg);
    else alert(msg);
  }

  function fmtDatePH(dateStr) {
    if (!dateStr) return "";
    const normalized = String(dateStr).trim().replace(/\bSept\b\.?/i, "September");
    const d = new Date(normalized);
    if (Number.isNaN(d.getTime())) return normalized;
    return d.toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" });
  }

  function normalizeSessionDateInput(value) {
    const raw = String(value ?? "").trim().replace(/\s+/g, " ");
    if (!raw) return "";

    const normalized = raw.replace(/\bSept\b\.?/i, "September");
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) return raw;

    return parsed.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function isBlank(v) {
    return String(v ?? "").trim() === "";
  }

  // ✅ Detect if a value is just a header/label (not real extracted narrative)
  function isHeaderOnlyValue(val, headerCandidates) {
    const v = String(val ?? "").trim();
    if (!v) return true;

    // normalize: remove punctuation and extra spaces
    const norm = v
      .toUpperCase()
      .replace(/[.:]/g, "")
      .replace(/\s+/g, " ")
      .trim();

    // too short / single label
    if (norm.length <= 3) return true;

    // if it matches any known header exactly
    return headerCandidates.some((h) => {
      const hn = String(h)
        .toUpperCase()
        .replace(/[.:]/g, "")
        .replace(/\s+/g, " ")
        .trim();
      return norm === hn;
    });
  }

  // ✅ Sanitize extracted text: if it’s only the section header, treat as empty
  function sanitizeExtractionPlaceholders() {
    const headerMap = {
      call_to_order: [
        "CALL TO ORDER",
        "I CALL TO ORDER",
        "I CALL TO ORDER",
        "I. CALL TO ORDER",
      ],
      roll_call: ["ROLL CALL", "II ROLL CALL", "II. ROLL CALL"],
      approval_of_minutes: [
        "READING AND APPROVAL OF PREVIOUS MINUTES",
        "III READING AND APPROVAL OF PREVIOUS MINUTES",
        "III. READING AND APPROVAL OF PREVIOUS MINUTES",
      ],
      agenda: [
        "INCLUSION, AMENDMENTS AND APPROVAL OF AGENDA",
        "INCLUSION, AMENDMENTS, AND APPROVAL OF AGENDA",
        "IV INCLUSION, AMENDMENTS, AND APPROVAL OF AGENDA",
        "IV. INCLUSION, AMENDMENTS, AND APPROVAL OF AGENDA",
      ],
      matters_for_information: [
        "MATTERS FOR INFORMATION",
        "V MATTERS FOR INFORMATION",
        "V. MATTERS FOR INFORMATION",
      ],
      privilege_hour: ["PRIVILEGE HOUR", "VI PRIVILEGE HOUR", "VI. PRIVILEGE HOUR"],
      first_reading_referral: [
        "FIRST READING AND REFERRAL",
        "FIRST READING & REFERRAL",
        "VII FIRST READING & REFERRAL",
        "VII. FIRST READING & REFERRAL",
      ],
      committee_report: ["COMMITTEE REPORT", "VIII COMMITTEE REPORT", "VIII. COMMITTEE REPORT"],
      calendar_of_business: [
        "CALENDAR OF BUSINESS",
        "IX CALENDAR OF BUSINESS",
        "IX. CALENDAR OF BUSINESS",
      ],
      third_reading: [
        "ORDINANCE AND RESOLUTION FOR THIRD READING",
        "ORDINANCE AND RESOLUTION FOR THIRD READING",
        "X ORDINANCE AND RESOLUTION FOR THIRD READING",
        "X. ORDINANCE AND RESOLUTION FOR THIRD READING",
      ],
      other_matters: ["OTHER MATTERS", "XI OTHER MATTERS", "XI. OTHER MATTERS"],
      announcement: ["ANNOUNCEMENT", "XII ANNOUNCEMENT", "XII. ANNOUNCEMENT"],
      adjournment: ["ADJOURNMENT", "XIII ADJOURNMENT", "XIII. ADJOURNMENT"],
    };

    Object.keys(headerMap).forEach((key) => {
      const val = state.minutes[key];
      if (val === null || val === undefined) return;

      if (isHeaderOnlyValue(val, headerMap[key])) {
        state.minutes[key] = ""; // treat as missing so "None." applies
      }
    });

    // Also sanitize "calendar_of_business" when it only contains "9.1 9.2 9.3" labels
    if (!isBlank(state.minutes.calendar_of_business)) {
      const v = String(state.minutes.calendar_of_business).toUpperCase();
      const hasRealText =
        /RESO|RESOLUTION|ORDINANCE|MOTION|APPROVED|DISCUSS|REMARKS|MOVANT|SECONDED|WHEREAS/.test(v);
      if (!hasRealText) state.minutes.calendar_of_business = "";
    }

    // Same for privilege_hour
    if (!isBlank(state.minutes.privilege_hour)) {
      const v = String(state.minutes.privilege_hour).toUpperCase();
      const hasRealText =
        /RESO|RESOLUTION|MOTION|QUESTION|DISCUSS|REMARKS|MOVANT|SECONDED|WHEREAS/.test(v);
      if (!hasRealText) state.minutes.privilege_hour = "";
    }
  }

  function ensureArrays() {
    if (!Array.isArray(state.minutes.present_list)) state.minutes.present_list = [];
    if (!Array.isArray(state.minutes.absent_list)) state.minutes.absent_list = [];

    state.minutes.present_list = state.minutes.present_list
      .map((x) => {
        if (x && typeof x === "object") {
          return { name: x.name ?? "", position: x.position ?? "" };
        }
        return { name: String(x ?? "").trim(), position: "" };
      })
      .filter((x) => x.name || x.position);

    state.minutes.absent_list = state.minutes.absent_list
      .map((x) => {
        if (x && typeof x === "object") {
          return { name: x.name ?? "", position: x.position ?? "" };
        }
        return { name: String(x ?? "").trim(), position: "" };
      })
      .filter((x) => x.name || x.position);

    if (!Array.isArray(state.minutes.signature_list)) {
      state.minutes.signature_list = state.signatureList.map((x) => ({
        official_id: null,
        name: x.name,
        title: x.title,
        signature_url: null,
        show_signature: false,
      }));
    } else {
      state.minutes.signature_list = state.minutes.signature_list.map((x) => {
        if (!x || typeof x !== "object") {
          return {
            official_id: null,
            name: String(x || ""),
            title: "",
            signature_url: null,
            show_signature: false,
          };
        }
        return {
          official_id: x.official_id ?? null,
          name: x.name ?? "",
          title: x.title ?? "",
          signature_url: x.signature_url ?? null,
          show_signature: !!x.show_signature,
        };
      });
    }
  }

  async function loadOfficialsOnce() {
    if (state.officialsLoaded) return;
    state.officialsLoaded = true;

    try {
      const res = await fetch("backend/auth/minutes_official_list_api.php", { cache: "no-store" });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Failed to load officials list.");

      state.officials = Array.isArray(data.rows) ? data.rows : [];
      renderSignatures();
    } catch (e) {
      toast("error", e?.message || "Failed to load officials list.");
      state.officials = [];
    }
  }

  function getOfficialById(id) {
    const n = Number(id);
    if (!n) return null;
    return state.officials.find((o) => Number(o.id) === n) || null;
  }

  function buildOfficialsNameOptions(selectedOfficialId, currentName) {
    const sid = Number(selectedOfficialId) || 0;
    const cur = String(currentName || "").trim();

    const opts = [];

    if (!sid) {
      if (cur) opts.push(`<option value="" selected>${esc(cur)}</option>`);
      else opts.push(`<option value="" selected>-- Select Name --</option>`);
    } else {
      opts.push(`<option value="">-- Select Name --</option>`);
    }

    state.officials.forEach((o) => {
      const id = Number(o.id);
      const selected = id === sid ? "selected" : "";
      opts.push(`<option value="${esc(id)}" ${selected}>${esc(o.full_name || "")}</option>`);
    });

    return opts.join("");
  }

  function applyEditableMode() {
    const editIcon = document.getElementById("minutesEditIcon");
    const editLabel = document.getElementById("minutesEditLabel");

    const btnSave = document.getElementById("btnMinutesSaveEdits");
    const btnPdf = document.getElementById("btnMinutesDownloadPdf");

    const btnAddPresent = document.getElementById("btnMinutesAddPresent");
    const btnAddAbsent = document.getElementById("btnMinutesAddAbsent");

    document.querySelectorAll(".minutes-editable").forEach((el) => {
      el.setAttribute("contenteditable", state.editMode ? "true" : "false");
    });

    if (state.editMode) {
      btnSave.classList.remove("d-none");
      btnPdf.classList.add("d-none");

      btnAddPresent.classList.remove("d-none");
      btnAddAbsent.classList.remove("d-none");

      editIcon.className = "bi bi-lock me-2";
      editLabel.textContent = "Lock Editing";

      loadOfficialsOnce();
    } else {
      btnSave.classList.add("d-none");
      btnPdf.classList.remove("d-none");

      btnAddPresent.classList.add("d-none");
      btnAddAbsent.classList.add("d-none");

      editIcon.className = "bi bi-pen me-2";
      editLabel.textContent = "Edit Text";
    }

    renderPresentAbsent();
    renderSignatures();
  }

  function bindEditableBlurHandlers() {
    document.querySelectorAll("[data-key]").forEach((el) => {
      if (el.dataset.bound === "1") return;
      el.dataset.bound = "1";

      el.addEventListener("blur", () => {
        const key = el.getAttribute("data-key");
        if (!key) return;
        if (key === "session_date_display") {
          const updatedDate = normalizeSessionDateInput(el.innerText);
          state.minutes.session_date = updatedDate;
          state.minutes.session_date_display = fmtDatePH(updatedDate);
          setTextByKey("session_date_display", state.minutes.session_date_display || updatedDate);
          return;
        }
        state.minutes[key] = el.innerText.trim();
      });
    });
  }

  function setTextByKey(key, value) {
    document.querySelectorAll(`[data-key="${key}"]`).forEach((el) => {
      el.innerText = (value ?? "").toString();
    });
  }

  function renderPresentAbsent() {
    const ulPresent = document.getElementById("minutesPresentList");
    const ulAbsent = document.getElementById("minutesAbsentList");
    if (!ulPresent || !ulAbsent) return;

    ulPresent.innerHTML = "";
    ulAbsent.innerHTML = "";

    state.minutes.present_list.forEach((p, idx) => {
      const li = document.createElement("li");
      li.className = "d-flex align-items-center gap-2";

      const span = document.createElement("span");
      span.className = "flex-grow-1";
      span.setAttribute("contenteditable", state.editMode ? "true" : "false");

      const name = String(p?.name ?? "").trim();
      const pos = String(p?.position ?? "").trim();
      span.innerText = [name, pos].filter(Boolean).join(" — ");

      span.addEventListener("blur", () => {
        const raw = span.innerText.trim();
        const parts = raw.split(/\s+[-—]\s+/);
        state.minutes.present_list[idx] = {
          name: (parts[0] || "").trim(),
          position: (parts.slice(1).join(" - ") || "").trim(),
        };
      });

      li.appendChild(span);

      if (state.editMode) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "btn btn-sm btn-outline-danger";
        btn.innerHTML = "&times;";
        btn.addEventListener("click", () => {
          state.minutes.present_list.splice(idx, 1);
          renderPresentAbsent();
        });
        li.appendChild(btn);
      }

      ulPresent.appendChild(li);
    });

    state.minutes.absent_list.forEach((a, idx) => {
      const li = document.createElement("li");
      li.className = "d-flex align-items-center gap-2";

      const span = document.createElement("span");
      span.className = "flex-grow-1";
      span.setAttribute("contenteditable", state.editMode ? "true" : "false");

      const name = String(a?.name ?? "").trim();
      const pos = String(a?.position ?? "").trim();
      span.innerText = [name, pos].filter(Boolean).join(" — ");

      span.addEventListener("blur", () => {
        const raw = span.innerText.trim();
        const parts = raw.split(/\s+[-—]\s+/);
        state.minutes.absent_list[idx] = {
          name: (parts[0] || "").trim(),
          position: (parts.slice(1).join(" - ") || "").trim(),
        };
      });

      li.appendChild(span);

      if (state.editMode) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "btn btn-sm btn-outline-danger";
        btn.innerHTML = "&times;";
        btn.addEventListener("click", () => {
          state.minutes.absent_list.splice(idx, 1);
          renderPresentAbsent();
        });
        li.appendChild(btn);
      }

      ulAbsent.appendChild(li);
    });
  }

  // ===========================
  // AUTO SENTENCES + NONE RULES
  // ===========================

  function setMatterNoneIfEmpty(key) {
    const v = state.minutes[key];
    if (isBlank(v)) state.minutes[key] = "None.";
  }

  function buildCallToOrderSentence() {
    const po = String(state.minutes.presiding_officer ?? "").trim();
    const at = String(state.minutes.adjournment_time ?? "").trim();
    const pl = String(state.minutes.prayer_leader ?? "").trim();
    if (!po || !at || !pl) return "";
    return (
      `The Regular Session was called to order by ${po}, who presided until its adjournment at ${at}. ` +
      `The session started with a prayer led by ${pl}.`
    );
  }

  function buildRollCallSentence() {
    const pcRaw = state.minutes.present_count;
    const pc = pcRaw === null || pcRaw === undefined ? "" : String(pcRaw).trim();
    const raw = state.minutes.has_quorum;
    const q =
      raw === true  || String(raw).toLowerCase() === "true"  ? true  :
      raw === false || String(raw).toLowerCase() === "false" ? false :
      null;
    if (!pc || q === null) return "";

    if (q) {
      return (
        `The Barangay Secretary performed the roll call and stated that ${pc} members were present. ` +
        `The Presiding Officer declared the presence of a quorum.`
      );
    }

    return (
      `The Barangay Secretary performed the roll call and stated that only ${pc} members were present, ` +
      `which did not constitute a quorum.`
    );
  }

  function buildAdjournmentSentence() {
    const mv = String(state.minutes.adjournment_movant ?? "").trim();
    const sc = String(state.minutes.adjournment_seconder ?? "").trim();
    const at = String(state.minutes.adjournment_time ?? "").trim();
    const st = String(state.minutes.session_title ?? "").trim();
    if (!mv || !sc || !at || !st) return "";
    return (
      `There being no other matter to be discussed, ${mv} moved for the adjournment of the ${st} ` +
      `of the Sangguniang Barangay of Brgy. Sto. Rosario, and was seconded by ${sc}. ` +
      `The Regular Session ended at exactly ${at}.`
    );
  }

  function applyAutoSentencesAndNoneRules() {
    // If user didn't type manual narratives, auto-build; otherwise keep manual.
    if (isBlank(state.minutes.call_to_order) || state.minutes.call_to_order === "None.") {
      const s = buildCallToOrderSentence();
      state.minutes.call_to_order = s || "None.";
    }

    if (isBlank(state.minutes.roll_call) || state.minutes.roll_call === "None.") {
      const s = buildRollCallSentence();
      state.minutes.roll_call = s || "None.";
    }

    if (isBlank(state.minutes.adjournment) || state.minutes.adjournment === "None.") {
      const s = buildAdjournmentSentence();
      state.minutes.adjournment = s || "None.";
    }

    [
      "others_present",
      "approval_of_minutes",
      "agenda",
      "matters_for_information",
      "privilege_hour",
      "first_reading_referral",
      "committee_report",
      "calendar_of_business",
      "third_reading",
      "other_matters",
      "announcement",
    ].forEach(setMatterNoneIfEmpty);
  }

  // ===========================
  // PAGE 4: SIGNATURE LAYOUT
  // ===========================

  function normTitle(t) {
    return String(t || "").trim().toLowerCase();
  }

  function pickByTitleIncludes(list, needle) {
    const n = String(needle || "").toLowerCase();
    return list.find((x) => normTitle(x.title).includes(n)) || null;
  }

  function pickMembers(list) {
    return list.filter((x) => normTitle(x.title).includes("sangguniang barangay member"));
  }

  function pickSK(list) {
    return list.find((x) => normTitle(x.title).includes("sk")) || null;
  }

  function createSigItem(m) {
    const wrap = document.createElement("div");
    wrap.className = "minutes-sig-item p4-sig-item";

    const showSig = !!m.show_signature && !!m.signature_url;

    if (showSig) {
      const img = document.createElement("img");
      img.className = "minutes-sig-img p4-sig-img";
      img.src = m.signature_url;
      img.alt = "signature";
      wrap.appendChild(img);
    }

    if (!state.editMode) {
      const name = document.createElement("div");
      name.className = "minutes-sig-name p4-sig-name";
      name.innerText = m.name || "";

      if (normTitle(m.title).includes("punong barangay")) {
        name.classList.add("p4-punong-name");
      }

      const title = document.createElement("div");
      title.className = "minutes-sig-title p4-sig-title";
      title.innerText = m.title || "";

      wrap.appendChild(name);
      wrap.appendChild(title);
      return wrap;
    }

    const selName = document.createElement("select");
    selName.className = "form-select form-select-sm minutes-sig-name-select p4-sig-name-select";
    selName.innerHTML = buildOfficialsNameOptions(m.official_id, m.name);

    selName.addEventListener("change", () => {
      const pickedId = Number(selName.value) || 0;
      const o = pickedId ? getOfficialById(pickedId) : null;

      if (!o) {
        m.official_id = null;
        renderSignatures();
        return;
      }

      m.official_id = Number(o.id);
      m.name = o.full_name || m.name || "";

      if (!String(m.title || "").trim()) {
        m.title = o.official_title || "";
      }

      m.signature_url = o.signature_url || m.signature_url || null;

      renderSignatures();
    });

    const chkWrap = document.createElement("label");
    chkWrap.className = "d-flex align-items-center justify-content-center gap-2 small mt-1 no-print";
    chkWrap.style.userSelect = "none";

    const chk = document.createElement("input");
    chk.type = "checkbox";
    chk.className = "form-check-input m-0";
    chk.checked = !!m.show_signature;

    chk.addEventListener("change", () => {
      m.show_signature = chk.checked;
      renderSignatures();
    });

    const chkText = document.createElement("span");
    chkText.textContent = "Show signature";

    chkWrap.appendChild(chk);
    chkWrap.appendChild(chkText);

    const title = document.createElement("div");
    title.className = "minutes-sig-title p4-sig-title";
    title.setAttribute("contenteditable", "true");
    title.innerText = m.title || "";
    title.addEventListener("blur", () => {
      m.title = title.innerText.trim();
    });

    wrap.appendChild(selName);
    wrap.appendChild(chkWrap);
    wrap.appendChild(title);

    return wrap;
  }

  function renderSignatures() {
    const grid = document.getElementById("minutesSignatureGrid");
    if (!grid) return;

    grid.innerHTML = "";

    const list = Array.isArray(state.minutes.signature_list)
      ? state.minutes.signature_list
      : state.signatureList;

    const secretary =
      pickByTitleIncludes(list, "barangay secretary") ||
      pickByTitleIncludes(list, "secretary") ||
      { official_id: null, name: "", title: "Barangay Secretary", signature_url: null, show_signature: false };

    const punong =
      pickByTitleIncludes(list, "punong barangay") ||
      { official_id: null, name: "", title: "Punong Barangay", signature_url: null, show_signature: false };

    const members = pickMembers(list);
    const sk = pickSK(list);

    const leftCol = members.slice(0, 4);
    const rightCol = members.slice(4, 7);

    const p4 = document.createElement("div");
    p4.className = "p4-wrap";

    const top = document.createElement("div");
    top.className = "p4-top";

    const cert = document.createElement("div");
    cert.className = "p4-cert";

    const certText = document.createElement("p");
    certText.className = "p4-cert-text";
    cert.appendChild(certText);

    const secBox = document.createElement("div");
    secBox.className = "p4-left-sig";
    secBox.appendChild(createSigItem(secretary));
    cert.appendChild(secBox);

    const att = document.createElement("div");
    att.className = "p4-attested";

    const attLabel = document.createElement("p");
    attLabel.className = "p4-attested-label";
    attLabel.textContent = "Attested by:";
    att.appendChild(attLabel);

    att.appendChild(createSigItem(punong));

    top.appendChild(cert);
    top.appendChild(att);
    p4.appendChild(top);

    const grid2 = document.createElement("div");
    grid2.className = "p4-grid";

    for (let i = 0; i < 4; i++) {
      const cellL = document.createElement("div");
      cellL.className = "p4-cell";
      if (leftCol[i]) cellL.appendChild(createSigItem(leftCol[i]));

      const cellR = document.createElement("div");
      cellR.className = "p4-cell";
      if (i < 3) {
        if (rightCol[i]) cellR.appendChild(createSigItem(rightCol[i]));
      } else {
        if (sk) cellR.appendChild(createSigItem(sk));
      }

      grid2.appendChild(cellL);
      grid2.appendChild(cellR);
    }

    p4.appendChild(grid2);
    grid.appendChild(p4);
  }

  function renderAll() {
    ensureArrays();

    // ✅ NEW: clean up “header-only” junk extraction
    sanitizeExtractionPlaceholders();

    // ✅ then auto-generate or show None.
    applyAutoSentencesAndNoneRules();

    state.minutes.session_date_display = fmtDatePH(state.minutes.session_date);

    Object.keys(state.minutes).forEach((k) => setTextByKey(k, state.minutes[k]));
    setTextByKey("session_date_display", state.minutes.session_date_display);

    // Apply fallback text for inline spans (e.g. excerpt header) when value is empty
    document.querySelectorAll("[data-inline-fallback]").forEach((el) => {
      if (el.getAttribute("data-key") === "session_time") return;
      if (!el.innerText.trim()) el.innerText = el.getAttribute("data-inline-fallback");
    });

    renderPresentAbsent();
    renderSignatures();
    bindEditableBlurHandlers();
    applyEditableMode();
  }

  async function saveEdits() {
    const id = window.BPSS_Minutes?.activeId;
    if (!id) return;

    const btn = document.getElementById("btnMinutesSaveEdits");
    const spn = document.getElementById("minutesSaveSpinner");
    const ico = document.getElementById("minutesSaveIcon");
    const lbl = document.getElementById("minutesSaveLabel");

    btn.disabled = true;
    spn.classList.remove("d-none");
    ico.classList.add("d-none");
    lbl.textContent = "Saving...";

    try {
      const payloadMinutes = { ...state.minutes };
      delete payloadMinutes.session_date_display;

      const res = await fetch("backend/auth/minutes_save_edits_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          minutes_document_id: id,
          minutes_json: payloadMinutes,
        }),
      });

      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Save failed.");

      toast("success", "Saved.");
      state.editMode = false;
      applyEditableMode();
    } catch (e) {
      toast("error", e?.message || "Save failed.");
    } finally {
      btn.disabled = false;
      spn.classList.add("d-none");
      ico.classList.remove("d-none");
      lbl.textContent = "Save Changes";
    }
  }

  async function downloadPDF() {
    const btn = document.getElementById("btnMinutesDownloadPdf");
    const spn = document.getElementById("minutesPdfSpinner");
    const ico = document.getElementById("minutesPdfIcon");
    const lbl = document.getElementById("minutesPdfLabel");

    btn.disabled = true;
    spn.classList.remove("d-none");
    ico.classList.add("d-none");
    lbl.textContent = "Generating...";

    try {
      const pages = document.querySelectorAll(".minutes-pdf-page");
      if (!pages.length) throw new Error("No pages to print.");

      if (!window.jspdf?.jsPDF) throw new Error("jsPDF not loaded.");
      if (!window.html2canvas) throw new Error("html2canvas not loaded.");

      const pdf = new window.jspdf.jsPDF("p", "mm", state.paperSize);
      const margin = 8;
      const pageWidth = pdf.internal.pageSize.getWidth();
      const usableWidth = pageWidth - margin * 2;

      let pageNum = 1;
      for (const page of pages) {
        const canvas = await window.html2canvas(page, { scale: 2, useCORS: true });
        const img = canvas.toDataURL("image/png");

        if (pageNum > 1) pdf.addPage();

        const imgHeight = (canvas.height * usableWidth) / canvas.width;
        pdf.addImage(img, "PNG", margin, margin, usableWidth, imgHeight);

        pageNum++;
      }

      pdf.save("Minutes.pdf");
    } catch (e) {
      toast("error", e?.message || "PDF generation failed.");
    } finally {
      btn.disabled = false;
      spn.classList.add("d-none");
      ico.classList.remove("d-none");
      lbl.textContent = "Download PDF";
    }
  }

  function bindToolbar() {
    const sel = document.getElementById("minutesPaperSize");
    const doc = document.getElementById("minutesDoc");
    const btnEdit = document.getElementById("btnMinutesToggleEdit");
    const btnSave = document.getElementById("btnMinutesSaveEdits");
    const btnPdf = document.getElementById("btnMinutesDownloadPdf");

    const btnAddPresent = document.getElementById("btnMinutesAddPresent");
    const btnAddAbsent = document.getElementById("btnMinutesAddAbsent");

    if (sel && doc) {
      sel.addEventListener("change", () => {
        state.paperSize = sel.value;
        doc.classList.remove("a4", "letter", "legal");
        doc.classList.add(state.paperSize);
      });
    }

    if (btnEdit) {
      btnEdit.addEventListener("click", () => {
        state.editMode = !state.editMode;
        applyEditableMode();
      });
    }

    if (btnSave) btnSave.addEventListener("click", saveEdits);
    if (btnPdf) btnPdf.addEventListener("click", downloadPDF);

    if (btnAddPresent) {
      btnAddPresent.addEventListener("click", () => {
        state.minutes.present_list.push({ name: "", position: "" });
        renderPresentAbsent();
      });
    }

    if (btnAddAbsent) {
      btnAddAbsent.addEventListener("click", () => {
        state.minutes.absent_list.push({ name: "", position: "" });
        renderPresentAbsent();
      });
    }
  }

  window.BPSS_MinutesPrint = {
    setMinutes(obj) {
      state.minutes = obj && typeof obj === "object" ? obj : {};
      renderAll();
    },
    getMinutes() {
      return state.minutes;
    },
  };

  document.addEventListener("DOMContentLoaded", () => {
    bindToolbar();
  });
})();
