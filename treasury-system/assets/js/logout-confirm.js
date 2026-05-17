document.addEventListener("DOMContentLoaded", () => {
  const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
  if (logoutLinks.length === 0) {
    return;
  }

  logoutLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      const confirmed = window.confirm("Are you sure you want to log out?");
      if (!confirmed) {
        event.preventDefault();
      }
    });
  });
});
