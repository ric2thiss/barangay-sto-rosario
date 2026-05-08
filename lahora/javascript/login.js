const messageDisplay = document.getElementById('error-message');
const loginForm = document.getElementById('loginForm');
const loginButton = loginForm.querySelector('button[type="submit"]');
const registerLink = document.querySelector('a[href="../php/registration.php"]');
const bottomRegisterLink = document.getElementById('registerLink');
const usernameInput = document.getElementById('usernameInput');
const passwordInput = document.getElementById('passwordInput');
const homeLink = document.querySelector('ul.home li:first-child a');
const inputFields = document.querySelectorAll('input');
const forgotPasswordLink = document.getElementById('forgotpass');
const messageUsernameDisplay = document.getElementById('username_error');
const messagePasswordDisplay = document.getElementById('password_error');

let loginFailed = false; // 🔥 real-time invalid message trigger

const cameFromLink = sessionStorage.getItem('cameFromLink');

if (!cameFromLink) {
    for (let i = 0; i < 10; i++) {
        history.pushState(null, '', window.location.href);
    }

    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
    });
}

sessionStorage.removeItem('cameFromLink');

window.history.pushState(null, '', window.location.href);
window.addEventListener('popstate', function () {
    window.history.pushState(null, '', window.location.href);
});


let loginAttempts = Number(localStorage.getItem('loginAttempts')) || 0;
let lockoutTime = Number(localStorage.getItem('lockoutTime')) || 0;

const linkElementsToDisable = [
    homeLink,
    registerLink,
]

const buttonElementsToDisable = [
    loginButton
]

const buttonElementsToDisable1 = [
    bottomRegisterLink,
    forgotPasswordLink
]


async function handleLogin(e) {
    e.preventDefault();
    const currentTime = Date.now();

    if (lockoutTime && currentTime < lockoutTime) {
        const remainingTime = Math.ceil((lockoutTime - currentTime) / 1000);
        messageDisplay.textContent = `Too many failed attempts. Try again in ${remainingTime} seconds.`;
        disableLogin(true);

        const interval = setInterval(() => {
            const timeLeft = Math.ceil((lockoutTime - Date.now()) / 1000);
            if (timeLeft > 0) {
                messageDisplay.textContent = `Too many failed attempts. Try again in ${timeLeft} seconds.`;
            } else {
                clearInterval(interval);
                messageDisplay.textContent = '';
                disableLogin(false);
            }
        }, 1000);
        return;
    }

    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();
    let hasError = false;

    messageUsernameDisplay.textContent = '';
    messagePasswordDisplay.textContent = '';
    messageDisplay.textContent = '';

    if (!username) {
        messageUsernameDisplay.textContent = 'Username is required.';
        messageUsernameDisplay.style.color = 'red';
        hasError = true;
    }

    if (!password) {
        messagePasswordDisplay.textContent = 'Password is required.';
        messagePasswordDisplay.style.color = 'red';
        hasError = true;
    }

    if (hasError) return;

    try {
        const res = await fetch('check_logincr.php', {
            method: 'POST',
            headers: {
                'Content-type': 'application/x-www-form-urlencoded'
            },
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });

        const data = await res.json();

        if (data.success) {
            loginFailed = false; // 🔥 reset real-time invalid errors
            messageDisplay.textContent = data.message || 'Login successful!';

            // Highlight in orange or red for blocked/pending messages
            if (data.redirect && data.redirect !== 'dashboard.php' && data.redirect !== 'landingpage.php') {
                messageDisplay.style.color = 'orange';
            } else {
                messageDisplay.style.color = 'green';
            }

            sessionStorage.setItem('username', username)
            resetAttempts();
            setTimeout(() => {
                // Use the redirect URL from server response, fallback to landingpage.php
                const redirectUrl = data.redirect || 'landingpage.php';
                window.location.href = redirectUrl;
            }, 2000);
        } else {
            handleFailedAttempt();
        }
    } catch (err) {
        console.error('Error:', err);
        messageDisplay.textContent = 'An error occurred while logging in. Please try again.';
        messageDisplay.style.color = 'red';
    }
}

function handleFailedAttempt() {
    loginFailed = true;

    loginAttempts += 1;
    localStorage.setItem('loginAttempts', loginAttempts);

    const remainingAttempts = 3 - (loginAttempts % 3 || 3);
    
    // Show "Help, I can't sign in" after 2 successive wrong attempts
    if (loginAttempts >= 2) {
        forgotPasswordLink.style.display = 'block';
    }

    if (loginAttempts % 3 === 0) {
        let lockDuration = 0;
        if (loginAttempts >= 9) lockDuration = 60000;
        else if (loginAttempts >= 6) lockDuration = 30000;
        else lockDuration = 15000;

        lockoutTime = Date.now() + lockDuration;
        localStorage.setItem('lockoutTime', lockoutTime);
        fetch('set_lockout_session.php', { method: 'POST' });

        messageDisplay.textContent = `Too many failed attempts. Please wait ${lockDuration / 1000} seconds.`;
        messageDisplay.style.color = 'red';
        disableLogin(true);

        const interval = setInterval(() => {
            const timeLeft = Math.ceil((lockoutTime - Date.now()) / 1000);
            if (timeLeft > 0) {
                messageDisplay.textContent = `Too many failed attempts. Try again in ${timeLeft} seconds.`;
            } else {
                clearInterval(interval);
                messageDisplay.textContent = '';
                // Keep the link visible if it was already shown
                disableLogin(false);
                localStorage.removeItem('lockoutTime');
                fetch('clear_lockout_session.php', { method: 'POST' });
            }
        }, 1000);
    } else {
        messageUsernameDisplay.textContent = "Invalid username or password.";
        messageUsernameDisplay.style.color = "red";
        messagePasswordDisplay.textContent = "Invalid username or password.";
        messagePasswordDisplay.style.color = "red";

        messageDisplay.textContent = `You have ${remainingAttempts} attempt${remainingAttempts === 1 ? '' : 's'} left before lockout.`;
        messageDisplay.style.color = 'red';
    }
}


function disableLogin(disable) {
    const allLinks = document.querySelectorAll('a');
    if (disable) {
        allLinks.forEach(link => {
            link.classList.add('disabled-button');
        });

        buttonElementsToDisable.forEach(el => {
            if (el) el.classList.add('disabled-button');
        });

        inputFields.forEach(input => {
            input.disabled = true;
            input.value = '';
        });

    } else {
        allLinks.forEach(link => {
            link.classList.remove('disabled-button');
        });

        buttonElementsToDisable.forEach(el => {
            if (el) el.classList.remove('disabled-button');
        });

        inputFields.forEach(input => {
            input.disabled = false;
        });
    }
}

function resetAttempts() {
    loginAttempts = 0;
    lockoutTime = 0;
    localStorage.removeItem('loginAttempts');
    localStorage.removeItem('lockoutTime');
    forgotPasswordLink.style.display = 'none';
}


// 🔥 REAL-TIME ERROR DISPLAY
usernameInput.addEventListener('input', () => {
    const value = usernameInput.value.trim();

    if (!value) {
        messageUsernameDisplay.textContent = 'Username is required.';
        messageUsernameDisplay.style.color = 'red';
        return;
    }

    // Clear any existing error message when user starts typing
    messageUsernameDisplay.textContent = '';
});

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value.trim();

    if (!value) {
        messagePasswordDisplay.textContent = 'Password is required.';
        messagePasswordDisplay.style.color = 'red';
        return;
    }

    // Clear any existing error message when user starts typing
    messagePasswordDisplay.textContent = '';
});



function checkLockoutOnLoad() {
    const currentTime = Date.now();
    lockoutTime = Number(localStorage.getItem('lockoutTime')) || 0;

    if (lockoutTime && currentTime < lockoutTime) {
        const timeLeft = Math.ceil((lockoutTime - currentTime) / 1000);
        messageDisplay.textContent = `Too many failed attempts. Try again in ${timeLeft} seconds.`;
        disableLogin(true);

        fetch('set_lockout_session.php', { method: 'POST' });

        const interval = setInterval(() => {
            const timeRemaining = Math.ceil((lockoutTime - Date.now()) / 1000);
            if (timeRemaining > 0) {
                messageDisplay.textContent = `Too many failed attempts. Try again in ${timeRemaining} seconds.`;
            } else {
                clearInterval(interval);
                messageDisplay.textContent = '';
                disableLogin(false);
                localStorage.removeItem('lockoutTime');

                fetch('clear_lockout_session.php', { method: 'POST' });
            }
        }, 1000);
    }
}
checkLockoutOnLoad();


loginForm.addEventListener('submit', handleLogin);


const togglePasswordElement = document.getElementById('togglePassword');
const eyeIconElement = document.getElementById('eyeIcon');

if (togglePasswordElement) {
    togglePasswordElement.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';

        if (eyeIconElement) {
            eyeIconElement.classList.toggle('fa-eye');
            eyeIconElement.classList.toggle('fa-eye-slash');
        }
    });
}