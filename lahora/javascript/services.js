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

// Edit service function
function editService(serviceId) {
    fetch('service_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_service&service_id=${serviceId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate edit form
            document.getElementById('edit_service_id').value = data.service.id;
            document.getElementById('edit_title').value = data.service.title;
            document.getElementById('edit_price').value = data.service.price;
            document.getElementById('edit_description').value = data.service.description;
            document.getElementById('edit_status').value = data.service.status;
            
            // Show modal
            document.getElementById('editServiceModal').style.display = 'block';
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error fetching service data', 'error');
        console.error('Error:', error);
    });
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editServiceModal').style.display = 'none';
}

// Toggle service status
function toggleServiceStatus(serviceId, currentStatus) {
    const newStatus = currentStatus === 'available' ? 'not_available' : 'available';
    const actionText = newStatus === 'available' ? 'enable' : 'disable';
    
    if (confirm(`Are you sure you want to ${actionText} this service?`)) {
        fetch('service_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_status&service_id=${serviceId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error updating service status', 'error');
            console.error('Error:', error);
        });
    }
}

// Delete service
function deleteService(serviceId) {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
        fetch('service_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_service&service_id=${serviceId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Error deleting service', 'error');
            console.error('Error:', error);
        });
    }
}

// Handle edit service form submission
document.getElementById('editServiceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('services.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON or HTML redirect
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If it's a redirect, reload the page
            if (response.redirected || response.url.includes('success=')) {
                showToast('Service updated successfully!', 'success');
                closeEditModal();
                setTimeout(() => location.reload(), 1000);
            }
            return { success: false };
        }
    })
    .then(data => {
        if (data && data.success) {
            showToast(data.message || 'Service updated successfully!', 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000);
        } else if (data && data.message) {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error updating service', 'error');
        console.error('Error:', error);
    });
});

// Prevent double form submission
document.addEventListener('DOMContentLoaded', function() {
    const addServiceForm = document.querySelector('.add-service-form');
    if (addServiceForm) {
        addServiceForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            
            // Re-enable after 5 seconds in case of error
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Service';
            }, 5000);
        });
    }
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (window.innerWidth <= 640 && 
        !sidebar.contains(event.target) && 
        !toggle.contains(event.target) && 
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editServiceModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
