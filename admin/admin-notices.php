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

/* ✅ POST NEW NOTICE & SEND MASS EMAIL LOGIC */
if (isset($_POST['post_notice'])) {
    set_time_limit(0); 

    $title    = mysqli_real_escape_string($conn, $_POST['title']);
    $message  = mysqli_real_escape_string($conn, $_POST['message']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $branch   = mysqli_real_escape_string($conn, $_POST['branch']);
    $send_email = isset($_POST['send_email']) ? true : false;
    
    $sql_notice = "INSERT INTO notices (title, message, category, branch, notice_date) VALUES ('$title', '$message', '$category', '$branch', NOW())";
    
    if (mysqli_query($conn, $sql_notice)) {
        $msg = "<div class='alert-success'>✅ Notice successfully published on the portal board.</div>";
        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Admin published a new notice: $title')");

        if ($send_email) {
            $sql_emails = "SELECT email FROM students WHERE status='approved'";
            if ($branch == 'CE') { $sql_emails .= " AND department='Computer Engineering'"; }
            elseif ($branch == 'IT') { $sql_emails .= " AND department='Information Technology'"; }
            elseif ($branch == 'ME') { $sql_emails .= " AND department='Mechanical Engineering'"; }
            
            $result = mysqli_query($conn, $sql_emails);
            $email_count = mysqli_num_rows($result);

            if ($email_count > 0) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'workwithme2501@gmail.com'; 
                    $mail->Password   = 'cdyh ixsm laoa qzhh'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom('workwithme2501@gmail.com', 'Placement Cell Admin');
                    $mail->addAddress('workwithme2501@gmail.com', 'Admin System'); 

                    while ($row = mysqli_fetch_assoc($result)) {
                        $mail->addBCC($row['email']);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "[$category Alert] $title";
                    
                    $html_message = nl2br(htmlspecialchars($message));
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; background:#f4f6fb; padding:20px;'>
                            <div style='background:#0f172a; padding:25px; border-radius:8px; border-top: 4px solid #38bdf8;'>
                                <h2 style='color:#f8fafc;'><i class='fa fa-bullhorn'></i> Placement Cell Notice</h2>
                                <h3 style='color:#38bdf8; margin-top:0;'>$title</h3>
                                <div style='color:#cbd5e1; font-size:15px; line-height:1.6; margin-top:15px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px;'>
                                    $html_message
                                </div>
                                <hr style='border: 0; border-bottom: 1px solid #1e293b; margin: 25px 0;'>
                                <p style='color:#94a3b8; font-size: 13px;'>You are receiving this because you are registered in the Target Branch: <b>$branch</b>.</p>
                            </div>
                        </div>
                    ";
                    
                    $mail->send();
                    $msg .= "<div class='alert-success' style='margin-top:-10px;'>📧 Mass Email successfully sent to <b>$email_count</b> students.</div>";
                    mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Mass email for notice \"$title\" sent to $email_count students.')");

                } catch (Exception $e) {
                    $msg .= "<div class='alert-error' style='margin-top:-10px;'>❌ Mail Error: {$mail->ErrorInfo}</div>";
                }
            } else {
                $msg .= "<div class='alert-error' style='margin-top:-10px;'>⚠️ No students found in this branch to send emails.</div>";
            }
        }
    } else {
        $msg = "<div class='alert-error'>❌ Database Error while posting notice.</div>";
    }
}

/* 🗑️ DELETE NOTICE LOGIC */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM notices WHERE id=$id");
    header("Location: admin-notices.php"); 
    exit();
}

$notices = mysqli_query($conn, "SELECT * FROM notices ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Notices & Emails</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1150px;margin:auto; display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 30px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
h2 { color: #38bdf8; margin-bottom: 20px; font-size: 20px; font-weight: 800; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;}

label { font-size: 13px; color: #94a3b8; font-weight: bold; margin-top: 15px; display: block; text-transform: uppercase; letter-spacing: 0.5px;}
input[type="text"], select, textarea { width: 100%; padding: 12px; margin-top: 6px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; font-family: inherit;}
input:focus, select:focus, textarea:focus { border-color: #38bdf8; background: rgba(0,0,0,0.4); box-shadow: 0 0 0 3px rgba(56,189,248,0.15);}
textarea { resize: none; height: 120px; }

/* CHECKBOX STYLING */
.checkbox-group { background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); padding: 15px; border-radius: 8px; margin-top: 20px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.3s;}
.checkbox-group:hover { background: rgba(56, 189, 248, 0.1); }
.checkbox-group input { width: 18px; height: 18px; accent-color: #38bdf8; cursor: pointer; margin: 0;}
.checkbox-group label { margin: 0; color: #f8fafc; font-size: 14px; text-transform: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;}

.btn-post { margin-top: 20px; width: 100%; padding: 15px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 15px;}
.btn-post:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}

.notice-item { background: rgba(0,0,0,0.2); border-left: 5px solid #38bdf8; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-top: 1px solid rgba(255,255,255,0.03); border-right: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); position: relative; transition: 0.3s;}
.notice-item:hover { background: rgba(255,255,255,0.03); transform: translateX(5px); }

.badge { font-size: 10px; padding: 4px 10px; border-radius: 6px; font-weight: 800; text-transform: uppercase; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); display: inline-block; margin-bottom: 8px;}
.badge.Urgent { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2); }
.badge.Event { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.2); }

.notice-title { font-size: 18px; color: #f8fafc; font-weight: bold; margin-bottom: 8px; }
.notice-msg { font-size: 14px; color: #cbd5e1; line-height: 1.5; margin-bottom: 10px; }
.notice-meta { color: #64748b; font-size: 12px; font-weight: bold; }

.delete-btn { position: absolute; top: 20px; right: 20px; color: #94a3b8; background: transparent; border: none; font-size: 16px; cursor: pointer; transition: 0.2s;}
.delete-btn:hover { color: #ef4444; transform: scale(1.1); }

.alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 15px; font-weight: bold; font-size: 14px;}
.alert-error { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 15px; font-weight: bold; font-size: 14px;}
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div>
        <div style="grid-column: 1/-1; margin-bottom: 15px;">
            <?= $msg ?>
        </div>

        <div class="card-glass">
            <h2><i class="fa fa-bullhorn"></i> Broadcast Global Notice</h2>
            <form method="POST">
                <label>Notice Title / Email Subject</label>
                <input type="text" name="title" placeholder="e.g. Urgent: Mega Job Fair Tomorrow!" required>
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Category</label>
                        <select name="category">
                            <option value="General">General Info</option>
                            <option value="Urgent">Urgent / Important</option>
                            <option value="Event">Event / Drive</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Target Audience (Branch)</label>
                        <select name="branch">
                            <option value="all">All Branches</option>
                            <option value="CE">Computer Engg. (CE)</option>
                            <option value="IT">Information Tech. (IT)</option>
                            <option value="ME">Mechanical Engg. (ME)</option>
                        </select>
                    </div>
                </div>

                <label>Detailed Message</label>
                <textarea name="message" required placeholder="Write the full announcement details here..."></textarea>
                
                <label class="checkbox-group">
                    <input type="checkbox" name="send_email" value="yes" checked>
                    <span><i class="fa fa-envelope" style="color: #38bdf8;"></i> Also send this as an Email to the selected branch.</span>
                </label>
                
                <button type="submit" name="post_notice" class="btn-post"><i class="fa fa-paper-plane"></i> Publish & Send</button>
            </form>
        </div>
    </div>

    <div class="card-glass">
        <h2><i class="fa fa-list"></i> Digital Notice Board</h2>
        <div style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
            <?php if (mysqli_num_rows($notices) > 0): ?>
                <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                    <div class="notice-item">
                        <span class="badge <?= $n['category'] ?>"><?= $n['category'] ?></span>
                        <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="notice-msg"><?= nl2br(htmlspecialchars($n['message'])) ?></div>
                        
                        <div class="notice-meta">
                            <i class="fa fa-users" style="color: #38bdf8;"></i> Target: <?= $n['branch'] == 'all' ? 'Everyone' : $n['branch'] ?> &nbsp;|&nbsp; 
                            <i class="fa fa-calendar-day"></i> <?= date('d M Y, h:i A', strtotime($n['notice_date'] ?? $n['created_at'] ?? 'now')) ?>
                        </div>

                        <a href="?delete=<?= $n['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this notice?')" title="Delete Notice">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 50px 20px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px dashed rgba(255,255,255,0.05);">
                    <i class="fa fa-folder-open" style="font-size: 40px; color: #64748b; margin-bottom: 15px;"></i>
                    <p style="color:#94a3b8; font-weight: bold;">No announcements active on the board.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>