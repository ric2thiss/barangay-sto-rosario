// Image preview functionality
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

// Auto-hide success messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s ease';
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 500);
        }, 5000);
    }
    
    // Auto-hide error messages after 8 seconds
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert) {
        setTimeout(() => {
            errorAlert.style.transition = 'opacity 0.5s ease';
            errorAlert.style.opacity = '0';
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 500);
        }, 8000);
    }
});

// Form validation
function validateEmailForm() {
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();
    
    if (!email) {
        showError('Email is required');
        return false;
    }
    
    if (!isValidEmail(email)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    return true;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.form-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-error form-error';
    errorDiv.textContent = message;
    
    // Insert after the content header
    const contentHeader = document.querySelector('.content-header');
    contentHeader.after(errorDiv);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.transition = 'opacity 0.5s ease';
        errorDiv.style.opacity = '0';
        setTimeout(() => {
            errorDiv.remove();
        }, 500);
    }, 5000);
}

// File validation
function validateFile(input) {
    const file = input.files[0];
    
    if (!file) {
        showError('Please select a file');
        return false;
    }
    
    // Check file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showError('Only JPG, PNG, and GIF images are allowed');
        return false;
    }
    
    // Check file size (5MB max)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showError('File size must be less than 5MB');
        return false;
    }
    
    return true;
}

// Add loading state to forms
document.addEventListener('DOMContentLoaded', function() {
    // Handle contact form submission
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            if (!validateEmailForm()) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Updating...';
            
            // Re-enable after 5 seconds as a fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 5000);
        });
    }
    
    // Handle upload form submission
    const uploadForm = document.querySelector('.upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('profile_picture');
            
            if (!validateFile(fileInput)) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Uploading...';
            
            // Re-enable after 10 seconds as a fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }, 10000);
        });
    }
    
    // Handle delete form submission
    const deleteForm = document.querySelector('.delete-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Removing...';
            
            // Re-enable after 5 seconds as a fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        });
    }
});

// Add spinner styles
const style = document.createElement('style');
style.textContent = `
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .form-error {
        margin-bottom: 20px;
    }
`;
document.head.appendChild(style);

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add hover effects to sections
document.querySelectorAll('.profile-picture-section, .personal-info-section, .contact-info-section, .address-info-section').forEach(section => {
    section.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    section.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Copy to clipboard functionality
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccess('Copied to clipboard!');
    }).catch(() => {
        showError('Failed to copy to clipboard');
    });
}

function showSuccess(message) {
    // Remove existing messages
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create success message
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success';
    successDiv.textContent = message;
    
    // Insert after the content header
    const contentHeader = document.querySelector('.content-header');
    contentHeader.after(successDiv);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        successDiv.style.transition = 'opacity 0.5s ease';
        successDiv.style.opacity = '0';
        setTimeout(() => {
            successDiv.remove();
        }, 500);
    }, 3000);
}

// Add copy buttons to info items
document.addEventListener('DOMContentLoaded', function() {
    const infoItems = document.querySelectorAll('.info-item span');
    infoItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.title = 'Click to copy';
        
        item.addEventListener('click', function() {
            copyToClipboard(this.textContent.trim());
        });
    });
});
