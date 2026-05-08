<?php
// Email Configuration for PHPMailer
// Updated with user's Gmail credentials for Vet Clinic Project

return [
    'host' => 'smtp.gmail.com',
    'username' => 'diana.villacorta@csucc.edu.ph',
    'password' => 'bwus ybdd iife kbiq',
    'from_email' => 'diana.villacorta@csucc.edu.ph',
    'from_name' => 'VET Clinic',
    'smtp_secure' => 'tls',  // Use string instead of constant
    'port' => 587,

    // Development mode settings
    'development_mode' => false,  // Set to false to enable actual email sending
    'log_otp_in_dev' => true    // Log OTPs in error log during development
];
?>