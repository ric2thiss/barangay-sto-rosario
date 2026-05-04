<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    @include('partials.head')
    <style>
        /* Admin Sidebar Styles - Premium Blue Design */
        :root {
            --sidebar-bg: #1F3A93;
            --sidebar-active: #ffffff;
            --sidebar-text: rgba(255, 255, 255, 0.85);
            --sidebar-hover: rgba(255, 255, 255, 0.1);
            --sidebar-user-bg: #1a317d;
            --accent-color: #3b82f6;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        /* Sidebar Container */
        .admin-sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: var(--sidebar-bg);
            color: #ffffff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .admin-sidebar-container.collapsed {
            width: 80px;
        }

        /* Header */
        .admin-sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
            background: var(--sidebar-bg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-sidebar-header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            color: #ffffff;
        }

        .admin-sidebar-header .logo img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            background: white;
            padding: 2px;
            flex-shrink: 0;
        }

        .admin-sidebar-container.collapsed .logo span {
            display: none;
        }

        .admin-burger-icon {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 14px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .admin-burger-icon:hover {
            background: #ffffff;
            color: var(--sidebar-bg);
        }

        /* Navigation */
        .admin-sidebar-nav {
            flex: 1;
            padding: 15px 0;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .admin-sidebar-nav::-webkit-scrollbar {
            display: none;
        }

        .admin-sidebar-nav ul {
            list-style: none;
            padding: 0 12px;
            margin: 0;
        }

        .admin-sidebar-nav li {
            position: relative;
            margin-bottom: 4px;
        }

        .nav-group-label {
            padding: 15px 15px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 1.2px;
        }

        .admin-sidebar-container.collapsed .nav-group-label {
            display: none;
        }

        .admin-sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
        }

        .admin-sidebar-container.collapsed .admin-sidebar-nav a span {
            display: none;
        }

        .admin-sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
            transform: translateX(3px);
        }

        .admin-sidebar-nav a.active {
            background: #ffffff;
            color: var(--sidebar-bg);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .admin-sidebar-nav a.active:hover {
            transform: none;
        }

        .admin-sidebar-nav a i {
            font-size: 18px;
            min-width: 24px;
            text-align: center;
        }

        .nav-separator {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 10px 15px;
        }

        .logout-item {
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .logout-item:hover {
            background: #ef4444 !important;
            border-color: #ef4444 !important;
            color: white !important;
        }

        /* User Section */
        .admin-sidebar-user {
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: var(--sidebar-user-bg);
        }

        .admin-sidebar-user-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }

        .admin-sidebar-user-content:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .admin-sidebar-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff, #bae6fd);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            color: var(--sidebar-bg);
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-sidebar-user-info {
            flex: 1;
            min-width: 0;
        }

        .admin-sidebar-container.collapsed .admin-sidebar-user-info,
        .admin-sidebar-container.collapsed .admin-sidebar-user-content i {
            display: none;
        }

        .admin-username {
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-user-role {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.5);
            display: block;
            margin-top: 1px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Main Content Adjustment */
        .main-content {
            margin-left: 260px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            padding: 25px;
        }

        .admin-sidebar-container.collapsed ~ .main-content {
            margin-left: 80px;
        }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            .admin-sidebar-container {
                transform: translateX(-100%);
                width: 280px !important; /* Fixed width on mobile */
            }

            .admin-sidebar-container.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                padding: 15px;
                padding-top: 75px;
            }

            .mobile-header {
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: white;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                z-index: 999;
                align-items: center;
                padding: 0 15px;
                justify-content: space-between;
            }

            .mobile-menu-toggle {
                font-size: 18px;
                color: var(--sidebar-bg);
                cursor: pointer;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8fafc;
                border-radius: 10px;
            }

            .mobile-logo-wrap {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .mobile-logo-wrap img {
                height: 32px;
                width: 32px;
                border-radius: 50%;
            }

            .mobile-logo-wrap span {
                font-weight: 700;
                font-size: 14px;
                color: #0f172a;
            }
        }

        @media (min-width: 1025px) {
            .mobile-header {
                display: none;
            }
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 998;
            backdrop-filter: blur(4px);
        }

        .mobile-overlay.show {
            display: block;
        }

        /* Tooltips for collapsed state */
        .admin-sidebar-container.collapsed .admin-sidebar-nav a {
            position: relative;
        }

        .admin-sidebar-container.collapsed .admin-sidebar-nav a:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #0f172a;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            margin-left: 15px;
            z-index: 1001;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    @php
        $roleName = auth()->user()?->role?->role_name;
        $isCollapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
    @endphp

    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="mobile-menu-toggle" id="mobileMenuOpen">
            <i class="fas fa-bars"></i>
        </div>
        <div class="mobile-logo-wrap">
            <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo">
            <span>Barangay Service</span>
        </div>
        <div class="w-[40px]"></div> <!-- Balancer -->
    </header>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar-container {{ $isCollapsed ? 'collapsed' : '' }}" id="adminSidebar">
        <div class="admin-sidebar-header">
            <div class="logo">
                <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo">
                <span>Services System</span>
            </div>
            <button class="admin-burger-icon" id="toggleSidebar">
                <i class="fas {{ $isCollapsed ? 'fa-bars' : 'fa-times' }}"></i>
            </button>
        </div>

        <nav class="admin-sidebar-nav">
            <ul>
                @if(in_array($roleName, ['Admin', 'Secretary']))
                    <div class="nav-group-label">Main Menu</div>
                    <li>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dashboard" wire:navigate>
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('incident-areas.index') }}" class="{{ request()->routeIs('incident-areas.*') ? 'active' : '' }}" data-tooltip="Incident Areas" wire:navigate>
                            <i class="fas fa-map-pin"></i>
                            <span>Incident Areas</span>
                        </a>
                    </li>

                    <div class="nav-group-label">Services</div>
                    <li>
                        <a href="{{ route('certificates.index') }}" class="{{ request()->routeIs('certificates.index') ? 'active' : '' }}" data-tooltip="Certificates" wire:navigate>
                            <i class="fas fa-file-alt"></i>
                            <span>Certificates</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.barangay-clearance') }}" class="{{ request()->routeIs('certificates.barangay-clearance') ? 'active' : '' }}" data-tooltip="Barangay Clearance" wire:navigate>
                            <i class="fas fa-file-contract"></i>
                            <span>Barangay Clearance</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.barangay-permit') }}" class="{{ request()->routeIs('certificates.barangay-permit') ? 'active' : '' }}" data-tooltip="Barangay Permit" wire:navigate>
                            <i class="fas fa-file-signature"></i>
                            <span>Barangay Permit</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.certificate-of-residency') }}" class="{{ request()->routeIs('certificates.certificate-of-residency') ? 'active' : '' }}" data-tooltip="Residency" wire:navigate>
                            <i class="fas fa-home"></i>
                            <span>Residency Cert</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.good-moral-certificate') }}" class="{{ request()->routeIs('certificates.good-moral-certificate') ? 'active' : '' }}" data-tooltip="Good Moral" wire:navigate>
                            <i class="fas fa-user-check"></i>
                            <span>Good Moral</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.indigency-certificate') }}" class="{{ request()->routeIs('certificates.indigency-certificate') ? 'active' : '' }}" data-tooltip="Indigency" wire:navigate>
                            <i class="fas fa-hand-holding-heart"></i>
                            <span>Indigency Cert</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('blotter.index') }}" class="{{ request()->routeIs('blotter.index') ? 'active' : '' }}" data-tooltip="Blotter" wire:navigate>
                            <i class="fas fa-shield-alt"></i>
                            <span>Blotter</span>
                        </a>
                    </li>

                    @if($roleName === 'Admin')
                        <div class="nav-group-label">Administration</div>
                        <li>
                            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" data-tooltip="User Management" wire:navigate>
                                <i class="fas fa-users-cog"></i>
                                <span>User Management</span>
                            </a>
                        </li>
                    @endif
                @else
                    <div class="nav-group-label">Services</div>
                    <li>
                        <a href="{{ route('blotter.resident_index') }}" class="{{ request()->routeIs('blotter.resident_index') ? 'active' : '' }}" data-tooltip="Blotter Reports" wire:navigate>
                            <i class="fas fa-shield-alt"></i>
                            <span>Blotter Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('certificates.resident_index') }}" class="{{ request()->routeIs('certificates.resident_index') ? 'active' : '' }}" data-tooltip="Certificate Requests" wire:navigate>
                            <i class="fas fa-file-invoice"></i>
                            <span>Cert Requests</span>
                        </a>
                    </li>
                @endif

                <div class="nav-separator"></div>

                <li>
                    <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                        @csrf
                        <a href="#" class="logout-item" onclick="event.preventDefault(); if(confirm('Are you sure you want to logout?')) document.getElementById('logoutForm').submit();" data-tooltip="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </form>
                </li>
            </ul>
        </nav>

        <div class="admin-sidebar-user">
            <a href="/settings/profile" class="admin-sidebar-user-content" wire:navigate>
                <div class="admin-sidebar-user-avatar">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="admin-sidebar-user-info">
                    <div class="admin-username">{{ auth()->user()->name }}</div>
                    <div class="admin-user-role">{{ $roleName }}</div>
                </div>
                <i class="fas fa-cog text-white/30 text-xs ml-auto"></i>
            </a>
        </div>
    </aside>

    <main class="main-content" id="mainContent">
        {{ $slot }}
    </main>

    @fluxScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('adminSidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const mobileOpenBtn = document.getElementById('mobileMenuOpen');
            const mobileOverlay = document.getElementById('mobileOverlay');

            // Toggle Sidebar function
            function toggleSidebar() {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                } else {
                    const isCollapsed = sidebar.classList.toggle('collapsed');
                    
                    // Update icon
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        if (isCollapsed) {
                            icon.classList.replace('fa-times', 'fa-bars');
                        } else {
                            icon.classList.replace('fa-bars', 'fa-times');
                        }
                    }

                    // Save state
                    document.cookie = "sidebar_collapsed=" + isCollapsed + "; path=/; max-age=" + (30 * 24 * 60 * 60);
                }
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleSidebar);
            }

            // Mobile menu open
            if (mobileOpenBtn) {
                mobileOpenBtn.addEventListener('click', function() {
                    sidebar.classList.add('mobile-open');
                    mobileOverlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });
            }

            // Mobile menu close (click overlay)
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }

            // Close mobile sidebar on navigation (for Livewire wire:navigate)
            document.addEventListener('livewire:navigating', () => {
                sidebar.classList.remove('mobile-open');
                mobileOverlay.classList.remove('show');
                document.body.style.overflow = '';
            });
        });
    </script>
</body>

</html>