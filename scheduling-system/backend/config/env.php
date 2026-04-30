<?php
// backend/config/env.php
declare(strict_types=1);

/**
 * Minimal .env loader (no composer)
 * Reads KEY=VALUE, ignores blank lines and comments (# or ;)
 */
function bpss_load_env(string $path): void {
  if (!is_file($path)) return;

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!$lines) return;

  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;

    $pos = strpos($line, '=');
    if ($pos === false) continue;

    $key = trim(substr($line, 0, $pos));
    $val = trim(substr($line, $pos + 1));

    if ($key === '') continue;

    // strip wrapping quotes
    $vlen = strlen($val);
    if ($vlen >= 2 &&
        (($val[0] === '"'  && $val[$vlen - 1] === '"') ||
         ($val[0] === "'"  && $val[$vlen - 1] === "'"))) {
      $val = substr($val, 1, -1);
    }

    // don't overwrite existing env
    if (getenv($key) === false) {
      putenv($key . '=' . $val);
      $_ENV[$key] = $val;
    }
  }
}