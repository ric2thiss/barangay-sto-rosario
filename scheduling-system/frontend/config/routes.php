<?php
return [
  'public' => [
    'landing' => __DIR__ . '/../pages/public/landing.php',
    'login'   => __DIR__ . '/../pages/public/login.php',
  ],
  'private' => [
    'dashboard'    => __DIR__ . '/../pages/private/dashboard.php',
    'create_event' => __DIR__ . '/../pages/private/create_event.php',
    'calendar'     => __DIR__ . '/../pages/private/calendar.php',
    'reports'      => __DIR__ . '/../pages/private/reports.php',
    'contacts'     => __DIR__ . '/../pages/private/contacts.php',
    'officials'    => __DIR__ . '/../pages/private/officials.php',
    'minutes'      => __DIR__ . '/../pages/private/minutes.php',
    'form'         => __DIR__ . '/../pages/private/form.php',
    'logs'      => __DIR__ . '/../pages/private/logs.php',
    'sms'       => __DIR__ . '/../pages/private/sms.php',
    'accounts'  => __DIR__ . '/../pages/private/accounts.php',
    'profile'   => __DIR__ . '/../pages/private/profile.php',
    'settings'  => __DIR__ . '/../pages/private/settings.php',
  ],
  'default_public'  => 'landing',
  'default_private' => 'dashboard',
];