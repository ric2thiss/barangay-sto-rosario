<?php

function Sidebar($nav = null, $data = [], $logo = null)
{
    // Detect current directory depth to adjust paths
    // Get the calling file's directory
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $callingFile = $backtrace[0]['file'] ?? __FILE__;
    $callingDir = dirname($callingFile);
    
    // Calculate relative path from calling file to admin directory
    $adminPath = realpath(__DIR__ . '/../../admin');
    $currentPath = realpath($callingDir);
    
    // Count directory depth difference
    $relativePath = '';
    if ($currentPath && $adminPath && strpos($currentPath, $adminPath) === 0) {
        // Current path is within admin directory
        $relativePath = str_replace($adminPath, '', $currentPath);
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);
        
        if (!empty($relativePath)) {
            // Count how many directories deep we are
            $depth = substr_count($relativePath, DIRECTORY_SEPARATOR) + 1;
            $relativePath = str_repeat('../', $depth);
        }
    }
    
    // Navigation: grouped by section; Font Awesome classes (solid) — load CDN below
    $nav_sections = [
        [
            'heading' => 'Overview',
            'items' => [
                'Dashboard' => ['link' => $relativePath . 'dashboard.php', 'icon' => 'fa-tachometer-alt'],
            ],
        ],
        [
            'heading' => 'People',
            'items' => [
                'Employees' => ['link' => $relativePath . 'employees.php', 'icon' => 'fa-users'],
                'Residents' => ['link' => $relativePath . 'residents.php', 'icon' => 'fa-home'],
                'Biometric Registration' => ['link' => $relativePath . 'biometric-registration.php', 'icon' => 'fa-fingerprint'],
            ],
        ],
        [
            'heading' => 'Attendance',
            'items' => [
                'Attendance Reports' => ['link' => $relativePath . 'attendance-reports.php', 'icon' => 'fa-calendar-check'],
                'Attendance Analytics' => ['link' => $relativePath . 'attendance-analytics.php', 'icon' => 'fa-calendar-check'],
                'Attendance' => ['link' => $relativePath . 'attendance.php', 'icon' => 'fa-calendar-check'],
                'DTR' => ['link' => $relativePath . 'dtr.php', 'icon' => 'fa-calendar-check'],
                'Activities' => ['link' => $relativePath . 'activities.php', 'icon' => 'fa-bell'],
            ],
        ],
        [
            'heading' => 'Visitors',
            'items' => [
                'Visitor Reports' => ['link' => $relativePath . 'visitor-lists.php', 'icon' => 'fa-user-friends'],
                'Visitor Analytics' => ['link' => $relativePath . 'visitor-analytics.php', 'icon' => 'fa-user-friends'],
                'Visitor Logging' => ['link' => $relativePath . 'visitors.php', 'icon' => 'fa-user-friends'],
            ],
        ],
        [
            'heading' => 'Records',
            'items' => [
                'Master Lists' => ['link' => $relativePath . 'master-lists.php', 'icon' => 'fa-list-ul'],
            ],
        ],
        [
            'heading' => 'System',
            'items' => [
                'Accounts' => ['link' => $relativePath . 'accounts.php', 'icon' => 'fa-user-shield'],
                'Settings' => ['link' => $relativePath . 'settings.php', 'icon' => 'fa-cogs'],
            ],
        ],
    ];
    ?>
    <!-- SIDEBAR -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <aside class="sidebar-bg w-64 fixed top-0 left-0 h-screen text-white shadow-xl z-10 transition-transform duration-300 transform -translate-x-full md:translate-x-0 flex flex-col" id="sidebar">
        <!-- Scrollable Content Area -->
        <div class="flex-1 overflow-y-auto sidebar-scrollable">
            <div class="py-6">
                <!-- Logo/System Name -->
                <div class="px-6 mb-8">
                    <div class="flex flex-col items-center text-center space-y-3">
                        <img src=<?=$logo? $logo : '../utils/img/logo.png'?> alt="Logo" class="w-20 h-20 drop-shadow-lg">
                        <span id="app-name" class="text-xl font-bold tracking-tight leading-tight"></span>
                    </div>
                </div>

                <!-- User Greeting Section -->
                <div class="px-4 mb-8">
                    <a href="<?= $relativePath ?>profile.php" class="flex items-center space-x-3 p-3 rounded-xl hover:bg-white hover:bg-opacity-10 transition-all group">
                        <!-- Yellow User Icon -->
                        <div class="flex-shrink-0 w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                            <svg class="w-8 h-8 text-blue-900" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs text-blue-100 font-medium">Welcome back,</p>
                            <p class="font-bold truncate text-sm">
                                <?php
                                if (function_exists('currentUser')) {
                                    $user = currentUser();
                                    echo htmlspecialchars($user ? ($user['full_name'] ?? $user['username']) : 'Guest');
                                } else {
                                    echo 'Guest';
                                }
                                ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="flex flex-col">
                    <?php foreach ($nav_sections as $section): ?>
                        <?php if (!empty($section['heading'])): ?>
                            <p class="px-6 pt-5 pb-2 text-[10px] font-bold uppercase tracking-widest text-blue-200/90"><?= htmlspecialchars($section['heading'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php foreach ($section['items'] as $label => $item): ?>
                            <?php
                            $isActive = ($nav === $label);
                            $linkClass = 'flex items-center px-6 py-3 text-sm font-medium transition-all nav-link ' . ($isActive ? 'active-link' : 'text-blue-100 hover:text-white');
                            $iconClass = isset($item['icon']) ? $item['icon'] : 'fa-circle';
                            ?>
                            <a href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>" class="<?= $linkClass ?>">
                                <span class="w-8 flex justify-center mr-1 flex-shrink-0"><i class="fas <?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?> fa-fw text-base opacity-95" aria-hidden="true"></i></span>
                                <span class="truncate"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <!-- Logout Button -->
        <div class="p-6 mt-auto">
            <?php
            if (!defined("BASE_URL")) {
                require_once __DIR__ . "/../../config/app.config.php";
            }
            ?>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center justify-center p-3 rounded-xl bg-white bg-opacity-10 hover:bg-red-600 transition-all text-sm font-medium group">
                <i class="fas fa-right-from-bracket w-5 mr-2 text-red-400 group-hover:text-white transition-colors text-center" aria-hidden="true"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Sidebar Toggle for Mobile -->
    <button id="sidebar-toggle" class="md:hidden fixed top-4 left-4 z-20 p-2 bg-blue-600 text-white rounded-full shadow-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" 
             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                   d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    <?php
}
