<?php
require_once __DIR__ . "/bootstrap.php";
require_once __DIR__ . "/auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Assume you already fetched employee data from your QueryBuilder model
$employeeId = 20201188;
?>

ID: <?php echo $employeeId; ?>
<button onclick="window.location.href='biometrics://enroll?employee_id=<?php echo $employeeId; ?>'">
    Register Biometrics
</button>
