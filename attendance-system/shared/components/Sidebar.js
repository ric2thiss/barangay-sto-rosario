// --- Sidebar Toggle Logic (All Screen Sizes) ---
const sidebar = document.getElementById('sidebar');
const toggleButton = document.getElementById('sidebar-toggle');
const mainContent = document.querySelector('main');

if (sidebar && toggleButton && mainContent) {
    // Function to toggle sidebar
    function toggleSidebar() {
        // Check if sidebar is visible by checking its position
        const rect = sidebar.getBoundingClientRect();
        const isVisible = rect.left >= 0;
        
        if (!isVisible) {
            // Show sidebar
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            sidebar.classList.add('md:translate-x-0');
            mainContent.classList.add('md:ml-64');
            // On mobile, dim the background when sidebar is open
            if (window.innerWidth < 768) {
                mainContent.classList.add('opacity-50', 'pointer-events-none');
            }
        } else {
            // Hide sidebar
            sidebar.classList.remove('translate-x-0', 'md:translate-x-0');
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('md:ml-64');
            mainContent.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    toggleButton.addEventListener('click', toggleSidebar);

    // Close sidebar if main content is clicked on mobile
    mainContent.addEventListener('click', () => {
        if (window.innerWidth < 768 && sidebar.classList.contains('translate-x-0')) {
            sidebar.classList.remove('translate-x-0', 'md:translate-x-0');
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('opacity-50', 'pointer-events-none');
        }
    });
}