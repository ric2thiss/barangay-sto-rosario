<?php
require_once 'config/config.php';

// Clean up previous batch with user_test emails
$conn->query("DELETE FROM users WHERE email LIKE 'user_test%@gmail.com'");

$firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'Robert', 'Jessica', 'William', 'Ashley', 'James', 'Amanda', 'Christopher', 'Melissa', 'Joseph', 'Stephanie', 'Richard', 'Rebecca', 'Thomas', 'Laura', 'Charles', 'Michelle', 'Daniel', 'Rachel', 'Matthew', 'Megan'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Jones', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson'];

echo "Inserting 20 users with random fullnames as emails...\n";

$generatedEmails = [];

for ($i = 1; $i <= 20; $i++) {
    do {
        $firstname = $firstNames[array_rand($firstNames)];
        $lastname = $lastNames[array_rand($lastNames)];
        $email = strtolower($firstname) . "." . strtolower($lastname) . "@gmail.com";
    } while (in_array($email, $generatedEmails));
    $generatedEmails[] = $email;

    // Delete any existing user with this new email in DB just in case
    $conn->query("DELETE FROM users WHERE email='{$email}'");

    $purok = "Purok " . rand(1, 7); // assuming 1-7
    $username = strtolower($firstname) . "_" . strtolower($lastname) . rand(10, 99);
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $user_type = 'user';
    $is_active = 1;

    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, purok, username, email, password, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $firstname, $lastname, $purok, $username, $email, $password, $user_type, $is_active);
    $stmt->execute();
    echo "Created user {$i}: {$firstname} {$lastname} ({$email})\n";
}

echo "Done inserting 20 users.\n";
?>