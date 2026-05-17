// Custom JavaScript for index.php
document.addEventListener('DOMContentLoaded', function() {
    // Initialization code
});

function checkSecurityKey(event) {
    event.preventDefault();
    const modal = document.getElementById('securityModal');
    const input = document.getElementById('securityKeyInput');
    const error = document.getElementById('modalError');
    
    error.style.display = 'none';
    input.value = '';
    modal.classList.add('active');
    setTimeout(() => input.focus(), 100);
    return false;
}

function closeSecurityModal() {
    document.getElementById('securityModal').classList.remove('active');
}

async function submitSecurityKey() {
    const key = document.getElementById('securityKeyInput').value;
    const error = document.getElementById('modalError');
    const btn = document.getElementById('submitKeyBtn');
    
    if (!key) {
        error.textContent = 'Please enter a key.';
        error.style.display = 'block';
        return;
    }

    btn.textContent = 'Verifying...';
    btn.disabled = true;

    try {
        const response = await fetch('verify_key.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ key: key })
        });
        const result = await response.json();
        
        if (result.success) {
            window.location.href = 'systems.php';
        } else {
            error.textContent = result.message || 'Invalid key.';
            error.style.display = 'block';
            btn.textContent = 'Access System';
            btn.disabled = false;
        }
    } catch (e) {
        error.textContent = 'Error verifying key. Try again later.';
        error.style.display = 'block';
        btn.textContent = 'Access System';
        btn.disabled = false;
    }
}
