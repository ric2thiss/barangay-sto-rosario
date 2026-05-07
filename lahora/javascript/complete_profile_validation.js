// Helper validation functions for complete profile

const showError = (fieldId, error) => {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(`${fieldId}-error`);

    if (!field || !errorEl) return;

    if (error) {
        errorEl.textContent = error;
        errorEl.style.fontSize = "0.75rem";
        errorEl.style.color = "red";
        errorEl.style.display = "block";
        errorEl.style.marginTop = "2px";
        field.style.border = "1px solid red";
    } else {
        errorEl.textContent = "";
        field.style.border = "";
    }
};

const isEmpty = (value, label) => !value.trim() ? `${label} is required.` : null;
const hasDoubleSpaces = (value) => /\s{2,}/.test(value) ? "Cannot contain double spaces." : null;
const hasWhiteSpaces = (value) => /\s/.test(value) ? "Cannot contain white spaces." : null;
const hasNumbers = (str) => /\d/.test(str);
const hasContainsNumber = (input, label) => /\d/.test(input) ? `${label} must not contain number.` : null;
const hasInvalidChars = (str) => /[^a-zA-Z\s-]/.test(str);
const hasTripleConsecutiveLetters = (value) => /(.)\1\1/.test(value.toLowerCase()) ? "Cannot have 3 identical letters." : null;
const hasRejectAllCaps = (value) => (/[A-Za-z]/.test(value) && /[A-Z]{2,}/.test(value)) ? "Do not use all capital letters." : null;

// Extracted from register.js
const isValidRomanNumeral = (str) => {
    const romanRegex = /^(M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3}))$/;
    return romanRegex.test(str);
};

const isSingleUppercaseLetter = (value) => {
    const trimmed = value.trim();
    if (!/^[A-Z]$/.test(trimmed)) {
        return "Single capital letter only.";
    }
    return null;
};

const validateMiddleInitial = (value) => {
    const trimmed = value.trim();
    if (trimmed === "") return null;

    const singleLetterError = isSingleUppercaseLetter(trimmed);
    if (singleLetterError) return singleLetterError;

    const numberError = hasContainsNumber(trimmed, "Middle Initial");
    if (numberError) return numberError;

    return null;
};

const validateSuffix = (value) => {
    const trimmed = value.trim();
    const doubleSpaceError = hasDoubleSpaces(value);
    if (doubleSpaceError) return doubleSpaceError;
    const containsNumber = hasContainsNumber(trimmed, 'Suffix');
    if (containsNumber) return containsNumber;
    if (!trimmed) return null;

    const allowedSuffixes = ['Jr', 'Sr'];
    if (allowedSuffixes.includes(trimmed)) return null;
    if (/^[IVXLCDM]+$/.test(trimmed) && isValidRomanNumeral(trimmed)) return null;

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

window.calculateAge = function () {
    const birthdateInput = document.getElementById('birthdate');
    const ageInput = document.getElementById('age');
    if (!birthdateInput || !ageInput) return;

    const birthdateValue = birthdateInput.value;
    ageInput.value = '';

    if (!birthdateValue) {
        showError('age', null);
        return;
    }

    const birthdate = new Date(birthdateValue);
    const today = new Date();
    let age = today.getFullYear() - birthdate.getFullYear();
    const m = today.getMonth() - birthdate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
        age--;
    }

    ageInput.value = isNaN(age) ? '' : age;
    const error = validateBirthAndAge(birthdateValue);
    if (error) {
        showError('age', error);
    } else {
        showError('age', null);
        showError('birthdate', null);
    }
};

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
    if (/^Purok$/i.test(trimmed)) return null;
    if (/^Purok-\d+$/i.test(trimmed)) return null;
    if (/^P-?\d+[A-Za-z]?$/.test(trimmed)) {
        if (words.length > 1) return `${label} must not contain extra words after "${words[0]}".`;
        if (trimmed !== trimmed.toUpperCase()) return `${label} must be in uppercase when using this format.`;
        return null;
    }

    if (/^\d/.test(trimmed)) {
        if ((words.length === 1 && /^(\d+[A-Za-z]?|\d+-[A-Za-z])$/.test(words[0])) ||
            (words.length === 2 && /^\d+$/.test(words[0]) && /^[A-Za-z]$/.test(words[1]))) {
            return null;
        }
        return `${label} is invalid when starting with a number.`;
    }

    if (/^Purok/i.test(words[0])) {
        if (words.length === 2 && /^Purok$/i.test(words[0]) && /^[0-9]+[A-Za-z]?$/.test(words[1])) {
            if (/^\d+-[A-Za-z]$/.test(words[1])) {
                return `${label} is invalid: dash with letter is not allowed (e.g. "1-A").`;
            }
            return null;
        }
        return `${label} is invalid when using the Purok format.`;
    }

    if (/^[A-Za-z]/.test(trimmed)) {
        if (words.length > 2) return `${label} must not exceed 2 words.`;
        for (const w of words) {
            if (/\d/.test(w)) return `${label} words must not contain numbers: "${w}"`;
            if (!/^[A-Za-z\-]+$/.test(w)) return `${label} contains an invalid word: "${w}"`;
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

    if (/^[a-z]/.test(trimmed)) return `${label} must start with a capital letter.`;
    if (/^\d+$/.test(trimmed)) return null;
    if (/^\d+[A-Za-z]/.test(trimmed)) return `${label} cannot start with a number + letter.`;
    if (/^(Brgy|Barangay|Poblacion|Purok)\s+\d+[-]?[A-Za-z]$/.test(trimmed)) return null;
    if (/^(Brgy|Barangay|Poblacion|Purok)\s+\d+$/.test(trimmed)) return null;

    if (/^[A-Za-z]/.test(trimmed)) {
        if (words.length > 4) return `${label} must not exceed 4 words.`;
        for (const w of words) {
            if (/\d/.test(w)) return `${label} words must not contain numbers: "${w}"`;
            if (!/^[A-Za-z\-]+$/.test(w)) return `${label} contains an invalid word: "${w}"`;
        }
        return null;
    }
    return `${label} is invalid.`;
};

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

    if (trimmed.length === 0) return `${label} is required.`;
    if (hasDoubleSpaces(municipality)) return `${label} cannot contain double spaces.`;
    if (/^[A-Z\s]+$/.test(trimmed)) return `Do not use all capital letters.`;
    if (hasNumbers(trimmed)) return `${label} must not contain number.`;
    if (hasInvalidChars(trimmed)) return `${label} must not contain special character.`;

    const words = trimmed.split(/\s+/);
    for (const word of words) {
        if (!/^[A-Z][a-z]*$/.test(word)) {
            return `Each word must start with a capital letter.`;
        }
    }
    return null;
};

const validateProvince = (province) => {
    const trimmed = province.trim();
    if (trimmed.length === 0) return "Province is required.";
    if (hasDoubleSpaces(province)) return "Province cannot contain double spaces.";
    if (hasNumbers(trimmed)) return "Province must not contain number.";
    if (hasInvalidChars(trimmed)) return "Province must not contain special character.";

    const words = trimmed.split(/\s+/);
    const firstWord = words[0];
    if (firstWord[0] !== firstWord[0].toUpperCase()) return "Province must start with a capital letter.";
    if (firstWord.slice(1) !== firstWord.slice(1).toLowerCase()) return "Do not use all capital letters.";

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

    if (trimmed.length === 0) return `${label} is required.`;
    if (/\s{2,}/.test(country)) return `Cannot contain double spaces.`;
    if (/^[A-Z\s]+$/.test(trimmed)) return "Do not use all capital letters.";
    if (hasNumbers(trimmed)) return `${label} must not contain number.`;
    if (hasInvalidChars(trimmed)) return `${label} must not contain special character.`;
    if (!checkCapitalization(trimmed)) return "Each word must start with a capital letter.";
    if (trimmed !== "Philippines") return "Invalid country spelling.";

    return null;
};

const validateZipCode = (zip) => {
    const trimmed = zip.trim();
    const label = "Zip Code";

    const emptyError = isEmpty(trimmed, label);
    if (emptyError) return emptyError;

    if (!/^\d+$/.test(trimmed)) return `${label} must contain numbers only.`;
    if (trimmed.length !== 4) return `${label} must be exactly 4 digits.`;
    if (parseInt(trimmed) < 1000 || parseInt(trimmed) > 9999) return `${label} must be between 1000 and 9999.`;

    return null;
};

const allowNumbersOnly = (e) => {
    const allowedKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'Backspace', 'ArrowLeft', 'ArrowRight', 'Tab', 'Delete'];
    if (!allowedKeys.includes(e.key)) {
        e.preventDefault();
    }
};

const validateSecurityAnswer = (val) => {
    if (!val.trim()) return "Answer is required.";
    if (val.trim().length < 2) return "Answer must be at least 2 characters.";
    return null;
};

// Main validation flow
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".formreg");

    // Add key restrictions where necessary
    const zipcode = document.getElementById("zipcode");
    if (zipcode) {
        zipcode.addEventListener('keydown', allowNumbersOnly);
    }

    // Bind inline validations
    const binders = [
        { id: "middlename", validator: validateMiddleInitial },
        { id: "suffix", validator: validateSuffix },
        { id: "birthdate", validator: () => validateBirthAndAge(document.getElementById("birthdate").value) },
        { id: "purokstreet", validator: validatePurok },
        { id: "brgy", validator: validateBarangay },
        { id: "municipal", validator: validateMunicipality },
        { id: "province", validator: validateProvince },
        { id: "country", validator: validateCountry },
        { id: "zipcode", validator: validateZipCode },
        { id: "answer1", validator: validateSecurityAnswer },
        { id: "answer2", validator: validateSecurityAnswer },
        { id: "answer3", validator: validateSecurityAnswer }
    ];

    binders.forEach(b => {
        const el = document.getElementById(b.id);
        if (el) {
            const check = () => {
                const val = el.value || "";
                const err = b.validator(val);
                if (b.id !== "birthdate") {
                    showError(b.id, err);
                } // Birthdate is checked by calculateAge which modifies both birthdate & age error logic
            };
            el.addEventListener("input", check);
            el.addEventListener("blur", check);
        }
    });

    form.addEventListener("submit", (e) => {
        let isValid = true;

        binders.forEach(b => {
            const el = document.getElementById(b.id);
            if (el) {
                const val = el.value || "";
                const err = b.validator(val);
                if (b.id !== "birthdate") {
                    showError(b.id, err);
                    if (err) isValid = false;
                } else {
                    const bErr = validateBirthAndAge(val);
                    if (bErr) { isValid = false; showError('age', bErr); showError('birthdate', bErr); }
                }
            }
        });

        const q1 = document.getElementById("question1").value;
        const q2 = document.getElementById("question2").value;
        const q3 = document.getElementById("question3").value;

        if (!q1 || !q2 || !q3) {
            alert('Please select all security questions.');
            isValid = false;
        } else if (new Set([q1, q2, q3]).size !== 3) {
            alert('Please select different security questions.');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
