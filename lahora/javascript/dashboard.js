// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// User action functions
function editUser(userId) {
    console.log('Editing user:', userId);

    // Fetch user data via AJAX
    fetch('user_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=edit&user_id=${encodeURIComponent(userId)}`
    })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Show edit modal with user data
                showEditUserModal(data.user);
            } else {
                showToast(data.message || 'Error fetching user data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error fetching user data', 'error');
        });
}

function showEditUserModal(user) {
    const cRole = typeof currentUserRole !== 'undefined' ? currentUserRole : '';
    let superAdminOption = '';
    if (cRole === 'super_admin' || user.role === 'super_admin') {
        superAdminOption = `<option value="super_admin" ${user.role === 'super_admin' ? 'selected' : ''}>Super Admin</option>`;
    }

    // Create modal HTML
    const modalHtml = `
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit User</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form id="editUserForm">
                    <input type="hidden" name="user_id" value="${user.idNo}">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="${user.username}" required>
                    </div>
                    <div class="form-group">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="firstName" value="${user.firstName}" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="lastName" value="${user.lastName}" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="${user.emailAddress}" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required>
                            <option value="customer" ${user.role === 'customer' ? 'selected' : ''}>Customer</option>
                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                            ${superAdminOption}
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Add form submit handler
    document.getElementById('editUserForm').addEventListener('submit', function (e) {
        e.preventDefault();
        updateUser();
    });
}

function updateUser() {
    console.log('Updating user...');
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    formData.append('action', 'update');

    fetch('user_actions.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                showToast(data.message || 'User updated successfully', 'success');
                closeModal();
                // Reload page to show updated data
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Error updating user', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error updating user', 'error');
        });
}

function closeModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) {
        modal.remove();
    }
    const viewModal = document.getElementById('viewUserModal');
    if (viewModal) {
        viewModal.remove();
    }
}

function viewUserDetails(userId) {
    console.log('Viewing user:', userId);

    // Fetch user data via AJAX
    fetch('user_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=edit&user_id=${encodeURIComponent(userId)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show view modal with user data
                showViewUserModal(data.user);
            } else {
                showToast(data.message || 'Error fetching user data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error fetching user data', 'error');
        });
}

function showViewUserModal(user) {
    let privilegesHTML = '';
    if (user.role === 'customer' || user.role === 'user') {
        privilegesHTML = `
            <ul style="margin: 5px 0 0 20px; padding: 0;">
                <li>Can View</li>
            </ul>
        `;
    } else if (user.role === 'admin') {
        privilegesHTML = `
            <ul style="margin: 5px 0 0 20px; padding: 0;">
                <li>Can Block <em>(User or Customer accounts only)</em></li>
                <li>Can Edit / Update <em>(User or Customer accounts only)</em></li>
                <li>Can Create <em>(User or Customer accounts only)</em></li>
            </ul>
        `;
    } else if (user.role === 'super_admin') {
        privilegesHTML = `
            <ul style="margin: 5px 0 0 20px; padding: 0;">
                <li>Can View</li>
                <li>Can Create</li>
                <li>Can Edit</li>
                <li>Can Delete</li>
                <li>Can Approve</li>
                <li>Can Block</li>
            </ul>
        `;
    }

    const statusColor = user.status === 'active' ? '#26de81' : (user.status === 'blocked' ? '#ff4757' : '#ffa502');
    const roleColor = user.role === 'super_admin' ? '#ff7200' : (user.role === 'admin' ? '#4a6a8c' : '#4c42d9');
    const badgeStyle = "padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; color: #fff; display: inline-block;";

    let editButtonHtml = '';
    // Safely check globals, if undefined fallback to empty to hide button just in case
    const cRole = typeof currentUserRole !== 'undefined' ? currentUserRole : '';
    const cUserId = typeof currentUserId !== 'undefined' ? currentUserId : '';

    if (cRole === 'super_admin') {
        editButtonHtml = `<button type="button" class="btn btn-primary" onclick="closeModal(); editUser('${user.idNo}')">Edit User</button>`;
    } else if (cRole === 'admin') {
        if (user.idNo === cUserId || user.role !== 'super_admin') {
            editButtonHtml = `<button type="button" class="btn btn-primary" onclick="closeModal(); editUser('${user.idNo}')">Edit User</button>`;
        }
    }

    const modalHtml = `
        <div id="viewUserModal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3><i>👤</i> User Overview</h3>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <div style="padding: 25px; color: #dcdcdc;">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(76, 66, 217, 0.2); border: 2px solid #4c42d9; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; font-weight: bold;">
                            ${user.firstName.charAt(0)}${user.lastName.charAt(0)}
                        </div>
                        <div>
                            <h2 style="margin: 0; color: #fff; font-size: 22px;">${user.firstName} ${user.lastName}</h2>
                            <p style="margin: 5px 0 10px 0; color: #a0a0a0; font-size: 14px;">@${user.username}</p>
                            <div style="display: flex; gap: 8px;">
                                <span style="background: ${roleColor}; ${badgeStyle}">${user.role.replace('_', ' ')}</span>
                                <span style="background: ${statusColor}; ${badgeStyle}">${user.status}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); border: 1px solid #4a6a8c; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tr>
                                <td style="padding: 8px 0; color: #a0a0a0; font-weight: bold; width: 40%;">ID Number</td>
                                <td style="padding: 8px 0; color: #fff;">${user.idNo}</td>
                            </tr>
                            <tr style="border-top: 1px solid rgba(74, 106, 140, 0.3);">
                                <td style="padding: 8px 0; color: #a0a0a0; font-weight: bold;">Email Address</td>
                                <td style="padding: 8px 0; color: #fff;">${user.emailAddress}</td>
                            </tr>
                        </table>
                    </div>

                    <div style="padding: 15px; background: rgba(76, 66, 217, 0.1); border-left: 4px solid #4c42d9; border-radius: 4px; color: #dcdcdc; font-size: 14px;">
                        <strong style="color: #fff; display: block; margin-bottom: 8px;">Privileges</strong>
                        ${privilegesHTML}
                    </div>
                </div>
                <div class="form-actions" style="padding: 0 25px 25px 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                    ${editButtonHtml}
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function blockUser(userId) {
    console.log('Blocking user:', userId);
    if (confirm('Are you sure you want to block this user?')) {
        fetch('user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=block&user_id=${encodeURIComponent(userId)}`
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message || 'User blocked successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error blocking user', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error blocking user', 'error');
            });
    }
}

function unblockUser(userId) {
    console.log('Unblocking user:', userId);
    if (confirm('Are you sure you want to unblock this user?')) {
        fetch('user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=unblock&user_id=${encodeURIComponent(userId)}`
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message || 'User unblocked successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error unblocking user', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error unblocking user', 'error');
            });
    }
}

function deleteUser(userId) {
    console.log('Deleting user:', userId);
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch('user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&user_id=${encodeURIComponent(userId)}`
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message || 'User deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error deleting user', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error deleting user', 'error');
            });
    }
}

function approveUser(userId) {
    console.log('Approving user:', userId);
    if (confirm('Are you sure you want to approve this user?')) {
        fetch('user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&user_id=${encodeURIComponent(userId)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'User approved successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error approving user', 'error');
                }
            })
            .catch(error => showToast('Error approving user', 'error'));
    }
}

function rejectUser(userId) {
    console.log('Rejecting user:', userId);
    if (confirm('Are you sure you want to reject this user?')) {
        fetch('user_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&user_id=${encodeURIComponent(userId)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'User rejected successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error rejecting user', 'error');
                }
            })
            .catch(error => showToast('Error rejecting user', 'error'));
    }
}

// Booking action functions
function confirmBooking(bookingId) {
    console.log('Confirming booking:', bookingId);
    if (confirm('Are you sure you want to confirm this booking?')) {
        fetch('booking_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=confirm&booking_id=${encodeURIComponent(bookingId)}`
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message || 'Booking confirmed successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error confirming booking', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error confirming booking', 'error');
            });
    }
}

function rejectBooking(bookingId) {
    console.log('Rejecting booking:', bookingId);
    if (confirm('Are you sure you want to reject this booking?')) {
        fetch('booking_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&booking_id=${encodeURIComponent(bookingId)}`
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showToast(data.message || 'Booking rejected successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error rejecting booking', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error rejecting booking', 'error');
            });
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function (event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');

    if (window.innerWidth <= 640 &&
        !sidebar.contains(event.target) &&
        !toggle.contains(event.target) &&
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
});
