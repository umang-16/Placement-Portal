function showRegister() {
  document.getElementById("loginForm").classList.remove("active");
  document.getElementById("registerForm").classList.add("active");
}

function showLogin() {
  document.getElementById("registerForm").classList.remove("active");
  document.getElementById("loginForm").classList.add("active");
}

function togglePass(id, icon) {
  const input = document.getElementById(id);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}

/* Theme Toggle */
const themeIcon = document.getElementById("themeIcon");
themeIcon.addEventListener("click", () => {
  document.body.classList.toggle("dark");
  themeIcon.classList.toggle("fa-sun");
  themeIcon.classList.toggle("fa-moon");
});
