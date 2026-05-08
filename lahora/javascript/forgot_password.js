// ============================================================
// DOM Elements
// ============================================================
const usernameForm       = document.getElementById('usernameForm');
const securityForm       = document.getElementById('securityForm');
const usernameTab        = document.getElementById('usernameTab');
const securityTab        = document.getElementById('securityTab');
const usernameInput      = document.getElementById('usernameInput');
const usernameError      = document.getElementById('username_error');
const errorMessage       = document.getElementById('error-message');
const successMessage     = document.getElementById('success-message');

// Step indicator elements
const step1Item          = document.getElementById('step1Item');
const step2Item          = document.getElementById('step2Item');
const step1Circle        = document.getElementById('step1Circle');
const stepConnector      = document.getElementById('stepConnector');

// Security question elements
const userUsername       = document.getElementById('userUsername');
const userIdNo           = document.getElementById('userIdNo');
const userAvatar         = document.getElementById('userAvatar');
const question1          = document.getElementById('question1');
const question2          = document.getElementById('question2');
const question3          = document.getElementById('question3');
const answer1            = document.getElementById('answer1');
const answer2            = document.getElementById('answer2');
const answer3            = document.getElementById('answer3');
const verifyAnswersBtn   = document.getElementById('verifyAnswers');
const findAnotherUserBtn = document.getElementById('findAnotherUsername');

// Validation icons
const icon1 = document.getElementById('icon1');
const icon2 = document.getElementById('icon2');
const icon3 = document.getElementById('icon3');

// ============================================================
// State
// ============================================================
let currentUserData = null;
let validationStates = { answer1: false, answer2: false, answer3: false };

// ============================================================
// Helpers
// ============================================================
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function clearMessages() {
    errorMessage.textContent   = '';
    successMessage.textContent = '';
}

function setError(msg)   { errorMessage.textContent   = msg; successMessage.textContent = ''; }
function setSuccess(msg) { successMessage.textContent = msg; errorMessage.textContent   = ''; }

// ============================================================
// Step Indicator
// ============================================================
function goToStep(step) {
    if (step === 1) {
        // Step 1 active, step 2 idle
        step1Item.classList.add('active');
        step1Item.classList.remove('completed');
        step2Item.classList.remove('active', 'completed');
        stepConnector.classList.remove('completed');

        // Restore icon
        step1Circle.innerHTML = '<i class="fas fa-user"></i>';
    } else if (step === 2) {
        // Step 1 completed, step 2 active
        step1Item.classList.remove('active');
        step1Item.classList.add('completed');
        step1Circle.innerHTML = '<i class="fas fa-check"></i>';

        stepConnector.classList.add('completed');

        step2Item.classList.add('active');
        step2Item.classList.remove('completed');
    }
}

// ============================================================
// Init
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    // Prevent back navigation
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', () => {
        history.pushState(null, '', window.location.href);
    });

    // Real-time answer validation (debounced)
    answer1.addEventListener('input', debounce(() => validateAnswer(1), 500));
    answer2.addEventListener('input', debounce(() => validateAnswer(2), 500));
    answer3.addEventListener('input', debounce(() => validateAnswer(3), 500));
});

// ============================================================
// Step 1 — Username form submit
// ============================================================
usernameForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const username = usernameInput.value.trim();
    usernameError.textContent = '';
    clearMessages();

    if (!username) {
        usernameError.textContent = 'Username is required.';
        return;
    }

    if (username.length < 3) {
        usernameError.textContent = 'Username must be at least 3 characters.';
        return;
    }

    // Disable button during request
    const btn = document.getElementById('verifyUsernameBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';

    try {
        const response = await fetch('forgot_password_backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=check_username&username=${encodeURIComponent(username)}`
        });

        const data = await response.json();

        if (data.success) {
            currentUserData = data.user_data;
            showSecurityQuestions();
            setSuccess('Username found! Please answer your security questions.');
            setTimeout(() => { successMessage.textContent = ''; }, 3000);
        } else {
            usernameError.textContent = data.message || 'Username not found.';
        }
    } catch (err) {
        setError('An error occurred. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Next <i class="fas fa-arrow-right" style="margin-left:5px;"></i>';
    }
});

// ============================================================
// Step 2 — Security form submit
// ============================================================
securityForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const correctCount = Object.values(validationStates).filter(v => v === true).length;

    if (correctCount < 2) {
        setError('You need at least 2 correct answers to proceed.');
        return;
    }

    clearMessages();

    const btn = verifyAnswersBtn;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

    try {
        const response = await fetch('forgot_password_backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=verify_answers`
                + `&username=${encodeURIComponent(currentUserData.username)}`
                + `&answer1=${encodeURIComponent(answer1.value.trim())}`
                + `&answer2=${encodeURIComponent(answer2.value.trim())}`
                + `&answer3=${encodeURIComponent(answer3.value.trim())}`
        });

        const data = await response.json();

        if (data.success) {
            setSuccess('Security answers verified! Redirecting...');
            setTimeout(() => {
                window.location.href = 'verify_otp.php';
            }, 1500);
        } else {
            setError(data.message || 'Verification failed.');
            btn.disabled = false;
            btn.innerHTML = 'Verify Answers <i class="fas fa-check" style="margin-left:5px;"></i>';
        }
    } catch (err) {
        setError('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Verify Answers <i class="fas fa-check" style="margin-left:5px;"></i>';
    }
});

// ============================================================
// "Try Another Username" button
// ============================================================
findAnotherUserBtn.addEventListener('click', function () {
    showUsernameTab();
});

// ============================================================
// Real-time answer validation
// ============================================================
async function validateAnswer(questionNumber) {
    const answerInput = [null, answer1, answer2, answer3][questionNumber];
    const icon        = [null, icon1,   icon2,   icon3  ][questionNumber];
    const answer      = answerInput.value.trim();

    validationStates[`answer${questionNumber}`] = false;
    answerInput.classList.remove('correct', 'incorrect');
    answerInput.classList.add('pending');
    icon.style.display = 'none';

    if (!answer) {
        updateVerifyButton();
        return;
    }

    try {
        const response = await fetch('forgot_password_backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=validate_answer`
                + `&username=${encodeURIComponent(currentUserData.username)}`
                + `&question_number=${questionNumber}`
                + `&answer=${encodeURIComponent(answer)}`
        });

        const data = await response.json();

        if (data.success) {
            if (data.is_correct) {
                answerInput.classList.remove('pending', 'incorrect');
                answerInput.classList.add('correct');
                icon.textContent  = '✓';
                icon.className    = 'validation-icon check';
                icon.style.display = 'block';
                validationStates[`answer${questionNumber}`] = true;
            } else {
                answerInput.classList.remove('pending', 'correct');
                answerInput.classList.add('incorrect');
                icon.textContent  = '✕';
                icon.className    = 'validation-icon cross';
                icon.style.display = 'block';
                validationStates[`answer${questionNumber}`] = false;
            }
        }
    } catch (err) {
        // Silent fail — keep pending state
    }

    updateVerifyButton();
}

// ============================================================
// Enable/disable Verify Answers button
// ============================================================
function updateVerifyButton() {
    const correctCount = Object.values(validationStates).filter(v => v === true).length;
    verifyAnswersBtn.disabled = correctCount < 2;
}

// ============================================================
// Show / hide tabs + step indicator
// ============================================================
function showSecurityQuestions() {
    usernameTab.classList.remove('active');
    securityTab.classList.add('active');

    goToStep(2);

    // Populate user info card
    const uname    = currentUserData.username || '';
    const initials = uname.substring(0, 2).toUpperCase();
    userAvatar.textContent   = initials;
    userUsername.textContent = uname;
    userIdNo.textContent     = currentUserData.idNo || '';

    // Populate security questions
    question1.textContent = currentUserData.security_question1;
    question2.textContent = currentUserData.security_question2;
    question3.textContent = currentUserData.security_question3;

    // Reset answers and states
    [answer1, answer2, answer3].forEach(inp => {
        inp.value = '';
        inp.classList.remove('correct', 'incorrect');
        inp.classList.add('pending');
    });
    [icon1, icon2, icon3].forEach(ic => { ic.style.display = 'none'; });

    validationStates = { answer1: false, answer2: false, answer3: false };
    verifyAnswersBtn.disabled = true;
}

function showUsernameTab() {
    securityTab.classList.remove('active');
    usernameTab.classList.add('active');

    goToStep(1);

    clearMessages();
    usernameError.textContent = '';
    usernameInput.value       = '';
    currentUserData           = null;

    validationStates = { answer1: false, answer2: false, answer3: false };
}

// Prevent resubmission on refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
