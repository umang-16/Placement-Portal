<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];

/* 🎫 HELPDESK TICKET SUBMISSION LOGIC */
if(isset($_POST['submit_ticket'])){
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $sql_ticket = "INSERT INTO helpdesk_tickets (sender_type, sender_id, subject, message, status, created_at) 
                   VALUES ('company', $company_id, '$subject', '$message', 'pending', NOW())";
    
    if(mysqli_query($conn, $sql_ticket)){
        $ticket_msg = "✅ Your query has been submitted! Admin will reply soon in your notifications.";
    } else {
        $ticket_err = "❌ Error submitting query. Please try again.";
    }
}

/* 🏢 COMPANY DATA */
$company = mysqli_fetch_assoc(mysqli_query($conn,"SELECT company_name, email, logo FROM companies WHERE id = $company_id"));

function getCompanyInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    if (count($words) >= 2) { $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)); } 
    else { $initials = strtoupper(substr($name, 0, 2)); }
    return $initials;
}

$company_name = $company['company_name'] ?? 'Company Name';
$logo_file = $company['logo'] ?? '';

/* 📊 STATS */
$jobs_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM jobs WHERE company_id = $company_id AND status='approved'"))['total'];

$applicants_count_query = "SELECT COUNT(*) AS total FROM applications JOIN jobs ON applications.job_id = jobs.id WHERE jobs.company_id = $company_id AND applications.admin_status = 'selected' AND applications.status NOT IN ('pending', 'withdrawn')";
$applicants_count = mysqli_fetch_assoc(mysqli_query($conn, $applicants_count_query))['total'];

$pending_count_query = "SELECT COUNT(*) AS total FROM applications JOIN jobs ON applications.job_id = jobs.id WHERE jobs.company_id = $company_id AND applications.admin_status = 'selected' AND applications.status = 'pending'";
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, $pending_count_query))['total'];

$interviews_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS total FROM interviews WHERE company_id = $company_id"))['total'];

/* 📝 RECENT JOBS */
$jobs = mysqli_query($conn,"SELECT title, status FROM jobs WHERE company_id = $company_id ORDER BY id DESC LIMIT 5");
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Dashboard - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}

main{padding:40px; max-width: 1200px; margin: auto;}
h1 { color: #f8fafc; font-weight: 800; font-size: 26px; margin-bottom: 5px;}

.stats{display:flex;gap:20px;margin-top:25px}
.stat-box{ flex:1; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding:25px; border-radius:16px; text-align:center; border: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 4px solid #38bdf8; transition: 0.3s; }
.stat-box:hover{transform: translateY(-5px); background: rgba(255, 255, 255, 0.04); box-shadow: 0 10px 25px rgba(0,0,0,0.3);}
.stat-box h2{color:#f8fafc; font-size: 36px; margin-bottom: 5px; font-weight: 800;}
.stat-box p{color: #94a3b8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;}
.stat-icon { color:#38bdf8; font-size: 32px; margin-bottom: 12px; text-shadow: 0 0 15px rgba(56, 189, 248, 0.4);}

.dashboard{ display:grid; grid-template-columns:2fr 1fr; gap:25px; margin-top:30px; }
.card-glass{ background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding:25px; border-radius:16px; border: 1px solid rgba(255, 255, 255, 0.05); }
.card-glass h2{ font-size: 18px; margin-bottom: 20px; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; }

.list li{ list-style:none; margin-bottom:12px; padding:15px; background: rgba(0,0,0,0.2); border-left:4px solid #38bdf8; border-radius:8px; font-size: 15px; color: #cbd5e1; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255,255,255,0.02); transition: 0.3s;}
.list li:hover { background: rgba(255,255,255,0.03); transform: translateX(5px); }

/* 👤 PROFILE LOGO */
.profile-box{text-align:center; padding: 25px 20px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);}
.logo-circle { width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold; color: #0a0f1a; background: #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.3); border: 4px solid #0f172a; overflow: hidden; }
.logo-img { width: 100%; height: 100%; object-fit: cover; }
.profile-box h3 { color: #f8fafc; font-size: 20px; margin-bottom: 5px; }
.profile-box p { color: #94a3b8; font-size: 14px; }

.action-links li a { display: block; padding: 15px; background: rgba(255,255,255,0.03); margin-bottom: 10px; border-radius: 8px; text-decoration: none; color: #cbd5e1; font-weight: 600; transition: 0.3s; border: 1px solid rgba(255,255,255,0.05);}
.action-links li a:hover { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: #38bdf8; transform: translateX(5px); }
.action-links li a i { width: 20px; color: #38bdf8;}

/* 🎧 FLOATING HELPDESK BUTTON & MODAL */
.help-fab { position: fixed; bottom: 30px; right: 30px; background: #f59e0b; color: #0a0f1a; width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4); cursor: pointer; transition: 0.3s; z-index: 999; border: none; }
.help-fab:hover { transform: scale(1.1) translateY(-5px); background: #fbbf24; }

.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: #0f172a; padding: 35px; border-radius: 16px; width: 450px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); }
.close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; transition: 0.3s; }
.close-btn:hover { background: #ef4444; color: #fff; transform: scale(1.1); }

.modal-content label { display: block; margin-top: 15px; font-weight: 800; font-size: 12px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; }
.form-input { width: 100%; padding: 14px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; margin-top: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.3s; }
.form-input:focus { border-color: #f59e0b; box-shadow: 0 0 15px rgba(245, 158, 11, 0.2); }
.btn-submit { width: 100%; background: #f59e0b; color: #0a0f1a; padding: 15px; border: none; border-radius: 8px; margin-top: 25px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; }
.btn-submit:hover { background: #fbbf24; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3); }
</style>
<script src="prevent_back.js"></script>
</head>

<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
<h1>Welcome back, <span style="color: #38bdf8;"><?= htmlspecialchars($company_name); ?></span> 👋</h1>
<p style="color: #94a3b8; margin-bottom: 20px;">Manage your job postings and applicants efficiently.</p>

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

<div class="stats">
  <div class="stat-box">
    <i class="fa fa-briefcase stat-icon"></i>
    <h2><?= $jobs_count ?></h2>
    <p>Active Job Postings</p>
  </div>
  <div class="stat-box" style="border-bottom-color: #f59e0b;">
    <i class="fa fa-clock stat-icon" style="color: #f59e0b;"></i>
    <h2><?= $pending_count ?></h2>
    <p>Pending Applications</p>
  </div>
  <div class="stat-box" style="border-bottom-color: #10b981;">
    <i class="fa fa-users stat-icon" style="color: #10b981;"></i>
    <h2><?= $applicants_count ?></h2>
    <p>Shortlisted Applicants</p>
  </div>
  <div class="stat-box" style="border-bottom-color: #8b5cf6;">
    <i class="fa fa-calendar-check stat-icon" style="color: #8b5cf6;"></i>
    <h2><?= $interviews_count ?></h2>
    <p>Scheduled Interviews</p>
  </div>
</div>

<div class="dashboard">
  <div class="card-glass">
    <h2><i class="fa fa-clock-rotate-left" style="color: #38bdf8;"></i> Recently Posted Jobs</h2>
    <ul class="list">
      <?php
      if(mysqli_num_rows($jobs) > 0){
        while($j = mysqli_fetch_assoc($jobs)){
            $status_color = ($j['status'] == 'approved') ? '#10b981' : '#f59e0b';
            echo "<li><b style='color:#f8fafc;'>{$j['title']}</b> <span style='color:$status_color; font-weight:800; font-size: 11px; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);'>".strtoupper($j['status'])."</span></li>";
        }
      }else{
        echo "<li style='text-align:center; color:#64748b; border-left:none; display:block; padding: 30px; background: transparent; border: 1px dashed rgba(255,255,255,0.1);'><i class='fa fa-folder-open' style='font-size: 30px; margin-bottom: 10px; display:block;'></i>No jobs posted yet.</li>";
      }
      ?>
    </ul>
  </div>

  <div class="card-glass">
    <div class="profile-box">
      <div class="logo-circle">
          <?php if (!empty($logo_file) && file_exists("../uploads/company_logos/" . $logo_file)): ?>
              <img src="../uploads/company_logos/<?= $logo_file ?>" class="logo-img" alt="Logo">
          <?php else: ?>
              <?= getCompanyInitials($company_name) ?>
          <?php endif; ?>
      </div>
      <h3><?= htmlspecialchars($company_name) ?></h3>
      <p><?= htmlspecialchars($company['email']) ?></p>
    </div>

    <h3 style="margin-top:25px; font-size: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;"><i class="fa fa-bolt" style="color: #f59e0b;"></i> Quick Actions</h3>
    <ul class="action-links" style="list-style: none; padding: 0;">
      <li><a href="post-job.php"><i class="fa fa-plus-circle"></i> Create New Job Post</a></li>
      <li><a href="applicants.php"><i class="fa fa-user-tie"></i> Review Applicants</a></li>
    </ul>
  </div>
</div>
</main>

<button class="help-fab" onclick="openHelpModal()" title="Need Help? Contact Admin">
    <i class="fa fa-headset"></i>
</button>

<div id="helpModal" class="modal">
    <div class="modal-content">
        <span onclick="closeHelpModal()" class="close-btn">&times;</span>
        <h2 style="color:#f59e0b; margin-bottom: 5px; font-weight: 800;"><i class="fa fa-headset"></i> Company Support</h2>
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">Facing issues with job posts or applicants? Ask the Admin.</p>
        
        <form method="POST">
            <label>Subject / Topic</label>
            <input type="text" name="subject" class="form-input" required placeholder="e.g. Need to edit approved job">
            
            <label>Detailed Message</label>
            <textarea name="message" class="form-input" rows="4" required placeholder="Describe your issue here..."></textarea>
            
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
