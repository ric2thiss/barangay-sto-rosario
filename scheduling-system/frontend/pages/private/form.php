<?php // frontend/pages/private/form.php ?>
<style>
  @media print {
    .no-print, .bpss-topbar, .bpss-footer, nav { display: none !important; }
    main.bpss-content { margin: 0 !important; padding: 0 !important; }
    .container-fluid { padding: 0 !important; }
    body { background: #fff !important; }
    .form-sheet { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; }
  }

  .form-sheet { max-width: 860px; margin: 0 auto; background: #fff; padding: 2rem; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
  .form-line  { border: 0; border-bottom: 1px solid #333; display: block; min-height: 22px; margin-bottom: 5px; }
  .f-label    { font-weight: 600; font-size: .82rem; white-space: nowrap; min-width: 145px; display: inline-block; }
  .f-row      { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 6px; }
  .f-row .form-line { flex: 1; }
  .f-section  { font-weight: 700; font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; color: #1f3a93; border-bottom: 2px solid #1f3a93; padding-bottom: 2px; margin: 14px 0 6px; }
  .f-num-row  { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 4px; }
  .f-num      { font-size: .82rem; width: 20px; text-align: right; flex-shrink: 0; font-weight: 600; padding-bottom: 2px; }
  .f-num-row .form-line { flex: 1; }
  .two-col    { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .sig-line   { border-top: 1px solid #333; text-align: center; font-size: .72rem; padding-top: 4px; margin-top: 48px; }
</style>

<div class="container-fluid py-3">

  <!-- Toolbar (screen only) -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 no-print">
    <div>
      <h5 class="mb-1 bpss-text-theme">Minutes Form</h5>
      <div class="text-muted small">Printable minutes template for the recording secretary.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" id="btnDownloadFormPdf">
        <i class="bi bi-file-earmark-pdf me-1" id="formPdfIcon"></i>
        <span class="spinner-border spinner-border-sm d-none" id="formPdfSpinner"></span>
        Download as PDF
      </button>
      <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print / Save as PDF
      </button>
    </div>
  </div>

  <div class="form-sheet">

    <!-- Letterhead -->
    <div class="text-center mb-2">
      <div style="font-size:.72rem;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:#888;margin-bottom:1px;">
        Barangay Planning &amp; Scheduling System
      </div>
      <div style="font-size:1.25rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#1f3a93;line-height:1.2;">
        Minutes of the Meeting
      </div>
      <div style="font-size:.72rem;color:#aaa;">Official Recording Form</div>
    </div>
    <hr style="border-color:#1f3a93;border-width:2px;margin:8px 0 10px;">

    <!-- Instructions box (printed on the form) -->
    <div style="border:1px solid #ccc;border-radius:4px;background:#fffbe6;padding:7px 12px;margin-bottom:12px;font-size:.73rem;color:#555;line-height:1.6;">
      <strong style="color:#1f3a93;">&#9432; Instructions for accurate digital extraction:</strong>
      <ol style="margin:4px 0 0 16px;padding:0;">
        <li>Write all entries in <strong>BLOCK / PRINT LETTERS</strong> — avoid cursive or joined writing.</li>
        <li>Stay within each field line; do not write across section borders.</li>
        <li>After filling, take a photo using the <strong>Minutes &rarr; Take Photo</strong> feature for a straight-on, well-lit shot.</li>
        <li>Alternatively, scan the completed form and upload the image or PDF.</li>
      </ol>
    </div>

    <!-- Basic Info -->
    <div class="two-col">
      <div>
        <div class="f-row"><span class="f-label">Title:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Date:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Time:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Venue:</span><span class="form-line"></span></div>
      </div>
      <div>
        <div class="f-row"><span class="f-label">Presiding Officer:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Prayer Leader:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Present Count:</span><span class="form-line"></span></div>
        <div class="f-row"><span class="f-label">Has Quorum:</span><span class="form-line"></span></div>
      </div>
    </div>

    <!-- Members Present -->
    <div class="f-section">Members Present</div>
    <div class="two-col">
      <div>
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="f-num-row"><span class="f-num"><?= $i ?>.</span><span class="form-line"></span></div>
        <?php endfor; ?>
      </div>
      <div>
        <?php for ($i = 6; $i <= 10; $i++): ?>
        <div class="f-num-row"><span class="f-num"><?= $i ?>.</span><span class="form-line"></span></div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Members Absent + Others Present -->
    <div class="two-col" style="margin-top:8px;">
      <div>
        <div class="f-section">Members Absent</div>
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="f-num-row"><span class="f-num"><?= $i ?>.</span><span class="form-line"></span></div>
        <?php endfor; ?>
      </div>
      <div>
        <div class="f-section">Others Present</div>
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="f-num-row"><span class="f-num"><?= $i ?>.</span><span class="form-line"></span></div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Proceedings sections (3 writing lines each) -->
    <?php
    $sections = [
      'Call to Order',
      'Approval of Previous Minutes',
      'Agenda',
      'Matters for Information',
      'Privilege Hour',
      'First Reading — Resolution',
      'First Reading — Referral',
      'Committee Report',
      'Calendar of Business',
      'Third Reading',
      'Other Matters',
      'Announcement',
    ];
    foreach ($sections as $sec):
    ?>
      <div class="f-section"><?= htmlspecialchars($sec) ?></div>
      <span class="form-line"></span>
      <span class="form-line"></span>
      <span class="form-line"></span>
    <?php endforeach; ?>

    <!-- Adjournment -->
    <div class="f-section">Adjournment</div>
    <div class="two-col">
      <div class="f-row"><span class="f-label">Time:</span><span class="form-line"></span></div>
      <div class="f-row"><span class="f-label">Movant:</span><span class="form-line"></span></div>
    </div>
    <div class="f-row" style="max-width:50%;"><span class="f-label">Seconder:</span><span class="form-line"></span></div>

    <!-- Signatures -->
    <div class="two-col" style="margin-top:24px;">
      <div class="sig-line">Prepared by (Signature over Printed Name)</div>
      <div class="sig-line">Approved by (Signature over Printed Name)</div>
    </div>

  </div>
</div>

<script>
document.getElementById("btnDownloadFormPdf").addEventListener("click", async function () {
  const btn     = this;
  const icon    = document.getElementById("formPdfIcon");
  const spinner = document.getElementById("formPdfSpinner");
  const sheet   = document.querySelector(".form-sheet");

  if (!window.html2canvas || !window.jspdf?.jsPDF) {
    alert("PDF libraries not loaded. Please refresh the page and try again.");
    return;
  }

  btn.disabled = true;
  icon.classList.add("d-none");
  spinner.classList.remove("d-none");

  try {
    const canvas = await window.html2canvas(sheet, { scale: 2, useCORS: true, backgroundColor: "#ffffff" });
    const pdf       = new window.jspdf.jsPDF("p", "mm", "a4");
    const pageW     = pdf.internal.pageSize.getWidth();
    const pageH     = pdf.internal.pageSize.getHeight();
    const margin    = 10;
    const usableW   = pageW - margin * 2;
    const imgH      = (canvas.height * usableW) / canvas.width;
    const totalPages = Math.ceil(imgH / (pageH - margin * 2));

    for (let i = 0; i < totalPages; i++) {
      if (i > 0) pdf.addPage();
      const srcY  = i * (pageH - margin * 2) * (canvas.width / usableW);
      const sliceH = Math.min((pageH - margin * 2) * (canvas.width / usableW), canvas.height - srcY);
      const sliceCanvas = document.createElement("canvas");
      sliceCanvas.width  = canvas.width;
      sliceCanvas.height = sliceH;
      sliceCanvas.getContext("2d").drawImage(canvas, 0, srcY, canvas.width, sliceH, 0, 0, canvas.width, sliceH);
      pdf.addImage(sliceCanvas.toDataURL("image/png"), "PNG", margin, margin, usableW, (sliceH * usableW) / canvas.width);
    }

    pdf.save("minutes_form.pdf");
  } catch (err) {
    alert("Failed to generate PDF: " + err.message);
  } finally {
    btn.disabled = false;
    icon.classList.remove("d-none");
    spinner.classList.add("d-none");
  }
});
</script>
