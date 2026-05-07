<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: index.php");
    exit();
}
$allowed_types = ['admin', 'staff', 'official', 'resident'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    header("Location: ../resident/dashboard.php");
    exit();
}
// Allow Purok Presidents (residents with staff_position) but block plain residents
$is_purok_president = (($_SESSION['staff_position'] ?? '') === 'Purok President');
if ($_SESSION['user_type'] === 'resident' && !$is_purok_president) {
    header("Location: ../resident/dashboard.php");
    exit();
}
$is_superadmin = ($_SESSION['user_type'] === 'admin')
    || (!empty($_SESSION['is_superadmin']));

include("connection.php");
include('sidebar_counts.php');
// Force collation for all queries on this page too
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
$conn->query("SET collation_connection = utf8mb4_general_ci");

// ── Puroks — unified from BOTH residents AND officials ───────────────────
$purok_result = $conn->query("
    SELECT DISTINCT purok FROM (
        SELECT purok FROM residents       WHERE purok IS NOT NULL AND purok != '' AND is_deleted = 0
        UNION
        SELECT purok FROM barangay_official WHERE purok IS NOT NULL AND purok != '' AND status = 'Active'
    ) AS all_puroks
    ORDER BY purok
");
$puroks = [];
if ($purok_result)
    while ($row = $purok_result->fetch_assoc())
        $puroks[] = $row['purok'];

// ── Officials (static, for Officials table) ──────────────────────────────
$result_officials = $conn->query("
    SELECT * FROM barangay_official WHERE status = 'Active'
    ORDER BY CASE position
        WHEN 'Barangay Captain'   THEN 1
        WHEN 'Barangay Kagawad'   THEN 2
        WHEN 'Sangguniang Barangay (SB) Member' THEN 3
        WHEN 'SK Chairman'        THEN 4
        WHEN 'Barangay Secretary' THEN 5
        WHEN 'Barangay Treasurer' THEN 6
        ELSE 7
    END, first_name
");
$officials = [];
if ($result_officials)
    while ($row = $result_officials->fetch_assoc())
        $officials[] = $row;
$total_officials = count($officials);

// ── Pending approvals badge ──────────────────────────────────────────────
$__pr = $conn->query("SELECT COUNT(*) c FROM pending_registrations WHERE status='Pending'");
$pending_count = $__pr ? (int) $__pr->fetch_assoc()['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Barangay Sto. Rosario</title>
    <?php include 'hybrid_assets.php'; ?>
    <style>
        html,
        body {
            padding: 0 !important;
            margin: 0 !important;
            overflow-x: hidden;
        }

        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #1a56db;
            --primary-light: #e8f0fe;
            --success: #0e9f6e;
            --danger: #e02424;
            --warning: #ff8a00;
            --info: #0891b2;
            --dark: #111827;
            --sidebar-w: 250px;
            --sidebar-bg: #0f172a;
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --muted: #64748b;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0, 0, 0, .08), 0 4px 16px rgba(0, 0, 0, .06);
            --purple: #7c3aed;
            --teal: #0d9488;
            --rose: #be185d;
            --amber: #d97706;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ───────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 28px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, .15);
        }

        .sidebar-brand h2 {
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            text-align: center;
        }

        .sidebar nav {
            padding: 16px 12px;
            flex: 1;
        }

        .sidebar nav ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .65) !important;
            text-decoration: none !important;
            font-size: .875rem !important;
            font-weight: 500 !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            transition: background .15s, color .15s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(255, 255, 255, .1);
            color: #fff !important;
        }

        .sidebar nav a.active {
            background: var(--primary);
        }

        .sidebar nav a i {
            width: 18px;
            text-align: center;
        }

        .nav-badge {
            margin-left: auto;
            background: #e02424;
            color: #fff;
            font-size: .68rem;
            font-weight: 800;
            padding: 1px 7px;
            border-radius: 20px;
            line-height: 1.4;
        }

        /* ── Main ──────────────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 28px 28px 48px;
            max-width: calc(100% - var(--sidebar-w));
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
        }

        .page-header p {
            font-size: .85rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Offline banner ─────────────────────────────────────────────── */
        .offline-banner {
            display: none;
            background: #fff8e1;
            border: 1px solid #ffc107;
            border-left: 4px solid #ff8a00;
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-bottom: 20px;
            align-items: center;
            gap: 12px;
        }

        .offline-banner.show {
            display: flex;
        }

        .offline-banner i {
            font-size: 1.2rem;
            color: #ff8a00;
        }

        .offline-banner .ob-text strong {
            display: block;
            font-size: .9rem;
            font-weight: 700;
            color: #92400e;
        }

        .offline-banner .ob-text span {
            font-size: .82rem;
            color: #78350f;
        }

        .offline-banner .ob-retry {
            margin-left: auto;
            padding: 6px 14px;
            border-radius: 7px;
            border: 1px solid #ff8a00;
            background: #fff;
            color: #92400e;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }

        /* ── Filter card ────────────────────────────────────────────────── */
        .filter-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }

        .filter-card-title {
            font-size: .8rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .filter-group label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .4px;
            display: block;
            margin-bottom: 5px;
        }

        .filter-group select,
        .filter-group input[type=text] {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: .875rem;
            color: var(--text);
            background: #fff;
            outline: none;
            transition: border-color .2s;
            font-family: inherit;
        }

        .filter-group select:focus,
        .filter-group input[type=text]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, .08);
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn:hover {
            opacity: .88;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-secondary {
            background: #f1f5f9;
            border: 1px solid var(--border);
            color: var(--muted);
        }

        .btn-secondary:hover {
            color: var(--text);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        /* ── PWD sub-filter row ─────────────────────────────────────────── */
        #pwdTypeRow {
            display: none;
            grid-column: 1/-1;
            background: linear-gradient(135deg, #fef2f2, #fff5f5);
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 4px;
            animation: fadeSlideIn .2s ease;
        }

        #pwdTypeRow.visible {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            align-items: end;
        }

        #pwdTypeRow .pwd-row-label {
            grid-column: 1/-1;
            font-size: .72rem;
            font-weight: 800;
            color: var(--danger);
            text-transform: uppercase;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Active filter pills ────────────────────────────────────────── */
        .active-filter-strip {
            display: none;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .active-filter-strip.visible {
            display: flex;
        }

        .active-filter-strip>span:first-child {
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
        }

        .af-pill {
            background: var(--primary-light);
            color: var(--primary);
            font-size: .72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .af-pill-red {
            background: #fef2f2;
            color: var(--danger);
            font-size: .72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── Stat grid ──────────────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: var(--shadow);
            transition: transform .15s, box-shadow .15s;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            user-select: none;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .stat-card:active {
            transform: scale(.97);
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .sc-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
        }

        .stat-card .sc-label {
            font-size: .7rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .stat-card .sc-value {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1;
            color: var(--dark);
        }

        .stat-card .sc-sub {
            font-size: .7rem;
            color: var(--muted);
        }

        .sc-blue::before {
            background: var(--primary);
        }

        .sc-blue .sc-icon {
            background: #eff6ff;
            color: var(--primary);
        }

        .sc-green::before {
            background: var(--success);
        }

        .sc-green .sc-icon {
            background: #ecfdf5;
            color: var(--success);
        }

        .sc-teal::before {
            background: var(--teal);
        }

        .sc-teal .sc-icon {
            background: #f0fdfa;
            color: var(--teal);
        }

        .sc-dark::before {
            background: #374151;
        }

        .sc-dark .sc-icon {
            background: #f3f4f6;
            color: #374151;
        }

        .sc-cyan::before {
            background: var(--info);
        }

        .sc-cyan .sc-icon {
            background: #ecfeff;
            color: var(--info);
        }

        .sc-orange::before {
            background: var(--warning);
        }

        .sc-orange .sc-icon {
            background: #fff7ed;
            color: var(--warning);
        }

        .sc-red::before {
            background: var(--danger);
        }

        .sc-red .sc-icon {
            background: #fef2f2;
            color: var(--danger);
        }

        .sc-gray::before {
            background: var(--muted);
        }

        .sc-gray .sc-icon {
            background: #f8fafc;
            color: var(--muted);
        }

        .sc-purple::before {
            background: var(--purple);
        }

        .sc-purple .sc-icon {
            background: #f5f3ff;
            color: var(--purple);
        }

        .sc-rose::before {
            background: var(--rose);
        }

        .sc-rose .sc-icon {
            background: #fff1f2;
            color: var(--rose);
        }

        .sc-amber::before {
            background: var(--amber);
        }

        .sc-amber .sc-icon {
            background: #fffbeb;
            color: var(--amber);
        }

        /* ── Section cards ──────────────────────────────────────────────── */
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            flex: 1;
        }

        .section-header .badge-count {
            background: var(--primary-light);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .section-header i {
            color: var(--primary);
        }

        /* ── Table ──────────────────────────────────────────────────────── */
        .table-wrap {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        thead th {
            background: #f8fafc;
            color: var(--muted);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .1s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody td {
            padding: 9px 12px;
            vertical-align: middle;
        }

        .resident-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        /* ── Pills ──────────────────────────────────────────────────────── */
        .pill {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pill-blue {
            background: #eff6ff;
            color: var(--primary);
        }

        .pill-red {
            background: #fef2f2;
            color: var(--danger);
        }

        .pill-dark {
            background: #f3f4f6;
            color: #374151;
        }

        .pill-cyan {
            background: #ecfeff;
            color: var(--info);
        }

        .pill-orange {
            background: #fff7ed;
            color: var(--warning);
        }

        .pill-gray {
            background: #f8fafc;
            color: var(--muted);
        }

        .pill-purple {
            background: #f5f3ff;
            color: var(--purple);
        }

        .pill-teal {
            background: #f0fdfa;
            color: var(--teal);
        }

        .pill-rose {
            background: #fff1f2;
            color: var(--rose);
        }

        .pill-amber {
            background: #fffbeb;
            color: var(--amber);
        }

        /* ── Loading overlay ────────────────────────────────────────────── */
        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .75);
            backdrop-filter: blur(3px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 14px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner-ring {
            width: 44px;
            height: 44px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        .loading-overlay p {
            font-size: .85rem;
            font-weight: 600;
            color: var(--muted);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .no-data {
            text-align: center;
            padding: 28px;
            color: var(--muted);
            font-size: .875rem;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 8px;
            display: block;
            opacity: .3;
        }

        /* ── Chart system ───────────────────────────────────────────────── */
        .chart-nav {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-bottom: 18px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            position: sticky;
            top: 10px;
            z-index: 50;
        }

        .chart-nav-label {
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
            margin-right: 4px;
        }

        .chart-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            color: var(--muted);
            transition: all .18s;
            text-decoration: none;
        }

        .chart-nav-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary);
        }

        .chart-nav-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .chart-nav-divider {
            width: 1px;
            height: 22px;
            background: var(--border);
            flex-shrink: 0;
        }

        .chart-nav-toggle-all {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            color: var(--muted);
            transition: all .18s;
        }

        .chart-nav-toggle-all:hover {
            background: var(--border);
            color: var(--text);
        }

        .chart-section-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chart-section-card.collapsed .chart-section-body {
            display: none;
        }

        .chart-section-hdr {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            cursor: pointer;
            user-select: none;
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        .chart-section-hdr:hover {
            background: #f8fafc;
        }

        .chart-section-card.collapsed .chart-section-hdr {
            border-bottom: none;
        }

        .chart-section-hdr .csh-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            flex-shrink: 0;
        }

        .chart-section-hdr h5 {
            font-size: .95rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            flex: 1;
        }

        .chart-section-hdr .csh-count {
            font-size: .72rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            background: var(--primary-light);
            color: var(--primary);
        }

        .chart-section-hdr .csh-chevron {
            color: var(--muted);
            font-size: .8rem;
            transition: transform .25s;
            flex-shrink: 0;
        }

        .chart-section-card.collapsed .csh-chevron {
            transform: rotate(-90deg);
        }

        .chart-tabs {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 14px 20px 0;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
        }

        .chart-tab {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            border-radius: 8px 8px 0 0;
            font-size: .8rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            background: transparent;
            color: var(--muted);
            transition: all .15s;
            position: relative;
            bottom: -1px;
        }

        .chart-tab:hover {
            background: #f1f5f9;
            color: var(--text);
        }

        .chart-tab.active {
            background: var(--card-bg);
            border-color: var(--border);
            border-bottom-color: var(--card-bg);
            color: var(--primary);
        }

        .chart-tab-pane {
            display: none;
            padding: 18px 20px;
        }

        .chart-tab-pane.active {
            display: block;
        }

        .chart-filter-pills {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            padding: 12px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }

        .chart-filter-pills span {
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-right: 4px;
        }

        .cfp {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: .76rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--muted);
            transition: all .15s;
        }

        .cfp:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .cfp.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow);
        }

        .chart-card h6 {
            font-size: .875rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-card h6 .cnt {
            background: var(--primary-light);
            color: var(--primary);
            font-size: .7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: auto;
        }

        .chart-card canvas {
            max-height: 220px;
        }

        .demo-section-label {
            background: linear-gradient(135deg, #1a56db, #7c3aed);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 700;
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* ── Print ──────────────────────────────────────────────────────── */
        @media print {
            body {
                background: white !important;
                font-family: Arial, sans-serif !important;
                font-size: 8pt !important;
            }

            .sidebar,
            .filter-card,
            .section-card,
            .loading-overlay,
            .offline-banner,
            .page-header,
            .stat-grid,
            .chart-nav,
            .chart-section-card,
            .chart-nav-toggle-all,
            #loadingOverlay,
            #dashboardModal {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            #printArea {
                display: block !important;
            }

            .print-page {
                page-break-after: always;
            }

            .print-page:last-child {
                page-break-after: avoid;
            }
        }

        #printArea {
            display: none;
        }

        .print-header {
            text-align: center;
            margin-bottom: 8px;
        }

        .print-header h2 {
            font-size: 13pt;
            font-weight: bold;
        }

        .print-header h3 {
            font-size: 10pt;
        }

        .print-header p {
            font-size: 8pt;
            margin: 2px 0;
        }

        .census-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }

        .census-table th,
        .census-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        .census-table thead th {
            background: #c6d9f1 !important;
            font-weight: bold;
            text-align: center;
            font-size: 6.5pt;
        }

        .census-table .group-hdr {
            background: #1a56db !important;
            color: #fff !important;
            font-weight: bold;
            text-align: center;
            font-size: 6pt;
        }

        .health-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }

        .health-table th,
        .health-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .health-table thead th {
            background: #fce8d5 !important;
            font-weight: bold;
            font-size: 6pt;
        }

        .health-table .group-hdr {
            background: #ff8a00 !important;
            color: #fff !important;
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }

        /* ── Dashboard Modal ────────────────────────────────────────────── */
        .dm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(4px);
            z-index: 1050;
            justify-content: center;
            align-items: flex-start;
            padding: 32px 16px;
            overflow-y: auto;
        }

        .dm-overlay.open {
            display: flex;
        }

        .dm-box {
            background: var(--card-bg);
            border-radius: 16px;
            width: 100%;
            max-width: 960px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
            animation: dmSlide .25s ease;
            margin: auto;
        }

        @keyframes dmSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dm-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
        }

        .dm-header h3 {
            flex: 1;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--dark);
            margin: 0;
        }

        .dm-header .dm-count {
            background: var(--primary-light);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .dm-header button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: var(--muted);
            transition: color .15s;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .dm-header button:hover {
            color: var(--danger);
            background: #fef2f2;
        }

        .dm-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }

        .dm-toolbar input {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: .875rem;
            font-family: inherit;
            outline: none;
            transition: border-color .2s;
        }

        .dm-toolbar input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, .08);
        }

        .dm-toolbar .dm-print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            background: var(--primary);
            color: #fff;
            font-family: inherit;
            transition: opacity .15s;
        }

        .dm-toolbar .dm-print-btn:hover {
            opacity: .85;
        }

        .dm-body {
            padding: 0;
            max-height: 60vh;
            overflow-y: auto;
        }

        .dm-body table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }

        .dm-body thead th {
            background: #f8fafc;
            color: var(--muted);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .dm-body tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .1s;
        }

        .dm-body tbody tr:hover {
            background: #f8fafc;
        }

        .dm-body tbody td {
            padding: 9px 14px;
            vertical-align: middle;
        }

        .dm-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: .78rem;
            color: var(--muted);
            font-weight: 600;
        }

        @media(max-width:768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0 !important;
                max-width: 100%;
                padding: 16px;
            }

            .chart-nav {
                position: relative;
                top: 0;
            }

            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .dm-box {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-ring"></div>
        <p>Loading data…</p>
    </div>

    <!-- ── SIDEBAR ──────────────────────────────────────────────────────────── -->
    <?php $current_page = 'dashboard';
    include 'sidebar.php'; ?>

    <!-- ── MAIN ─────────────────────────────────────────────────────────────── -->
    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Barangay Sto. Rosario — Unified Population, Demographics &amp; Officials</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn btn-success" onclick="printResidentList()">
                    <i class="fas fa-print"></i> Print Census List
                </button>
                <a href="export_children_masterlist.php" class="btn btn-primary" id="exportChildrenBtn">
                    <i class="fas fa-file-excel"></i> Export Children Masterlist
                </a>
            </div>
        </div>

        <div class="offline-banner" id="offlineBanner">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="ob-text">
                <strong id="offlineTitle">Database Unavailable</strong>
                <span id="offlineMsg">Could not load data. Make sure XAMPP/MySQL is running.</span>
            </div>
            <button class="ob-retry" onclick="retryLoad()"><i class="fas fa-redo"></i> Retry</button>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
         FILTER BAR
         ══════════════════════════════════════════════════════════════════ -->
        <div class="filter-card">
            <div class="filter-card-title"><i class="fas fa-sliders-h"></i> Data Filters — Residents &amp; Officials
                (Combined)</div>
            <div class="filter-grid">

                <!-- Purok — populated dynamically from AJAX (includes officials-only puroks) -->
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Purok</label>
                    <select id="purokFilter" onchange="loadDashboardData()">
                        <option value="all">All Puroks</option>
                        <?php foreach ($puroks as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Barangay — pre-populated with known barangays, also updated dynamically from AJAX -->
                <div class="filter-group">
                    <label><i class="fas fa-map-signs"></i> Barangay</label>
                    <select id="barangayFilter" onchange="loadDashboardData()">
                        <option value="all">All Barangays</option>
                        <option value="Buhang">Buhang</option>
                        <option value="Caloc-an">Caloc-an</option>
                        <option value="Guiasan">Guiasan</option>
                        <option value="Marcos">Marcos</option>
                        <option value="Poblacion">Poblacion</option>
                        <option value="Santo Niño">Santo Niño</option>
                        <option value="Santo Rosario">Santo Rosario</option>
                        <option value="Taod-oy">Taod-oy</option>
                    </select>
                </div>

                <!-- Category -->
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Category</label>
                    <select id="categoryFilter" onchange="onCategoryChange()">
                        <option value="all">All</option>
                        <option value="children_0_17">Children 0–17 Years Old</option>
                        <option value="pwd">PWD Only</option>
                        <option value="deceased">Deceased Only</option>
                        <option value="newborns">Newborns Only</option>
                        <option value="seniors">Senior Citizens</option>
                        <option value="voters">Registered Voters</option>
                        <option value="4ps">4Ps Beneficiaries</option>
                        <option value="solo_parent">Solo Parents</option>
                        <option value="lgbtq">LGBTQ+</option>
                        <option value="hypertension">With Hypertension</option>
                        <option value="diabetes">With Diabetes</option>
                        <option value="graduates">Graduates</option>
                    </select>
                </div>

                <!-- Socioeconomic Status -->
                <div class="filter-group">
                    <label><i class="fas fa-chart-bar"></i> Socioeconomic Status</label>
                    <select id="sesFilter" onchange="loadDashboardData()">
                        <option value="all">All SES</option>
                        <option value="Poor">Poor</option>
                        <option value="Low Income">Low Income</option>
                        <option value="Lower Middle Income">Lower Middle Income</option>
                        <option value="Middle Income">Middle Income</option>
                        <option value="Upper Middle Income">Upper Middle Income</option>
                        <option value="High Income">High Income</option>
                    </select>
                </div>

                <!-- Voter Status -->
                <div class="filter-group">
                    <label><i class="fas fa-vote-yea"></i> Voter Status</label>
                    <select id="voterStatusFilter" onchange="loadDashboardData()">
                        <option value="all">All</option>
                        <option value="Yes">Registered</option>
                        <option value="No">Not Registered</option>
                    </select>
                </div>

                <!-- Household No. -->
                <div class="filter-group">
                    <label><i class="fas fa-home"></i> Household No.</label>
                    <select id="householdNoFilter" onchange="loadDashboardData()">
                        <option value="all">All Households</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="filter-actions">
                    <button class="btn btn-secondary" onclick="clearFilters()"><i class="fas fa-times"></i>
                        Clear</button>
                </div>

                <!-- PWD Type sub-filter (animated, shown only when Category = PWD) -->
                <div id="pwdTypeRow">
                    <div class="pwd-row-label">
                        <i class="fas fa-wheelchair"></i>
                        Disability Type Filter
                        <span
                            style="font-weight:500;color:#6b7280;font-size:.7rem;text-transform:none;margin-left:4px;">(Step
                            2 — optional)</span>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-list"></i> Type of Disability</label>
                        <select id="pwdTypeFilter" onchange="loadDashboardData()">
                            <option value="all">— All Disability Types —</option>
                            <option value="Physical Disability">💪 Physical Disability (Pisikal nga Kapansanan)</option>
                            <option value="Visual Impairment">👁 Visual Impairment (Kapansanan sa Panan-aw)</option>
                            <option value="Hearing Impairment">👂 Hearing Disability (Kapansanan sa Pandungog)</option>
                            <option value="Speech Impairment">🗣 Speech Disability (Kapansanan sa Pagsulti)</option>
                            <option value="Intellectual Disability">🧠 Intellectual Disability (Panghunahuna nga
                                Kapansanan)</option>
                            <option value="Psychosocial Disability">💙 Psychosocial Disability (Mental/Emosyonal nga
                                Kapansanan)</option>
                            <option value="Multiple Disabilities">♾ Multiple Disabilities (Daghang Kapansanan)</option>
                            <option value="Chronic Illness">🏥 Chronic Illness (Malungtarong Sakit)</option>
                            <option value="Other">📋 Other / Not Specified</option>
                        </select>
                    </div>
                </div>

                <!-- Graduates sub-filter (shown only when Category = Graduates) -->
                <div id="graduatesFilterRow"
                    style="display:none;padding:10px 20px;border-top:1px solid #e2e8f0;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);animation:slideDown .25s ease">
                    <div class="pwd-row-label" style="color:#065f46">
                        <i class="fas fa-graduation-cap"></i>
                        Graduate Filters
                        <span
                            style="font-weight:500;color:#6b7280;font-size:.7rem;text-transform:none;margin-left:4px;">(Step
                            2 — optional)</span>
                    </div>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:8px">
                        <div class="filter-group">
                            <label><i class="fas fa-book"></i> Course</label>
                            <select id="gradCourseFilter" onchange="loadDashboardData()">
                                <option value="all">— All Courses —</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-calendar-alt"></i> Graduation Year</label>
                            <select id="gradYearFilter" onchange="loadDashboardData()">
                                <option value="all">— All Years —</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Active filter pills -->
            <div class="active-filter-strip" id="activeFilterStrip">
                <span>Active:</span>
            </div>
        </div>

        <!-- ── STAT CARDS (clickable → opens modal) ─────────────────────── -->
        <div class="stat-grid">
            <div class="stat-card sc-green" onclick="openModalList('all','Total Population')">
                <div class="sc-icon"><i class="fas fa-users"></i></div>
                <div class="sc-label">Total Population</div>
                <div class="sc-value" id="statPopulation">—</div>
                <div class="sc-sub">Residents + Officials</div>
            </div>
            <div class="stat-card sc-blue" onclick="openModalList('residents','Residents')">
                <div class="sc-icon"><i class="fas fa-user"></i></div>
                <div class="sc-label">Residents</div>
                <div class="sc-value" id="statResidents">—</div>
            </div>
            <div class="stat-card sc-teal" onclick="openModalList('officials','Barangay Officials')">
                <div class="sc-icon"><i class="fas fa-user-tie"></i></div>
                <div class="sc-label">Officials</div>
                <div class="sc-value" id="statOfficials">—</div>
                <div class="sc-sub"><?= $total_officials ?> active</div>
            </div>
            <div class="stat-card sc-red" onclick="openModalList('pwd','PWD List')">
                <div class="sc-icon"><i class="fas fa-wheelchair"></i></div>
                <div class="sc-label">PWD</div>
                <div class="sc-value" id="statPwd">—</div>
            </div>
            <div class="stat-card sc-orange" onclick="openModalList('seniors','Senior Citizens')">
                <div class="sc-icon"><i class="fas fa-user-clock"></i></div>
                <div class="sc-label">Senior Citizens</div>
                <div class="sc-value" id="statSeniors">—</div>
            </div>
            <div class="stat-card sc-cyan" onclick="openModalList('children_0_17','Children 0–17 Years Old')">
                <div class="sc-icon"><i class="fas fa-child"></i></div>
                <div class="sc-label">Children 0-17</div>
                <div class="sc-value" id="statChildren017">—</div>
                <div class="sc-sub">Age 0–17 yrs old</div>
            </div>
            <div class="stat-card sc-purple" onclick="openModalList('lgbtq','LGBTQ+')">
                <div class="sc-icon"><i class="fas fa-rainbow"></i></div>
                <div class="sc-label">LGBTQ+</div>
                <div class="sc-value" id="statLgbtq">—</div>
            </div>
            <div class="stat-card sc-dark" onclick="openModalList('deceased','Deceased')">
                <div class="sc-icon"><i class="fas fa-cross"></i></div>
                <div class="sc-label">Deceased</div>
                <div class="sc-value" id="statDeceased">—</div>
            </div>
            <div class="stat-card sc-gray" onclick="openModalList('voters','Registered Voters')">
                <div class="sc-icon"><i class="fas fa-vote-yea"></i></div>
                <div class="sc-label">Voters</div>
                <div class="sc-value" id="statVoters">—</div>
            </div>
            <div class="stat-card sc-purple" onclick="openModalList('4ps','4Ps Beneficiaries')">
                <div class="sc-icon"><i class="fas fa-hands-helping"></i></div>
                <div class="sc-label">4Ps</div>
                <div class="sc-value" id="stat4ps">—</div>
            </div>
            <div class="stat-card sc-teal" onclick="openModalList('solo_parent','Solo Parents')">
                <div class="sc-icon"><i class="fas fa-user-friends"></i></div>
                <div class="sc-label">Solo Parents</div>
                <div class="sc-value" id="statSoloParent">—</div>
            </div>
            <div class="stat-card sc-purple" onclick="openModalList('nhts','NHTS Households')">
                <div class="sc-icon"><i class="fas fa-home"></i></div>
                <div class="sc-label">NHTS</div>
                <div class="sc-value" id="statNhts">—</div>
            </div>
            <div class="stat-card sc-rose" onclick="openModalList('hypertension','With Hypertension')">
                <div class="sc-icon"><i class="fas fa-heartbeat"></i></div>
                <div class="sc-label">Hypertension</div>
                <div class="sc-value" id="statHypertension">—</div>
            </div>
            <div class="stat-card sc-amber" onclick="openModalList('diabetes','With Diabetes')">
                <div class="sc-icon"><i class="fas fa-syringe"></i></div>
                <div class="sc-label">Diabetes</div>
                <div class="sc-value" id="statDiabetes">—</div>
            </div>
            <div class="stat-card sc-blue" onclick="openModalList('smokers','Smokers')">
                <div class="sc-icon"><i class="fas fa-smoking"></i></div>
                <div class="sc-label">Smokers</div>
                <div class="sc-value" id="statSmokers">—</div>
            </div>
        </div>

        <!-- ── REUSABLE DASHBOARD MODAL ─────────────────────────────────── -->
        <div class="dm-overlay" id="dashboardModal" onclick="if(event.target===this)closeModal()">
            <div class="dm-box">
                <div class="dm-header">
                    <h3 id="dmTitle">Loading…</h3>
                    <span class="dm-count" id="dmCount">0</span>
                    <button onclick="closeModal()" title="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="dm-toolbar">
                    <input type="text" id="dmSearch" placeholder="Search by name, purok…" oninput="filterModalTable()">
                    <button class="dm-print-btn" onclick="printModalData()"><i class="fas fa-print"></i> Print</button>
                </div>
                <div class="dm-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Full Name</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Purok</th>
                                <th>Category / Status</th>
                            </tr>
                        </thead>
                        <tbody id="dmTableBody">
                            <tr>
                                <td colspan="6" class="no-data"><i class="fas fa-circle-notch fa-spin"></i> Loading…
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="dm-footer" id="dmFooter">Showing 0 records</div>
            </div>
        </div>

        <!-- ── CHART NAVIGATION ───────────────────────────────────────────── -->
        <div class="chart-nav">
            <span class="chart-nav-label"><i class="fas fa-chart-bar"></i> Charts</span>
            <a class="chart-nav-btn active" data-section="all" onclick="navFilter('all',this)"><i class="fas fa-th"></i>
                All</a>
            <div class="chart-nav-divider"></div>
            <a class="chart-nav-btn" data-section="officials" onclick="navFilter('officials',this)"><i
                    class="fas fa-user-tie"></i> Officials</a>
            <a class="chart-nav-btn" data-section="population" onclick="navFilter('population',this)"><i
                    class="fas fa-users"></i> Population</a>
            <a class="chart-nav-btn" data-section="demographics" onclick="navFilter('demographics',this)"><i
                    class="fas fa-id-card"></i> Demographics</a>
            <div class="chart-nav-divider"></div>
            <button class="chart-nav-toggle-all" onclick="toggleAllSections()" id="toggleAllBtn">
                <i class="fas fa-compress-alt"></i> Collapse All
            </button>
        </div>

        <!-- ── OFFICIALS ANALYTICS ────────────────────────────────────────── -->
        <div class="chart-section-card" id="sec-officials" data-section="officials">
            <div class="chart-section-hdr" onclick="toggleSection('sec-officials')">
                <div class="csh-icon" style="background:#eff6ff;color:var(--primary)"><i class="fas fa-user-tie"></i>
                </div>
                <h5>Officials Analytics</h5>
                <span class="csh-count">4 charts</span>
                <i class="fas fa-chevron-down csh-chevron"></i>
            </div>
            <div class="chart-section-body">
                <div style="padding:18px 20px">
                    <div class="chart-grid">
                        <div class="chart-card">
                            <h6><i class="fas fa-user-tie" style="color:var(--primary)"></i> By Position <span
                                    class="cnt" id="officialsByPositionCount">0</span></h6><canvas
                                id="officialsPositionChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-map-marker-alt" style="color:var(--success)"></i> By Purok <span
                                    class="cnt" id="officialsByPurokCount">0</span></h6><canvas
                                id="officialsPurokChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-clipboard-list" style="color:var(--warning)"></i> By Chairmanship <span
                                    class="cnt" id="officialsByChairmanshipCount">0</span></h6><canvas
                                id="officialsChairmanshipChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-venus-mars" style="color:var(--info)"></i> By Sex <span class="cnt"
                                    id="officialsBySexCount">0</span></h6><canvas id="officialsSexChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── POPULATION ANALYTICS ───────────────────────────────────────── -->
        <div class="chart-section-card" id="sec-population" data-section="population">
            <div class="chart-section-hdr" onclick="toggleSection('sec-population')">
                <div class="csh-icon" style="background:#ecfdf5;color:var(--success)"><i class="fas fa-users"></i></div>
                <h5>Population Analytics <small style="font-size:.75rem;font-weight:500;color:var(--muted)">(Residents +
                        Officials unified)</small></h5>
                <span class="csh-count">14 charts</span>
                <i class="fas fa-chevron-down csh-chevron"></i>
            </div>
            <div class="chart-section-body">
                <div class="chart-filter-pills">
                    <span><i class="fas fa-filter"></i> Show:</span>
                    <button class="cfp active" data-pop="all" onclick="popFilter('all',this)">All</button>
                    <button class="cfp" data-pop="overview" onclick="popFilter('overview',this)"><i
                            class="fas fa-chart-pie"></i> Overview</button>
                    <button class="cfp" data-pop="distribution" onclick="popFilter('distribution',this)"><i
                            class="fas fa-map-marker-alt"></i> By Purok</button>
                    <button class="cfp" data-pop="demographics" onclick="popFilter('demographics',this)"><i
                            class="fas fa-venus-mars"></i> Demographics</button>
                    <button class="cfp" data-pop="vulnerable" onclick="popFilter('vulnerable',this)"><i
                            class="fas fa-wheelchair"></i> Vulnerable</button>
                    <button class="cfp" data-pop="economy" onclick="popFilter('economy',this)"><i
                            class="fas fa-peso-sign"></i> Economy</button>
                    <button class="cfp" data-pop="education" onclick="popFilter('education',this)"><i
                            class="fas fa-graduation-cap"></i> Education</button>
                </div>
                <div style="padding:18px 20px">
                    <div class="chart-grid">
                        <div class="chart-card" data-groups="overview all">
                            <h6><i class="fas fa-users" style="color:var(--success)"></i> Total Population per Purok
                            </h6><canvas id="populationChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="overview all">
                            <h6><i class="fas fa-user-friends" style="color:var(--primary)"></i> Residents vs Officials
                                per Purok</h6><canvas id="populationSplitChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="economy all">
                            <h6><i class="fas fa-chart-pie" style="color:var(--purple)"></i> Socioeconomic Status
                                Distribution</h6><canvas id="sesChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="economy all">
                            <h6><i class="fas fa-briefcase" style="color:var(--info)"></i> Occupation Type Distribution
                            </h6><canvas id="occupationTypeChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="economy all">
                            <h6><i class="fas fa-peso-sign" style="color:var(--primary)"></i> Monthly Income per Purok
                            </h6><canvas id="incomeChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="demographics all">
                            <h6><i class="fas fa-venus-mars" style="color:var(--info)"></i> Sex Distribution per Purok
                            </h6><canvas id="sexChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="demographics distribution all">
                            <h6><i class="fas fa-layer-group" style="color:var(--warning)"></i> Age Groups per Purok
                            </h6><canvas id="ageGroupsChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="vulnerable all">
                            <h6><i class="fas fa-wheelchair" style="color:var(--danger)"></i> PWD Count per Purok</h6>
                            <canvas id="pwdChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="vulnerable all">
                            <h6><i class="fas fa-universal-access" style="color:var(--purple)"></i> PWD Breakdown by
                                Disability Type</h6><canvas id="pwdTypeChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="vulnerable all">
                            <h6><i class="fas fa-vote-yea" style="color:var(--muted)"></i> Voter Status (Registered vs
                                Not)</h6><canvas id="voterStatusChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="distribution vulnerable all">
                            <h6><i class="fas fa-vote-yea" style="color:var(--info)"></i> Registered Voters per Purok
                            </h6><canvas id="votersChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="vulnerable all">
                            <h6><i class="fas fa-user-clock" style="color:var(--warning)"></i> Senior Citizens per Purok
                            </h6><canvas id="seniorChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="vulnerable all">
                            <h6><i class="fas fa-cross" style="color:#374151"></i> Deceased per Purok</h6><canvas
                                id="deceasedChart"></canvas>
                        </div>
                        <div class="chart-card" data-groups="education all">
                            <h6><i class="fas fa-graduation-cap" style="color:var(--primary)"></i> Educational
                                Attainment per Purok</h6><canvas id="educationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── DEMOGRAPHIC ANALYTICS ──────────────────────────────────────── -->
        <div class="chart-section-card" id="sec-demographics" data-section="demographics">
            <div class="chart-section-hdr" onclick="toggleSection('sec-demographics')">
                <div class="csh-icon" style="background:#f5f3ff;color:var(--purple)"><i class="fas fa-id-card"></i>
                </div>
                <h5>Demographic Analytics</h5>
                <span class="csh-count">10 charts · 4 tabs</span>
                <i class="fas fa-chevron-down csh-chevron"></i>
            </div>
            <div class="chart-section-body">
                <div class="chart-tabs">
                    <button class="chart-tab active" data-tab="demo-religion" onclick="demoTab('demo-religion',this)"><i
                            class="fas fa-church"></i> Religion &amp; Ethnicity</button>
                    <button class="chart-tab" data-tab="demo-housing" onclick="demoTab('demo-housing',this)"><i
                            class="fas fa-home"></i> Housing</button>
                    <button class="chart-tab" data-tab="demo-health" onclick="demoTab('demo-health',this)"><i
                            class="fas fa-heartbeat"></i> Health</button>
                    <button class="chart-tab" data-tab="demo-social" onclick="demoTab('demo-social',this)"><i
                            class="fas fa-hands-helping"></i> Social</button>
                </div>
                <div class="chart-tab-pane active" id="demo-religion">
                    <div class="demo-section-label"><i class="fas fa-church"></i> Religion &amp; Ethnicity</div>
                    <div class="chart-grid">
                        <div class="chart-card">
                            <h6><i class="fas fa-church" style="color:var(--purple)"></i> Religion Distribution</h6>
                            <canvas id="religionChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-globe-asia" style="color:var(--teal)"></i> Ethnicity / Indigenous Group
                            </h6><canvas id="ethnicityChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-tab-pane" id="demo-housing">
                    <div class="demo-section-label"><i class="fas fa-home"></i> Housing &amp; Infrastructure</div>
                    <div class="chart-grid">
                        <div class="chart-card">
                            <h6><i class="fas fa-key" style="color:var(--primary)"></i> House Ownership</h6><canvas
                                id="houseOwnershipChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-building" style="color:var(--amber)"></i> House Material</h6><canvas
                                id="houseMaterialChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-faucet" style="color:var(--info)"></i> Water Source</h6><canvas
                                id="waterSourceChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-toilet" style="color:var(--success)"></i> Toilet Type</h6><canvas
                                id="toiletTypeChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-tab-pane" id="demo-health">
                    <div class="demo-section-label"><i class="fas fa-heartbeat"></i> Health Conditions</div>
                    <div class="chart-grid">
                        <div class="chart-card">
                            <h6><i class="fas fa-heartbeat" style="color:var(--danger)"></i> Health Conditions Overview
                            </h6><canvas id="healthSummaryChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-hospital" style="color:var(--rose)"></i> Health Conditions per Purok
                            </h6><canvas id="healthChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-tab-pane" id="demo-social">
                    <div class="demo-section-label"><i class="fas fa-hands-helping"></i> Social Programs &amp; Lifestyle
                    </div>
                    <div class="chart-grid">
                        <div class="chart-card">
                            <h6><i class="fas fa-hands-helping" style="color:var(--purple)"></i> Social Programs per
                                Purok</h6><canvas id="socialChart"></canvas>
                        </div>
                        <div class="chart-card">
                            <h6><i class="fas fa-id-badge" style="color:var(--teal)"></i> PhilHealth Membership Type
                            </h6><canvas id="membershipChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── PRINT AREA ─────────────────────────────────────────────────── -->
        <div id="printArea">
            <div class="print-page">
                <div class="print-header">
                    <h2>BARANGAY STO. ROSARIO</h2>
                    <h3>HOUSEHOLD CENSUS RECORD (Residents + Officials)</h3>
                    <p>Magallanes, Agusan del Norte &nbsp;|&nbsp; YEAR: <span id="printYear"></span> &nbsp;|&nbsp;
                        PUROK: <span id="printPurok"></span></p>
                    <p>Printed: <span id="printDate"></span></p>
                </div>
                <table class="census-table" id="censusTable">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:28px">HH No.</th>
                            <th colspan="3" class="group-hdr">NAME</th>
                            <th rowspan="2">Sex</th>
                            <th rowspan="2">Birthdate</th>
                            <th rowspan="2">Birthplace</th>
                            <th rowspan="2">Age</th>
                            <th rowspan="2">Civil Status</th>
                            <th rowspan="2">Relation to Head</th>
                            <th rowspan="2">SES</th>
                            <th colspan="3" class="group-hdr">EDUCATION</th>
                            <th colspan="2" class="group-hdr">OCCUPATION</th>
                            <th rowspan="2">Religion</th>
                            <th rowspan="2">Ethnicity</th>
                            <th rowspan="2">PHIC No.</th>
                            <th rowspan="2">PWD Type</th>
                        </tr>
                        <tr>
                            <th>Surname</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Attainment</th>
                            <th>Grade/Year</th>
                            <th>School</th>
                            <th>Private</th>
                            <th>Government</th>
                        </tr>
                    </thead>
                    <tbody id="censusTableBody">
                        <tr>
                            <td colspan="20" style="text-align:center;padding:20px">Loading…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="print-page page-break">
                <div class="print-header">
                    <h2>BARANGAY STO. ROSARIO</h2>
                    <h3>HEALTH &amp; SOCIAL PROFILE</h3>
                    <p>Magallanes, Agusan del Norte &nbsp;|&nbsp; YEAR: <span class="printYearP2"></span> &nbsp;|&nbsp;
                        PUROK: <span class="printPurokP2"></span></p>
                    <p style="font-style:italic;font-size:7pt">✓ = Yes &nbsp;&nbsp; Fill manually where data is
                        unavailable.</p>
                </div>
                <table class="health-table" id="healthTable">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:28px">HH No.</th>
                            <th rowspan="2">Name</th>
                            <th rowspan="2">PHIC No.</th>
                            <th colspan="3" class="group-hdr">MEMBERSHIP</th>
                            <th rowspan="2">Family Planning</th>
                            <th colspan="2" class="group-hdr">TOILET</th>
                            <th rowspan="2">Water Source</th>
                            <th rowspan="2">Smoker</th>
                            <th rowspan="2">Binge Drinker</th>
                            <th rowspan="2">HPN</th>
                            <th rowspan="2">DM</th>
                            <th rowspan="2">Asthma</th>
                            <th rowspan="2">TB</th>
                            <th rowspan="2">Cancer</th>
                            <th rowspan="2">Mental Health</th>
                            <th rowspan="2">PWD</th>
                            <th rowspan="2">4Ps</th>
                            <th rowspan="2">Solo Parent</th>
                            <th rowspan="2">NHTS</th>
                            <th rowspan="2">Remarks</th>
                        </tr>
                        <tr>
                            <th>Private</th>
                            <th>Govt</th>
                            <th>NHTS</th>
                            <th>w/</th>
                            <th>w/out</th>
                        </tr>
                    </thead>
                    <tbody id="healthTableBody">
                        <tr>
                            <td colspan="23" style="text-align:center;padding:20px">Loading…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        window.DASHBOARD_OFFICIALS = <?= json_encode($officials) ?>;
    </script>
    <script src="../js/dashboard.js"></script>
    <script src="../officials/csv_export.js"></script>

    <script>
        (function () {
            'use strict';
            var LS = 'brgy_chart_prefs_v2';
            var prefs = {};
            try { prefs = JSON.parse(localStorage.getItem(LS) || '{}'); } catch (e) { }

            // ── Section nav ────────────────────────────────────────────────────
            window.navFilter = function (section, btn) {
                document.querySelectorAll('.chart-nav-btn').forEach(function (b) { b.classList.remove('active'); });
                if (btn) btn.classList.add('active');
                document.querySelectorAll('.chart-section-card').forEach(function (card) {
                    card.style.display = (section === 'all' || card.dataset.section === section) ? '' : 'none';
                });
                if (section !== 'all') {
                    var target = document.getElementById('sec-' + section);
                    if (target) setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 50);
                }
                prefs.navSection = section; _save();
            };

            // ── Collapse / expand ──────────────────────────────────────────────
            window.toggleSection = function (id) {
                var card = document.getElementById(id);
                if (!card) return;
                card.classList.toggle('collapsed');
                if (!card.classList.contains('collapsed') && window.Chart) {
                    setTimeout(function () {
                        card.querySelectorAll('canvas').forEach(function (c) {
                            var inst = Chart.getChart(c);
                            if (inst) inst.resize();
                        });
                    }, 50);
                }
                if (!prefs.collapsed) prefs.collapsed = {};
                prefs.collapsed[id] = card.classList.contains('collapsed');
                _save();
            };

            var allCollapsed = false;
            window.toggleAllSections = function () {
                allCollapsed = !allCollapsed;
                document.querySelectorAll('.chart-section-card').forEach(function (c) { c.classList.toggle('collapsed', allCollapsed); });
                var btn = document.getElementById('toggleAllBtn');
                if (btn) btn.innerHTML = allCollapsed ? '<i class="fas fa-expand-alt"></i> Expand All' : '<i class="fas fa-compress-alt"></i> Collapse All';
            };

            // ── Population sub-filter pills ────────────────────────────────────
            window.popFilter = function (group, btn) {
                document.querySelectorAll('.cfp').forEach(function (b) { b.classList.remove('active'); });
                if (btn) btn.classList.add('active');
                document.querySelectorAll('#sec-population .chart-card').forEach(function (card) {
                    var groups = (card.dataset.groups || 'all').split(' ');
                    card.style.display = (group === 'all' || groups.indexOf(group) !== -1) ? '' : 'none';
                });
                if (window.Chart) {
                    Object.keys(Chart.instances || {}).forEach(function (k) {
                        var inst = Chart.instances[k];
                        if (inst && inst.canvas) {
                            var c = inst.canvas.closest('.chart-card');
                            if (c && c.style.display !== 'none') inst.resize();
                        }
                    });
                }
                prefs.popFilter = group; _save();
            };

            // ── Demographic tabs ───────────────────────────────────────────────
            window.demoTab = function (tabId, btn) {
                if (window.event) window.event.stopPropagation();
                document.querySelectorAll('.chart-tab').forEach(function (t) { t.classList.remove('active'); });
                if (btn) btn.classList.add('active');
                document.querySelectorAll('.chart-tab-pane').forEach(function (p) { p.classList.remove('active'); });
                var pane = document.getElementById(tabId);
                if (pane) {
                    pane.classList.add('active');
                    if (window.Chart) {
                        setTimeout(function () {
                            pane.querySelectorAll('canvas').forEach(function (c) {
                                var inst = Chart.getChart(c);
                                if (inst) inst.resize();
                            });
                        }, 50);
                    }
                }
                prefs.demoTab = tabId; _save();
            };

            // ── PWD Type sub-filter visibility ─────────────────────────────────
            window.onCategoryChange = function () {
                var cat = document.getElementById('categoryFilter');
                var pwdRow = document.getElementById('pwdTypeRow');
                var pwdSel = document.getElementById('pwdTypeFilter');
                var gradRow = document.getElementById('graduatesFilterRow');
                var gradCourse = document.getElementById('gradCourseFilter');
                var gradYear = document.getElementById('gradYearFilter');
                if (!cat) return;

                // PWD sub-filter
                if (pwdRow) {
                    if (cat.value === 'pwd') {
                        pwdRow.classList.add('visible');
                    } else {
                        pwdRow.classList.remove('visible');
                        if (pwdSel) pwdSel.value = 'all';
                    }
                }

                // Graduates sub-filter
                if (gradRow) {
                    if (cat.value === 'graduates') {
                        gradRow.style.display = '';
                    } else {
                        gradRow.style.display = 'none';
                        if (gradCourse) gradCourse.value = 'all';
                        if (gradYear) gradYear.value = 'all';
                    }
                }

                loadDashboardData();
            };

            // ── Active filter pills ─────────────────────────────────────────────
            function updateActiveFilters() {
                var strip = document.getElementById('activeFilterStrip');
                if (!strip) return;
                var pills = [];
                var map = {
                    purokFilter: { label: 'Purok', cls: 'af-pill' },
                    barangayFilter: { label: 'Barangay', cls: 'af-pill' },
                    sesFilter: { label: 'SES', cls: 'af-pill' },
                    categoryFilter: { label: 'Category', cls: 'af-pill' },
                    voterStatusFilter: { label: 'Voter', cls: 'af-pill' },
                    householdNoFilter: { label: 'HH No.', cls: 'af-pill' },
                    pwdTypeFilter: { label: 'Disability', cls: 'af-pill-red' },
                    gradCourseFilter: { label: 'Course', cls: 'af-pill' },
                    gradYearFilter: { label: 'Grad Year', cls: 'af-pill' },
                };
                Object.keys(map).forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el && el.value && el.value !== 'all') {
                        // strip emoji from PWD type display
                        var val = el.options[el.selectedIndex].text.replace(/^[^\w]+ /, '').split(' (')[0];
                        pills.push('<span class="' + map[id].cls + '">' + map[id].label + ': ' + val + '</span>');
                    }
                });
                if (pills.length) {
                    strip.innerHTML = '<span>Active:</span>' + pills.join('');
                    strip.classList.add('visible');
                } else {
                    strip.innerHTML = '';
                    strip.classList.remove('visible');
                }
            }

            // Patch loadDashboardData to also refresh pills
            document.addEventListener('DOMContentLoaded', function () {
                var _origLoad = window.loadDashboardData;
                if (typeof _origLoad === 'function') {
                    window.loadDashboardData = function () {
                        updateActiveFilters();
                        return _origLoad.apply(this, arguments);
                    };
                }
                ['purokFilter', 'barangayFilter', 'categoryFilter', 'sesFilter', 'pwdTypeFilter', 'voterStatusFilter', 'householdNoFilter', 'gradCourseFilter', 'gradYearFilter'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) el.addEventListener('change', updateActiveFilters);
                });

                // Also update purok and barangay dropdowns dynamically from AJAX response
                var _origHook = window._onDashboardData;
                window._onDashboardData = function (data) {
                    if (data && data.puroks) updatePurokDropdown(data.puroks);
                    if (data && data.barangays) updateBarangayDropdown(data.barangays);
                    if (typeof _origHook === 'function') _origHook(data);
                };

                restore();
            });

            // ── Dynamic purok dropdown update ──────────────────────────────────
            window.updatePurokDropdown = function (puroks) {
                var sel = document.getElementById('purokFilter');
                if (!sel) return;
                var current = sel.value;
                while (sel.options.length > 1) sel.remove(1);
                (puroks || []).forEach(function (p) {
                    var opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    if (p === current) opt.selected = true;
                    sel.appendChild(opt);
                });
            };

            // ── Dynamic barangay dropdown update ────────────────────────────────
            window.updateBarangayDropdown = function (barangays) {
                var sel = document.getElementById('barangayFilter');
                if (!sel) return;
                var current = sel.value;
                while (sel.options.length > 1) sel.remove(1);
                (barangays || []).forEach(function (b) {
                    var opt = document.createElement('option');
                    opt.value = b;
                    opt.textContent = b;
                    if (b === current) opt.selected = true;
                    sel.appendChild(opt);
                });
            };

            // ── Restore saved prefs ─────────────────────────────────────────────
            function restore() {
                if (prefs.collapsed) {
                    Object.keys(prefs.collapsed).forEach(function (id) {
                        var card = document.getElementById(id);
                        if (card && prefs.collapsed[id]) card.classList.add('collapsed');
                    });
                }
                if (prefs.popFilter && prefs.popFilter !== 'all') {
                    var pb = document.querySelector('[data-pop="' + prefs.popFilter + '"]');
                    if (pb) popFilter(prefs.popFilter, pb);
                }
                if (prefs.demoTab) {
                    var tb = document.querySelector('[data-tab="' + prefs.demoTab + '"]');
                    if (tb) demoTab(prefs.demoTab, tb);
                }
                if (prefs.navSection && prefs.navSection !== 'all') {
                    var nb = document.querySelector('[data-section="' + prefs.navSection + '"]');
                    if (nb) navFilter(prefs.navSection, nb);
                }
            }

            function _save() {
                try { localStorage.setItem(LS, JSON.stringify(prefs)); } catch (e) { }
            }

        })();
    </script>
    <?php if (isset($conn))
        $conn->close(); ?>
</body>

</html>