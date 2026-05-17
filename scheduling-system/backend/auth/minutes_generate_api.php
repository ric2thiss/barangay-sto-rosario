<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../config/gemini.php';

function out(bool $success, string $message = '', array $extra = []): void {
  echo json_encode(array_merge(['success'=>$success,'message'=>$message], $extra));
  exit;
}

function strip_code_fences(string $s): string {
  $s = preg_replace('/```json/mi', '', $s);
  $s = preg_replace('/```/m', '', $s);
  return trim((string)$s);
}

/**
 * Extracts JSON-ish content from first "{" to end.
 * Keeps it safe if Gemini adds text before JSON.
 */
function extract_json_start(string $s): string {
  $p = strpos($s, '{');
  if ($p === false) return trim($s);
  return trim(substr($s, $p));
}

/**
 * Remove trailing commas like:
 *   ... , }  or  ... , ]
 * And trailing comma at end of string.
 */
function remove_trailing_commas(string $s): string {
  $s = preg_replace('/,\s*([}\]])/', '$1', $s);
  $s = preg_replace('/,\s*$/', '', $s);
  return (string)$s;
}

/**
 * Attempt to repair truncated JSON by balancing brackets/braces.
 * String-aware (won’t count braces inside quoted strings).
 */
function balance_json(string $s): string {
  $stack       = [];
  $out         = '';
  $inStr       = false;
  $esc         = false;
  $lastSafePos = -1; // position of last ',' that was outside a string

  $len = strlen($s);
  for ($i = 0; $i < $len; $i++) {
    $ch = $s[$i];
    $out .= $ch;

    if ($esc) { $esc = false; continue; }
    if ($ch === "\\") { if ($inStr) $esc = true; continue; }
    if ($ch === '"') { $inStr = !$inStr; continue; }
    if ($inStr) continue;

    if ($ch === ',') {
      $lastSafePos = strlen($out) - 1; // position of this comma in $out
    }

    if ($ch === '{' || $ch === '[') {
      $stack[] = $ch;
    } elseif ($ch === '}' || $ch === ']') {
      if (!$stack) continue;
      $top = end($stack);
      if (($ch === '}' && $top === '{') || ($ch === ']' && $top === '[')) {
        array_pop($stack);
      }
    }
  }

  // If truncated mid-string, trim back to last safe comma to drop the incomplete item
  if ($inStr) {
    if ($lastSafePos >= 0) {
      $out = substr($out, 0, $lastSafePos); // trim up to (not including) the comma
    } else {
      // No comma found at all — trim to after the last opening [ or {
      $lastOpen = max((int)strrpos($out, '['), (int)strrpos($out, '{'));
      $out = $lastOpen > 0 ? substr($out, 0, $lastOpen + 1) : '{';
    }

    // Recount stack for the trimmed string
    $stack  = [];
    $inStr2 = false;
    $esc2   = false;
    $tlen   = strlen($out);
    for ($i = 0; $i < $tlen; $i++) {
      $ch = $out[$i];
      if ($esc2) { $esc2 = false; continue; }
      if ($ch === "\\") { if ($inStr2) $esc2 = true; continue; }
      if ($ch === '"') { $inStr2 = !$inStr2; continue; }
      if ($inStr2) continue;
      if ($ch === '{' || $ch === '[') $stack[] = $ch;
      elseif ($ch === '}' || $ch === ']') {
        if (!$stack) continue;
        $top = end($stack);
        if (($ch === '}' && $top === '{') || ($ch === ']' && $top === '[')) array_pop($stack);
      }
    }
  }

  // remove trailing commas before we append closers
  $out = remove_trailing_commas($out);

  // append missing closers in reverse order
  for ($i = count($stack) - 1; $i >= 0; $i--) {
    $open = $stack[$i];
    $out .= ($open === '{') ? '}' : ']';
  }

  // final cleanup
  $out = remove_trailing_commas($out);
  return trim($out);
}

/**
 * If Gemini returns an array of document objects (one per page/image),
 * merge them all into a single object — session header from the first item,
 * and the most populated value wins for every other field.
 */
function merge_document_array(string $s): string {
  $trimmed = trim($s);
  if ($trimmed === '' || $trimmed[0] !== '[') return $s;

  $decoded = json_decode($trimmed, true);
  if (!is_array($decoded) || empty($decoded) || !is_array($decoded[0])) return $s;

  // Use the first object as the base (gives us the primary session header)
  $merged = $decoded[0];

  foreach (array_slice($decoded, 1) as $obj) {
    if (!is_array($obj)) continue;
    foreach ($obj as $k => $v) {
      // Arrays: prefer the longer one
      if (is_array($v) && is_array($merged[$k] ?? null)) {
        if (count($v) > count($merged[$k])) $merged[$k] = $v;
        continue;
      }
      // Strings/scalars: fill in only if current value is empty
      $cur = $merged[$k] ?? null;
      if ($cur === null || $cur === '' || $cur === [] || $cur === false) {
        $merged[$k] = $v;
      }
    }
  }

  return (string)json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Full repair pipeline:
 * - strip fences
 * - merge array-of-docs into single object
 * - extract from first "{"
 * - cleanup commas
 * - balance braces/brackets
 */
function repair_json(string $raw): string {
  $s = strip_code_fences($raw);
  $s = merge_document_array($s);
  $s = extract_json_start($s);
  $s = remove_trailing_commas($s);
  $s = balance_json($s);
  return $s;
}

/**
 * Extract plain text from a .docx file (ZIP-based Office Open XML).
 */
function extract_docx_text(string $path): string {
  $zip = new ZipArchive();
  if ($zip->open($path) !== true) return '';
  $xml = $zip->getFromName('word/document.xml');
  $zip->close();
  if ($xml === false || $xml === '') return '';
  // Paragraph and line-break tags → newlines
  $xml = preg_replace('/<w:br[^>]*\/?>/', "\n", (string)$xml);
  $xml = preg_replace('/<\/w:p>/', "\n", (string)$xml);
  return trim(strip_tags((string)$xml));
}

/**
 * Best-effort plain-text extraction from legacy .doc (OLE binary).
 * Reads printable ASCII runs; not perfect but recovers most body text.
 */
function extract_doc_text(string $path): string {
  $raw = file_get_contents($path);
  if ($raw === false) return '';
  // Keep printable chars + basic whitespace; collapse noise
  $text = preg_replace('/[^\x20-\x7E\r\n\t]/', ' ', $raw);
  $text = preg_replace('/[ \t]{3,}/', '  ', (string)$text);
  $text = preg_replace('/(\r\n|\r|\n){3,}/', "\n\n", (string)$text);
  return trim((string)$text);
}

function normalize_minutes_whitespace(string $text): string {
  $text = preg_replace('/[ \t]+/', ' ', $text);
  $text = preg_replace('/\s*\n\s*/', "\n", (string)$text);
  return trim((string)$text);
}

function normalize_session_date_string(string $value): string {
  $value = trim($value);
  if ($value === '') return '';

  $value = preg_replace('/\bSept\b\.?/i', 'September', $value);
  $value = preg_replace('/\bOct\b\.?/i', 'October', $value);
  $value = preg_replace('/\bNov\b\.?/i', 'November', $value);
  $value = preg_replace('/\bDec\b\.?/i', 'December', $value);
  $value = preg_replace('/\bJan\b\.?/i', 'January', $value);
  $value = preg_replace('/\bFeb\b\.?/i', 'February', $value);
  $value = preg_replace('/\bMar\b\.?/i', 'March', $value);
  $value = preg_replace('/\bApr\b\.?/i', 'April', $value);
  $value = preg_replace('/\bJun\b\.?/i', 'June', $value);
  $value = preg_replace('/\bJul\b\.?/i', 'July', $value);
  $value = preg_replace('/\bAug\b\.?/i', 'August', $value);
  $value = preg_replace('/\s+/', ' ', (string)$value);
  $value = preg_replace('/\s+,/', ',', (string)$value);

  return trim((string)$value, " \t\n\r\0\x0B,");
}

function date_candidate_timestamp(string $value): ?int {
  $value = normalize_session_date_string($value);
  if ($value === '') return null;

  $ts = strtotime($value);
  if ($ts === false) return null;

  $year = (int)date('Y', $ts);
  if ($year < 2000 || $year > 2100) return null;

  return $ts;
}

function extract_session_date_candidates(string $text): array {
  $text = normalize_minutes_whitespace($text);
  if ($text === '') return [];

  $lines = preg_split('/\n+/', $text) ?: [];
  $candidates = [];
  $patterns = [
    '/\b(?:January|February|March|April|May|June|July|August|September|Sept\.?|October|November|December)\s+\d{1,2},\s*\d{4}\b/i',
    '/\b\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}\b/',
  ];

  foreach ($lines as $idx => $line) {
    $line = trim($line);
    if ($line === '') continue;

    foreach ($patterns as $pattern) {
      if (!preg_match_all($pattern, $line, $matches)) continue;

      foreach (($matches[0] ?? []) as $match) {
        $date = normalize_session_date_string((string)$match);
        $ts = date_candidate_timestamp($date);
        if ($ts === null) continue;

        $score = 0;
        if ($idx <= 8) $score += 4;
        if (preg_match('/\b(order and calendar of business|session|held on|date|regular session|special session)\b/i', $line)) {
          $score += 6;
        }
        if (preg_match('/\b(previous minutes|approval of previous minutes|excerpt from the minutes)\b/i', $line)) {
          $score -= 8;
        }

        $windowStart = max(0, $idx - 2);
        $windowEnd = min(count($lines) - 1, $idx + 2);
        for ($j = $windowStart; $j <= $windowEnd; $j++) {
          if ($j === $idx) continue;
          $near = trim((string)$lines[$j]);
          if ($near === '') continue;

          if (preg_match('/\b(order and calendar of business|session|regular session|special session)\b/i', $near)) {
            $score += 3;
          }
          if (preg_match('/\b(previous minutes|approval of previous minutes|excerpt from the minutes)\b/i', $near)) {
            $score -= 4;
          }
        }

        $candidates[] = [
          'value' => $date,
          'score' => $score,
          'line_index' => $idx,
        ];
      }
    }
  }

  usort($candidates, static function (array $a, array $b): int {
    if ($a['score'] === $b['score']) {
      return $a['line_index'] <=> $b['line_index'];
    }
    return $b['score'] <=> $a['score'];
  });

  $unique = [];
  $seen = [];
  foreach ($candidates as $candidate) {
    $key = strtolower((string)$candidate['value']);
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $unique[] = $candidate;
  }

  return $unique;
}

function refine_minutes_payload(array $parsed, string $textCorpus): array {
  if (!array_key_exists('session_date', $parsed)) {
    $parsed['session_date'] = '';
  }

  $existing = normalize_session_date_string((string)($parsed['session_date'] ?? ''));
  $existingTs = date_candidate_timestamp($existing);

  $candidates = extract_session_date_candidates($textCorpus);
  $best = $candidates[0]['value'] ?? '';
  $bestTs = date_candidate_timestamp($best);

  if ($best !== '') {
    $parsed['session_date'] = $best;
  } elseif ($existingTs !== null) {
    $parsed['session_date'] = $existing;
  } else {
    $parsed['session_date'] = '';
  }

  if ($existing !== '' && $best !== '' && $existingTs !== null && $bestTs !== null && $existingTs === $bestTs) {
    $parsed['session_date'] = $existing;
  }

  return $parsed;
}

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  out(false, 'Unauthorized.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  out(false, 'Method not allowed.');
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

$docId = (int)($data['minutes_document_id'] ?? 0);
if ($docId <= 0) out(false, 'Invalid minutes_document_id.');

$createdBy = (int)$_SESSION['user_id'];

try {
  // ✅ doc exists
  $stmt = $pdo->prepare("SELECT * FROM minutes_documents WHERE id = ?");
  $stmt->execute([$docId]);
  $doc = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$doc) out(false, 'Minutes not found.');

  // ✅ Generate ONCE rule: block if already extracted
  $check = $pdo->prepare("
    SELECT id
    FROM minutes_extractions
    WHERE minutes_document_id = ?
    LIMIT 1
  ");
  $check->execute([$docId]);
  $exists = $check->fetchColumn();
  if ($exists) {
    out(false, 'This minutes record is already extracted. If you need to extract again, upload as a NEW minutes record.');
  }

  // ✅ files
  $filesStmt = $pdo->prepare("
    SELECT * FROM minutes_attachments
    WHERE minutes_document_id = ?
    ORDER BY id ASC
  ");
  $filesStmt->execute([$docId]);
  $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$files) out(false, 'No uploaded files found.');

  $roleInstruction = <<<PROMPT
You are an expert data extractor for Philippine Barangay Minutes of Meeting documents.
The uploaded file is a HANDWRITTEN or TYPED official minutes form.

CRITICAL OUTPUT RULES:
- Return ONE single JSON object — NOT an array.
- If multiple images or pages are provided, they are ALL pages of the SAME document. Combine data from all pages into one JSON object.
- No markdown fences, no comments, no explanation — raw JSON only.
- Do NOT invent data. Unknown fields use: "" (empty string), null, or [].
- Preserve the original language exactly as written in the document. Do NOT translate, paraphrase, formalize, or convert Bisaya/Cebuano, Filipino, English, or mixed-language text into another language.
- Keep original wording, spelling, grammar, and code-switching as written, only trimming obvious extra whitespace.
- Output MUST be a complete, valid, properly closed JSON object. Do not stop early.

════════════════════════════
DOCUMENT LAYOUT AWARENESS
════════════════════════════
The document has:
1. A header block: session title, date, time.
2. An attendance block: a table or list with two columns — NAME | POSITION.
   Columns may be separated by: vertical bar (|), tab, dash (—/-), or wide spacing.
3. Roman numeral sections I through XIII, each with a heading and handwritten/typed narrative below it.

════════════════════════════
JSON SCHEMA (return exactly this structure)
════════════════════════════
{
  "session_title": "",
  "session_date": "",
  "session_time": "",

  "present_list": [ { "name": "", "position": "" } ],
  "absent_list":  [ { "name": "", "position": "" } ],
  "others_present": "",

  "presiding_officer": "",
  "prayer_leader": "",
  "adjournment_time": "",
  "present_count": null,
  "has_quorum": null,

  "approval_of_minutes": "",
  "agenda": "",
  "matters_for_information": "",
  "privilege_hour": "",
  "first_reading_resolution": "",
  "first_reading_referral": "",
  "committee_report": "",
  "calendar_of_business": "",
  "third_reading": "",
  "other_matters": "",
  "announcement": "",

  "adjournment_movant": "",
  "adjournment_seconder": "",

  "call_to_order": "",
  "roll_call": "",
  "adjournment": ""
}

════════════════════════════
FIELD-BY-FIELD EXTRACTION RULES
════════════════════════════

--- SESSION HEADER ---
session_title : Full session name found at the top or near "ORDER AND CALENDAR OF BUSINESS".
                Examples: "18th Regular Session", "Special Session No. 3", "1st Regular Session".
session_date  : Date of the CURRENT session, taken from the header/top block of the current document only.
                NEVER use a date mentioned inside "approval of previous minutes", "excerpt from the minutes",
                or past-session references unless that same date is also clearly shown in the current header.
                If multiple dates appear, prefer the one nearest the session title / "ORDER AND CALENDAR OF BUSINESS" / "HELD ON".
                Return the exact current-session date as written in the document header.
                Examples: "September 8, 2023" / "Sept. 8, 2023" / "9/8/2023".
session_time  : Time the session started, as written. Example: "9:00 AM", "2:30 PM".

--- ATTENDANCE ---
present_list  : All persons listed under "PRESENT:" or "PRESENTE:".
                The attendance block is a table or list — read EVERY row, do not stop early.
                Each row = one person. Left column = NAME, right column = POSITION.
                Column separators: | (pipe), tab, dash, or wide spacing.
                Common POSITION values: Punong Barangay, Barangay Kagawad, Sangguniang Barangay Member,
                  SK Chairman, SK Chairperson, Barangay Secretary, Barangay Treasurer.
                Abbreviations to expand: SBM = Sangguniang Barangay Member, P.B./PB = Punong Barangay,
                  "Hon." prefix before names is normal — keep it.
                Checkmarks (✓), "P", or ticks in a column mean PRESENT — include that person.
                Return name and position exactly as written (trim extra whitespace only).

absent_list   : All persons listed under "ABSENT:" in the same table format as present_list.
                "A" or "X" marks in an attendance column may indicate ABSENT.
                Return [] if no absent section exists.

others_present: Text after "OTHERS PRESENT:" or "OTHER OFFICIALS PRESENT:". Return as a single string.

--- CALL TO ORDER (Section I) ---
presiding_officer : Person who called the session to order.
                    Keywords: "called to order by", "presided by", "presiding officer", "presided over by".
                    Usually the Punong Barangay.
prayer_leader     : Person who led the opening prayer.
                    Keywords: "prayer was led by", "invocation by", "led by [NAME] with a prayer",
                    "opened with a prayer by", "prayer led by".
adjournment_time  : Time the session ended.
                    Keywords: "adjourned at", "ended at", "session ended at", "adjourned the session at",
                    "adjourned at exactly", "time of adjournment".
                    Example return value: "10:30 AM".

--- ROLL CALL (Section II) ---
present_count : Integer — number of members present at roll call.
                Keywords: "[N] members were present", "[N] out of [total]", "only [N] members",
                "a total of [N]". Normalize word numbers to digits (e.g. "eight" → 8). Return null if not found.
has_quorum    : JSON boolean true or false — NEVER a string.
                true  → keywords: "quorum is present", "quorum was declared", "presence of a quorum",
                         "there being a quorum", "declared the presence of quorum".
                false → keywords: "no quorum", "quorum was not met", "did not constitute a quorum",
                         "lack of quorum", "no quorum was declared".
                null  → only if quorum is not mentioned at all.

--- SECTION NARRATIVES (Sections III–XII) ---
IMPORTANT: Do NOT copy the section heading/title as the value. Extract only the narrative text below it.
If only a heading exists with no narrative written, return "".
Preserve the original language and wording exactly as written in the document. If the text is in Bisaya/Cebuano, Filipino, or mixed language, return it in that same language.

III  → approval_of_minutes      (Reading and Approval of Previous Minutes)
IV   → agenda                   (Inclusion, Amendments and Approval of Agenda)
V    → matters_for_information  (Matters for Information)
VI   → privilege_hour           (Privilege Hour / Question Hour)
VII  → first_reading_resolution : The resolution/ordinance reference line(s) only.
                                   Keywords: "Reso. No.", "Resolution No.", "Ordinance No.", "Ord. No."
                                   Example: "Reso. No. 001-2023: Requesting..."
                                   Multiple items → join with newline character.
     → first_reading_referral   : The full discussion/narrative of what happened during First Reading.
VIII → committee_report         (Committee Report)
IX   → calendar_of_business     (Calendar of Business — include sub-items 9.1 Unfinished, 9.2 Business of the Day, 9.3 Unassigned)
X    → third_reading            (Ordinance and Resolution for Third Reading)
XI   → other_matters            (Other Matters)
XII  → announcement             (Announcement)

--- ADJOURNMENT (Section XIII) ---
adjournment_movant   : Person who moved for adjournment.
                       Keywords: "Movant:", "Movent:", "moved by", "motion by", "moved to adjourn".
adjournment_seconder : Person who seconded the adjournment motion.
                       Keywords: "Seconded by:", "seconded by", "duly seconded by", "second by".

--- AUTO-BUILT FIELDS (leave as "" when component fields are filled) ---
call_to_order : Leave "" if presiding_officer + prayer_leader + adjournment_time are ALL non-empty.
                Otherwise extract the full narrative from Section I.
roll_call     : Leave "" if present_count + has_quorum are BOTH non-null/non-empty.
                Otherwise extract the full narrative from Section II.
adjournment   : Leave "" if adjournment_movant + adjournment_seconder + adjournment_time are ALL non-empty.
                Otherwise extract the full narrative from Section XIII.

════════════════════════════
HANDWRITING TIPS
════════════════════════════
- Attendance tables may have rows that are partially legible — extract what is readable, skip truly illegible rows.
- Names with "Hon." prefix are Kagawads — keep the prefix.
- Crossed-out or struck-through entries should be skipped.
- If a value appears in both Roman numeral sections AND the attendance block, use the attendance block for present_list/absent_list.
- Do not duplicate entries between present_list and absent_list.
PROMPT;

  $parts = [];
  $parts[] = ["text" => $roleInstruction];
  $textCorpusParts = [];

  foreach ($files as $f) {
    $fullPath = __DIR__ . '/../../' . ltrim((string)$f['stored_path'], '/');
    if (!is_file($fullPath)) continue;

    $mime = (string)($f['mime_type'] ?? '');
    if ($mime === '') {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = ($finfo ? (string)finfo_file($finfo, $fullPath) : '') ?: 'application/octet-stream';
      if ($finfo) finfo_close($finfo);
    }

    $ext = strtolower(pathinfo((string)$f['original_name'], PATHINFO_EXTENSION));

    // ── Word documents: extract text and send as a text part ─────────────────
    if ($ext === 'docx' ||
        $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
      $text = extract_docx_text($fullPath);
      if ($text !== '') {
        $textCorpusParts[] = $text;
        $parts[] = ["text" => "=== Document: {$f['original_name']} ===\n" . $text];
      }
      continue;
    }

    if ($ext === 'doc' ||
        $mime === 'application/msword') {
      $text = extract_doc_text($fullPath);
      if ($text !== '') {
        $textCorpusParts[] = $text;
        $parts[] = ["text" => "=== Document: {$f['original_name']} ===\n" . $text];
      }
      continue;
    }

    // ── PDF and images: send as inline_data (Gemini supports both) ────────────
    if ($ext === 'pdf') $mime = 'application/pdf';  // normalise just in case

    $bytes = file_get_contents($fullPath);
    if ($bytes === false) continue;

    $parts[] = [
      "inline_data" => [
        "mime_type" => $mime,
        "data" => base64_encode($bytes),
      ]
    ];
  }

  if (count($parts) < 2) out(false, 'No readable files on disk.');

  $payload = [
    "contents" => [[
      "role" => "user",
      "parts" => $parts,
    ]],
    "generationConfig" => [
      "temperature" => 0.2,
      "topP" => 0.9,
      "maxOutputTokens" => 8192,
      "response_mime_type" => "application/json"
    ],
  ];

  $url = "https://generativelanguage.googleapis.com/v1beta/models/" . rawurlencode(GEMINI_MODEL)
       . ":generateContent?key=" . urlencode(GEMINI_API_KEY);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 120,
  ]);

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) throw new RuntimeException("Curl error: $err");
  if ($code < 200 || $code >= 300) throw new RuntimeException("Gemini HTTP $code: $resp");

  $json = json_decode($resp, true);
  if (!is_array($json)) throw new RuntimeException("Invalid Gemini response JSON.");

  $text = trim((string)($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));
  if ($text === '') throw new RuntimeException("Gemini returned empty output.");

  // ✅ repair pipeline
  $repaired = repair_json($text);

  $parsed = json_decode($repaired, true);

  if (!is_array($parsed)) {
    // still broken → return error wrapper but also include repaired for debugging
    $fallback = [
      "error" => "Invalid JSON received",
      "raw_output" => $text,
      "repaired_output" => $repaired,
    ];
    $repaired = json_encode($fallback, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $parsed = $fallback;
  } else {
    $textCorpus = implode("\n\n", $textCorpusParts);
    $parsed = refine_minutes_payload($parsed, $textCorpus);
    $repaired = (string)json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  // ✅ save extraction ONCE + update status
  $pdo->beginTransaction();

  $ins = $pdo->prepare("
    INSERT INTO minutes_extractions
      (minutes_document_id, extraction_version, extracted_json, model_name, confidence_score)
    VALUES (?, 1, ?, ?, NULL)
  ");
  $ins->execute([$docId, $repaired, GEMINI_MODEL]);

  $pdo->prepare("UPDATE minutes_documents SET status='extracted', updated_at=NOW() WHERE id=?")
      ->execute([$docId]);

  $pdo->commit();

  out(true, 'Extracted.', ['extracted_json' => $repaired]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  out(false, 'Generate failed.');
}
