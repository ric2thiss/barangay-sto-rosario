document.addEventListener("DOMContentLoaded", () => {
  const offcanvasEl = document.getElementById("mainMenu");
  const hamburgerBtn = document.querySelector(".bpss-hamburger");

  if (!offcanvasEl || !hamburgerBtn) return;

  offcanvasEl.addEventListener("show.bs.offcanvas", () => {
    hamburgerBtn.classList.add("is-open");
  });

  offcanvasEl.addEventListener("hidden.bs.offcanvas", () => {
    hamburgerBtn.classList.remove("is-open");
  });
});