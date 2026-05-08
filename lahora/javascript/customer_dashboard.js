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

// Add loading state to buttons
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function() {
        if (this.type === 'submit') {
            const originalText = this.textContent;
            this.disabled = true;
            this.innerHTML = '<span class="spinner"></span> Processing...';
            
            // Re-enable after 3 seconds as a fallback
            setTimeout(() => {
                this.disabled = false;
                this.textContent = originalText;
            }, 3000);
        }
    });
});

// Add hover effects to cards
document.querySelectorAll('.stat-card, .profile-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Table row hover effects
document.querySelectorAll('.bookings-table tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#f8f9fa';
        this.style.transition = 'background-color 0.2s ease';
    });
    
    row.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
    });
});

// Print functionality
function printBookings() {
    window.print();
}

// Export functionality (placeholder)
function exportBookings() {
    // This would typically generate a CSV or PDF
    alert('Export functionality would be implemented here');
}

// Combined search and filter functionality
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.bookings-table tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        // Apply status filter
        if (statusFilter !== 'all') {
            const statusElement = row.querySelector('.booking-status');
            if (statusElement) {
                const currentStatus = statusElement.textContent.toLowerCase().trim();
                if (currentStatus !== statusFilter.toLowerCase().trim()) {
                    showRow = false;
                }
            } else {
                showRow = false;
            }
        }
        
        // Apply search filter
        if (showRow && searchTerm !== '') {
            const serviceName = row.querySelector('.service-info h4')?.textContent.toLowerCase() || '';
            const serviceDescription = row.querySelector('.service-info small')?.textContent.toLowerCase() || '';
            const bookingDate = row.cells[1]?.textContent.toLowerCase() || '';
            const price = row.cells[2]?.textContent.toLowerCase() || '';
            const status = row.querySelector('.booking-status')?.textContent.toLowerCase() || '';
            const notes = row.cells[4]?.textContent.toLowerCase() || '';
            
            const searchableText = `${serviceName} ${serviceDescription} ${bookingDate} ${price} ${status} ${notes}`;
            
            if (!searchableText.includes(searchTerm)) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Filter bookings by status
function filterBookings(status) {
    applyFilters();
}

// Search functionality
function searchBookings(searchTerm) {
    applyFilters();
}

// Initialize search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional initialization if needed
    console.log('Search and filter functionality initialized');
});
