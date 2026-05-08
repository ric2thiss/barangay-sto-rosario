// landingpage.js – simplified for authentication‑only portal
// This script now only handles auto‑hiding of success alerts.
// Any booking‑related functionality has been removed as services are decommissioned.

document.addEventListener('DOMContentLoaded', function () {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 5000);
    }
});

