document.addEventListener("DOMContentLoaded", () => {
  const toggles = document.querySelectorAll(".password-toggle");

  toggles.forEach((button) => {
    button.addEventListener("click", () => {
      const field = button.closest(".password-field");
      if (!field) {
        return;
      }

      const input = field.querySelector("input");
      if (!input) {
        return;
      }

      const wasHidden = input.type === "password";
      input.type = wasHidden ? "text" : "password";

      const isVisible = input.type === "text";
      button.setAttribute(
        "aria-label",
        isVisible ? "Hide password" : "Show password",
      );
      button.setAttribute("aria-pressed", isVisible ? "true" : "false");

      const icon = button.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-eye", !isVisible);
        icon.classList.toggle("fa-eye-slash", isVisible);
      }
    });
  });
});
