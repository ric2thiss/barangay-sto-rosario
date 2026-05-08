<?php
require_once 'config/config.php';

$users = [
    ['firstname' => 'Admin', 'lastname' => 'User', 'purok' => 'Purok 1', 'username' => 'admin_test', 'email' => 'admin_test@gmail.com', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'user_type' => 'admin', 'is_active' => 1],
    ['firstname' => 'Resident', 'lastname' => 'User', 'purok' => 'Purok 2', 'username' => 'user_test', 'email' => 'user_test@gmail.com', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'user_type' => 'user', 'is_active' => 1]
];

foreach ($users as $user) {
    $check = $conn->query("SELECT id FROM users WHERE email='{$user['email']}'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, purok, username, email, password, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $user['firstname'], $user['lastname'], $user['purok'], $user['username'], $user['email'], $user['password'], $user['user_type'], $user['is_active']);
        $stmt->execute();
        echo "Created {$user['email']}\n";
    } else {
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $user['password'], $user['email']);
        $stmt->execute();
        echo "Updated {$user['email']}\n";
    }
}
?>