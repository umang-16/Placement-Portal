<?php
session_start();
require_once __DIR__ . "/db.php";

// Include PHPMailer files
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";

/* ---------------------------------------------------------------------------
   🔐 REGISTRATION LOGIC WITH OTP & UNVERIFIED STATUS
--------------------------------------------------------------------------- */

if(isset($_POST['register_submit'])){
    $role = $_POST['role']; 

    // 🎲 Generate 6-digit OTP
    $otp = rand(100000, 999999);

    if($role == 'student'){
        $name    = trim($_POST['name']);
        $email   = trim($_POST['email']);
        $contact = trim($_POST['contact']); 
        $pass    = $_POST['password'];
        $cpass   = $_POST['confirm_password'];

        if($name=="" || $email=="" || $contact=="" || $pass==""){
            $error = "All fields are required";
        } elseif($pass !== $cpass){
            $error = "Passwords do not match";
        } elseif(strlen($contact) < 10) {
            $error = "Please enter a valid contact number";
        } else {
            $email_esc = mysqli_real_escape_string($conn, $email);
            
            // Check if email already exists
            $check = mysqli_query($conn, "SELECT id FROM students WHERE email='$email_esc'");
            if(mysqli_num_rows($check) > 0){
                $error = "Email already registered!";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $name_esc = mysqli_real_escape_string($conn, $name);
                $contact_esc = mysqli_real_escape_string($conn, $contact);

                $query = "INSERT INTO students (name, email, contact, password, status, otp) 
                          VALUES ('$name_esc', '$email_esc', '$contact_esc', '$hash', 'unverified', '$otp')";
                
                if(mysqli_query($conn, $query)){
                    sendOTP($email_esc, $name_esc, $otp, 'student');
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    } 
    elseif($role == 'company'){
        $cname   = trim($_POST['company_name']);
        $email   = trim($_POST['email']);
        $contact = trim($_POST['contact']);
        $pass    = $_POST['password'];
        $cpass   = $_POST['confirm_password'];

        if($cname=="" || $email=="" || $contact=="" || $pass==""){
            $error = "All fields are required";
        } elseif($pass !== $cpass){
            $error = "Passwords do not match";
        } elseif(strlen($contact) < 10) {
            $error = "Please enter a valid contact number";
        } else {
            $email_esc = mysqli_real_escape_string($conn, $email);
            
            // Check if company already exists
            $check = mysqli_query($conn, "SELECT id FROM companies WHERE email='$email_esc'");
            if(mysqli_num_rows($check) > 0){
                $error = "Company Email already registered!";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $cname_esc = mysqli_real_escape_string($conn, $cname);
                $contact_esc = mysqli_real_escape_string($conn, $contact);

                $query = "INSERT INTO companies (company_name, email, contact_no, password, status, otp) 
                          VALUES ('$cname_esc', '$email_esc', '$contact_esc', '$hash', 'unverified', '$otp')";
                
                if(mysqli_query($conn, $query)){
                    sendOTP($email_esc, $cname_esc, $otp, 'company');
                } else {
                    $error = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// 📧 PHPMailer Function
function sendOTP($email, $name, $otp, $type) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'darshangaadhe@gmail.com'; 
        $mail->Password   = 'vymx hqej eivz obor'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 465;

        $mail->setFrom('darshangaadhe@gmail.com', 'Placement Portal');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Verify Your Account - Placement Portal";
        $mail->Body    = "
            <h3>Hello $name,</h3>
            <p>Thank you for registering on our Placement Portal.</p>
            <p>Your OTP for email verification is: <b style='font-size: 20px; color: #2563eb;'>$otp</b></p>
            <p>Please enter this OTP on the verification page to activate your account.</p>
            <br>
            <p>Regards,<br>Placement Portal Team</p>
        ";

        $mail->send();
        
        // Redirect to OTP verification page
        header("Location: verify-otp.php?email=$email&type=$type");
        exit();
        
    } catch (Exception $e) {
        global $error;
        $error = "Mail Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }

    /* BACKGROUND GRADIENT */
    .main-wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 50px 20px; background: radial-gradient(circle at center, #1e3a8a 0%, #0a0f1a 70%); }

    /* GLASS CONTAINER */
    .container { width: 100%; max-width: 480px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 25px 50px rgba(0,0,0,0.5); border-radius: 20px; padding: 40px 30px; text-align: center; }
    
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

    /* FOOTER LINK */
    .footer-link { margin-top: 25px; font-size: 14px; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; }
    .footer-link a { color: #38bdf8; text-decoration: none; font-weight: bold; transition: 0.2s;}
    .footer-link a:hover { color: #0ea5e9; text-decoration: underline;}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-wrapper">
    <div class="container">
        <h2>Create an Account 🚀</h2>

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
                            <input type="text" name="name" placeholder="Full Name" required>
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Student Email ID" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="input-group">
                            <input type="text" name="contact" placeholder="Contact Number" required>
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Create Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="input-group">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <button type="submit" name="register_submit" class="submit-btn">Register as Student</button>
                    </form>
                </div>

                <div class="form-box" id="companyForm">
                    <form method="POST">
                        <input type="hidden" name="role" value="company">
                        <div class="input-group">
                            <input type="text" name="company_name" placeholder="Company Name" required>
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Official Email ID" required>
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="input-group">
                            <input type="text" name="contact" placeholder="HR Contact Number" required>
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Create Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="input-group">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="eye-btn" onclick="togglePass(this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <button type="submit" name="register_submit" class="submit-btn">Register as Company</button>
                    </form>
                </div>

            </div>
        </div>

        <p class="footer-link">Already have an account? <a href="login-selection.php">Login here</a></p>
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