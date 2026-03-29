<?php
session_start();
require_once __DIR__ . "/db.php";

$error = "";

if(isset($_POST['login_submit'])){
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $role  = $_POST['role']; 

    if($email=="" || $pass==""){
        $error = "All fields are required";
    } else {
        if($role == 'student'){
            $email_esc = mysqli_real_escape_string($conn, $email);
            $query = mysqli_query($conn, "SELECT * FROM students WHERE email='$email_esc' LIMIT 1");
            
            if($query && mysqli_num_rows($query) == 1){
                $row = mysqli_fetch_assoc($query);
                
                if(password_verify($pass, $row['password'])){
                    $status = strtolower(trim($row['status']));
                    
                    if($status === 'unverified'){
                        $error = "❌ Please verify your email first. <br><a href='verify-otp.php?email=$email&type=student' style='color:#38bdf8; text-decoration:underline; font-weight:bold;'>Click here to Verify</a>";
                    } elseif($status === 'rejected'){
                        $error = "🚫 Your account has been rejected by Admin.";
                    } elseif($status === 'pending'){
                        $error = "⏳ Your account is currently pending admin approval.";
                    } else {
                        // Let them login
                        $_SESSION['student_id'] = $row['id'];
                        $_SESSION['student_name'] = $row['name'];
                        
                        // 🔔 ACTIVITY TRACKING
                        $s_name = mysqli_real_escape_string($conn, $row['name']);
                        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Student $s_name logged in.')");
                        
                        header("Location: student/dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid Password!";
                }
            } else {
                $error = "Student Email not found!";
            }
        } 
        elseif($role == 'company'){
            $email_esc = mysqli_real_escape_string($conn, $email);
            $query = mysqli_query($conn, "SELECT * FROM companies WHERE email='$email_esc' LIMIT 1");
            
            if($query && mysqli_num_rows($query) == 1){
                $row = mysqli_fetch_assoc($query);
                
                if(password_verify($pass, $row['password'])){
                    $status = strtolower(trim($row['status']));
                    
                    if($status === 'unverified'){
                        $error = "❌ Please verify your email first. <br><a href='verify-otp.php?email=$email&type=company' style='color:#38bdf8; text-decoration:underline; font-weight:bold;'>Click here to Verify</a>";
                    } elseif($status === 'rejected'){
                        $error = "🚫 Your company account has been rejected by Admin.";
                    } elseif($status === 'pending'){
                        $error = "⏳ Your company account is currently pending admin approval.";
                    } else {
                        // Let them login
                        $_SESSION['company_id'] = $row['id'];
                        $_SESSION['company_name'] = $row['company_name'];
                        
                        // 🔔 ACTIVITY TRACKING
                        $c_name = mysqli_real_escape_string($conn, $row['company_name']);
                        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Company $c_name logged in.')");
                        
                        header("Location: company/company-dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid Password!";
                }
            } else {
                $error = "Company Email not found!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }

    /* BACKGROUND GRADIENT FOR MAIN AREA */
    .main-wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 50px 20px; background: radial-gradient(circle at center, #1e3a8a 0%, #0a0f1a 70%); }

    /* GLASS CONTAINER */
    .container { width: 100%; max-width: 420px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 25px 50px rgba(0,0,0,0.5); border-radius: 20px; padding: 40px 30px; text-align: center; }
    
    h2 { color: #f8fafc; margin-bottom: 25px; font-size: 26px; font-weight: 800; letter-spacing: 0.5px; }

    /* TOGGLE SWITCH */
    .toggle-box { display: flex; position: relative; background: rgba(0, 0, 0, 0.3); border-radius: 30px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden; }
    .toggle-btn { flex: 1; padding: 12px 0; border: none; background: transparent; color: #94a3b8; font-weight: bold; cursor: pointer; z-index: 1; font-size: 15px; transition: 0.3s; }
    .toggle-btn.active { color: #0a0f1a; }
    .indicator { position: absolute; top: 0; left: 0; width: 50%; height: 100%; background: #38bdf8; border-radius: 30px; transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); }

    /* FORMS WRAPPER */
    .forms-container { overflow: hidden; position: relative; }
    .forms-wrapper { display: flex; width: 200%; transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); }
    .form-box { width: 50%; padding: 0 5px; }

    /* 🛠️ FIXED INPUTS & EYE ICON */
    .input-group { position: relative; margin-bottom: 20px; text-align: left; }
    
    .input-group > i { position: absolute; top: 50%; left: 15px; transform: translateY(-50%); color: #64748b; font-size: 16px; transition: 0.3s; pointer-events: none; }
    
    .input-group input { width: 100%; padding: 14px 45px 14px 45px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #f8fafc; font-size: 15px; outline: none; transition: 0.3s; }
    .input-group input::placeholder { color: #64748b; }
    .input-group input:focus { border-color: #38bdf8; background: rgba(0,0,0,0.4); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
    .input-group input:focus + i { color: #38bdf8; }

    .eye-btn { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px; padding: 5px; transition: 0.3s; }
    .eye-btn:hover { color: #cbd5e1; }

    /* BUTTONS */
    .submit-btn { width: 100%; background: #38bdf8; color: #0a0f1a; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; margin-top: 10px; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3); }
    .submit-btn:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(56, 189, 248, 0.4); }

    /* ALERTS */
    .error-msg { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 20px; line-height: 1.5;}

    /* FOOTER */
    .footer-link { margin-top: 25px; font-size: 14px; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; }
    .footer-link a { color: #38bdf8; text-decoration: none; font-weight: bold; transition: 0.2s;}
    .footer-link a:hover { color: #0ea5e9; text-decoration: underline;}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-wrapper">
    <div class="container">
        <h2>Welcome Back 👋</h2>

        <?php if($error != ""): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <div class="toggle-box">
            <div class="indicator" id="indicator"></div>
            <button class="toggle-btn active" id="btnStudent" onclick="switchTab('student')"><i class="fa fa-user-graduate"></i> Student</button>
            <button class="toggle-btn" id="btnCompany" onclick="switchTab('company')"><i class="fa fa-building"></i> Company</button>
        </div>

        <div class="forms-container">
            <div class="forms-wrapper" id="formsWrapper">
                
                <div class="form-box" id="studentForm">
                    <form method="POST">
                        <input type="hidden" name="role" value="student">
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Student Email ID" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div style="text-align: right; margin-bottom: 15px;">
                            <a href="student/forgot-password.php" style="font-size: 13px; color: #38bdf8; text-decoration: none;">Forgot Password?</a>
                        </div>
                        <button type="submit" name="login_submit" class="submit-btn">Login as Student</button>
                    </form>
                </div>

                <div class="form-box" id="companyForm">
                    <form method="POST">
                        <input type="hidden" name="role" value="company">
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Company Email ID" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div style="text-align: right; margin-bottom: 15px;">
                            <a href="company/company-forgot-password.php" style="font-size: 13px; color: #38bdf8; text-decoration: none;">Forgot Password?</a>
                        </div>
                        <button type="submit" name="login_submit" class="submit-btn">Login as Company</button>
                    </form>
                </div>

            </div>
        </div>

        <p class="footer-link">Don't have an account? <a href="register-selection.php">Register Here</a></p>
    </div>
</div>

<script>
function switchTab(role) {
    const wrapper = document.getElementById("formsWrapper");
    const indicator = document.getElementById("indicator");
    const btnS = document.getElementById("btnStudent");
    const btnC = document.getElementById("btnCompany");

    if(role === 'student') {
        wrapper.style.transform = "translateX(0%)";
        indicator.style.transform = "translateX(0%)";
        btnS.classList.add("active");
        btnC.classList.remove("active");
    } else {
        wrapper.style.transform = "translateX(-50%)";
        indicator.style.transform = "translateX(100%)";
        btnC.classList.add("active");
        btnS.classList.remove("active");
    }
}

function togglePass(btn) {
    const input = btn.parentElement.querySelector('input');
    const icon = btn.querySelector('i');
    if(input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>