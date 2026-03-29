<?php
session_start();
require_once __DIR__ . "/../db.php"; 

// Include PHPMailer files
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";
$color = "green";

if(isset($_POST['reset_request'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if the email exists in the database
    $check = mysqli_query($conn, "SELECT * FROM students WHERE email='$email' LIMIT 1");
    
    if(mysqli_num_rows($check) > 0){
        $token = bin2hex(random_bytes(16)); // Generate unique token
        $expire = date("Y-m-d H:i:s", strtotime("+30 minutes")); // 30 mins expiry
        
        // Update database with reset token and expiry
        mysqli_query($conn, "UPDATE students SET reset_token='$token', token_expire='$expire' WHERE email='$email'");

        // Email sending process (PHPMailer)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'workwithme2501@gmail.com'; 
            $mail->Password   = 'cdyh ixsm laoa qzhh'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('workwithme2501@gmail.com', 'Placement Portal');
            $mail->addAddress($email);

            $reset_link = "http://localhost/place_portal/student/reset-password.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = "Student Password Reset Request";
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; background:#f4f6fb; padding:20px;'>
                    <div style='background:#0f172a; padding:20px; border-radius:8px; border-top: 4px solid #38bdf8;'>
                        <h2 style='color:#f8fafc;'>Password Reset Request</h2>
                        <p style='color:#cbd5e1;'>We received a request to reset the password for your student account.</p>
                        <p style='color:#cbd5e1;'>Click the button below to reset it. This link is valid for <b>30 minutes</b>.</p>
                        <a href='$reset_link' style='display:inline-block; padding:12px 20px; background:#38bdf8; color:#0a0f1a; text-decoration:none; font-weight:bold; border-radius:6px; margin: 15px 0;'>Reset Password</a>
                        <p style='color:#94a3b8; font-size: 13px;'>If you didn't request this, please ignore this email.</p>
                    </div>
                </div>
            ";

            $mail->send();
            $msg = "<i class='fa fa-check-circle'></i> Reset link sent! Please check your email inbox.";
            $color = "green";
        } catch (Exception $e) {
            $msg = "<i class='fa fa-triangle-exclamation'></i> Mail Error: {$mail->ErrorInfo}";
            $color = "red";
        }
    } else {
        $msg = "<i class='fa fa-triangle-exclamation'></i> Error: This email is not registered with any student.";
        $color = "red";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Forgot Password</title>
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
input { width: 100%; padding: 14px; margin-bottom: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; transition: 0.3s; font-family: inherit; font-size: 14px;}
input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }

button { width: 100%; padding: 14px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; }
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
      <h2><i class="fa fa-user-lock" style="color: #38bdf8;"></i> Student Reset</h2>
      <p>Enter your email to receive a secure password reset link.</p>

      <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="student@example.com">
        <button type="submit" name="reset_request"><i class="fa fa-paper-plane"></i> Send Reset Link</button>
      </form>

      <?php if($msg): ?>
        <div class="msg <?= ($color == 'green') ? 'msg-success' : 'msg-error' ?>">
          <?= $msg ?>
        </div>
      <?php endif; ?>
    </div>
</div>

</body>
</html>