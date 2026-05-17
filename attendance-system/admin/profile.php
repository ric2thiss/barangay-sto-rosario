<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth();

include_once __DIR__ . "/../shared/components/Sidebar.php";
include_once __DIR__ . "/../shared/components/Breadcrumb.php";

$currentUser = currentUser();
$userName = $currentUser ? ($currentUser["full_name"] ?? $currentUser["username"]) : "Guest";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionAuthSource = $_SESSION["auth_source"] ?? "";
$profileReadOnly = $sessionAuthSource === "profiling_resident";

$headerDate = date("l, F j, Y");

$extraLastLogin = null;
if ($sessionAuthSource === "profiling_admin") {
    $pPdo = ProfilingPdo::get();
    if ($pPdo && $currentUser && isset($currentUser["id"])) {
        try {
            $st = $pPdo->prepare("SELECT last_login FROM `admin` WHERE id = ? LIMIT 1");
            $st->execute([(int) $currentUser["id"]]);
            $lr = $st->fetch(PDO::FETCH_ASSOC);
            if ($lr && !empty($lr["last_login"])) {
                $extraLastLogin = $lr["last_login"];
            }
        } catch (Throwable $e) {
            error_log("profile last_login: " . $e->getMessage());
        }
    }
}

$lastLoginRaw = $extraLastLogin ?? ($currentUser["last_login"] ?? null);
$lastLoginLabel = null;
if ($lastLoginRaw) {
    $lastLoginLabel = date("M j, Y g:i A", strtotime($lastLoginRaw));
}

$roleDisplay = "";
if ($currentUser && isset($currentUser["role"])) {
    $roleDisplay = (string) $currentUser["role"];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, "UTF-8") ?>">
    <title>My Profile</title>
    <link rel="stylesheet" href="../utils/styles/global.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        window.BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <style>
        body { overflow-x: hidden; }
        main { overflow-x: hidden; max-width: 100%; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <div class="flex min-h-screen">

        <?= Sidebar("Profile", null) ?>

        <main class="flex-1 md:ml-64 p-6 transition-all duration-300">

            <header class="mb-8 border-b border-slate-200 pb-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">My Profile</h1>
                        <p class="mt-2 text-slate-600 text-sm sm:text-base"><?= getGreeting($userName) ?> — manage how you appear in this system.</p>
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today</p>
                        <p id="profile-header-date" class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($headerDate) ?></p>
                    </div>
                </div>
                <?php Breadcrumb([
                    ["label" => "Dashboard", "link" => "dashboard.php"],
                    ["label" => "Profile", "link" => "profile.php"],
                ]); ?>
            </header>

            <div id="message-container" class="mb-4 hidden" role="alert"></div>

            <?php if ($profileReadOnly): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Resident accounts are updated in the <strong>Profiling</strong> system. Here you can only view your summary.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                <div class="xl:col-span-4">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-br from-blue-700 to-blue-900 px-6 py-8 text-center text-white">
                            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-white/15 ring-4 ring-white/20 text-2xl font-bold">
                                <?php
                                $initials = "U";
                                if ($currentUser) {
                                    $name = $currentUser["full_name"] ?? $currentUser["username"] ?? "U";
                                    $parts = preg_split('/\s+/', trim($name));
                                    if (count($parts) > 1) {
                                        $initials = strtoupper(
                                            mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1)
                                        );
                                    } else {
                                        $initials = strtoupper(mb_substr($name, 0, 2));
                                    }
                                }
                                echo htmlspecialchars($initials);
                                ?>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold truncate px-2">
                                <?= htmlspecialchars($currentUser ? ($currentUser["full_name"] ?: $currentUser["username"]) : "Guest") ?>
                            </h2>
                            <?php if ($roleDisplay !== ""): ?>
                                <p class="mt-1 text-sm text-blue-100"><?= htmlspecialchars($roleDisplay) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="px-6 py-5 space-y-3 text-sm border-t border-slate-100">
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-500">Username</span>
                                <span class="font-medium text-slate-900 text-right truncate max-w-[55%]"><?= htmlspecialchars($currentUser["username"] ?? "—") ?></span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-500">Email</span>
                                <span class="font-medium text-slate-900 text-right truncate max-w-[55%]"><?= htmlspecialchars($currentUser["email"] ?? "—") ?></span>
                            </div>
                            <?php if ($lastLoginLabel): ?>
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-500">Last login</span>
                                    <span class="font-medium text-slate-900 text-right"><?= htmlspecialchars($lastLoginLabel) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-8 space-y-6">

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 sm:p-8">
                        <h2 class="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-6">Profile information</h2>

                        <form id="profile-form" class="space-y-5" <?= $profileReadOnly ? 'data-readonly="1"' : "" ?>>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                                    <input
                                        type="text"
                                        id="username"
                                        name="username"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        value="<?= htmlspecialchars($currentUser["username"] ?? "") ?>"
                                        <?= $profileReadOnly ? "disabled" : "required" ?>
                                    >
                                    <p class="mt-1.5 text-xs text-slate-500">Used to sign in.</p>
                                </div>
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                                    <input
                                        type="text"
                                        id="full_name"
                                        name="full_name"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        value="<?= htmlspecialchars($currentUser["full_name"] ?? "") ?>"
                                        <?= $profileReadOnly ? "disabled" : "" ?>
                                    >
                                </div>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                    value="<?= htmlspecialchars($currentUser["email"] ?? "") ?>"
                                    <?= $profileReadOnly ? "disabled" : "required" ?>
                                >
                            </div>
                            <?php if (!$profileReadOnly): ?>
                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    >
                                        Save changes
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 sm:p-8">
                        <h2 class="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-6">Change password</h2>

                        <form id="password-form" class="space-y-5" <?= $profileReadOnly ? 'data-readonly="1"' : "" ?>>
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5">Current password</label>
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                    placeholder="••••••••"
                                    <?= $profileReadOnly ? "disabled" : "required" ?>
                                >
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1.5">New password</label>
                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        placeholder="At least 6 characters"
                                        minlength="6"
                                        <?= $profileReadOnly ? "disabled" : "required" ?>
                                    >
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm new password</label>
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        placeholder="Repeat new password"
                                        <?= $profileReadOnly ? "disabled" : "required" ?>
                                    >
                                </div>
                            </div>
                            <?php if (!$profileReadOnly): ?>
                                <div class="pt-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors"
                                    >
                                        Update password
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script type="module" src="./js/profile/main.js"></script>
</body>
</html>
