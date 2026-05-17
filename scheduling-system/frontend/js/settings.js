document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('darkModeToggle');
  if (!toggle) return;

  // Sync toggle with current applied theme
  toggle.checked = document.documentElement.getAttribute('data-theme') === 'dark';

  toggle.addEventListener('change', () => {
    const isDark = toggle.checked;
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    localStorage.setItem('bpss-theme', isDark ? 'dark' : 'light');
  });
});
