const API = "api";

document.getElementById("login-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const status = document.getElementById("login-status");
  const btn = document.getElementById("login-btn");
  const username = document.getElementById("l-username").value;
  const password = document.getElementById("l-password").value;

  status.textContent = "Logger ind…";
  status.className = "upload-status";
  btn.disabled = true;

  try {
    const res = await fetch(`${API}/login.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password }),
    });
    const data = await res.json();

    if (!res.ok) {
      status.textContent = data.error || "Login mislykkedes";
      status.className = "upload-status error";
      return;
    }

    window.location.href = "admin.php";
  } catch (err) {
    status.textContent = "Network error: " + err.message;
    status.className = "upload-status error";
  } finally {
    btn.disabled = false;
  }
});
