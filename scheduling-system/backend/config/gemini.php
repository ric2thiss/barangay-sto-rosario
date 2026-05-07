<?php
// backend/config/gemini.php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// project root: backend/config -> ../../
bpss_load_env(__DIR__ . '/../../.env');

define('GEMINI_API_KEY', (string)(getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '')));
define('GEMINI_MODEL', (string)(getenv('GEMINI_MODEL') ?: ($_ENV['GEMINI_MODEL'] ?? 'gemini-3-flash-preview')));

if (GEMINI_API_KEY === '') {
  throw new RuntimeException('Missing GEMINI_API_KEY. Add it to your .env file.');
}