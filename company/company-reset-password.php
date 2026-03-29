<?php
session_start();
require_once __DIR__ . "/../db.php";

$msg = "";
$color = "red";

// Check if token exists in the URL
if(!isset($_GET['token'])){
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif; background:#0a0f1a; color:#f8fafc; height:100vh; padding-top:50px;'>
            <h2 style='color:#ef4444;'>⛔ Invalid Request</h2>
            <p style='color:#94a3b8;'>The reset token is missing. Please check the link sent to your email.</p>
            <a href='company-forgot-password.php' style='color:#38bdf8;'>Back to Forgot Password</a>
         </div>");
}

$token = $_GET['token'];

// 🔍 1. Verify Company Token and Expiry (Using token_expire from your DB)
$check_query = "SELECT id FROM companies WHERE reset_token = '$token' AND token_expire > NOW() LIMIT 1";
$check = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check) != 1){
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif; background:#0a0f1a; color:#f8fafc; height:100vh; padding-top:50px;'>
            <h2 style='color:#ef4444;'>⛔ Link Expired or Invalid</h2>
            <p style='color:#94a3b8;'>This password reset link is invalid or has expired (after 30 mins).</p>
            <a href='company-forgot-password.php' style='color:#38bdf8;'>Request a new link</a>
         </div>");
}

// 🔐 2. Update Password Logic
if(isset($_POST['reset_company_pass'])){
    $pass = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    if($pass !== $cpass){
        $msg = "<i class='fa fa-triangle-exclamation'></i> Passwords do not match!";
        $color = "red";
    } elseif(strlen($pass) < 6){
        $msg = "<i class='fa fa-triangle-exclamation'></i> Password must be at least 6 characters long.";
        $color = "red";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // Update password and clear token
        mysqli_query($conn, "UPDATE companies SET password='$hash', reset_token=NULL, token_expire=NULL WHERE reset_token='$token'");
        
        $msg = "<i class='fa fa-check-circle'></i> Password updated successfully! <br><br> <a href='../login-selection.php' style='color:#0a0f1a; background:#38bdf8; padding:8px 15px; border-radius:6px; text-decoration:none; display:inline-block; font-weight:bold;'>Go to Login</a>";
        $color = "green";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Password</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body { background: #0a0f1a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }

/* 🔵 NAVBAR CSS (ADJUSTED FOR 2.2x LOGO) */
.navbar { background: rgba(10, 15, 26, 0.85); backdrop-filter: blur(10px); padding: 15px 35px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 30px rgba(0,0,0,0.5);}
.nav-logo-container { display: flex; align-items: center; text-decoration: none; }
.nav-logo-img { height: 48px; width: auto; transform: scale(1.5); transform-origin: left center; transition: transform 0.3s ease; }
.nav-logo-container:hover .nav-logo-img { transform: scale(1.6); }

.nav-links { list-style: none; display: flex; gap: 20px; align-items: center; }
.nav-links li a { color: #cbd5e1; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.3s; padding: 8px 15px; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
.nav-links li a:hover { color: #0a0f1a; background: #38bdf8; border-color: #38bdf8; }

.page-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; background: radial-gradient(circle at center, #1e3a8a 0%, #0a0f1a 70%); }
.box { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 40px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 25px 50px rgba(0,0,0,0.5); width: 100%; max-width: 420px; text-align: center; }

h2 { margin-bottom: 10px; font-size: 24px; color: #f8fafc; font-weight: 800;}
p { color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }

label { display: block; text-align: left; font-weight: bold; font-size: 12px; color: #cbd5e1; text-transform: uppercase; margin-bottom: 8px; }
.input-group { position: relative; margin-bottom: 20px; }
input { width: 100%; padding: 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; transition: 0.3s; font-family: inherit; font-size: 14px;}
input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
.eye-icon { position: absolute; right: 15px; top: 16px; cursor: pointer; color: #64748b; font-size: 16px; transition: 0.3s;}
.eye-icon:hover { color: #cbd5e1; }

button { width: 100%; padding: 14px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px;}
button:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4); }

.msg { padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 14px; font-weight: bold; }
.msg-success { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.msg-error { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
</style>
</head>
<body>

<nav class="navbar">
  <a href="../index.php" class="nav-logo-container">
    <img src="../assets/logo.png" onerror="this.src='../assets/logo.jpg'" alt="Placement Portal Logo" class="nav-logo-img">
  </a>
  <ul class="nav-links">
    <li><a href="../login-selection.php"><i class="fa fa-arrow-left"></i> Back to Login</a></li>
  </ul>
</nav>

<div class="page-container">
    <div class="box">
        <h2><i class="fa fa-lock" style="color: #38bdf8;"></i> Create New Password</h2>
        <p>Set a strong password for your company account.</p>

        <form method="POST">
            <label>New Password</label>
            <div class="input-group">
                <input type="password" id="pass" name="password" required placeholder="Min 6 characters">
                <i class="fa fa-eye eye-icon" onclick="togglePass('pass', this)"></i>
            </div>

            <label>Confirm Password</label>
            <div class="input-group">
                <input type="password" id="cpass" name="confirm_password" required placeholder="Repeat password">
                <i class="fa fa-eye eye-icon" onclick="togglePass('cpass', this)"></i>
            </div>

            <button type="submit" name="reset_company_pass">Update Company Password</button>
        </form>

        <?php if($msg): ?>
            <div class="msg <?= ($color == 'green') ? 'msg-success' : 'msg-error' ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function togglePass(id, icon) {
        var input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
            icon.style.color = "#38bdf8";
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
            icon.style.color = "#64748b";
        }
    }
</script>
</body>
</html>
