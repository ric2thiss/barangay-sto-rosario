<?php
require_once 'php/connection.php';

// Disable foreign key checks to allow truncating
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "Truncating tables...\n";
foreach ($tables as $table) {
    if ($conn->query("TRUNCATE TABLE `$table`")) {
        echo "Table `$table` truncated and ID reset.\n";
    } else {
        echo "Error truncating table `$table`: " . $conn->error . "\n";
    }
}

// Seed 1 super admin
echo "Seeding super admin...\n";
$sql = "INSERT INTO `users` (`id`, `idNo`, `firstName`, `middleName`, `lastName`, `extension`, `birthday`, `age`, `sex`, `username`, `emailAddress`, `profile_picture`, `password`, `purok`, `barangay`, `municipality`, `province`, `country`, `zipCode`, `security_question1`, `answer1`, `security_question2`, `answer2`, `security_question3`, `answer3`, `otp_code`, `otp_expiry`, `role`, `status`, `is_logged_in`, `ip_address`, `device_used`) VALUES
(1, '2026-0001', 'Super', '', 'Admin', '', '2000-01-01', 26, 'Male', 'superadmin', 'ric2thiss1@gmail.com', 'user_1_1774400832.jpg', '$2y$10\$x2mPw24MHaf0Ltq3x0DJU.nMOUL/l/EDcAeZklHKJ/ptw3ZrDwql.', 'Purok 2', 'Barangay Centro', 'Municipality', 'Province', 'Philippines', '8600', 'Who is your bestfriend in elementary?', '$2y$10\$GTrmIYob9MZsAmO7IxFpn.IXPj8SzjHWHws.klv7IqRF6t39Ghqh6', 'What is the name of your favorite pet?', '$2y$10\$Qn.Qq6FsZxoFw1nDxy/ncudNuBquuLlSjKeQ1eKhfQAN4Q/oZwDHm', 'Who is your favorite teacher in highschool?', '$2y$10\$HTXZaH370VuK/ZSUMX2MOeLe/X7xU.2H8wD2LKAXCZIv2PK30QQzS', NULL, NULL, 'super_admin', 'active', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36')";

if ($conn->query($sql)) {
    echo "Super admin seeded successfully.\n";
} else {
    echo "Error seeding super admin: " . $conn->error . "\n";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->close();
echo "Database reset complete.\n";
?>
