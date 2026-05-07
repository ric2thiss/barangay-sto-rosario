document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const msg  = document.getElementById("loginMsg");
  const btn  = document.getElementById("btnLogin");

  function showMsg(type, text) {
    msg.innerHTML = `<div class="alert alert-${type} py-2 mb-0">${text}</div>`;
  }

  form?.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Manual validation with UI feedback
    const un    = document.getElementById("username");
    const unErr = document.getElementById("usernameError");
    const pw    = document.getElementById("password");
    const pwErr = document.getElementById("passwordError");
    let valid = true;

    if (!un.value.trim()) {
      un.classList.add("is-invalid");
      if (unErr) unErr.style.display = "block";
      valid = false;
    } else {
      un.classList.remove("is-invalid");
      if (unErr) unErr.style.display = "none";
    }

    if (!pw.value.trim()) {
      pw.classList.add("is-invalid");
      if (pwErr) pwErr.style.display = "block";
      valid = false;
    } else {
      pw.classList.remove("is-invalid");
      if (pwErr) pwErr.style.display = "none";
    }

    if (!valid) return;

    msg.innerHTML = "";

    // loading state
    const resetBtn = () => {
      btn.disabled = false;
      btn.innerHTML = `<span id="btnLoginLabel"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</span>`;
    };

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...`;

    try {
      const res = await fetch("backend/auth/login_api.php", {
        method: "POST",
        body: new FormData(form)
      });

      const data = await res.json().catch(() => null);

      if (!data) {
        showMsg("danger", "Login failed. Invalid server response.");
        resetBtn(); return;
      }

      if (data.success) {
        showMsg("success", "Login successful. Redirecting...");
        sessionStorage.setItem("bpss_flash", JSON.stringify({ msg: "Welcome back! You are now logged in.", type: "success" }));
        window.location.href = "index.php?page=dashboard";
      } else {
        showMsg("danger", data.message || "Invalid username or password.");
        resetBtn();
      }
    } catch (err) {
      showMsg("danger", "Network error. Please try again.");
      resetBtn();
    }
  });
});