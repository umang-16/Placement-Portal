<?php
session_start();
require_once __DIR__ . "/../db.php"; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username == "" || $password == "") {
        $error = "⚠ Please enter username and password";
    } else {
        $res = mysqli_query($conn,
            "SELECT * FROM admins WHERE username='$username' LIMIT 1"
        );

        if (mysqli_num_rows($res) == 1) {
            $admin = mysqli_fetch_assoc($res);

            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = "❌ Invalid Admin Credentials";
            }
        } else {
            $error = "❌ Invalid Admin Credentials";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
* { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }
body { background: #0a0f1a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }

/* 🔵 NAVBAR CSS (ADJUSTED FOR 2.2x LOGO) */
.navbar { background: rgba(10, 15, 26, 0.85); backdrop-filter: blur(10px); padding: 15px 35px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 30px rgba(0,0,0,0.5);}
.nav-logo-container { display: flex; align-items: center; text-decoration: none; }
.nav-logo-img { height: 48px; width: auto; transform: scale(1.5); transform-origin: left center; transition: transform 0.3s ease; }
.nav-logo-container:hover .nav-logo-img { transform: scale(1.6); }

.nav-links { list-style: none; display: flex; gap: 20px; align-items: center; }
.nav-links li a { color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.3s; padding: 8px 15px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
.nav-links li a:hover { color: #0a0f1a; background: #38bdf8; border-color: #38bdf8; }

/* PAGE CONTAINER */
.page-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; background: radial-gradient(circle at center, #1e3a8a 0%, #0a0f1a 70%); }
.login-box { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 40px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 25px 50px rgba(0,0,0,0.5); width: 100%; max-width: 420px; text-align: center; border-top: 4px solid #38bdf8;}

.login-box h2 { font-size: 26px; color: #f8fafc; margin-bottom: 5px; font-weight: 800; }
.login-box p { color: #94a3b8; font-size: 14px; margin-bottom: 25px; }

.input-group { position: relative; margin-bottom: 20px; text-align: left; }
.input-group input { width: 100%; padding: 14px 45px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; transition: 0.3s; font-size: 15px; }
.input-group input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4);}
.left-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 16px; transition: 0.3s;}
.input-group input:focus + .left-icon { color: #38bdf8; }

.eye-btn { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px; transition: 0.3s;}
.eye-btn:hover { color: #cbd5e1; }

.submit-btn { width: 100%; padding: 14px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
.submit-btn:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4); }

.error-msg { background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 12px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 20px; font-size: 14px; font-weight: bold; }

.footer-link { margin-top: 25px; font-size: 12px; color: #64748b; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;}
</style>
</head>
<body>

<nav class="navbar">
  <a href="../index.php" class="nav-logo-container">
    <img src="../assets/logo.png" onerror="this.src='../assets/logo.jpg'" alt="Placement Portal Logo" class="nav-logo-img">
  </a>
  <ul class="nav-links">
    <li><a href="../index.php"><i class="fa fa-home"></i> Home</a></li>
  </ul>
</nav>

<div class="page-container">
    <div class="login-box">
        <h2><i class="fa fa-shield-halved" style="color:#38bdf8;"></i> Admin Central</h2>
        <p>Secure login for system administrators</p>

        <?php if($error != ""): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Admin Username" required autocomplete="off">
                <i class="fas fa-user-tie left-icon"></i>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Admin Password" required autocomplete="off">
                <i class="fas fa-key left-icon"></i>
                <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
            </div>
            
            <button type="submit" class="submit-btn"><i class="fa fa-sign-in-alt"></i> Secure Login</button>
        </form>

        <p class="footer-link"><i class="fa fa-lock"></i> Authorized Person Only</p>
    </div>
</div>

<script>
function togglePass(btn) {
    const input = btn.parentElement.querySelector('input');
    const icon = btn.querySelector('i');
    if(input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        icon.style.color = '#38bdf8';
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        icon.style.color = '#64748b';
    }
}
</script>
</body>
</html>