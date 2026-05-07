
                        // DOM Elements
                        const sidebar = document.getElementById('sidebar');
                        const toggleBtn = document.getElementById('toggleBtn');
                        const mobileToggleBtn = document.getElementById('mobileToggleBtn');
                        const mainContent = document.getElementById('mainContent');
                        const menuLinks = document.querySelectorAll('.menu-link');
                        const currentDateElement = document.getElementById('currentDate');
                        const overlay = document.getElementById('overlay');

                        // Modal elements
                        const createUserModal = document.getElementById('createUserModal');
                        const editUserModal = document.getElementById('editUserModal');
                        const deleteUserModal = document.getElementById('deleteUserModal');
                        const openCreateUserModalBtn = document.getElementById('openCreateUserModal');
                        const closeCreateModalBtn = document.getElementById('closeCreateModalBtn');
                        const closeEditModalBtn = document.getElementById('closeEditModalBtn');
                        const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
                        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
                        const resetCreateFormBtn = document.getElementById('resetCreateFormBtn');
                        const resetEditFormBtn = document.getElementById('resetEditFormBtn');
                        const createUserForm = document.getElementById('createUserForm');
                        const editUserForm = document.getElementById('editUserForm');
                        const deleteUserForm = document.getElementById('deleteUserForm');
                        const purokTags = document.querySelectorAll('.purok-tag');
                        const searchInput = document.getElementById('search');
                        const editUserBadge = document.getElementById('editUserBadge');
                        const editUserType = document.getElementById('edit_user_type');
                        const changePasswordCheckbox = document.getElementById('change_password');
                        const passwordFields = document.getElementById('passwordFields');
                        const editUserButtons = document.querySelectorAll('.edit-user-btn');
                        const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
                        const deleteUserInfo = document.getElementById('deleteUserInfo');
                        const deleteUserIdInput = document.getElementById('delete_user_id');
                        // ========== LOGOUT MODAL FUNCTIONALITY ==========
                        // Add this at the end of existing JavaScript

                        // Get modal elements
                        const logoutModal = document.getElementById('logoutModal');
                        const logoutTrigger = document.getElementById('logoutTrigger');
                        const closeModal = document.getElementById('closeModal');
                        const cancelLogout = document.getElementById('cancelLogout');

                        // Function to open logout modal
                        function openLogoutModal() {
                            logoutModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }

                        // Function to close logout modal
                        function closeLogoutModal() {
                            logoutModal.classList.remove('active');
                            document.body.style.overflow = '';
                        }

                        // Event listeners
                        if (logoutTrigger) {
                            logoutTrigger.addEventListener('click', openLogoutModal);
                        }

                        if (closeModal) {
                            closeModal.addEventListener('click', closeLogoutModal);
                        }

                        if (cancelLogout) {
                            cancelLogout.addEventListener('click', closeLogoutModal);
                        }

                        // Close modal when clicking outside
                        if (logoutModal) {
                            logoutModal.addEventListener('click', function (e) {
                                if (e.target === logoutModal) {
                                    closeLogoutModal();
                                }
                            });
                        }

                        // Close modal with Escape key
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                                closeLogoutModal();
                            }
                        });

                        // Update setupTooltips to include logout button
                        const originalSetupTooltips = setupTooltips;
                        setupTooltips = function () {
                            if (originalSetupTooltips) originalSetupTooltips();

                            // Add tooltip for logout button
                            const logoutItem = document.getElementById('logoutTrigger');
                            if (logoutItem) {
                                const text = logoutItem.querySelector('.logout-text');
                                if (text) {
                                    logoutItem.setAttribute('data-tooltip', text.textContent);
                                }
                            }
                        };

                        // Call the updated function
                        setupTooltips();
                        // ========== END LOGOUT MODAL FUNCTIONALITY ==========


                        // Toggle Sidebar Function for desktop
                        function toggleSidebar() {
                            if (window.innerWidth > 768) {
                                sidebar.classList.toggle('closed');

                                // Update title based on state
                                if (sidebar.classList.contains('closed')) {
                                    toggleBtn.setAttribute('title', 'Expand Sidebar');
                                } else {
                                    toggleBtn.setAttribute('title', 'Collapse Sidebar');
                                }

                                // Save state to localStorage
                                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('closed'));
                            }
                        }

                        // Mobile sidebar functions
                        function openMobileSidebar() {
                            sidebar.classList.add('open');
                            overlay.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeMobileSidebar() {
                            sidebar.classList.remove('open');
                            overlay.classList.remove('active');
                            document.body.style.overflow = '';
                        }

                        // Modal functions
                        function openCreateUserModal() {
                            createUserModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeCreateUserModal() {
                            createUserModal.classList.remove('active');
                            document.body.style.overflow = '';
                        }

                        function openEditUserModal() {
                            editUserModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeEditUserModal() {
                            editUserModal.classList.remove('active');
                            document.body.style.overflow = '';
                        }

                        function openDeleteUserModal() {
                            deleteUserModal.classList.add('active');
                            document.body.style.overflow = 'hidden';
                        }

                        function closeDeleteUserModal() {
                            deleteUserModal.classList.remove('active');
                            document.body.style.overflow = '';
                        }

                        // Toggle password fields in edit modal
                        function togglePasswordFields() {
                            if (changePasswordCheckbox.checked) {
                                passwordFields.classList.add('show');
                            } else {
                                passwordFields.classList.remove('show');
                            }
                        }

                        // Load user data into edit modal
                        function loadUserDataToEditModal(userData) {
                            // Set form values
                            document.getElementById('edit_user_id').value = userData.userId;
                            document.getElementById('edit_firstname').value = userData.firstname;
                            document.getElementById('edit_lastname').value = userData.lastname;
                            document.getElementById('edit_purok').value = userData.purok;
                            document.getElementById('edit_username').value = userData.username;
                            document.getElementById('edit_email').value = userData.email;
                            document.getElementById('edit_user_type').value = userData.userType;

                            // Update badge
                            editUserBadge.textContent = userData.userType.toUpperCase();

                            // Clear password fields
                            document.getElementById('edit_password').value = '';
                            document.getElementById('edit_confirm_password').value = '';
                            changePasswordCheckbox.checked = false;
                            passwordFields.classList.remove('show');

                            // Open modal
                            openEditUserModal();
                        }

                        // Load user data into delete modal
                        function loadUserDataToDeleteModal(userData) {
                            // Set the user ID in the form
                            deleteUserIdInput.value = userData.userId;

                            // Format created date
                            const createdDate = new Date(userData.createdAt);
                            const formattedDate = createdDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });

                            // Create user info HTML
                            const userInfoHTML = `
                <h5>User Details:</h5>
                <div class="info-row">
                    <span class="info-label">ID:</span>
                    <span class="info-value">#${userData.userId}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">${userData.firstname} ${userData.lastname}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value">${userData.username}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">${userData.email || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Purok:</span>
                    <span class="info-value">${userData.purok || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User Type:</span>
                    <span class="info-value">${userData.userType === 'admin' ? 'Admin' : 'Regular User'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value">${formattedDate}</span>
                </div>
            `;

                            // Update the info box
                            deleteUserInfo.innerHTML = userInfoHTML;

                            // Open modal
                            openDeleteUserModal();
                        }

                        // Format and Display Current Date
                        function displayCurrentDate() {
                            const now = new Date();
                            const options = {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            };
                            currentDateElement.textContent = now.toLocaleDateString('en-US', options);
                        }

                        // Purok tag filter
                        purokTags.forEach(tag => {
                            tag.addEventListener('click', function () {
                                const purok = this.getAttribute('data-purok');
                                searchInput.value = purok;

                                // Remove active class from all tags
                                purokTags.forEach(t => t.classList.remove('active'));
                                // Add active class to clicked tag
                                this.classList.add('active');

                                // Submit the form
                                this.closest('form').submit();
                            });
                        });

                        // Event Listeners
                        toggleBtn.addEventListener('click', toggleSidebar);

                        mobileToggleBtn.addEventListener('click', openMobileSidebar);

                        overlay.addEventListener('click', closeMobileSidebar);

                        // Close sidebar when clicking on main content (mobile only)
                        mainContent.addEventListener('click', function (e) {
                            if (window.innerWidth <= 768 && sidebar.classList.contains('open') &&
                                !e.target.closest('.mobile-toggle')) {
                                closeMobileSidebar();
                            }
                        });

                        // Close sidebar when clicking on a menu link (mobile)
                        menuLinks.forEach(link => {
                            link.addEventListener('click', () => {
                                if (window.innerWidth <= 768) {
                                    closeMobileSidebar();
                                }
                            });
                        });

                        // Modal event listeners
                        // openCreateUserModalBtn.addEventListener('click', openCreateUserModal);
                        // closeCreateModalBtn.addEventListener('click', closeCreateUserModal);
                        // closeEditModalBtn.addEventListener('click', closeEditUserModal);
                        // closeDeleteModalBtn.addEventListener('click', closeDeleteUserModal);
                        // cancelDeleteBtn.addEventListener('click', closeDeleteUserModal);

                        // Close modal when clicking outside
                        /* createUserModal.addEventListener('click', function (e) {
                            if (e.target === createUserModal) {
                                closeCreateUserModal();
                            }
                        });

                        editUserModal.addEventListener('click', function (e) {
                            if (e.target === editUserModal) {
                                closeEditUserModal();
                            }
                        });

                        deleteUserModal.addEventListener('click', function (e) {
                            if (e.target === deleteUserModal) {
                                closeDeleteUserModal();
                            }
                        }); */

                        // Reset form buttons
                        resetCreateFormBtn.addEventListener('click', function () {
                            createUserForm.reset();
                        });

                        resetEditFormBtn.addEventListener('click', function () {
                            // Reload the original user data from the button
                            const editBtn = document.querySelector(`.edit-user-btn[data-user-id="${document.getElementById('edit_user_id').value}"]`);
                            if (editBtn) {
                                const userData = {
                                    userId: editBtn.getAttribute('data-user-id'),
                                    firstname: editBtn.getAttribute('data-firstname'),
                                    lastname: editBtn.getAttribute('data-lastname'),
                                    purok: editBtn.getAttribute('data-purok'),
                                    username: editBtn.getAttribute('data-username'),
                                    email: editBtn.getAttribute('data-email'),
                                    userType: editBtn.getAttribute('data-user-type')
                                };
                                loadUserDataToEditModal(userData);
                            }
                        });

                        // Edit user button listeners
                        editUserButtons.forEach(button => {
                            button.addEventListener('click', function () {
                                const userData = {
                                    userId: this.getAttribute('data-user-id'),
                                    firstname: this.getAttribute('data-firstname'),
                                    lastname: this.getAttribute('data-lastname'),
                                    purok: this.getAttribute('data-purok'),
                                    username: this.getAttribute('data-username'),
                                    email: this.getAttribute('data-email'),
                                    userType: this.getAttribute('data-user-type')
                                };
                                loadUserDataToEditModal(userData);
                            });
                        });

                        // Delete user button listeners
                        deleteUserButtons.forEach(button => {
                            button.addEventListener('click', function () {
                                const userData = {
                                    userId: this.getAttribute('data-user-id'),
                                    username: this.getAttribute('data-username'),
                                    firstname: this.getAttribute('data-firstname'),
                                    lastname: this.getAttribute('data-lastname'),
                                    email: this.getAttribute('data-email'),
                                    purok: this.getAttribute('data-purok'),
                                    userType: this.getAttribute('data-user-type'),
                                    createdAt: this.getAttribute('data-created-at')
                                };
                                loadUserDataToDeleteModal(userData);
                            });
                        });

                        // Change password checkbox listener
                        if (changePasswordCheckbox) {
                            changePasswordCheckbox.addEventListener('change', togglePasswordFields);
                        }

                        // Update user type badge when changed
                        if (editUserType) {
                            editUserType.addEventListener('change', function () {
                                editUserBadge.textContent = this.value.toUpperCase();
                            });
                        }

                        // Initialize
                        displayCurrentDate();

                        // Load saved sidebar state
                        function loadSidebarState() {
                            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                            if (window.innerWidth > 768) {
                                if (isCollapsed) {
                                    sidebar.classList.add('closed');
                                    toggleBtn.setAttribute('title', 'Expand Sidebar');
                                } else {
                                    sidebar.classList.remove('closed');
                                    toggleBtn.setAttribute('title', 'Collapse Sidebar');
                                }
                            }
                        }

                        // Handle window resize
                        function handleResize() {
                            if (window.innerWidth > 768) {
                                // Reset mobile states
                                sidebar.classList.remove('open');
                                overlay.classList.remove('active');
                                document.body.style.overflow = '';
                                mobileToggleBtn.style.display = 'none';

                                // Make sure toggle button is visible
                                toggleBtn.style.display = 'flex';

                                // Reset sidebar position for desktop
                                sidebar.style.left = '0';

                                // Load saved state
                                loadSidebarState();
                            } else {
                                // Mobile view
                                mobileToggleBtn.style.display = 'flex';
                                sidebar.classList.remove('closed');

                                // Make sure toggle button is visible in mobile sidebar
                                toggleBtn.style.display = 'flex';
                            }
                        }

                        // Initialize on load
                        loadSidebarState();
                        handleResize();
                        window.addEventListener('resize', handleResize);

                        // Handle active menu items - Force User Management to be active
                        document.addEventListener('DOMContentLoaded', function () {
                            const userManagementLink = document.querySelector('a[href="user_management.php"]');
                            if (userManagementLink) {
                                // Remove active class from all menu links
                                menuLinks.forEach(link => {
                                    link.classList.remove('active');
                                });
                                // Add active class to User Management
                                userManagementLink.classList.add('active');
                            }
                        });

                        // Add tooltips for collapsed sidebar items
                        function setupTooltips() {
                            const menuItems = sidebar.querySelectorAll('.menu-link');
                            const logoutItem = sidebar.querySelector('.logout-link');
                            const userInfo = sidebar.querySelector('.user-info');

                            menuItems.forEach(item => {
                                const text = item.querySelector('.menu-text');
                                if (text) {
                                    item.setAttribute('data-tooltip', text.textContent);
                                }
                            });

                            if (logoutItem) {
                                const text = logoutItem.querySelector('.logout-text');
                                if (text) {
                                    logoutItem.setAttribute('data-tooltip', text.textContent);
                                }
                            }

                            if (userInfo) {
                                const name = userInfo.querySelector('.user-name');
                                if (name) {
                                    userInfo.setAttribute('data-tooltip', name.textContent);
                                }
                            }
                        }

                        setupTooltips();

                        // Add animation to stat cards
                        document.addEventListener('DOMContentLoaded', function () {
                            const statCards = document.querySelectorAll('.stat-card');
                            statCards.forEach((card, index) => {
                                setTimeout(() => {
                                    card.style.transform = 'translateY(0)';
                                    card.style.opacity = '1';
                                }, index * 100);
                            });
                        });

                        // Auto-focus search input if search parameter exists
                        
                            document.addEventListener('DOMContentLoaded', function () {
                                if (searchInput) {
                                    searchInput.focus();
                                    searchInput.select();

                                    // Highlight active purok tag if search matches a purok
                                    purokTags.forEach(tag => {
                                        const purok = tag.getAttribute('data-purok');
                                        if (purok === '') {
                                            tag.classList.add('active');
                                        }
                                    });
                                }
                            });
                        

                        // ========== CLIENT-SIDE VALIDATION ========== 
                        // Logic adapted from user/login.php and user/profile.php

                        function setupNameValidation(inputId) {
                            const input = document.getElementById(inputId);
                            if (!input) return;

                            // Create error container
                            let errorDiv = input.parentNode.querySelector('.invalid-feedback-client');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback invalid-feedback-client';
                                errorDiv.style.display = 'none';
                                errorDiv.style.color = '#ef4444';
                                errorDiv.style.fontSize = '0.875rem';
                                errorDiv.style.marginTop = '0.25rem';
                                input.parentNode.appendChild(errorDiv);
                            }

                            input.addEventListener('input', function () {
                                let val = this.value;
                                const originalVal = val;

                                // Auto Capitalization
                                let words = val.split(' ');
                                for (let i = 0; i < words.length; i++) {
                                    if (words[i].length > 0) {
                                        words[i] = words[i][0].toUpperCase() + words[i].substring(1);
                                    }
                                }
                                const capitalized = words.join(' ');

                                // Only update value if capitalization changed it
                                if (val !== capitalized) {
                                    // Save cursor position
                                    const start = this.selectionStart;
                                    const end = this.selectionEnd;
                                    this.value = capitalized;
                                    this.setSelectionRange(start, end);
                                    val = capitalized;
                                }

                                // Validation: Only letters (a-z, ñ/Ñ)
                                // Regex: /[^a-zA-ZñÑ\s]/g
                                const invalidChars = /[^a-zA-ZñÑ\s]/g;
                                if (invalidChars.test(val)) {
                                    this.classList.add('is-invalid');
                                    errorDiv.textContent = 'Only letters (a-z, ñ/Ñ) are allowed.';
                                    errorDiv.style.display = 'block';
                                } else {
                                    this.classList.remove('is-invalid');
                                    errorDiv.style.display = 'none';
                                }
                            });
                        }

                        function setupNumericValidation(inputId) {
                            const input = document.getElementById(inputId);
                            if (!input) return;

                            let errorDiv = input.parentNode.querySelector('.invalid-feedback-client');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback invalid-feedback-client';
                                errorDiv.style.display = 'none';
                                errorDiv.style.color = '#ef4444';
                                errorDiv.style.fontSize = '0.875rem';
                                errorDiv.style.marginTop = '0.25rem';
                                input.parentNode.appendChild(errorDiv);
                            }

                            input.addEventListener('input', function () {
                                let val = this.value;
                                // Simply warn if non-numeric
                                const invalidChars = /[^0-9]/g;

                                if (invalidChars.test(val)) {
                                    // Optionally strip them? User requirement summary says "Allowed only numeric values... Disallowed letters..." 
                                    // and "Added numeric-only validation... removing...".
                                    // Login.php logic was typically strict replacement or warning. 
                                    // Let's do strict replacement for Purok as it's often an ID or simple number.
                                    this.value = val.replace(/[^0-9]/g, '');

                                    // Show temporal error saying only numbers allowed? 
                                    // Or just strict replacement.
                                    // The previous summary said "Error Message: Only numbers are allowed."
                                    // So I should show error if they try to type bad stuff.

                                    this.classList.add('is-invalid');
                                    errorDiv.textContent = 'Only numbers are allowed.';
                                    errorDiv.style.display = 'block';

                                    // Hide after 2 seconds
                                    setTimeout(() => {
                                        this.classList.remove('is-invalid');
                                        errorDiv.style.display = 'none';
                                    }, 2000);
                                } else {
                                    this.classList.remove('is-invalid');
                                    errorDiv.style.display = 'none';
                                }
                            });
                        }

                        function setupUsernameValidation(inputId) {
                            const input = document.getElementById(inputId);
                            if (!input) return;

                            let errorDiv = input.parentNode.querySelector('.invalid-feedback-client');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback invalid-feedback-client';
                                errorDiv.style.display = 'none';
                                errorDiv.style.color = '#ef4444';
                                errorDiv.style.fontSize = '0.875rem';
                                errorDiv.style.marginTop = '0.25rem';
                                input.parentNode.appendChild(errorDiv);
                            }

                            input.addEventListener('input', function () {
                                let val = this.value;
                                const originalVal = val;

                                // Remove spaces strictly
                                val = val.replace(/\s/g, '');
                                if (val !== originalVal) {
                                    this.value = val;
                                }

                                // Check for invalid chars (anything that isn't Letter, Number, Underscore, Dot)
                                // Allowed: [a-zA-Z0-9_.]
                                const invalidChars = /[^a-zA-Z0-9_.]/g;
                                if (invalidChars.test(val)) {
                                    this.classList.add('is-invalid');
                                    errorDiv.textContent = 'Only letters, numbers, underscore (_), and dot (.) are allowed. No spaces.';
                                    errorDiv.style.display = 'block';
                                } else {
                                    this.classList.remove('is-invalid');
                                    errorDiv.style.display = 'none';
                                }
                            });
                        }

                        function setupEmailValidation(inputId) {
                            const input = document.getElementById(inputId);
                            if (!input) return;

                            // Email usually relies on browser type="email" + strict cleaning
                            input.addEventListener('input', function () {
                                let val = this.value;
                                let originalVal = val;

                                // Strict cleaning
                                val = val.replace(/\s/g, ''); // No spaces
                                val = val.replace(/[^a-zA-Z0-9@._-]/g, ''); // Allowed chars only
                                val = val.replace(/\.\./g, '.'); // No double dots

                                if (val !== originalVal) {
                                    this.value = val;
                                }
                            });
                        }

                        function setupPasswordValidation(passwordId, confirmId) {
                            const passwordInput = document.getElementById(passwordId);
                            const confirmInput = document.getElementById(confirmId);

                            if (!passwordInput || !confirmInput) return;

                            // Create error container
                            const createErrorDiv = (input, className) => {
                                let div = input.parentNode.querySelector('.' + className);
                                if (!div) {
                                    div = document.createElement('div');
                                    div.className = 'invalid-feedback ' + className;
                                    div.style.display = 'none';
                                    div.style.color = '#ef4444';
                                    div.style.fontSize = '0.875rem';
                                    div.style.marginTop = '0.25rem';
                                    input.parentNode.appendChild(div);
                                }
                                return div;
                            };

                            const passError = createErrorDiv(passwordInput, 'invalid-feedback-client-password');
                            const confirmError = createErrorDiv(confirmInput, 'invalid-feedback-client-confirm');

                            function validatePassword() {
                                let val = passwordInput.value;
                                const originalVal = val;

                                // Remove spaces
                                val = val.replace(/\s/g, '');
                                if (val !== originalVal) {
                                    passwordInput.value = val;
                                }

                                // Check if empty
                                if (val.length === 0) {
                                    passwordInput.classList.remove('is-invalid');
                                    passError.style.display = 'none';

                                    // If password cleared, re-check confirm match (it might now fail or be cleared)
                                    if (confirmInput.value.length > 0) {
                                        validateMatch();
                                    } else {
                                        confirmInput.classList.remove('is-invalid');
                                        confirmError.style.display = 'none';
                                    }
                                    return;
                                }

                                let errors = [];
                                // Length 8-12
                                if (val.length < 8 || val.length > 12) errors.push("8-12 characters");
                                // Complexity
                                if (!/[A-Z]/.test(val)) errors.push("uppercase letter");
                                if (!/[a-z]/.test(val)) errors.push("lowercase letter");
                                if (!/[0-9]/.test(val)) errors.push("number");
                                if (!/[!@#$%^&*(),.?":{}|<>]/.test(val)) errors.push("special character");

                                if (errors.length > 0) {
                                    passwordInput.classList.add('is-invalid');
                                    passError.textContent = 'Must include: ' + errors.join(', ');
                                    passError.style.display = 'block';
                                } else {
                                    passwordInput.classList.remove('is-invalid');
                                    passError.style.display = 'none';
                                }

                                if (confirmInput.value.length > 0) validateMatch();
                            }

                            function validateMatch() {
                                let val = confirmInput.value;
                                const originalVal = val;

                                // Remove spaces
                                val = val.replace(/\s/g, '');
                                if (val !== originalVal) {
                                    confirmInput.value = val;
                                }

                                // If password is empty (e.g. not changing it in edit mode)
                                // then confirm should essentially be empty too, or we warn them "Password is empty".
                                if (passwordInput.value.length === 0) {
                                    if (val.length > 0) {
                                        confirmInput.classList.add('is-invalid');
                                        confirmError.textContent = 'Please enter a password first.';
                                        confirmError.style.display = 'block';
                                    } else {
                                        confirmInput.classList.remove('is-invalid');
                                        confirmError.style.display = 'none';
                                    }
                                    return;
                                }

                                if (val.length > 0 && val !== passwordInput.value) {
                                    confirmInput.classList.add('is-invalid');
                                    confirmError.textContent = 'Passwords do not match.';
                                    confirmError.style.display = 'block';
                                } else {
                                    confirmInput.classList.remove('is-invalid');
                                    confirmError.style.display = 'none';
                                }
                            }

                            passwordInput.addEventListener('input', validatePassword);
                            confirmInput.addEventListener('input', validateMatch);
                        }

                        // Initialize Validations
                        document.addEventListener('DOMContentLoaded', function () {
                            // --- Create User Form Validations ---
                            setupNameValidation('firstname');
                            setupNameValidation('lastname');
                            setupNumericValidation('purok');
                            setupUsernameValidation('username');
                            setupEmailValidation('email');
                            setupPasswordValidation('password', 'confirm_password');

                            // --- Edit User Form Validations ---
                            setupNameValidation('edit_firstname');
                            setupNameValidation('edit_lastname');
                            setupNumericValidation('edit_purok');
                            setupUsernameValidation('edit_username');
                            setupEmailValidation('edit_email');
                            setupPasswordValidation('edit_password', 'edit_confirm_password');
                        });

                        // Auto-open modal if there are errors
                        
                            document.addEventListener('DOMContentLoaded', function () {
                                openCreateUserModal();
                            });
                        

                        
                            document.addEventListener('DOMContentLoaded', function () {
                                openEditUserModal();
                            });
                        

                        // Auto-fill purok from tag click in create modal
                        document.querySelectorAll('.purok-tag').forEach(tag => {
                            tag.addEventListener('click', function () {
                                const purokInput = document.getElementById('purok');
                                if (purokInput && !purokInput.value) {
                                    purokInput.value = this.getAttribute('data-purok');
                                }
                            });
                        });

                        // Handle window resize for responsive user cards
                        function handleUserCardsResize() {
                            const mobileView = document.getElementById('mobileUserView');
                            const desktopView = document.getElementById('desktopUserView');

                            if (window.innerWidth <= 768) {
                                // Mobile view
                                if (mobileView) mobileView.style.display = 'block';
                                if (desktopView) desktopView.style.display = 'none';
                            } else {
                                // Desktop view
                                if (mobileView) mobileView.style.display = 'none';
                                if (desktopView) desktopView.style.display = 'block';
                            }
                        }

                        // Initialize on load and resize
                        handleUserCardsResize();
                        window.addEventListener('resize', handleUserCardsResize);

                        // Update existing edit and delete button event listeners to work with mobile cards
                        document.addEventListener('click', function (e) {
                            // Handle edit button clicks for mobile cards
                            if (e.target.closest('.edit-user-btn')) {
                                const button = e.target.closest('.edit-user-btn');
                                const userData = {
                                    userId: button.getAttribute('data-user-id'),
                                    firstname: button.getAttribute('data-firstname'),
                                    lastname: button.getAttribute('data-lastname'),
                                    purok: button.getAttribute('data-purok'),
                                    username: button.getAttribute('data-username'),
                                    email: button.getAttribute('data-email'),
                                    userType: button.getAttribute('data-user-type')
                                };
                                loadUserDataToEditModal(userData);
                            }

                            // Handle delete button clicks for mobile cards
                            if (e.target.closest('.delete-user-btn')) {
                                const button = e.target.closest('.delete-user-btn');
                                const userData = {
                                    userId: button.getAttribute('data-user-id'),
                                    username: button.getAttribute('data-username'),
                                    firstname: button.getAttribute('data-firstname'),
                                    lastname: button.getAttribute('data-lastname'),
                                    email: button.getAttribute('data-email'),
                                    purok: button.getAttribute('data-purok'),
                                    userType: button.getAttribute('data-user-type'),
                                    createdAt: button.getAttribute('data-created-at')
                                };
                                loadUserDataToDeleteModal(userData);
                            }
                        });
                    
