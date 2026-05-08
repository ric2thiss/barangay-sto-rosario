document.addEventListener('DOMContentLoaded', function() {
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const resetBtn = document.getElementById('resetBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const statusMessage = document.getElementById('statusMessage');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordMatch = document.getElementById('passwordMatch');
    
    // Password requirement elements
    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number'),
        special: document.getElementById('special')
    };
    
    // Initialize event listeners
    setupEventListeners();
    
    function setupEventListeners() {
        // Password input validation
        newPasswordInput.addEventListener('input', function() {
            validatePassword();
            checkPasswordStrength();
            updatePasswordRequirements();
        });
        
        // Confirm password validation
        confirmPasswordInput.addEventListener('input', function() {
            validatePasswordMatch();
        });
        
        // Form submission
        resetPasswordForm.addEventListener('submit', handlePasswordReset);
        
        // Cancel button
        cancelBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel? You will need to start the password reset process again.')) {
                // Clear session and redirect to login
                fetch('reset_password_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=cancel_reset'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            }
        });
    }
    
    function validatePassword() {
        const password = newPasswordInput.value;
        let isValid = true;
        
        // Check minimum length
        if (password.length < 8) {
            isValid = false;
        }
        
        // Check if passwords match
        if (confirmPasswordInput.value && password !== confirmPasswordInput.value) {
            isValid = false;
        }
        
        // Enable/disable reset button
        resetBtn.disabled = !isValid || password.length === 0;
        
        return isValid;
    }
    
    function validatePasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            passwordMatch.textContent = '';
            passwordMatch.className = 'password-match';
            return;
        }
        
        if (password === confirmPassword) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.className = 'password-match valid';
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'password-match invalid';
        }
        
        validatePassword();
    }
    
    function checkPasswordStrength() {
        const password = newPasswordInput.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        if (password.length === 0) {
            passwordStrength.textContent = '';
            passwordStrength.className = 'password-strength';
        } else if (strength <= 2) {
            passwordStrength.textContent = 'Weak password';
            passwordStrength.className = 'password-strength weak';
        } else if (strength <= 4) {
            passwordStrength.textContent = 'Medium password';
            passwordStrength.className = 'password-strength medium';
        } else {
            passwordStrength.textContent = 'Strong password';
            passwordStrength.className = 'password-strength strong';
        }
    }
    
    function updatePasswordRequirements() {
        const password = newPasswordInput.value;
        
        // Length requirement
        if (password.length >= 8) {
            requirements.length.classList.add('valid');
        } else {
            requirements.length.classList.remove('valid');
        }
        
        // Uppercase requirement
        if (/[A-Z]/.test(password)) {
            requirements.uppercase.classList.add('valid');
        } else {
            requirements.uppercase.classList.remove('valid');
        }
        
        // Lowercase requirement
        if (/[a-z]/.test(password)) {
            requirements.lowercase.classList.add('valid');
        } else {
            requirements.lowercase.classList.remove('valid');
        }
        
        // Number requirement
        if (/[0-9]/.test(password)) {
            requirements.number.classList.add('valid');
        } else {
            requirements.number.classList.remove('valid');
        }
        
        // Special character requirement
        if (/[^A-Za-z0-9]/.test(password)) {
            requirements.special.classList.add('valid');
        } else {
            requirements.special.classList.remove('valid');
        }
    }
    
    function handlePasswordReset(e) {
        e.preventDefault();
        
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        // Final validation
        if (newPassword.length < 8) {
            showErrorMessage('Password must be at least 8 characters long.');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showErrorMessage('Passwords do not match.');
            return;
        }
        
        if (!validatePasswordRequirements(newPassword)) {
            showErrorMessage('Password does not meet all requirements.');
            return;
        }
        
        // Show loading state
        resetBtn.disabled = true;
        resetBtn.textContent = 'Resetting...';
        
        // Send password reset request
        fetch('reset_password_backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reset_password&new_password=${encodeURIComponent(newPassword)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                
                // Redirect to login after successful reset
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Failed to reset password. Please try again.');
        })
        .finally(() => {
            resetBtn.disabled = false;
            resetBtn.textContent = 'Reset Password';
        });
    }
    
    function validatePasswordRequirements(password) {
        return (
            password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /[0-9]/.test(password) &&
            /[^A-Za-z0-9]/.test(password)
        );
    }
    
    function showErrorMessage(message) {
        statusMessage.textContent = message;
        statusMessage.className = 'status-message error';
        statusMessage.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 5000);
    }
    
    function showSuccessMessage(message) {
        statusMessage.textContent = message;
        statusMessage.className = 'status-message success';
        statusMessage.style.display = 'block';
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            statusMessage.style.display = 'none';
        }, 3000);
    }
});
