<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// project root: backend/config -> ../../
bpss_load_env(__DIR__ . '/../../.env');

define('REMOVEBG_API_KEY', (string)(getenv('REMOVEBG_API_KEY') ?: ($_ENV['REMOVEBG_API_KEY'] ?? '')));

if (REMOVEBG_API_KEY === '') {
  throw new RuntimeException('Missing REMOVEBG_API_KEY. Add it to your .env file.');
}