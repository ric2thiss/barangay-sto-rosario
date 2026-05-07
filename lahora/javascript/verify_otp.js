document.addEventListener('DOMContentLoaded', function() {
    let countdownInterval;
    let timeLeft = 300; // 5 minutes in seconds
    let isExpired = false;
    
    const otpForm = document.getElementById('otpForm');
    const otpInput = document.getElementById('otpInput');
    const otpBoxes = document.querySelectorAll('.otp-box');
    const resendBtn = document.getElementById('resendBtn');
    const verifyBtn = document.getElementById('verifyBtn');
    const countdown = document.getElementById('countdown');
    const statusMessage = document.getElementById('statusMessage');
    const otpError = document.getElementById('otp_error');
    const otpSuccess = document.getElementById('otp_success');
    
    // Initialize page
    function init() {
        startTimer();
        requestOTP(); // Auto-generate OTP on page load
        setupEventListeners();
        
        // Focus first box on load
        if(otpBoxes.length > 0) otpBoxes[0].focus();
    }
    
    // Setup event listeners
    function setupEventListeners() {
        otpForm.addEventListener('submit', handleOTPSubmit);
        resendBtn.addEventListener('click', handleResendOTP);
        
        // Handle OTP box inputs
        otpBoxes.forEach((box, index) => {
            // Handle input
            box.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value) {
                    this.classList.add('filled');
                    // Move to next box
                    if (index < otpBoxes.length - 1) {
                        otpBoxes[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }
                
                updateHiddenInput();
                clearMessages();
            });

            // Handle backspace
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    otpBoxes[index - 1].focus();
                }
            });

            // Handle paste
            box.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = pasteData.replace(/[^0-9]/g, '').split('').slice(0, 6);
                
                numbers.forEach((num, i) => {
                    if (otpBoxes[i]) {
                        otpBoxes[i].value = num;
                        otpBoxes[i].classList.add('filled');
                    }
                });
                
                const nextFocus = numbers.length < 6 ? numbers.length : 5;
                otpBoxes[nextFocus].focus();
                updateHiddenInput();
            });
        });
    }

    function updateHiddenInput() {
        let combinedOTP = '';
        otpBoxes.forEach(box => combinedOTP += box.value);
        otpInput.value = combinedOTP;
        
        // Enable/disable verify button based on input
        verifyBtn.disabled = combinedOTP.length !== 6 || isExpired;
    }
    
    // Request OTP from server
    function requestOTP() {
        fetch('verify_otp_backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=generate_otp'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                resetTimer();
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error generating OTP:', error);
            showErrorMessage('Failed to generate OTP. Please try again.');
        });
    }
    
    // Handle OTP form submission
    function handleOTPSubmit(e) {
        e.preventDefault();
        
        const otp = otpInput.value.trim();
        
        if (otp.length !== 6) {
            showErrorMessage('Please enter a valid 6-digit OTP.');
            return;
        }
        
        if (isExpired) {
            showErrorMessage('OTP has expired. Please request a new one.');
            return;
        }
        
        // Show loading state
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';
        
        // Send OTP verification request
        fetch('verify_otp_backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=verify_otp&otp=${encodeURIComponent(otp)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                otpSuccess.textContent = data.message;
                
                // Redirect to reset password page after successful verification
                setTimeout(() => {
                    window.location.href = 'reset_password.php';
                }, 1500);
            } else {
                showErrorMessage(data.message);
                otpError.textContent = data.message;
                
                // If OTP is expired, disable verification
                if (data.expired) {
                    isExpired = true;
                    verifyBtn.disabled = true;
                    otpBoxes.forEach(box => box.disabled = true);
                    clearInterval(countdownInterval);
                    countdown.textContent = 'EXPIRED';
                }
            }
        })
        .catch(error => {
            console.error('Error verifying OTP:', error);
            showErrorMessage('Failed to verify OTP. Please try again.');
        })
        .finally(() => {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify OTP';
        });
    }
    
    // Handle resend OTP
    function handleResendOTP() {
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending...';
        
        fetch('verify_otp_backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=resend_otp'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                resetTimer();
                clearMessages();
                otpBoxes.forEach(box => {
                    box.value = '';
                    box.disabled = false;
                    box.classList.remove('filled');
                });
                otpInput.value = '';
                verifyBtn.disabled = true;
                isExpired = false;
                if(otpBoxes.length > 0) otpBoxes[0].focus();
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error resending OTP:', error);
            showErrorMessage('Failed to resend OTP. Please try again.');
        })
        .finally(() => {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend OTP';
        });
    }
    
    // Timer functions
    function startTimer() {
        clearInterval(countdownInterval);
        countdownInterval = setInterval(updateTimer, 1000);
    }
    
    function updateTimer() {
        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            handleOTPExpired();
            return;
        }
        
        timeLeft--;
        updateTimerDisplay();
    }
    
    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        countdown.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Change color based on time left
        if (timeLeft <= 60) {
            countdown.style.color = '#dc3545'; // Red
        } else if (timeLeft <= 120) {
            countdown.style.color = '#ffc107'; // Yellow
        } else {
            countdown.style.color = '#28a745'; // Green
        }
    }
    
    
    
    function resetTimer() {
        timeLeft = 300;
        isExpired = false;
        updateTimerDisplay();
        startTimer();
    }
    
    function handleOTPExpired() {
        isExpired = true;
        countdown.textContent = 'EXPIRED';
        countdown.style.color = '#dc3545';
        verifyBtn.disabled = true;
        otpBoxes.forEach(box => box.disabled = true);
        showErrorMessage('Your OTP has expired. Please resend a new OTP.');
    }
    
    // Message display functions
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
    
    function clearMessages() {
        statusMessage.style.display = 'none';
        otpError.textContent = '';
        otpSuccess.textContent = '';
    }
    
    // Initialize the page
    init();
});
