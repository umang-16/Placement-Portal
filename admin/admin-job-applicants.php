<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 
if(!isset($_SESSION['admin_id'])){ header("Location: admin-login.php"); exit(); }

if(!isset($_GET['job_id'])) { header("Location: admin-jobs.php"); exit(); }
$job_id = (int)$_GET['job_id'];

/* 🛠️ 1. સ્માર્ટ ડેટાબેઝ અપડેટ */
$check_letter = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'offer_letter'");
if(mysqli_num_rows($check_letter) == 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN offer_letter VARCHAR(255) DEFAULT NULL");
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN offer_status VARCHAR(50) DEFAULT 'Pending'");
}

/* ✅ 2. UPDATE STATUS LOGIC */
if(isset($_POST['update_status'])){
    $app_id = (int)$_POST['app_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $check_time = mysqli_query($conn, "SELECT TIMESTAMPDIFF(HOUR, applied_at, NOW()) as hours_passed FROM applications WHERE id=$app_id");
    $time_data = mysqli_fetch_assoc($check_time);

    if($time_data['hours_passed'] < 24 && $new_status != 'pending' && $new_status != 'withdrawn') {
        echo "<script>alert('⏳ This application is locked for 24 hours.'); window.location.href='admin-job-applicants.php?job_id=$job_id';</script>";
        exit();
    }
    
    // Update admin_status, not the main status (which is for company now)
    mysqli_query($conn, "UPDATE applications SET admin_status='$new_status' WHERE id=$app_id");
    
    // If admin forwards (selects) student, notify company
    if($new_status == 'selected'){
        $job_res = mysqli_query($conn,"SELECT company_id, title FROM jobs WHERE id=$job_id");
        if($job_data = mysqli_fetch_assoc($job_res)){
            $c_id = $job_data['company_id'];
            $j_name = mysqli_real_escape_string($conn, $job_data['title']);
            $msg = "Admin forwarded a new applicant for $j_name.";
            mysqli_query($conn, "INSERT INTO notifications (company_id, message, created_at) VALUES ($c_id, '$msg', NOW())");
        }
    }

    $stu_q = mysqli_query($conn, "SELECT student_id, (SELECT title FROM jobs WHERE id=$job_id) as job_title FROM applications WHERE id=$app_id");
    if($s = mysqli_fetch_assoc($stu_q)){
        $safe_msg = mysqli_real_escape_string($conn, "🔔 Update: Your application (Admin Stage) for '{$s['job_title']}' is now '$new_status'.");
        mysqli_query($conn, "INSERT INTO notifications (student_id, message) VALUES ({$s['student_id']}, '$safe_msg')");
    }
    header("Location: admin-job-applicants.php?job_id=$job_id");
    exit();
}

/* ✨ 3. OFFER VERIFICATION LOGIC */
if(isset($_GET['offer_action']) && isset($_GET['app_id'])) {
    $aid = (int)$_GET['app_id'];
    $act = $_GET['offer_action'];
    if($act == 'verify') {
        mysqli_query($conn, "UPDATE applications SET offer_status='Verified' WHERE id=$aid");
    } elseif($act == 'reject') {
        mysqli_query($conn, "UPDATE applications SET offer_status='Rejected' WHERE id=$aid");
    }
    header("Location: admin-job-applicants.php?job_id=$job_id");
    exit();
}

$job_details = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.title, c.company_name FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.id = $job_id"));

/* 📊 4. FETCH APPLICANTS (SORT BY MATCH SCORE BY DEFAULT) */
$applicants = mysqli_query($conn, "
    SELECT a.id as app_id, a.status as company_status, a.admin_status as app_status, a.applied_at, a.offer_letter, a.offer_status,
           a.match_score, TIMESTAMPDIFF(HOUR, a.applied_at, NOW()) as hours_passed,
           s.name, s.email, s.contact, s.department, s.resume 
    FROM applications a 
    JOIN students s ON a.student_id = s.id 
    WHERE a.job_id = $job_id ORDER BY a.match_score DESC, a.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Applicants</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color:#f8fafc;}
main{padding:30px;max-width:1200px;margin:auto}

.header-box { background: rgba(255,255,255,0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); border-left: 5px solid #38bdf8; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.header-box h1 { font-size: 24px; color: #f8fafc; margin-bottom: 5px; }
.header-box p { color: #94a3b8; font-size: 15px; }
.back-btn { background: rgba(255,255,255,0.05); color: #f8fafc; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 1px solid rgba(255,255,255,0.1); transition: 0.3s;}
.back-btn:hover { background: rgba(255,255,255,0.1); }

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }

.status-form { display: flex; gap: 8px; align-items: center; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); }
.status-select { background: #0f172a; color: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); outline: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; }
.status-select:focus { border-color: #38bdf8; box-shadow: 0 0 10px rgba(56,189,248,0.2); }
.btn-update { background: #38bdf8; color: #0a0f1a; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.3s; }
.btn-update:hover { background: #0ea5e9; transform: translateY(-1px); }
.resume-link { color: #38bdf8; font-weight: bold; text-decoration: none; font-size: 13px; background: rgba(56,189,248,0.1); padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(56,189,248,0.2); transition: 0.3s; display: inline-block; }
.resume-link:hover { background: rgba(56,189,248,0.2); }

.offer-verify-btn { color: #10b981; font-size: 11px; font-weight: bold; text-decoration: none; margin-left: 5px; border: 1px solid #10b981; padding: 4px 8px; border-radius: 4px; transition: 0.3s;}
.offer-verify-btn:hover { background: #10b981; color: #fff;}
.offer-reject-btn { color: #ef4444; font-size: 11px; font-weight: bold; text-decoration: none; margin-left: 5px; border: 1px solid #ef4444; padding: 4px 8px; border-radius: 4px; transition: 0.3s;}
.offer-reject-btn:hover { background: #ef4444; color: #fff;}

.locked-badge { background: rgba(255,255,255,0.05); color: #94a3b8; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; cursor: not-allowed; border: 1px solid rgba(255,255,255,0.1);}
.withdrawn-badge { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="header-box">
        <div>
            <h1>👥 Applicants List</h1>
            <p>Role: <b style="color:#38bdf8;"><?= htmlspecialchars($job_details['title'] ?? '') ?></b> at <?= htmlspecialchars($job_details['company_name'] ?? '') ?></p>
        </div>
        <a href="admin-jobs.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Jobs</a>
    </div>

    <div class="card-glass">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Resume</th>
                    <th>Status Action</th>
                    <th>✨ Offer Letter</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($applicants) > 0): ?>
                    <?php while($app = mysqli_fetch_assoc($applicants)): ?>
                        <tr>
                            <td>
                                <b style="color: #f8fafc;"><?= htmlspecialchars($app['name']) ?></b>
                                <div style="font-size: 11px; color: #64748b; margin-top:3px;"><i class="fa fa-envelope"></i> <?= htmlspecialchars($app['email']) ?></div>
                                <?php if($app['match_score'] > 0): ?>
                                    <div style="font-size: 11px; font-weight:bold; color: #10b981; margin-top:5px; background: rgba(16,185,129,0.1); padding: 4px; border-radius: 4px; display:inline-block;"><i class="fa fa-robot"></i> AI Match: <?= htmlspecialchars($app['match_score']) ?>%</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($app['resume'])): ?>
                                    <a href="../uploads/resumes/<?= $app['resume'] ?>" target="_blank" class="resume-link"><i class="fa fa-file-pdf"></i> Open</a>
                                <?php else: ?>
                                    <span style="color:#64748b; font-size:12px;">No Resume</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($app['app_status'] == 'withdrawn'): ?>
                                    <span class="withdrawn-badge"><i class="fa fa-ban"></i> Withdrawn</span>
                                <?php elseif($app['app_status'] == 'pending' && $app['hours_passed'] < 24): ?>
                                    <span class="locked-badge"><i class="fa fa-lock"></i> Locked (<?= 24 - $app['hours_passed'] ?> hrs left)</span>
                                <?php elseif($app['app_status'] == 'pending'): ?>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="app_id" value="<?= $app['app_id'] ?>">
                                        <select name="status" class="status-select">
                                            <option value="pending" <?= $app['app_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="shortlisted" <?= $app['app_status'] == 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                            <option value="selected" <?= $app['app_status'] == 'selected' ? 'selected' : '' ?>>Selected</option>
                                            <option value="rejected" <?= $app['app_status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Save</button>
                                    </form>
                                <?php else: ?>
                                    <?php 
                                        $badge_class = '';
                                        if($app['app_status'] == 'shortlisted') $badge_class = 'background: rgba(56,189,248,0.1); color: #38bdf8; border: 1px solid rgba(56,189,248,0.2);';
                                        elseif($app['app_status'] == 'selected') $badge_class = 'background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2);';
                                        elseif($app['app_status'] == 'rejected') $badge_class = 'background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2);';
                                    ?>
                                    <span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; <?= $badge_class ?>">
                                        <?= ucfirst($app['app_status']) ?> (Saved)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($app['app_status'] == 'selected'): ?>
                                    <?php if(!empty($app['offer_letter'])): ?>
                                        <a href="../uploads/offers/<?= $app['offer_letter'] ?>" target="_blank" style="color:#38bdf8; font-size:13px; font-weight:bold;"><i class="fa fa-file-pdf"></i> View PDF</a>
                                        
                                        <div style="font-size: 11px; margin-top:6px; color:<?= ($app['offer_status']=='Verified')?'#10b981':(($app['offer_status']=='Rejected')?'#ef4444':'#f59e0b') ?>;">
                                            Status: <b><?= $app['offer_status'] ?></b>
                                        </div>
                                        
                                        <?php if($app['offer_status'] == 'Pending'): ?>
                                            <div style="margin-top:8px;">
                                                <a href="?job_id=<?= $job_id ?>&offer_action=verify&app_id=<?= $app['app_id'] ?>" class="offer-verify-btn" onclick="return confirm('Verify this offer letter?')"><i class="fa fa-check"></i> Verify</a>
                                                <a href="?job_id=<?= $job_id ?>&offer_action=reject&app_id=<?= $app['app_id'] ?>" class="offer-reject-btn" onclick="return confirm('Reject this offer letter?')"><i class="fa fa-times"></i></a>
                                            </div>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <span style="color:#64748b; font-size:12px; font-style: italic;"><i class="fa fa-clock"></i> Not Uploaded Yet</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#475569; font-size:18px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:40px; color:#64748b;">No one has applied for this job yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>