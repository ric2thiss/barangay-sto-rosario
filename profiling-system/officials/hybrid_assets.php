<?php
/**
 * hybrid_assets.php — LOCAL FIRST, CDN fallback
 *
 * CRITICAL FIX: Bootstrap must load EXACTLY ONCE.
 * Loading it twice destroys all modal/offcanvas instances created
 * by the first load's DOMContentLoaded handlers, causing:
 *   TypeError: Cannot read properties of undefined (reading 'backdrop')
 *
 * Guard: window.__BS_LOADED prevents any second include of this file
 * from emitting another <script> tag.
 */
?>
<link rel="stylesheet"
      href="assets/bootstrap/css/bootstrap.min.css"
      onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'">

<link rel="stylesheet"
      href="assets/fontawesome/css/all.min.css"
      onerror="this.onerror=null;this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">

<script>
/* ── Single-load guard ───────────────────────────────────────────────────
   Every page that includes hybrid_assets.php will hit this block.
   The flag prevents any subsequent <script> from re-loading Bootstrap,
   which is the #1 cause of the "backdrop undefined" modal crash.
   ──────────────────────────────────────────────────────────────────── */
if (!window.__BS_LOADED) {
    window.__BS_LOADED = true;
    document.write(
        '<scr' + 'ipt src="assets/bootstrap/js/bootstrap.bundle.min.js" ' +
        'onerror="' +
            "document.write('<scr'+'ipt src=\\'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js\\'><\\/scr'+'ipt>')" +
        '"><\/scr' + 'ipt>'
    );
}
</script>

<script>
if (typeof Chart === 'undefined') {
    document.write(
        '<scr' + 'ipt src="assets/chartjs/chart.umd.min.js" ' +
        'onerror="' +
            "document.write('<scr'+'ipt src=\\'https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js\\'><\\/scr'+'ipt>')" +
        '"><\/scr' + 'ipt>'
    );
}
</script>

<script>
(function () {
    function showBanner() {
        if (document.getElementById('_offlineBanner')) return;
        var b = document.createElement('div');
        b.id = '_offlineBanner';
        b.style.cssText =
            'position:fixed;bottom:0;left:0;right:0;z-index:99999;' +
            'background:#1e293b;color:#f8fafc;text-align:center;' +
            'padding:7px 16px;font-size:13px;font-weight:600;';
        b.innerHTML = '&#9889; Offline &mdash; running on local assets';
        document.body
            ? document.body.appendChild(b)
            : document.addEventListener('DOMContentLoaded', function () {
                document.body.appendChild(b);
              });
    }
    if (!navigator.onLine) {
        document.addEventListener('DOMContentLoaded', showBanner);
    }
    window.addEventListener('offline', showBanner);
    window.addEventListener('online', function () {
        var b = document.getElementById('_offlineBanner');
        if (b) b.remove();
    });
})();
</script>

<script>
/* ── Global Modal Stacking Context Fix ────────────────────────────────────
   Moves all modals to the body element to prevent z-index issues where
   the backdrop appears over the modal when modals are trapped in containers.
   ──────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
});
</script>