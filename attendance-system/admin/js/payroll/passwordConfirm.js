/**
 * Password Confirmation Modal
 * 
 * Blocks access to Payroll Management until admin re-enters password.
 * Handles password verification via API and manages session confirmation.
 */

class PasswordConfirmModal {
    constructor() {
        this.modal = null;
        this.passwordInput = null;
        this.submitButton = null;
        this.errorMessage = null;
        this.isVerified = false;
        this.idleTimeout = 4 * 60 * 1000; // 4 minutes in milliseconds (middle of 3-5 range)
        this.lastActivity = Date.now();
        this.activityCheckInterval = null;
    }

    /**
     * Initialize the modal
     */
    init() {
        this.createModal();
        this.attachEventListeners();
        this.startIdleTracking();
        this.checkPasswordConfirmation();
    }

    /**
     * Check if password is already confirmed in session
     */
    async checkPasswordConfirmation() {
        // Check server-side confirmation status first
        const serverConfirmed = window.payrollPasswordConfirmed === true;
        
        if (serverConfirmed) {
            // Server says password is confirmed, verify with API
            const { getBaseUrl } = await import('../shared/baseUrl.js');
            const baseUrl = getBaseUrl();
            
            try {
                const response = await fetch(`${baseUrl}/api/auth/check-password-confirmation.php`);
                const data = await response.json();
                
                if (data.success && data.confirmed) {
                    this.isVerified = true;
                    this.hideModal();
                    this.updateLastActivity();
                    // Dispatch event to notify main.js that password is confirmed
                    window.dispatchEvent(new CustomEvent('payrollPasswordConfirmed'));
                    return;
                }
            } catch (error) {
                console.error('Error checking password confirmation:', error);
            }
        }
        
        // Password not confirmed - show modal
        this.showModal();
    }

    /**
     * Create the modal HTML structure
     */
    createModal() {
        const modalHTML = `
            <div id="passwordConfirmModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full mx-4 transform scale-100 transition-transform duration-300">
                    <div class="text-center mb-6">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-semibold text-gray-900 mb-2">Password Confirmation Required</h3>
                        <p class="text-sm text-gray-500">Please re-enter your password to access Payroll Management</p>
                    </div>

                    <form id="passwordConfirmForm" class="space-y-4">
                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                id="confirmPassword" 
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                placeholder="Enter your password"
                            />
                        </div>

                        <div id="passwordError" class="hidden text-sm text-red-600 bg-red-50 p-3 rounded-lg">
                            <span id="errorText"></span>
                        </div>

                        <div class="flex space-x-3">
                            <button 
                                type="submit" 
                                id="confirmPasswordButton"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span id="buttonText">Confirm</span>
                                <span id="buttonSpinner" class="hidden inline-block ml-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        // Insert modal at the beginning of body
        document.body.insertAdjacentHTML('afterbegin', modalHTML);
        
        // Get references
        this.modal = document.getElementById('passwordConfirmModal');
        this.passwordInput = document.getElementById('confirmPassword');
        this.submitButton = document.getElementById('confirmPasswordButton');
        this.errorMessage = document.getElementById('passwordError');
        this.errorText = document.getElementById('errorText');
        this.buttonText = document.getElementById('buttonText');
        this.buttonSpinner = document.getElementById('buttonSpinner');
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const form = document.getElementById('passwordConfirmForm');
        
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.verifyPassword();
        });

        // Track user activity
        document.addEventListener('mousedown', () => this.updateLastActivity());
        document.addEventListener('keydown', () => this.updateLastActivity());
        document.addEventListener('scroll', () => this.updateLastActivity());
        document.addEventListener('touchstart', () => this.updateLastActivity());
    }

    /**
     * Start idle time tracking
     */
    startIdleTracking() {
        // Check for idle timeout every 30 seconds
        this.activityCheckInterval = setInterval(() => {
            const timeSinceLastActivity = Date.now() - this.lastActivity;
            
            if (timeSinceLastActivity >= this.idleTimeout && this.isVerified) {
                // User has been idle for too long, require password again
                this.isVerified = false;
                this.showModal();
                this.showError('Session expired due to inactivity. Please confirm your password again.');
            }
        }, 30000); // Check every 30 seconds
    }

    /**
     * Update last activity timestamp
     */
    updateLastActivity() {
        this.lastActivity = Date.now();
        
        // Also update server-side activity timestamp
        if (this.isVerified) {
            // Import base URL utility
            import('../shared/baseUrl.js').then(({ getBaseUrl }) => {
                const baseUrl = getBaseUrl();
                fetch(`${baseUrl}/api/auth/update-activity.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            }).catch(err => console.error('Error updating activity:', err));
            });
        }
    }

    /**
     * Verify password via API
     */
    async verifyPassword() {
        const password = this.passwordInput.value.trim();

        if (!password) {
            this.showError('Please enter your password');
            return;
        }

        // Disable form during verification
        this.setLoading(true);
        this.hideError();

        // Import base URL utility
        const { getBaseUrl } = await import('../shared/baseUrl.js');
        const baseUrl = getBaseUrl();
        
        try {
            const response = await fetch(`${baseUrl}/api/auth/verify-password.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ password: password })
            });

            const data = await response.json();

            if (data.success) {
                // Password verified successfully
                this.isVerified = true;
                this.passwordInput.value = '';
                this.hideModal();
                this.updateLastActivity();
                // Dispatch event to notify main.js that password is confirmed
                window.dispatchEvent(new CustomEvent('payrollPasswordConfirmed'));
            } else {
                // Password verification failed
                this.showError(data.message || 'Invalid password. Please try again.');
                this.passwordInput.focus();
                this.passwordInput.select();
            }
        } catch (error) {
            console.error('Password verification error:', error);
            this.showError('An error occurred. Please try again.');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Show the modal
     */
    showModal() {
        if (this.modal) {
            this.modal.classList.remove('hidden');
            this.passwordInput.focus();
        }
    }

    /**
     * Hide the modal
     */
    hideModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (this.errorMessage && this.errorText) {
            this.errorText.textContent = message;
            this.errorMessage.classList.remove('hidden');
        }
    }

    /**
     * Hide error message
     */
    hideError() {
        if (this.errorMessage) {
            this.errorMessage.classList.add('hidden');
        }
    }

    /**
     * Set loading state
     */
    setLoading(loading) {
        if (this.submitButton) {
            this.submitButton.disabled = loading;
            if (loading) {
                this.buttonText.classList.add('hidden');
                this.buttonSpinner.classList.remove('hidden');
            } else {
                this.buttonText.classList.remove('hidden');
                this.buttonSpinner.classList.add('hidden');
            }
        }
    }

    /**
     * Cleanup
     */
    destroy() {
        if (this.activityCheckInterval) {
            clearInterval(this.activityCheckInterval);
        }
    }
}

// Export for use in main.js
export { PasswordConfirmModal };
