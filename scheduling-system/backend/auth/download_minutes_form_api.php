<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  exit('Unauthorized.');
}

// Build numbered rows helper
function numRows(int $from, int $to): string {
  $out = '';
  for ($i = $from; $i <= $to; $i++) {
    $out .= "<div class=\"f-num-row\"><span class=\"f-num\">{$i}.</span><span class=\"form-line\"></span></div>";
  }
  return $out;
}

$sections = [
  'Call to Order',
  'Approval of Previous Minutes',
  'Agenda',
  'Matters for Information',
  'Privilege Hour',
  'First Reading &mdash; Resolution',
  'First Reading &mdash; Referral',
  'Committee Report',
  'Calendar of Business',
  'Third Reading',
  'Other Matters',
  'Announcement',
];

$sectionHtml = '';
foreach ($sections as $sec) {
  $sectionHtml .= "<div class=\"f-section\">{$sec}</div>"
                . "<span class=\"form-line\"></span>"
                . "<span class=\"form-line\"></span>"
                . "<span class=\"form-line\"></span>";
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minutes of the Meeting — Official Form</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 28px; }

    @media print {
      body { padding: 0; }
      .no-print { display: none !important; }
    }

    .no-print {
      text-align: right;
      margin-bottom: 14px;
    }
    .no-print button {
      padding: 6px 16px; background: #1f3a93; color: #fff;
      border: none; border-radius: 4px; cursor: pointer; font-size: 13px;
    }
    .no-print button:hover { background: #2e4fc7; }

    .letterhead { text-align: center; margin-bottom: 10px; }
    .letterhead .sys  { font-size: 10px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #888; }
    .letterhead .title { font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #1f3a93; }
    .letterhead .sub  { font-size: 10px; color: #aaa; }

    hr.divider { border: 0; border-top: 2px solid #1f3a93; margin: 8px 0 14px; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .f-row      { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 6px; }
    .f-label    { font-weight: 600; font-size: 12px; white-space: nowrap; min-width: 130px; display: inline-block; }
    .form-line  { flex: 1; border: 0; border-bottom: 1px solid #333; min-height: 20px; display: block; margin-bottom: 5px; }
    .f-section  { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
                  color: #1f3a93; border-bottom: 2px solid #1f3a93; padding-bottom: 2px; margin: 14px 0 6px; }
    .f-num-row  { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 4px; }
    .f-num      { font-size: 12px; width: 20px; text-align: right; flex-shrink: 0; font-weight: 600; padding-bottom: 2px; }
    .f-num-row .form-line { flex: 1; }
    .sig-line   { border-top: 1px solid #333; text-align: center; font-size: 10px; padding-top: 4px; margin-top: 48px; }
  </style>
</head>
<body>

  <div class="no-print">
    <button onclick="window.print()">&#128424; Print / Save as PDF</button>
  </div>

  <div class="letterhead">
    <div class="sys">Barangay Planning &amp; Scheduling System</div>
    <div class="title">Minutes of the Meeting</div>
    <div class="sub">Official Recording Form</div>
  </div>
  <hr class="divider">

  <!-- Instructions -->
  <div style="border:1px solid #ccc;border-radius:4px;background:#fffbe6;padding:7px 12px;margin-bottom:12px;font-size:11px;color:#555;line-height:1.6;">
    <strong style="color:#1f3a93;">&#9432; Instructions for accurate digital extraction:</strong>
    <ol style="margin:4px 0 0 16px;padding:0;">
      <li>Write all entries in <strong>BLOCK / PRINT LETTERS</strong> &mdash; avoid cursive or joined writing.</li>
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
    <div>{$left}</div>
    <div>{$right}</div>
  </div>

  <!-- Absent + Others -->
  <div class="two-col" style="margin-top:8px;">
    <div>
      <div class="f-section">Members Absent</div>
      {$absent}
    </div>
    <div>
      <div class="f-section">Others Present</div>
      {$others}
    </div>
  </div>

  <!-- Proceedings -->
  {$sectionHtml}

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

</body>
</html>
HTML;

// Fill placeholders
$html = str_replace('{$left}',        numRows(1, 5),  $html);
$html = str_replace('{$right}',       numRows(6, 10), $html);
$html = str_replace('{$absent}',      numRows(1, 3),  $html);
$html = str_replace('{$others}',      numRows(1, 3),  $html);
$html = str_replace('{$sectionHtml}', $sectionHtml,   $html);

header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="minutes_form.html"');
header('Content-Length: ' . strlen($html));
header('Cache-Control: no-cache');
echo $html;
exit;
