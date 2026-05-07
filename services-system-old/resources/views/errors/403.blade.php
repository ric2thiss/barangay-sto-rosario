<x-layouts.app :title="__('Access Denied')">
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
        <h1 class="text-6xl font-bold text-red-500 mb-4">403</h1>
        <h2 class="text-2xl font-semibold mb-2 dark:text-white">Access Denied</h2>
        <p class="text-neutral-500 dark:text-neutral-400 mb-6">You don't have permission to view this page.</p>

        <div class="text-neutral-400 dark:text-neutral-500 text-sm">
            Redirecting you back in <span id="countdown" class="font-semibold text-indigo-500">3</span> seconds...
        </div>
    </div>

    <script>
        let seconds = 3;
        const countdown = document.getElementById('countdown');

        const timer = setInterval(() => {
            seconds--;
            countdown.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(timer);
                window.history.length > 1 ? window.history.back() : window.location.href = '/dashboard';
            }
        }, 1000);
    </script>
</x-layouts.app>