const idno = document.myform.idNo;
const firstname = document.myform.firstname;
const middlename = document.myform.middlename;
const lastname = document.myform.lastname;
const purokstreet = document.myform.purokstreet;
const brgy = document.myform.brgy;
const municipal = document.myform.municipal;
const province = document.myform.province;
const country = document.myform.country;
const zipcode = document.myform.zipcode;

// Multi-step registration variables
let currentStep = 1;
const totalSteps = 2;

// Security questions validation
const validateSecurityQuestions = () => {
    const questions = [
        { select: 'question1', answer: 'answer1', error: 'question1-error' },
        { select: 'question2', answer: 'answer2', error: 'question2-error' },
        { select: 'question3', answer: 'answer3', error: 'question3-error' }
    ];
    
    let isValid = true;
    const selectedQuestions = new Set();
    
    questions.forEach((q, index) => {
        const questionSelect = document.getElementById(q.select);
        const answerInput = document.getElementById(q.answer);
        const errorSpan = document.getElementById(q.error);
        
        // Clear previous errors
        errorSpan.textContent = '';
        
        // Validate question selection
        if (!questionSelect.value) {
            isValid = false;
            return;
        }
        
        // Check for duplicate questions
        if (selectedQuestions.has(questionSelect.value)) {
            errorSpan.textContent = 'Please select different questions.';
            questionSelect.style.borderColor = 'red';
            questionSelect.style.outline = '1px solid red';
            isValid = false;
            return;
        }
        selectedQuestions.add(questionSelect.value);
        
        // Validate answer
        if (!answerInput.value.trim()) {
            isValid = false;
        } else if (answerInput.value.trim().length < 2) {
            errorSpan.textContent = 'Answer must be at least 2 characters.';
            answerInput.style.borderColor = 'red';
            isValid = false;
        }
    });
    
    return isValid;
};

// Individual question validation
const validateIndividualQuestion = (questionNumber) => {
    const questionSelect = document.getElementById(`question${questionNumber}`);
    const answerInput = document.getElementById(`answer${questionNumber}`);
    const errorSpan = document.getElementById(`question${questionNumber}-error`);
    
    // Clear previous errors
    errorSpan.textContent = '';
    questionSelect.style.borderColor = '';
    questionSelect.style.outline = '';
    answerInput.style.borderColor = '';
    answerInput.style.outline = '';
    
    let isValid = true;
    
    // Only validate if user has interacted with this question or previous ones
    const hasInteraction = questionSelect.value || answerInput.value || (questionNumber > 1 && document.getElementById(`question${questionNumber - 1}`).value);
    
    if (!hasInteraction) {
        return true;
    }
    
    // Validate question selection
    if (!questionSelect.value) {
        errorSpan.textContent = 'Please select a security question.';
        questionSelect.style.borderColor = 'red';
        questionSelect.style.outline = '1px solid red';
        isValid = false;
    }
    
    // Validate answer
    if (questionSelect.value && !answerInput.value.trim()) {
        errorSpan.textContent = 'Please provide an answer.';
        answerInput.style.borderColor = 'red';
        isValid = false;
    } else if (answerInput.value.trim() && answerInput.value.trim().length < 2) {
        errorSpan.textContent = 'Answer must be at least 2 characters.';
        answerInput.style.borderColor = 'red';
        isValid = false;
    }
    
    // Check for duplicates with previous questions
    if (questionNumber > 1) {
        for (let i = 1; i < questionNumber; i++) {
            const prevQuestion = document.getElementById(`question${i}`);
            if (prevQuestion && prevQuestion.value === questionSelect.value) {
                errorSpan.textContent = 'Please select different questions.';
                questionSelect.style.borderColor = 'red';
                questionSelect.style.outline = '1px solid red';
                isValid = false;
                break;
            }
        }
    }
    
    // If valid, show green border
    if (isValid && questionSelect.value && answerInput.value.trim()) {
        questionSelect.style.borderColor = 'green';
        answerInput.style.borderColor = 'green';
    }
    
    return isValid;
};

// Sequential security question highlighting
// After confirm password is valid, highlight Q1 with red border
// After Q1 is done, highlight Q2, etc.
const updateSecurityQuestionHighlights = () => {
    const confirmPasswordValid = validationStatus['confirm_password'];
    
    for (let i = 1; i <= 3; i++) {
        const questionSelect = document.getElementById(`question${i}`);
        const answerInput = document.getElementById(`answer${i}`);
        if (!questionSelect || !answerInput) continue;
        
        const isQuestionFilled = questionSelect.value && answerInput.value.trim().length >= 2;
        
        if (isQuestionFilled) {
            // Already complete - green
            questionSelect.style.borderColor = 'green';
            questionSelect.style.outline = '1px solid green';
            answerInput.style.borderColor = 'green';
            continue;
        }
        
        // Check if previous question is done (or if it's Q1, confirm password must be valid)
        let previousComplete = false;
        if (i === 1) {
            previousComplete = confirmPasswordValid;
        } else {
            const prevQ = document.getElementById(`question${i - 1}`);
            const prevA = document.getElementById(`answer${i - 1}`);
            previousComplete = prevQ && prevA && prevQ.value && prevA.value.trim().length >= 2;
        }
        
        if (previousComplete && !isQuestionFilled) {
            // This is the next one to fill - red highlight
            questionSelect.style.borderColor = 'red';
            questionSelect.style.outline = '1px solid red';
            answerInput.style.borderColor = '';
            answerInput.style.outline = '';
        } else {
            // Not yet their turn - default
            questionSelect.style.borderColor = '';
            questionSelect.style.outline = '';
            answerInput.style.borderColor = '';
            answerInput.style.outline = '';
        }
        break; // Only highlight the next incomplete one
    }
};

// Toggle security answer visibility
const toggleSecurityAnswer = (fieldId, toggleElement) => {
    const input = document.getElementById(fieldId);
    if (!input) return;
    
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    // Toggle icon
    const eyeSVG = `
        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20" fill="#555">
            <path d="M0 0h24v24H0z" fill="none"/>
            <path d="M12 4.5C7 4.5 2.7 8 1 12c1.7 4 6 7.5 11 7.5s9.3-3.5 11-7.5c-1.7-4-6-7.5-11-7.5zm0 13c-3 0-5.5-2.5-5.5-5.5S9 6.5 12 6.5 17.5 9 17.5 12 15 17.5 12 17.5z"/>
            <circle cx="12" cy="12" r="2"/>
        </svg>`;

    const eyeSlashSVG = `
        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20" fill="#555">
            <path d="M12 6.5c2.97 0 5.5 2.53 5.5 5.5 0 .95-.26 1.84-.7 2.6l1.45 1.45C19.28 14.81 20 13.46 20 12c-1.7-4-6-7.5-11-7.5-1.46 0-2.81.28-4.05.78l1.44 1.44c.76-.44 1.65-.7 2.61-.7zM2.81 2.81L1.39 4.22l4.88 4.88C5.5 9.53 5.5 10.74 5.5 12c0 2.97 2.53 5.5 5.5 5.5 1.26 0 2.47-.5 3.41-1.29l4.88 4.88 1.41-1.41L2.81 2.81z"/>
        </svg>`;

    toggleElement.innerHTML = isPassword ? eyeSlashSVG : eyeSVG;
};

// Step navigation functions
const showStep = (stepNumber) => {
    // Hide all steps
    document.querySelectorAll('.registration-step').forEach(step => {
        step.style.display = 'none';
    });
    
    // Show current step
    document.getElementById(`step${stepNumber}`).style.display = 'block';
    currentStep = stepNumber;
};

const validateStep1 = async () => {
    const requiredFields = [
        "idNo", "firstname", "lastname", "birthdate", "purokstreet", "brgy", 
        "municipal", "province", "country", "zipcode", "sex"
    ];

    let hasErrors = false;

    for (const field of requiredFields) {
        const input = document.getElementById(field);
        if (input) {
            await validateAllFormInput({ target: input });
            if (!validationStatus[field]) {
                hasErrors = true;
            }
        }
    }

    return !hasErrors;
};

// Event listeners for step navigation
document.addEventListener('DOMContentLoaded', () => {
    // Next button handler
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', async () => {
            const isValid = await validateStep1();
            if (isValid) {
                showStep(2);
            } else {
                alert('Please fill in all required fields correctly before proceeding.');
            }
        });
    }

    // Previous button handler
    const prevBtn = document.getElementById('prevBtn');
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            showStep(1);
        });
    }

    // Security questions validation - individual validation
    for (let i = 1; i <= 3; i++) {
        const questionSelect = document.getElementById(`question${i}`);
        const answerInput = document.getElementById(`answer${i}`);
        
        if (questionSelect) {
            questionSelect.addEventListener('change', () => {
                validateIndividualQuestion(i);
                // Also validate subsequent questions to check for duplicates
                for (let j = i + 1; j <= 3; j++) {
                    validateIndividualQuestion(j);
                }
                toggleSecurityRegisterButton();
            });
        }
        
        if (answerInput) {
            answerInput.addEventListener('input', () => {
                validateIndividualQuestion(i);
                toggleSecurityRegisterButton();
            });
            answerInput.addEventListener('blur', () => {
                validateIndividualQuestion(i);
                toggleSecurityRegisterButton();
                updateSecurityQuestionHighlights();
            });
        }
    }

    // Initial step
    showStep(1);
    
    // Register button will trigger form submit event
    const registerBtn = document.getElementById("registerBtn");
    if (registerBtn) {
        registerBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            console.log('Register button clicked');
            
            // Validate login account fields first
            const step2Fields = ["username", "email", "password", "confirm_password"];
            let hasStep2Errors = false;
            
            for (const field of step2Fields) {
                const input = document.getElementById(field);
                if (input) {
                    await validateAllFormInput({ target: input });
                    if (!validationStatus[field]) {
                        hasStep2Errors = true;
                    }
                }
            }
            
            if (hasStep2Errors) {
                alert('Please fill in all login account fields correctly.');
                return;
            }
            
            // Validate security questions
            const securityValid = validateSecurityQuestions();
            if (!securityValid) {
                alert('Please complete all security questions before registering.');
                return;
            }
            
            // Trigger form submit to use the unified submission logic
            const form = document.getElementById("myform");
            if (form) {
                const submitEvent = new Event('submit', { cancelable: true, bubbles: true });
                form.dispatchEvent(submitEvent);
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", () => {
  const birthdateInput = document.getElementById('birthdate');
  if (birthdateInput) {
    birthdateInput.max = new Date().toISOString().split('T')[0];
  }
});

// Lockout time check
const lockoutTime = Number(localStorage.getItem('lockoutTime')) || 0;
const currentTime = Date.now();

if (lockoutTime && currentTime < lockoutTime) {
    window.location.href = 'login.php';
}

function togglePassword(fieldId, toggleElement) {
  const input = document.getElementById(fieldId);
  if (!input) return;
  
  const isPassword = input.type === 'password';

  // Toggle input type
  input.type = isPassword ? 'text' : 'password';

  // Toggle icon
  const eyeSVG = `
      <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20" fill="#555">
          <path d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 4.5C7 4.5 2.7 8 1 12c1.7 4 6 7.5 11 7.5s9.3-3.5 11-7.5c-1.7-4-6-7.5-11-7.5zm0 13c-3 0-5.5-2.5-5.5-5.5S9 6.5 12 6.5 17.5 9 17.5 12 15 17.5 12 17.5z"/>
          <circle cx="12" cy="12" r="2"/>
      </svg>`;

  const eyeSlashSVG = `
      <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 24 24" width="20" fill="#555">
          <path d="M12 6.5c2.97 0 5.5 2.53 5.5 5.5 0 .95-.26 1.84-.7 2.6l1.45 1.45C19.28 14.81 20 13.46 20 12c-1.7-4-6-7.5-11-7.5-1.46 0-2.81.28-4.05.78l1.44 1.44c.76-.44 1.65-.7 2.61-.7zM2.81 2.81L1.39 4.22l4.88 4.88C5.5 9.53 5.5 10.74 5.5 12c0 2.97 2.53 5.5 5.5 5.5 1.26 0 2.47-.5 3.41-1.29l4.88 4.88 1.41-1.41L2.81 2.81z"/>
      </svg>`;

  toggleElement.innerHTML = isPassword ? eyeSlashSVG : eyeSVG;
}

// Live refresher
function debounce(func, wait) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    return new Promise((resolve) => {
      timeout = setTimeout(() => resolve(func.apply(this, args)), wait);
    });
  }
}

// Show errors
const showError = (fieldId, error) => {
  const field = document.getElementById(fieldId);
  const errorEl = document.getElementById(`${fieldId}-error`);

  if (!field || !errorEl) return;

  if (error) {
    errorEl.textContent = error;
    errorEl.style.fontSize = "0.75rem";
    errorEl.style.color = "red";
    field.style.border = "1px solid red";
  } else {
    errorEl.textContent = "";
    field.style.border = "";
  }
};

// Helper validation functions
const isEmpty = (value, label) => {
  return !value.trim() ? `${label} is required.` : null;
};

const hasWhiteSpaces = (value) => {
  return /\s/.test(value) ? "Cannot contain white spaces." : null;
};

const hasDoubleSpaces = (value) => {
  return /\s{2,}/.test(value) ? "Cannot contain double spaces." : null;
};

const hasTripleConsecutiveLetters = (value) => {
  const normalized = value.toLowerCase();
  return /(.)\1\1/.test(normalized) ? "Cannot have 3 identical letters." : null;
};

const hasContainsNumber = (input, label) => {
  if (/\d/.test(input)) {
    return `${label} must not contain number.`;
  }
  return null;
};

const hasInvalidSpecialCharacters = (value) => {
  return /[^a-zA-Z0-9 ñÑ]/.test(value) 
    ? "Cannot contain special character." 
    : null;
};

const hasRejectAllCaps = (value) => {
  if (!/[A-Za-z]/.test(value)) return null;
  if (/[A-Z]{2,}/.test(value)) {
    return "Do not use all capital letters.";
  }
  return null;
};

const hasRejectAllCapsv2 = (value) => {
  if (!/[A-Za-z]/.test(value)) return null;
  if (value === value.toUpperCase()) {
    return "Do not use all capital letters.";
  }
  return null;
};

const hasProperCasingv2 = (value) => {
  const trimmed = value.trim();
  if (!trimmed) return null;

  const words = trimmed.split(/\s+/);
  for (let word of words) {
    if (word.length === 0) continue;
    const rest = word.slice(1);
    if (/[A-Z]/.test(rest)) {
      return "Each word must start with a capital letter; other letters must be lowercase.";
    }
  }
  return null;
};

const hasCheckMinMaxLength = (value, label, min, max) => {
  const length = value.trim().length;

  if (length < min) {
    return `${label} must be at least ${min} characters.`;
  }

  if (length > max) {
   return `${label} must be ${max} characters or fewer.`;
  }

  return null;
};

const isSingleUppercaseLetter = (value) => {
  const trimmed = value.trim();
  if (!/^[A-Z]$/.test(trimmed)) {
    return "Single capital letter only.";
  }
  return null;
};

const allowNumbersAndDashOnly = (e) => {
  const allowedKeys = [
    '0','1','2','3','4','5','6','7','8','9','-',
    'Backspace','ArrowLeft','ArrowRight','Tab','Delete'
  ];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }
};

const allowNumbersOnly = (e) => {
  const allowedKeys = [
    '0','1','2','3','4','5','6','7','8','9',
    'Backspace','ArrowLeft','ArrowRight','Tab','Delete'
  ];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }
};

const isValidRomanNumeral = (str) => {
  const romanRegex = /^(M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
  return romanRegex.test(str);
};

const clearFieldErrors = (fieldIds) => {
  fieldIds.forEach(fieldId => {
    showError(fieldId, null);
    const field = document.getElementById(fieldId);
    if (field) field.classList.remove('input-error');
  });
};

const calculateAge = () => {
  const birthdateInput = document.getElementById('birthdate');
  const ageInput = document.getElementById('age');

  if (!birthdateInput || !ageInput) return;

  const birthdateValue = birthdateInput.value;
  ageInput.value = '';

  if (!birthdateValue) {
    clearFieldErrors(['birthdate', 'age']);
    return;
  }

  const birthdate = new Date(birthdateValue);
  const today = new Date();
  let age = today.getFullYear() - birthdate.getFullYear();
  const m = today.getMonth() - birthdate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
    age--;
  }

  ageInput.value = age;
  const error = validateBirthAndAge(birthdateValue);

  if (error) {
    showError('age', error);
    birthdateInput.classList.add('input-error');
    ageInput.classList.add('input-error');
  } else {
    showError('age', null);
    birthdateInput.classList.remove('input-error');
    ageInput.classList.remove('input-error');
  }
};

const getPasswordStrength = (password) => {
  const hasUppercase = /[A-Z]/.test(password);
  const hasNumber = /[0-9]/.test(password);
  const hasSpecialChar = /[!@#$%^&*()_+]/.test(password);
  const hasMinLength = /.{8,}/.test(password);

  let score = 0;
  if (hasUppercase) score++;
  if (hasNumber) score++;
  if (hasSpecialChar) score++;
  if (hasMinLength) score++;

  if (score <= 2) return { level: "Weak", color: "red" };
  if (score === 3) return { level: "Medium", color: "orange" };
  if (score === 4) return { level: "Strong", color: "green" };
  return { level: "Weak", color: "red" };
};

const checkExists = async (field, value) => {
  try {
    const response = await fetch(`check_exists.php?field=${field}&value=${encodeURIComponent(value)}`);
    const result = await response.json();
    return result.exists;
  } catch (err) {
    console.error("Error checking value:", err);
    return false;
  }
};

// ==== PERSONAL INFORMATION VALIDATION ===== //
const validateIDNumber = (value, label) => {
  const trimmed = value.trim();
  const pattern = /^\d{4}-\d{4}$/;

  const emptyError = isEmpty(trimmed, label);
  if (emptyError) return emptyError;

  if (!pattern.test(trimmed)) {
    return `${label} must be in the format XXXX-XXXX.`;
  }

  return null;
};

if (idno) {
  idno.addEventListener('keydown', allowNumbersAndDashOnly);
}

const findFirstErrorPosition = (value, label) => {
  const trimmed = value.trim();
  
  if (label === "Middle Initial") {
    if (trimmed === "") return null;
    
    const singleLetterError = isSingleUppercaseLetter(trimmed);
    if (singleLetterError) return singleLetterError;
    
    const numberError = hasContainsNumber(trimmed, label);
    if (numberError) return numberError;
    
    return null;
  }

  const emptyError = isEmpty(trimmed, label);
  if (emptyError) return emptyError;

  const doubleSpaceError = hasDoubleSpaces(value);
  if (doubleSpaceError) return doubleSpaceError;

  const errors = [];
  
  for (let i = 0; i < trimmed.length; i++) {
    const char = trimmed[i];
    
    if (/\d/.test(char)) {
      errors.push({ position: i, error: `${label} must not contain number.` });
    }
    
    if (/[^a-zA-Z0-9 ñÑ]/.test(char)) {
      errors.push({ position: i, error: "Cannot contain special character." });
    }
    
    if (i >= 2) {
      const threeChars = trimmed.substring(i-2, i+1).toLowerCase();
      if (/(.)\1\1/.test(threeChars)) {
        errors.push({ position: i-2, error: "Cannot have 3 identical letters." });
      }
    }
  }
  
  const words = trimmed.split(/\s+/);
  for (let word of words) {
    if (word.length > 0) {
      const firstChar = word[0];
      if (firstChar !== firstChar.toUpperCase()) {
        const wordPosition = trimmed.indexOf(word);
        errors.push({ position: wordPosition, error: "Each word must start with a capital letter." });
      }
    }
  }
  
  if (/[A-Za-z]/.test(trimmed)) {
    if (/[A-Z]{2,}/.test(trimmed)) {
      for (let i = 0; i < trimmed.length - 1; i++) {
        if (/[A-Z]/.test(trimmed[i]) && /[A-Z]/.test(trimmed[i+1])) {
          errors.push({ position: i, error: "Do not use all capital letters." });
          break;
        }
      }
    }
  }

  if (errors.length > 0) {
    const firstError = errors.reduce((earliest, current) => 
      current.position < earliest.position ? current : earliest
    );
    return firstError.error;
  }

  const lengthError = hasCheckMinMaxLength(trimmed, label, 2, 20);
  if (lengthError) return lengthError;

  return null;
};

const validateAllNameFields = (value, label) => {
  return findFirstErrorPosition(value, label);
};

const validateSuffix = (value) => {
  const trimmed = value.trim();

  const doubleSpaceError = hasDoubleSpaces(value);
  if (doubleSpaceError) return doubleSpaceError;

  const containsNumber = hasContainsNumber(trimmed, 'Suffix');
  if (containsNumber) return containsNumber;
  
  if (!trimmed) return null;

  const allowedSuffixes = ['Jr', 'Sr'];

  if (allowedSuffixes.includes(trimmed)) {
    return null;
  }

  if (/^[IVXLCDM]+$/.test(trimmed) && isValidRomanNumeral(trimmed)) {
    return null;
  }

  return "Invalid suffix.";
};

const validateBirthAndAge = (birthdateValue) => {
  if (!birthdateValue) return "Birthdate is required.";

  const birthdate = new Date(birthdateValue);
  const today = new Date();
  let age = today.getFullYear() - birthdate.getFullYear();
  const m = today.getMonth() - birthdate.getMonth();

  if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
    age--;
  }

  if (isNaN(age)) return "Invalid birthdate.";
  if (age < 18) return "You must be at least 18 years old.";

  return null; 
};

const validateEmail = (value) => {
  const trimmed = value.trim();

  const emptyError = isEmpty(trimmed, "Email");
  if (emptyError) return emptyError;

  if (hasDoubleSpaces(trimmed)) return "Email must not contain double spaces.";

  if (hasWhiteSpaces(trimmed)) return "Email must not contain white spaces.";

  if (hasTripleConsecutiveLetters(trimmed)) return "Email must not contain 3 consecutive letters.";

  const limitError = hasCheckMinMaxLength(trimmed, "Email", 8, 50);
  if (limitError) return limitError;

  const emailRegex = /^[a-z0-9._-]+@[^\s@]+\.[^\s@]{2,}$/;
  if (!emailRegex.test(trimmed)) return "Invalid email format.";

  const localPart = trimmed.split('@')[0];
  if (localPart.length === 1) return "Local part (before @) must be at least 2 characters.";

  return null;
};

const validateUsername = (value) => {
  const trimmed = value.trim();

  const emptyError = isEmpty(trimmed, "Username");
  if (emptyError) return emptyError;

  if (hasDoubleSpaces(trimmed)) return "Username must not contain double spaces.";

  if (hasWhiteSpaces(trimmed)) return "Username must not contain white spaces.";

  if (hasTripleConsecutiveLetters(trimmed)) return "Username must not contain 3 consecutive letters.";

  if (hasRejectAllCapsv2(trimmed)) return "Username must not be all caps.";

  const limitError = hasCheckMinMaxLength(trimmed, "Username", 5, 20);
  if (limitError) return limitError;

  if (/^\d/.test(trimmed)) return "Username must not start with a number.";

  const usernameRegex = /^[a-zA-Z][a-zA-Z0-9._]*$/;
  if (!usernameRegex.test(trimmed)) return "Username contains invalid characters.";

  return null;
};

const validatePasswordOnly = (password) => {
  const trimmed = password.trim();
  const label = "Password";

  const emptyError = isEmpty(trimmed, label);
  if (emptyError) return emptyError;

  const limitError = hasCheckMinMaxLength(trimmed, label, 8, 20);
  if (limitError) return limitError;

  const hasUppercase = /[A-Z]/.test(trimmed);
  const hasNumber = /[0-9]/.test(trimmed);
  const hasSpecialChar = /[!@#$%^&*()_+]/.test(trimmed);

  if (!hasUppercase || !hasNumber || !hasSpecialChar) {
    return "Password must contain at least one uppercase letter, one number, and one special character.";
  }

  return null;
};

const validatePasswordMatch = (password, confirmPassword) => {
  if (confirmPassword.trim() === "") return "Please confirm your password.";
  if (password.trim() !== confirmPassword.trim()) {
    return "Passwords do not match.";
  }
  return null;
};

// Password strength indicator
const passwordInput = document.getElementById("password");
if (passwordInput) {
  passwordInput.addEventListener("input", (e) => {
    const value = e.target.value.trim();
    const pswrdLabel = document.getElementById("pswrd");
    if (!pswrdLabel) return;

    const emptyError = isEmpty(value, "Password");
    const limitError = hasCheckMinMaxLength(value, "Password", 8, 20);

    if (emptyError || limitError) {
      pswrdLabel.textContent = ""; 
      return;
    }

    const { level, color } = getPasswordStrength(e.target.value);
    pswrdLabel.textContent = level + " password";
    pswrdLabel.style.color = color;
  });
}

const isProperCasingAddress = (value) => {
  const words = value.trim().split(/\s+/);

  for (const word of words) {
    if (/^(Purok)$/i.test(word)) {
      if (word !== "Purok") return false;
    } else if (/^P-?\d+[A-Z]?$/.test(word)) {
      if (word !== word.toUpperCase()) return false;
    } else if (/^\d+[A-Z]?$/.test(word) || /^\d$/.test(word)) {
      continue;
    } else {
      if (!/^[A-Z][a-z]*$/.test(word)) return false;
    }
  }

  return true;
};

const isProperCasingAddressv2 = (value) => {
  const words = value.trim().split(/\s+/);
  for (const word of words) {
    if (word.length > 0 && !/^[A-Z][a-z]*$/.test(word)) {
      return false;
    }
  }
  return true;
};

const validatePurok = (value) => {
  const trimmed = value.trim();
  const label = "Purok";

  if (hasDoubleSpaces(value)) return `Cannot contain double spaces.`;
  if (isEmpty(trimmed, label)) return `${label} is required.`;

  const consecutiveError = hasTripleConsecutiveLetters(trimmed);
  if (consecutiveError) return consecutiveError;

  if (!isProperCasingAddress(trimmed)) {
    return `${label} must start with a capital letter`;
  }

  const rejectCapsErr = hasRejectAllCaps(trimmed);
  if (rejectCapsErr) return rejectCapsErr;

  const words = trimmed.split(/\s+/);

  // Allow exactly "Purok"
  if (/^Purok$/i.test(trimmed)) return null;

  // 🔵 Allow "Purok-1" or "Purok-12"
  // 🔴 Reject "Purok-1-A"
  if (/^Purok-\d+$/i.test(trimmed)) {
    return null;
  }

  // Allow "P-1", "P-12", "P1", "P12A"
  if (/^P-?\d+[A-Za-z]?$/.test(trimmed)) {
    if (words.length > 1)
      return `${label} must not contain extra words after "${words[0]}".`;

    if (trimmed !== trimmed.toUpperCase())
      return `${label} must be in uppercase when using this format.`;

    return null;
  }

  // Starting with number rules
  if (/^\d/.test(trimmed)) {
    if (
      (words.length === 1 && /^(\d+[A-Za-z]?|\d+-[A-Za-z])$/.test(words[0])) ||
      (words.length === 2 &&
        /^\d+$/.test(words[0]) &&
        /^[A-Za-z]$/.test(words[1]))
    ) {
      return null;
    }
    return `${label} is invalid when starting with a number.`;
  }

  // 🔵 Allow "Purok 1" or "Purok 1A"
  // 🔴 Reject "Purok 1-A"
  if (/^Purok/i.test(words[0])) {
    if (
      words.length === 2 &&
      /^Purok$/i.test(words[0]) &&
      /^[0-9]+[A-Za-z]?$/.test(words[1])
    ) {
      // Reject if inside there is a dash + letter combo like "1-A"
      if (/^\d+-[A-Za-z]$/.test(words[1])) {
        return `${label} is invalid: dash with letter is not allowed (e.g. "1-A").`;
      }
      return null;
    }
    return `${label} is invalid when using the Purok format.`;
  }

  // Alphabet-only formats
  if (/^[A-Za-z]/.test(trimmed)) {
    if (words.length > 2) return `${label} must not exceed 2 words.`;

    for (const w of words) {
      if (/\d/.test(w)) return `${label} words must not contain numbers: "${w}"`;
      if (!/^[A-Za-z\-]+$/.test(w))
        return `${label} contains an invalid word: "${w}"`;
    }

    return null;
  }

  return `${label} is invalid.`;
};


const validateBarangay = (value) => {
  const trimmed = value.trim();
  const label = "Barangay";

  if (hasDoubleSpaces(value)) return `Cannot contain double spaces.`;
  if (isEmpty(trimmed, label)) return `${label} is required.`;

  const tripleErr = hasTripleConsecutiveLetters(trimmed);
  if (tripleErr) return tripleErr;

  const rejectCapsErr = hasRejectAllCaps(trimmed);
  if (rejectCapsErr) return rejectCapsErr;

  const words = trimmed.split(/\s+/);

  // 👉 FIXED: Only check FIRST LETTER of the entire Barangay
  if (/^[a-z]/.test(trimmed)) {
    return `${label} must start with a capital letter.`;
  }


  // 👉 1) ALLOW PURE NUMBER ONLY
  if (/^\d+$/.test(trimmed)) return null;

  // 👉 2) REJECT: number + letter at START ("12B Poblacion")
  if (/^\d+[A-Za-z]/.test(trimmed)) {
    return `${label} cannot start with a number + letter.`;
  }

  // 👉 3) ALLOW: Prefix + (number + letter)  
  // Barangay 12B, Barangay 12-B, Brgy 3A, Brgy 3-A
  if (/^(Brgy|Barangay|Poblacion|Purok)\s+\d+[-]?[A-Za-z]$/.test(trimmed)) {
    return null;
  }

  // 👉 4) Prefix + PURE number is still allowed (old rule)
  if (/^(Brgy|Barangay|Poblacion|Purok)\s+\d+$/.test(trimmed)) {
    return null;
  }

  // 👉 5) Validate regular words (no numbers inside)
  if (/^[A-Za-z]/.test(trimmed)) {
    if (words.length > 4) return `${label} must not exceed 4 words.`;

    for (const w of words) {
      // Reject words with numbers like "San2" or "Ro3que"
      if (/\d/.test(w)) {
        return `${label} words must not contain numbers: "${w}"`;
      }

      // Letters + hyphen only
      if (!/^[A-Za-z\-]+$/.test(w)) {
        return `${label} contains an invalid word: "${w}"`;
      }
    }

    return null;
  }

  return `${label} is invalid.`;
};


// Address validation helper functions
const hasNumbers = (str) => /\d/.test(str);
const hasInvalidChars = (str) => /[^a-zA-Z\s-]/.test(str);
const checkCapitalization = (str) => {
  const words = str.split(/\s+/);
  for (let word of words) {
    if (word[0] !== word[0].toUpperCase()) return false;
  }
  return true;
};

const validateMunicipality = (municipality) => {
  const trimmed = municipality.trim();
  const label = "Municipality";

  // Required
  if (trimmed.length === 0) 
    return `${label} is required.`;

  // Double spaces
  if (hasDoubleSpaces(municipality)) 
    return `${label} cannot contain double spaces.`;

  // Reject ALL CAPS (e.g., "BUTUAN CITY")
  if (/^[A-Z\s]+$/.test(trimmed)) 
    return `Do not use all capital letters.`;

  // Numbers not allowed
  if (hasNumbers(trimmed)) 
    return `${label} must not contain number.`;

  // Special characters not allowed
  if (hasInvalidChars(trimmed)) 
    return `${label} must not contain special character.`;

  // Split into words
  const words = trimmed.split(/\s+/);

  // Each word must start with a capital letter
  for (const word of words) {
    if (!/^[A-Z][a-z]*$/.test(word)) {
      return `Each word must start with a capital letter.`;
    }
  }

  return null; // Valid
};


const validateProvince = (province) => {
  const trimmed = province.trim();
  if (trimmed.length === 0) return "Province is required.";
  if (hasDoubleSpaces(province)) return "Province cannot contain double spaces.";
  if (hasNumbers(trimmed)) return "Province must not contain number.";
  if (hasInvalidChars(trimmed)) return "Province must not contain special character.";

  const words = trimmed.split(/\s+/);

  // First word must start with capital
  const firstWord = words[0];
  if (firstWord[0] !== firstWord[0].toUpperCase()) {
    return "Province must start with a capital letter.";
  }
// First word: only the first letter must be capital,
// the rest must be lowercase
if (firstWord.slice(1) !== firstWord.slice(1).toLowerCase()) {
  return "Do not use all capital letters.";
}

// Next words: first letter can be capital OR small,
// but the rest must be lowercase
for (let i = 1; i < words.length; i++) {
  const word = words[i];
  const rest = word.slice(1);

  if (rest !== rest.toLowerCase()) {
    return "Do not use all capital letters.";
  }
}

  return null;
};

const validateCountry = (country) => {
  const trimmed = country.trim();
  const label = "Country";

  if (trimmed.length === 0) 
    return `${label} is required.`;

  // ❌ Cannot contain double spaces anywhere
  if (/\s{2,}/.test(country))
    return `Cannot contain double spaces.`;

  // ❌ Reject ALL CAPS first  
  if (/^[A-Z\s]+$/.test(trimmed)) 
    return "Do not use all capital letters."; 

  // ❌ No numbers allowed
  if (hasNumbers(trimmed)) 
    return `${label} must not contain number.`;

  // ❌ No special characters
  if (hasInvalidChars(trimmed)) 
    return `${label} must not contain special character.`;

  // ❌ Each word must start with capital letter
  if (!checkCapitalization(trimmed)) 
    return "Each word must start with a capital letter.";

  // ❌ Must exactly be "Philippines"
  if (trimmed !== "Philippines") 
    return "Invalid country spelling.";

  return null;
};



const validateZipCode = (zip) => {
  const trimmed = zip.trim();
  const label = "ZIP Code";

  const emptyError = isEmpty(trimmed, label);
  if (emptyError) return emptyError; 

  if (!/^\d+$/.test(trimmed)) return `${label} must contain numbers only.`;
  if (trimmed.length !== 4) return `${label} must be exactly 4 digits.`;
  if (parseInt(trimmed) < 1000 || parseInt(trimmed) > 9999) return `${label} must be between 1000 and 9999.`;

  return null;
};

if (zipcode) {
  zipcode.addEventListener('keydown', allowNumbersOnly);
}

// ==== Validation Register ==== //
const validationStatus = {
  idno: false,
  firstname: false,
  middlename: false,
  lastname: false,
  suffix: false,
  birthdate: false,
  username: false,
  email: false,
  password: false,
  confirm_password: false,
  purokstreet: false,
  brgy: false,
  municipal: false,
  province: false,
  country: false,
  zipcode: false,
  sex: false
};

const toggleRegisterButton = () => {
    // Validation is now handled inline on register button click
    // No longer auto-disabling the button
};

// Enhanced toggle for security questions step
const toggleSecurityRegisterButton = () => {
    // Validation is now handled inline on register button click
};

document.addEventListener('DOMContentLoaded', () => {
  // Register button is always enabled; validation happens on click
});

const validateAllFormInput = async (e) => {   
  const id = e.target.id;
  const value = e.target.value;
  let error = null;

  switch(id){
    case "idNo":
      error = validateIDNumber(value, "ID Number");
      if (!error) {
        const exists = await checkExists('idNo', value);
        if (exists) error = "ID number already exists.";
      }
      break;
    case 'firstname':
      error = validateAllNameFields(value, "First Name");
      break;
    case 'middlename':
      error = validateAllNameFields(value, "Middle Initial");
      break;
    case 'lastname':
      error = validateAllNameFields(value, "Last Name");
      break;
    case "suffix":
      error = validateSuffix(value);
      break;
    case "birthdate":
      const birthdate = value.trim();
      if (!birthdate) {
        error = "Birthdate is required.";
      } else {
        error = validateBirthAndAge(birthdate);
      }
      break;
    case "sex":
      const sex = value.trim();
      if (!sex || sex === "Select Sex") {
        error = "Sex is required.";
      }
      break;
    case "username":
      error = validateUsername(value);
      if (!error) {
        const exists = await checkExists('username', value);
        if (exists) error = "Username already exists.";
      }
      break;
    case "email":
      error = validateEmail(value);
      if (!error) {
        const exists = await checkExists('emailAddress', value);
        if (exists) error = "Email already exists.";
      }
      break;
    case "password":
      error = validatePasswordOnly(value);
      break;
    case "confirm_password":
      const originalPassword = document.getElementById("password")?.value || '';
      error = validatePasswordMatch(originalPassword, value);
      const successEl = document.getElementById("confirm_password-success");
      if (!error && value.trim()) {
        if (successEl) successEl.textContent = "✓ Passwords match!";
      } else {
        if (successEl) successEl.textContent = "";
      }
      break;
    case "purokstreet":
      error = validatePurok(value);
      break;
    case "brgy":
      error = validateBarangay(value);
      break;
    case "municipal":
      error = validateMunicipality(value);
      break;
    case "province":
      error = validateProvince(value);
      break;
    case "country":
      error = validateCountry(value);
      break;
    case "zipcode":
      error = validateZipCode(value);
      break;
  }

  validationStatus[id] = (error === null);
  showError(id, error);
  toggleRegisterButton();
  
  // Update sequential security question highlighting
  if (currentStep === 2) {
    updateSecurityQuestionHighlights();
  }
};

const form = document.getElementById("myform");
if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // If we're on step 1, prevent submission and show validation message
    if (currentStep === 1) {
      alert('Please complete all steps before registering.');
      return;
    }

    // Validate security questions
    const securityValid = validateSecurityQuestions();
    if (!securityValid) {
      alert('Please complete all security questions before registering.');
      return;
    }

    let hasErrors = false;
    const allFields = [
      "idNo", "firstname", "middlename", "lastname", "birthdate",
      "username", "email", "password", "confirm_password", "purokstreet",
      "brgy", "municipal", "province", "country", "zipcode", "sex", "suffix"
    ];

    for (const field of allFields) {
      const input = document.getElementById(field);
      if (input) {
        await validateAllFormInput({ target: input });
        if (!validationStatus[field]) {
          console.log(`[${field}] ❌`);
          hasErrors = true;
        }
      }
    }

    if (hasErrors) {
      console.log("Form has errors. Not submitting.");
      return;
    }

    const formData = new FormData(form);

    try {
      const response = await fetch("register.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.text();

      if (response.ok && result.trim() === "success") {
        alert("Registration successful! Redirecting to login...");
        window.location.href = "login.php";
      } else {
        alert("Registration failed: " + result);
      }
    } catch (error) {
      console.error("Error submitting form:", error);
      alert("An error occurred while submitting the form.");
    }
  });

  form.addEventListener('input', debounce(validateAllFormInput, 400));
}

// Back button handling
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