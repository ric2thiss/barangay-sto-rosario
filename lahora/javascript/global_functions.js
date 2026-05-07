// Make validation functions globally accessible for testing
if (typeof validateStep1 !== 'undefined') {
    window.validateStep1 = validateStep1;
}
if (typeof validateSecurityQuestions !== 'undefined') {
    window.validateSecurityQuestions = validateSecurityQuestions;
}
if (typeof checkExists !== 'undefined') {
    window.checkExists = checkExists;
}

// Also make other utility functions global if they exist
if (typeof toggleSecurityAnswer !== 'undefined') {
    window.toggleSecurityAnswer = toggleSecurityAnswer;
}
if (typeof showStep !== 'undefined') {
    window.showStep = showStep;
}
if (typeof validateAllFormInput !== 'undefined') {
    window.validateAllFormInput = validateAllFormInput;
}
