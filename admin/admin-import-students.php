<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

// Include PHPMailer
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_id'])) { header("Location: admin-login.php"); exit(); }

$msg = "";

/* 📂 CSV UPLOAD & PROCESS LOGIC */
if (isset($_POST['import_csv'])) {
    set_time_limit(0); 

    $batch_year = (int)$_POST['batch_year'];
    $filename = $_FILES["csv_file"]["tmp_name"];

    if ($_FILES["csv_file"]["size"] > 0) {
        $file = fopen($filename, "r");
        fgetcsv($file); // Skip Header

        $success_count = 0;
        $exist_count = 0;
        $email_errors = ""; 

        while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
            $name    = mysqli_real_escape_string($conn, $data[0]);
            $email   = mysqli_real_escape_string($conn, $data[1]);
            $contact = mysqli_real_escape_string($conn, $data[2]);
            $dept    = mysqli_real_escape_string($conn, $data[3]);

            $check = mysqli_query($conn, "SELECT id FROM students WHERE email='$email'");
            
            if (mysqli_num_rows($check) == 0) {
                $raw_pass = "TPO@" . rand(1000, 9999);
                $hash_pass = password_hash($raw_pass, PASSWORD_DEFAULT);

                $insert = "INSERT INTO students (name, email, contact, department, batch_year, password, status) 
                           VALUES ('$name', '$email', '$contact', '$dept', $batch_year, '$hash_pass', 'approved')";
                
                if (mysqli_query($conn, $insert)) {
                    $success_count++;
                    $mail_status = sendWelcomeEmail($email, $name, $raw_pass);
                    if($mail_status !== true) {
                        $email_errors .= "<br>Failed for $email: " . $mail_status;
                    }
                }
            } else {
                $exist_count++;
            }
        }
        fclose($file);
        
        $msg = "<div class='alert-success'>✅ Successfully imported <b>$success_count</b> students. (<b>$exist_count</b> emails already existed).</div>";
        
        if($email_errors != "") {
            $msg .= "<div class='alert-error'>⚠️ Accounts created, but Emails failed to send: $email_errors</div>";
        } else if($success_count > 0) {
            $msg .= "<div class='alert-success' style='margin-top:-10px;'>📧 Login credentials sent to their emails successfully!</div>";
        }
        
        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Admin imported $success_count students for Batch $batch_year.')");
    } else {
        $msg = "<div class='alert-error'>❌ Please upload a valid CSV file.</div>";
    }
}

// 📧 PHPMailer Function (FIXED PORT, SECURITY & CREDENTIALS)
function sendWelcomeEmail($email, $name, $password) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // ✨ YOUR REAL CREDENTIALS ✨
        $mail->Username   = 'workwithme2501@gmail.com'; 
        $mail->Password   = 'cdyh ixsm laoa qzhh'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // ✨ YOUR REAL SENDING EMAIL ✨
        $mail->setFrom('workwithme2501@gmail.com', 'Placement Cell');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Welcome to Placement Portal - Your Login Details";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; background:#f4f6fb; padding:20px;'>
                <div style='background:#0f172a; padding:20px; border-radius:8px; border-top: 4px solid #38bdf8;'>
                    <h2 style='color:#f8fafc;'>Hello $name,</h2>
                    <p style='color:#cbd5e1;'>Your college placement profile has been successfully created by the Placement Office.</p>
                    <p style='color:#cbd5e1;'>You can now login and complete your profile/resume to apply for upcoming job drives.</p>
                    <div style='background:#1e293b; padding:15px; border-radius:8px; margin: 20px 0; border: 1px solid #38bdf8;'>
                        <p style='color:#f8fafc; margin:5px 0;'><b>Login Email:</b> $email</p>
                        <p style='color:#f8fafc; margin:5px 0;'><b>Your Password:</b> <span style='color:#38bdf8; font-size:18px;'>$password</span></p>
                    </div>
                    <p style='color:#94a3b8; font-size: 13px;'><i>⚠️ Note: We recommend changing your password after your first login.</i></p>
                    <p style='color:#cbd5e1;'>Best Regards,<br><b style='color:#38bdf8;'>Placement Cell Admin</b></p>
                </div>
            </div>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bulk Import Students</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:40px;max-width:800px;margin:auto}

.header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-flex h1 { font-size: 24px; font-weight: 800; color: #38bdf8;}
.back-btn { background: rgba(255,255,255,0.05); color: #f8fafc; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 1px solid rgba(255,255,255,0.1); transition: 0.3s;}
.back-btn:hover { background: rgba(255,255,255,0.1); }

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 40px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }

.upload-box { border: 2px dashed rgba(56, 189, 248, 0.4); padding: 40px; text-align: center; border-radius: 12px; background: rgba(56, 189, 248, 0.02); margin-bottom: 25px; transition: 0.3s; }
.upload-box:hover { background: rgba(56, 189, 248, 0.05); border-color: #38bdf8; }
.upload-box i { font-size: 50px; color: #38bdf8; margin-bottom: 15px; }
.upload-box input[type="file"] { margin-top: 15px; color: #cbd5e1; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; color: #94a3b8; font-weight: bold; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;}
.form-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; }

.btn-import { width: 100%; padding: 15px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px;}
.btn-import:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}

.alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 20px; }
.alert-error { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px; }

.csv-format { margin-top: 30px; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); }
.csv-format h4 { color: #f59e0b; margin-bottom: 10px; }
.csv-format p { color: #cbd5e1; font-size: 13px; line-height: 1.6; }
.csv-format code { background: rgba(255,255,255,0.1); padding: 3px 6px; border-radius: 4px; color: #fff; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="header-flex">
        <h1><i class="fa fa-file-csv"></i> Bulk Import Students</h1>
        <a href="admin-students.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>
    </div>

    <?= $msg ?>

    <div class="card-glass">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Select Passing Year (Batch)</label>
                <select name="batch_year" required>
                    <option value="2025">Batch 2025</option>
                    <option value="2026" selected>Batch 2026</option>
                    <option value="2027">Batch 2027</option>
                    <option value="2028">Batch 2028</option>
                </select>
            </div>

            <div class="upload-box">
                <i class="fa fa-cloud-arrow-up"></i>
                <h3 style="color: #f8fafc; margin-bottom: 5px;">Drag & Drop CSV File Here</h3>
                <p style="color: #64748b; font-size: 13px;">Only .csv files are supported</p>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>

            <button type="submit" name="import_csv" class="btn-import"><i class="fa fa-upload"></i> Start Import & Send Emails</button>
        </form>
    </div>

    <div class="csv-format">
        <h4><i class="fa fa-info-circle"></i> CSV File Format Guide</h4>
        <p>Please ensure your Excel/CSV file exactly follows this column order. Do not change the order.</p>
        <p style="margin-top: 10px;"><b>Column 1:</b> Full Name <br><b>Column 2:</b> Email Address <br><b>Column 3:</b> Contact Number <br><b>Column 4:</b> Department (e.g., CE, IT, ME)</p>
        <p style="margin-top: 15px; font-style: italic;">Example Row: <code>Rahul Patel, rahul@gmail.com, 9876543210, CE</code></p>
    </div>
</main>
</body>
</html>