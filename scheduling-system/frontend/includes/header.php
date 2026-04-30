<?php
// frontend/includes/header.php

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Get current page
$currentPage = $_GET['page'] ?? 'dashboard';

// Format page title (convert dashboard → Dashboard)
$pageTitle = ucwords(str_replace('_', ' ', $currentPage));
?>
<!doctype html>
<html lang="en">
<head>
  <!-- Apply saved theme before CSS renders to prevent flash -->
  <script>(function(){var t=localStorage.getItem('bpss-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})()</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $pageTitle; ?> | Barangay PSS</title>
  <link rel="icon" type="image/png" href="frontend/assets/images/seal-nobg.png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="frontend/css/app.css" rel="stylesheet">
  <link rel="stylesheet" href="frontend/css/confirm_modal.css">

  <?php if (isset($page) && $page === 'create_event'): ?>
      <link rel="stylesheet" href="frontend/css/create_event.css">
      <link rel="stylesheet" href="frontend/css/create_event_modal.css">
  <?php endif; ?>

  <?php if (isset($page) && $page === 'minutes'): ?>
      <link rel="stylesheet" href="frontend/css/minutes.css">
      <link rel="stylesheet" href="frontend/css/minutes_print.css">
  <?php endif; ?>


  <?php if (isset($page) && $page === 'logs'): ?>
       <link rel="stylesheet" href="frontend/css/logs.css">
  <?php endif; ?>



</head>
<body class="bpss-body">