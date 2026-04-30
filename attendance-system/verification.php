<?php
require_once __DIR__ . "/bootstrap.php";
require_once __DIR__ . "/auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated
?>
<button onclick="window.location.href='biometrics://verify'">
  Verify
</button>
