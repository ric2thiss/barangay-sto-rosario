document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("eventForm");
  if (!form) return;

  const stepHint = document.getElementById("stepHint");
  const btnPrev  = document.getElementById("btnPrev");
  const btnNext  = document.getElementById("btnNext");
  const btnSave  = document.getElementById("btnSave");

  const panes = Array.from(document.querySelectorAll(".step-pane"));
  const pills = Array.from(document.querySelectorAll(".step-pill"));

  const btnRefreshPreview = document.getElementById("btnRefreshPreview");

  let currentStep = 1;
  const totalSteps = 4;

  function setPills(step) {
    pills.forEach(p => {
      const s = Number(p.getAttribute("data-step-pill"));
      p.classList.toggle("active", s === step);

      // ✅ clickable feel
      p.style.cursor = "pointer";
      p.title = `Go to Step ${s}`;
      p.setAttribute("role", "button");
      p.setAttribute("tabindex", "0"); // allow keyboard focus
      p.setAttribute("aria-label", `Go to Step ${s}`);
    });
  }

  function setButtons(step) {
    btnPrev.classList.toggle("d-none", step === 1);
    btnNext.classList.toggle("d-none", step === totalSteps);
    btnSave.classList.toggle("d-none", step !== totalSteps);
    stepHint.textContent = `Step ${step} of ${totalSteps}`;
  }

  function showStep(step) {
    currentStep = Math.max(1, Math.min(totalSteps, step));

    panes.forEach(p => {
      const s = Number(p.getAttribute("data-step"));
      p.classList.toggle("d-none", s !== currentStep);
    });

    setPills(currentStep);
    setButtons(currentStep);

    // Step 4 always refresh preview when arriving
    if (currentStep === totalSteps) {
      if (typeof window.BPSS_buildPreviewMessage === "function") {
        window.BPSS_buildPreviewMessage();
      }
      btnSave.focus();
    } else {
      btnNext.focus();
    }
  }

  function validateStep(step) {
    // Validate Step 1 required fields
    if (step === 1) {
      const title = document.getElementById("evTitle");
      const start = document.getElementById("evStart");
      const end   = document.getElementById("evEnd");

      if (!title.value.trim()) { title.focus(); return false; }
      if (!start.value) { start.focus(); return false; }
      if (!end.value) { end.focus(); return false; }

      const s = new Date(start.value);
      const e = new Date(end.value);
      if (!isNaN(s.getTime()) && !isNaN(e.getTime()) && e <=  s) {
        end.focus();
        window.BPSS_toastMsg?.("End date/time must be after start.", "danger");
        return false;
      }
    }
    return true;
  }

  // ✅ central jump function (used by pills/buttons)
  function goToStep(targetStep) {
    targetStep = Number(targetStep);
    if (!targetStep || targetStep < 1 || targetStep > totalSteps) return;

    // If jumping forward, validate current step first
    if (targetStep > currentStep) {
      if (!validateStep(currentStep)) return;
    }

    showStep(targetStep);
  }

  btnNext.addEventListener("click", () => {
    if (!validateStep(currentStep)) return;
    showStep(currentStep + 1);
  });

  btnPrev.addEventListener("click", () => {
    showStep(currentStep - 1);
  });

  // ✅ Clickable pills (mouse)
  pills.forEach(p => {
    p.addEventListener("click", () => {
      const target = p.getAttribute("data-step-pill");
      goToStep(target);
    });

    // ✅ Clickable pills (keyboard Enter/Space)
    p.addEventListener("keydown", (e) => {
      if (e.key !== "Enter" && e.key !== " ") return;
      e.preventDefault();
      const target = p.getAttribute("data-step-pill");
      goToStep(target);
    });
  });

  if (btnRefreshPreview) {
    btnRefreshPreview.addEventListener("click", () => {
      if (typeof window.BPSS_buildPreviewMessage === "function") {
        window.BPSS_buildPreviewMessage(true);
      }
    });
  }

  // ENTER KEY: Next until Step 4, then Save
  // - Do not hijack textarea Enter
  form.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;

    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : "";
    if (tag === "textarea") return;

    e.preventDefault();

    if (currentStep < totalSteps) {
      btnNext.focus();
      btnNext.click();
    } else {
      btnSave.focus();
      btnSave.click(); // triggers form submit
    }
  });

  // Expose for other scripts (core/calendar)
  window.BPSS_showStep = showStep;
  window.BPSS_getStep = () => currentStep;
  window.BPSS_goToStep = goToStep;

  showStep(1);
});