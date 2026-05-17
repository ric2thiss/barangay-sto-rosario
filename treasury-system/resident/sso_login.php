<?php
session_start();
include "../config/database.php";

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit;
}

// Token format: base64payload.signature
$parts = explode('.', $token);
if (count($parts) !== 2) {
    die("Invalid SSO token format.");
}

$encodedPayload = $parts[0];
$signature = $parts[1];

// 1. Validate Signature
$secret = SSO_SECRET;
if (str_starts_with($secret, 'base64:')) {
    $secret = base64_decode(substr($secret, 7));
}

$expectedSignature = hash_hmac('sha256', $encodedPayload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    die("SSO Token signature validation failed.");
}

// 2. Decode Payload
$payload = json_decode(base64_decode($encodedPayload), true);

if (!$payload) {
    die("Failed to decode SSO payload.");
}

// 3. Check Expiration (60 seconds)
$timestamp = $payload['timestamp'] ?? 0;
if (time() - $timestamp > 60) {
    die("SSO Token has expired.");
}

// 4. Set Session
$_SESSION['resident_id'] = $payload['resident_id'];
$_SESSION['resident_name'] = $payload['name'];
$_SESSION['name'] = $payload['name']; // Some templates use $_SESSION['name']
$_SESSION['resident_username'] = $payload['username'];
$_SESSION['user_type'] = $payload['user_type'];

// Redirect to resident dashboard
header("Location: pending_payments.php");
exit;
