<?php
session_start();
require_once "db.php";

$msg = "";
$email = isset($_GET['email']) ? mysqli_real_escape_string($conn, $_GET['email']) : '';
$type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : 'student';

if(isset($_POST['verify_otp'])){
    $entered_otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $user_email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = mysqli_real_escape_string($conn, $_POST['type']);

    $table = ($user_type === 'company') ? 'companies' : 'students';

    // Check if OTP is correct
    $query = "SELECT id FROM $table WHERE email='$user_email' AND otp='$entered_otp' AND status='unverified'";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        // OTP સાચો છે -> સ્ટેટસ Pending કરો અને OTP કાઢી નાખો
        mysqli_query($conn, "UPDATE $table SET status='pending', otp=NULL WHERE email='$user_email'");
        
        $msg = "<div class='alert-success'><i class='fa fa-check-circle'></i> Email verified successfully! Your account is now pending admin approval. You can login once approved.</div>";
    } else {
        $msg = "<div class='alert-danger'><i class='fa fa-triangle-exclamation'></i> Invalid OTP. Please check your email and try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; overflow: hidden; }
    
    .verify-card { 
        background: rgba(255, 255, 255, 0.02); 
        backdrop-filter: blur(15px); 
        border: 1px solid rgba(255, 255, 255, 0.05); 
        padding: 40px; 
        border-radius: 16px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
        width: 100%; 
        max-width: 450px; 
        text-align: center; 
        animation: fadeIn 0.5s ease-out;
    }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

    .verify-card h2 { color: #f8fafc; margin-bottom: 10px; font-weight: 800; font-size: 26px; }
    .verify-card p { color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.6; }
    .highlight-email { color: #38bdf8; font-weight: bold; }

    .form-group { text-align: left; margin-bottom: 25px; }
    .form-group label { display: block; font-size: 12px; font-weight: 800; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
    
    .form-group input { 
        width: 100%; 
        padding: 15px; 
        border: 1px solid rgba(255,255,255,0.1); 
        background: rgba(0,0,0,0.2); 
        color: #fff; 
        border-radius: 8px; 
        font-size: 24px; 
        outline: none; 
        letter-spacing: 8px; 
        text-align: center; 
        transition: 0.3s;
    }
    .form-group input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4); }

    .btn-verify { 
        width: 100%; 
        background: #38bdf8; 
        color: #0a0f1a; 
        border: none; 
        padding: 15px; 
        border-radius: 8px; 
        font-weight: 800; 
        font-size: 16px; 
        cursor: pointer; 
        transition: 0.3s; 
    }
    .btn-verify:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }

    .alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 20px; font-size: 14px; font-weight: bold; }
    .alert-danger { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px; font-size: 14px; font-weight: bold; }

    .back-login { display: inline-block; margin-top: 25px; color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.3s; }
    .back-login:hover { color: #38bdf8; }
</style>
</head>
<body>

<div class="verify-card">
    <h2><i class="fa fa-envelope-open-text" style="color: #38bdf8;"></i> Verify OTP</h2>
    <p>We have sent a 6-digit security code to <br><span class="highlight-email"><?= htmlspecialchars($email) ?></span>. Please enter it below.</p>

    <?= $msg ?>

    <?php if(!strpos($msg, 'successfully')): ?>
    <form method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        
        <div class="form-group">
            <label>Enter 6-Digit OTP</label>
            <input type="text" name="otp" maxlength="6" required placeholder="••••••" autocomplete="off">
        </div>

        <button type="submit" name="verify_otp" class="btn-verify"><i class="fa fa-shield-check"></i> Verify Email Address</button>
    </form>
    <?php endif; ?>

    <a href="login-selection.php" class="back-login"><i class="fa fa-arrow-left"></i> Back to Login</a>
</div>

</body>
</html>