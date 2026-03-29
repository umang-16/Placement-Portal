<?php
session_start();
require_once __DIR__ . "/../db.php";

if(!isset($_SESSION['student_id'])){
    header("Location: student-login.php");
    exit();
}

$student_id   = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

/* 🎫 HELPDESK TICKET SUBMISSION LOGIC */
if(isset($_POST['submit_ticket'])){
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $sql_ticket = "INSERT INTO helpdesk_tickets (sender_type, sender_id, subject, message, status, created_at) 
                   VALUES ('student', $student_id, '$subject', '$message', 'pending', NOW())";
    
    if(mysqli_query($conn, $sql_ticket)){
        $ticket_msg = "✅ Your query has been submitted! Admin will reply soon in your notifications.";
    } else {
        $ticket_err = "❌ Error submitting query. Please try again.";
    }
}

/* 📊 FETCH STUDENT DATA */
$student_res = mysqli_query($conn, "SELECT email, avatar, department, skills FROM students WHERE id=$student_id");
$student_data = mysqli_fetch_assoc($student_res);

$student_email = $student_data['email'];
$student_dept  = !empty($student_data['department']) ? $student_data['department'] : 'all'; 

/* 📈 FETCH LIVE STATS */
$total_applied = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE student_id=$student_id AND status != 'withdrawn'"))['total'];
$shortlisted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE student_id=$student_id AND status='shortlisted'"))['total'];
$selected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE student_id=$student_id AND status='selected'"))['total'];

/* 🖼️ CORRECT PHOTO DISPLAY LOGIC */
$photoFilename = !empty($student_data['avatar']) ? $student_data['avatar'] : 'boy.png';
if ($photoFilename === 'boy.png' || $photoFilename === 'girl.png') {
    $photoPath = "../assets/avatars/" . $photoFilename;
} else {
    $photoPath = "../uploads/photos/" . $photoFilename;
}

/* 📢 FETCH LATEST NOTICES */
$marquee_notices = mysqli_query($conn, "SELECT title FROM notices WHERE branch='all' OR branch='$student_dept' ORDER BY id DESC LIMIT 5");
$notice_texts = [];
while($n = mysqli_fetch_assoc($marquee_notices)){
    $notice_texts[] = "⭐ " . htmlspecialchars($n['title']);
}
$marquee_string = empty($notice_texts) ? "Welcome to the Placement Portal!" : implode(" &nbsp;&nbsp;|&nbsp;&nbsp; ", $notice_texts);

/* 🎯 SMART JOB RECOMMENDATION ALGORITHM (AI-Match) */
$student_skills_raw = $student_data['skills'] ?? '';
$student_skills_array = array_filter(array_map('trim', explode(',', strtolower($student_skills_raw))));

$recommended_jobs = [];

if (!empty($student_skills_array)) {
    $rec_sql = "SELECT j.id, j.title, j.skills, j.salary, c.company_name 
                FROM jobs j JOIN companies c ON j.company_id = c.id
                WHERE j.status = 'approved' AND j.id NOT IN (SELECT job_id FROM applications WHERE student_id = $student_id)";
    $rec_res = mysqli_query($conn, $rec_sql);
    
    while($job = mysqli_fetch_assoc($rec_res)) {
        $job_skills_raw = $job['skills'] ?? '';
        $job_skills_array = array_filter(array_map('trim', explode(',', strtolower($job_skills_raw))));
        
        $match_count = 0;
        foreach($job_skills_array as $js) {
            if(in_array($js, $student_skills_array)) { $match_count++; }
        }
        
        if($match_count > 0) {
            $total_job_skills = count($job_skills_array);
            $match_percentage = ($total_job_skills > 0) ? round(($match_count / $total_job_skills) * 100) : 0;
            if($match_percentage > 100) $match_percentage = 100;
            $job['match_percent'] = $match_percentage;
            $recommended_jobs[] = $job;
        }
    }
    
    usort($recommended_jobs, function($a, $b) { return $b['match_percent'] <=> $a['match_percent']; });
    $recommended_jobs = array_slice($recommended_jobs, 0, 3);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; }
    
    main { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
    
    .stats-row { display: flex; gap: 20px; margin-bottom: 25px; }
    .stat-card { flex: 1; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); text-align: center; border-bottom: 4px solid #38bdf8; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.04); }
    .stat-card h3 { font-size: 36px; color: #f8fafc; margin: 5px 0; font-weight: 800;}
    .stat-card p { color: #94a3b8; font-size: 13px; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 1px;}
    
    .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); margin-bottom: 20px;}
    h2 { font-size: 20px; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 20px; font-weight: 800;}
    
    .notice-bar { background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.05); color: #38bdf8; padding: 12px 35px; font-size: 14px; display: flex; align-items: center; }
    .notice-bar span { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 4px 12px; border-radius: 6px; font-weight: bold; margin-right: 15px; white-space: nowrap; font-size: 11px; text-transform: uppercase;}
    
    /* TABLE FIX */
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 15px !important; text-align: left !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; font-size: 14px !important; color: #cbd5e1; }
    th { background: rgba(255,255,255,0.02) !important; color: #38bdf8 !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 1px; font-size: 12px !important;}
    
    .status { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; border: 1px solid transparent;}
    .pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3); }
    .shortlisted { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); }
    .selected { background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.3); }
    
    .profile-box { text-align: center; padding-bottom: 20px; border-bottom: 1px dashed rgba(255,255,255,0.1); }
    .profile-box img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #38bdf8; padding: 3px; box-shadow: 0 0 20px rgba(56, 189, 248, 0.3); background: #0f172a;}
    
    ul.list { list-style: none; padding: 0; margin: 0; }
    ul.list li { padding: 15px 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; color: #cbd5e1; display: flex; gap: 10px; align-items: flex-start; transition: 0.3s; border-radius: 8px;}
    ul.list li:hover { background: rgba(255,255,255,0.02); transform: translateX(5px); }

    /* AI CARD */
    .ai-card { background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.2); border-left: 4px solid #10b981; padding: 15px 20px; border-radius: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s;}
    .match-badge { background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; margin-bottom: 5px;}
    .btn-apply-ai { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 800; border: 1px solid #10b981; transition: 0.3s;}

    /* 🎧 FLOATING HELPDESK BUTTON & MODAL */
    .help-fab { position: fixed; bottom: 30px; right: 30px; background: #38bdf8; color: #0a0f1a; width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; box-shadow: 0 10px 25px rgba(56, 189, 248, 0.4); cursor: pointer; transition: 0.3s; z-index: 999; border: none; }
    .help-fab:hover { transform: scale(1.1) translateY(-5px); background: #0ea5e9; }

    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); justify-content: center; align-items: center; z-index: 2000; }
    .modal-content { background: #0f172a; padding: 35px; border-radius: 16px; width: 450px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }
    .close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; transition: 0.3s; }
    .close-btn:hover { background: #ef4444; color: #fff; transform: scale(1.1); }
    
    .modal-content label { display: block; margin-top: 15px; font-weight: 800; font-size: 12px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; }
    .form-input { width: 100%; padding: 14px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; margin-top: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.3s; }
    .form-input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
    .btn-submit { width: 100%; background: #38bdf8; color: #0a0f1a; padding: 15px; border: none; border-radius: 8px; margin-top: 25px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; }
    .btn-submit:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }
  </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<div class="notice-bar">
    <span>LATEST UPDATES</span>
    <marquee behavior="scroll" direction="left" scrollamount="6"><?= $marquee_string ?></marquee>
</div>

<main>
    <h1 style="color: #f8fafc; font-weight: 800; font-size: 28px; margin-bottom: 5px;">Welcome back, <span style="color: #38bdf8;"><?= htmlspecialchars($student_name) ?></span> 👋</h1>
    <p style="color: #94a3b8; margin-bottom: 25px;">Track your applications, interviews, and placement progress here.</p>

    <?php if(isset($ticket_msg)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid rgba(16, 185, 129, 0.3);">
            <?= $ticket_msg ?>
        </div>
    <?php endif; ?>
    <?php if(isset($ticket_err)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; border: 1px solid rgba(239, 68, 68, 0.3);">
            <?= $ticket_err ?>
        </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card" style="border-bottom-color: #38bdf8;"><h3><?= $total_applied ?></h3><p>Total Applied</p></div>
        <div class="stat-card" style="border-bottom-color: #f59e0b;"><h3 style="color: #f59e0b;"><?= $shortlisted ?></h3><p>Shortlisted</p></div>
        <div class="stat-card" style="border-bottom-color: #10b981;"><h3 style="color: #10b981;"><?= $selected ?></h3><p>Selected / Offers</p></div>
    </div>

    <div class="grid">
        <div class="left-col">
            <?php if(!empty($student_skills_array) && !empty($recommended_jobs)): ?>
            <div class="card" style="border-top: 4px solid #10b981;">
                <h2 style="color: #10b981;"><i class="fa fa-wand-magic-sparkles"></i> Top Job Matches For You</h2>
                <p style="font-size: 12px; color: #94a3b8; margin-top: -15px; margin-bottom: 20px;">Based on your profile skills.</p>
                <?php foreach($recommended_jobs as $job): ?>
                    <div class="ai-card">
                        <div>
                            <span class="match-badge"><i class="fa fa-fire"></i> <?= $job['match_percent'] ?>% Match</span>
                            <h3 style="color: #f8fafc; font-size: 16px; margin: 3px 0;"><?= htmlspecialchars($job['title']) ?></h3>
                            <p style="color: #94a3b8; font-size: 13px; font-weight: 600;"><i class="fa fa-building"></i> <?= htmlspecialchars($job['company_name']) ?></p>
                        </div>
                        <a href="companies.php?search=<?= urlencode($job['title']) ?>" class="btn-apply-ai">View & Apply</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <h2><i class="fa fa-briefcase" style="color:#38bdf8;"></i> Recent Applications</h2>
                <table>
                    <thead><tr><th>Job Role</th><th>Company</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php
                        $apps = mysqli_query($conn, "SELECT j.title, c.company_name, a.status FROM applications a JOIN jobs j ON a.job_id = j.id JOIN companies c ON j.company_id = c.id WHERE a.student_id = $student_id ORDER BY a.id DESC LIMIT 4");
                        if(mysqli_num_rows($apps) > 0){
                            while($row = mysqli_fetch_assoc($apps)){
                                echo "<tr><td><b style='color:#f8fafc;'>".htmlspecialchars($row['title'])."</b></td><td style='color:#94a3b8;'>".htmlspecialchars($row['company_name'])."</td><td><span class='status ".strtolower($row['status'])."'>{$row['status']}</span></td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; padding:30px; color:#64748b; font-style:italic;'>No applications yet. Start applying!</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="right-col">
            <div class="card">
                <div class="profile-box">
                    <img src="<?= $photoPath ?>" alt="Profile" onerror="this.src='../assets/avatars/boy.png'">
                    <h3><?= htmlspecialchars($student_name) ?></h3>
                    <p style="font-size: 13px; color: #94a3b8;"><i class="fa fa-envelope" style="color:#38bdf8;"></i> <?= htmlspecialchars($student_email) ?></p>
                </div>
                <h2 style="margin-top:25px; font-size: 16px;"><i class="fa fa-bell" style="color:#f59e0b;"></i> Recent Alerts</h2>
                <ul class="list">
                    <?php
                    $noti = mysqli_query($conn, "SELECT message FROM notifications WHERE student_id=$student_id ORDER BY id DESC LIMIT 4");
                    if(mysqli_num_rows($noti) > 0){
                        while($n = mysqli_fetch_assoc($noti)){
                            echo "<li><i class='fa fa-caret-right' style='color:#38bdf8; margin-top:3px;'></i> <span>" . htmlspecialchars($n['message']) . "</span></li>";
                        }
                    } else {
                        echo "<li style='color:#64748b; font-style: italic; justify-content: center;'>No recent alerts.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</main>

<button class="help-fab" onclick="openHelpModal()" title="Need Help? Contact Admin">
    <i class="fa fa-headset"></i>
</button>

<div id="helpModal" class="modal">
    <div class="modal-content">
        <span onclick="closeHelpModal()" class="close-btn">&times;</span>
        <h2 style="color:#38bdf8; margin-bottom: 5px; font-weight: 800;"><i class="fa fa-headset"></i> Need Help?</h2>
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">Send a message directly to the Placement Admin. You will receive a reply in your notifications.</p>
        
        <form method="POST">
            <label>Subject / Topic</label>
            <input type="text" name="subject" class="form-input" required placeholder="e.g. Cannot upload resume">
            
            <label>Detailed Message</label>
            <textarea name="message" class="form-input" rows="4" required placeholder="Describe your issue or query here..."></textarea>
            
            <button type="submit" name="submit_ticket" class="btn-submit"><i class="fa fa-paper-plane"></i> Send to Admin</button>
        </form>
    </div>
</div>

<script>
    function openHelpModal() { document.getElementById('helpModal').style.display = 'flex'; }
    function closeHelpModal() { document.getElementById('helpModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('helpModal')) closeHelpModal(); }
</script>

</body>
</html>