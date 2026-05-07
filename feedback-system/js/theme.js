class ThemeManager {
    constructor() {
        this.theme = this.getStoredTheme();
        this.init();
    }

    init() {
        this.applyTheme();
        this.setupThemeToggle();
        // Check if on settings page for these specific elements
        this.setupThemeSelector();
        this.setupThemePreviews();
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (this.theme === 'auto') {
                this.applyTheme();
            }
        });
    }

    getStoredTheme() {
        // Get theme from localStorage, default to 'auto'
        return localStorage.getItem('theme') || 'auto';
    }

    saveTheme(theme) {
        localStorage.setItem('theme', theme);
        this.theme = theme;
        this.applyTheme();
    }

    applyTheme() {
        let themeToApply = this.theme;
        
        if (themeToApply === 'auto') {
            // Check system preference
            themeToApply = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        
        if (themeToApply === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        // Update UI elements
        this.updateThemeToggle(themeToApply);
        this.updateThemeSelector();
        this.updateThemePreviews(themeToApply);
        
        // Dispatch event for other components
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: themeToApply }));
    }

    updateThemeToggle(currentTheme) {
        const toggleBtn = document.getElementById('themeToggleBtn');
        if (!toggleBtn) return;
        
        if (currentTheme === 'dark') {
            toggleBtn.innerHTML = '<i class="fas fa-sun"></i><span>Light Mode</span>';
            toggleBtn.setAttribute('title', 'Switch to Light Mode');
        } else {
            toggleBtn.innerHTML = '<i class="fas fa-moon"></i><span>Dark Mode</span>';
            toggleBtn.setAttribute('title', 'Switch to Dark Mode');
        }
    }

    updateThemeSelector() {
        const themeRadios = document.querySelectorAll('input[name="theme"]');
        if (themeRadios.length === 0) return;
        
        themeRadios.forEach(radio => {
            radio.checked = radio.value === this.theme;
        });
    }

    updateThemePreviews(currentTheme) {
        const previews = document.querySelectorAll('.theme-preview');
        if (previews.length === 0) return;
        
        previews.forEach(preview => {
            preview.classList.remove('active');
            if (preview.dataset.theme === currentTheme) {
                preview.classList.add('active');
            }
        });
    }

    setupThemeToggle() {
        const toggleBtn = document.getElementById('themeToggleBtn');
        if (toggleBtn) {
            // Remove existing event listeners to avoid duplicates if this runs multiple times
            // simpler to just assign onclick or ensure init runs once
            toggleBtn.onclick = () => {
                let newTheme;
                const currentAppliedTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
                
                if (this.theme === 'auto') {
                    // If currently auto, switch to opposite of current system/applied theme
                    newTheme = currentAppliedTheme === 'dark' ? 'light' : 'dark';
                } else {
                    // If specific theme is set, toggle between light/dark
                    newTheme = this.theme === 'dark' ? 'light' : 'dark';
                }
                
                this.saveTheme(newTheme);
                this.showNotification(`Switched to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`);
            };
        }
    }

    setupThemeSelector() {
        const themeSelector = document.getElementById('themeSelector');
        if (themeSelector) {
            themeSelector.addEventListener('change', (e) => {
                if (e.target.name === 'theme') {
                    this.saveTheme(e.target.value);
                    const themeNames = {
                        'light': 'Light Mode',
                        'dark': 'Dark Mode',
                        'auto': 'Auto (Follow System)'
                    };
                    this.showNotification(`Theme set to ${themeNames[e.target.value]}`);
                }
            });
        }
    }

    setupThemePreviews() {
        const previews = document.querySelectorAll('.theme-preview');
        previews.forEach(preview => {
            preview.addEventListener('click', () => {
                const theme = preview.dataset.theme;
                this.saveTheme(theme);
                this.showNotification(`Switched to ${theme === 'dark' ? 'Dark' : 'Light'} Mode`);
            });
        });
    }

    showNotification(message) {
        // Remove existing notification
        const existingAlert = document.querySelector('.theme-notification');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create new notification
        const alert = document.createElement('div');
        alert.className = 'alert theme-notification';
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '10000';
        alert.style.maxWidth = '300px';
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        
        // CSS for notification should also be in theme.css or inline here
        // Since we removed inline css from settings.php, make sure alert class is available
        // It should be available from global styles or theme.css
        
        document.body.appendChild(alert);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 3000);
    }
}

// Initialize Theme Manager on DOM Content Loaded to ensure elements exist
document.addEventListener('DOMContentLoaded', () => {
    // Only init if not already existing (global var check? mostly fine to just init)
    window.themeManager = new ThemeManager();
});
