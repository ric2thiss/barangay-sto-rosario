/* global bootstrap */
(function () {
  const table = document.getElementById("minutesTable");
  if (!table) return;

  table.addEventListener("click", (e) => {
    const tr = e.target.closest("tbody tr[data-id]");
    if (!tr) return;

    const id = tr.getAttribute("data-id");
    if (!id) return;

    // If clicked the View button -> still call same function
    if (e.target.closest(".js-view")) {
      window.BPSS_Minutes?.loadDetail?.(id);
      return;
    }

    // If clicked other interactive elements, don't trigger row click
    if (e.target.closest("button, a, input, select, textarea, label")) return;

    // Click anywhere else on row -> same as View
    window.BPSS_Minutes?.loadDetail?.(id);
  });
})();