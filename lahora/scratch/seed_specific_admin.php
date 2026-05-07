<?php
require_once 'php/connection.php';

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate users table to reset ID
echo "Truncating users table...\n";
if ($conn->query("TRUNCATE TABLE `users`")) {
    echo "Users table truncated.\n";
} else {
    echo "Error truncating users table: " . $conn->error . "\n";
}

// Seed the specific super admin
echo "Seeding new super admin...\n";

$firstName = "Ric Charles";
$lastName = "Paquibot";
$username = "ric2thiss";
$password = "Godlovesme2021";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$idNo = "2026-0001"; // Consistent with previous ID format
$role = "super_admin";
$status = "active";

// Answers (hashed as per previous SQL structure if they were hashed, but let's check the SQL again)
// Looking at previous SQL: answer1, answer2, answer3 were hashed: '$2y$10$...'
$answer1 = password_hash("aa", PASSWORD_DEFAULT);
$answer2 = password_hash("bb", PASSWORD_DEFAULT);
$answer3 = password_hash("bb", PASSWORD_DEFAULT);

$question1 = "Who is your bestfriend in elementary?";
$question2 = "What is the name of your favorite pet?";
$question3 = "Who is your favorite teacher in highschool?";

$sql = "INSERT INTO `users` (
    `id`, `idNo`, `firstName`, `lastName`, `username`, `password`, 
    `security_question1`, `answer1`, `security_question2`, `answer2`, `security_question3`, `answer3`, 
    `role`, `status`, `municipality`
) VALUES (
    1, ?, ?, ?, ?, ?, 
    ?, ?, ?, ?, ?, ?, 
    ?, ?, 'Butuan City'
)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssssssss", 
    $idNo, $firstName, $lastName, $username, $hashedPassword, 
    $question1, $answer1, $question2, $answer2, $question3, $answer3, 
    $role, $status
);

if ($stmt->execute()) {
    echo "Super admin seeded successfully: $username\n";
} else {
    echo "Error seeding super admin: " . $stmt->error . "\n";
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->close();
echo "Done.\n";
?>
