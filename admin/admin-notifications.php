<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 
if(!isset($_SESSION['admin_id'])){ header("Location: admin-login.php"); exit(); }

$msg = "";

/* 🛠️ AUTO-UPDATE DB SCHEME (Fix for created_at error) */
$tables_to_check = ['students', 'companies', 'jobs'];
foreach($tables_to_check as $tbl) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM $tbl LIKE 'created_at'");
    if($check_col && mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE $tbl ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
}

/* ⚙️ 1. SYSTEM SETTINGS LOGIC */
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
if(mysqli_num_rows($table_check) == 0) {
    mysqli_query($conn, "CREATE TABLE system_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value VARCHAR(255) NOT NULL)");
    mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('student_registration', '1'), ('company_registration', '1'), ('current_batch', '2026'), ('maintenance_mode', '0')");
}

if (isset($_POST['save_settings'])) {
    $student_reg = isset($_POST['student_registration']) ? '1' : '0';
    $company_reg = isset($_POST['company_registration']) ? '1' : '0';
    $maint_mode  = isset($_POST['maintenance_mode']) ? '1' : '0';
    $curr_batch  = mysqli_real_escape_string($conn, $_POST['current_batch']);

    mysqli_query($conn, "UPDATE system_settings SET setting_value='$student_reg' WHERE setting_key='student_registration'");
    mysqli_query($conn, "UPDATE system_settings SET setting_value='$company_reg' WHERE setting_key='company_registration'");
    mysqli_query($conn, "UPDATE system_settings SET setting_value='$maint_mode' WHERE setting_key='maintenance_mode'");
    mysqli_query($conn, "UPDATE system_settings SET setting_value='$curr_batch' WHERE setting_key='current_batch'");

    $msg = "<div class='alert-success'><i class='fa fa-circle-check'></i> System Settings updated successfully!</div>";
    mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Admin updated system core settings.')");
}

$settings = [];
$res_settings = mysqli_query($conn, "SELECT * FROM system_settings");
while($row = mysqli_fetch_assoc($res_settings)) { $settings[$row['setting_key']] = $row['setting_value']; }


/* 🔴 2. FETCH ALL PENDING APPROVALS & TICKETS */
$pending_students = mysqli_query($conn, "SELECT id, name, email, department, created_at FROM students WHERE status='pending' ORDER BY id DESC");
$pending_companies = mysqli_query($conn, "SELECT id, company_name, email, created_at FROM companies WHERE status='pending' ORDER BY id DESC");
$pending_jobs = mysqli_query($conn, "SELECT j.id, j.title, c.company_name, j.created_at FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.status='pending' ORDER BY j.id DESC");

// Fetch Pending Applications
$pending_apps_query = "
    SELECT a.id as app_id, a.job_id, s.name as student_name, j.title as job_title, c.company_name 
    FROM applications a 
    JOIN students s ON a.student_id = s.id 
    JOIN jobs j ON a.job_id = j.id 
    JOIN companies c ON j.company_id = c.id 
    WHERE a.status='pending' 
    ORDER BY a.id DESC";
$pending_apps = mysqli_query($conn, $pending_apps_query);

// Fetch Pending Helpdesk Tickets
$pending_tickets_query = "
    SELECT t.id, t.subject, t.sender_type, 
           CASE WHEN t.sender_type = 'student' THEN s.name ELSE c.company_name END AS sender_name
    FROM helpdesk_tickets t
    LEFT JOIN students s ON t.sender_type = 'student' AND t.sender_id = s.id
    LEFT JOIN companies c ON t.sender_type = 'company' AND t.sender_id = c.id
    WHERE t.status='pending' 
    ORDER BY t.id ASC";
$pending_tickets = mysqli_query($conn, $pending_tickets_query);

$total_pending = mysqli_num_rows($pending_students) + mysqli_num_rows($pending_companies) + mysqli_num_rows($pending_jobs) + mysqli_num_rows($pending_apps) + mysqli_num_rows($pending_tickets);


/* 🟢 3. BUILD LIVE SYSTEM ACTIVITY TIMELINE */
$timeline = [];
$q_apps = mysqli_query($conn, "SELECT s.name, j.title, a.applied_at FROM applications a JOIN students s ON a.student_id=s.id JOIN jobs j ON a.job_id=j.id ORDER BY a.id DESC LIMIT 15");
if($q_apps) {
    while($r = mysqli_fetch_assoc($q_apps)){
        $timeline[] = ['time' => $r['applied_at'], 'title' => 'New Application', 'desc' => "<b style='color:#38bdf8;'>{$r['name']}</b> applied for <b>{$r['title']}</b>.", 'icon' => 'fa-paper-plane', 'color' => '#38bdf8'];
    }
}
if($pending_jobs) {
    mysqli_data_seek($pending_jobs, 0);
    while($r = mysqli_fetch_assoc($pending_jobs)){
        $timeline[] = ['time' => $r['created_at'], 'title' => 'New Job Posted', 'desc' => "<b style='color:#10b981;'>{$r['company_name']}</b> posted a vacancy: <b>{$r['title']}</b>.", 'icon' => 'fa-briefcase', 'color' => '#10b981'];
    }
}
$q_alerts = mysqli_query($conn, "SELECT message, created_at FROM admin_notifications ORDER BY id DESC LIMIT 15");
if($q_alerts) {
    while($r = mysqli_fetch_assoc($q_alerts)){
        $timeline[] = ['time' => $r['created_at'], 'title' => 'System Alert', 'desc' => htmlspecialchars($r['message']), 'icon' => 'fa-server', 'color' => '#f59e0b'];
    }
}
usort($timeline, function($a, $b) { return strtotime($b['time']) - strtotime($a['time']); });
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Command Center - Alerts & Settings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}

main{padding:30px;max-width:1150px;margin:auto; display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px;}
.section-title { font-size: 16px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;}

/* SETTINGS CARD */
.settings-panel { grid-column: 1 / -1; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); margin-bottom: 10px;}
.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;}
.setting-box { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); padding: 15px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s;}
.setting-box:hover { border-color: rgba(56, 189, 248, 0.3); }
.setting-info h3 { font-size: 15px; color: #f8fafc; margin-bottom: 4px; }
.setting-info p { font-size: 12px; color: #94a3b8; }

.switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0;}
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #1e293b; transition: .4s; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1);}
.slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: #94a3b8; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #10b981; border-color: #10b981;}
input:checked + .slider:before { transform: translateX(20px); background-color: #fff; }

.btn-save { padding: 10px 20px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 14px;}
.btn-save:hover { background: #0ea5e9; }
select.setting-select { padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: #0f172a; color: #fff; outline: none; font-weight: bold; font-size: 13px;}
.alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 12px 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 15px; font-weight: bold; font-size: 14px; display: flex; align-items: center; gap: 8px;}

/* PENDING CARDS */
.pending-section { display: flex; flex-direction: column; gap: 15px; }
.notif-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 15px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s;}
.notif-card:hover { background: rgba(255,255,255,0.04); transform: translateX(5px); }

/* Color coding for different pending types */
.notif-card.type-ticket { border-left: 4px solid #ef4444; } 
.notif-card.type-student { border-left: 4px solid #10b981; }
.notif-card.type-company { border-left: 4px solid #f59e0b; }
.notif-card.type-job { border-left: 4px solid #8b5cf6; }
.notif-card.type-application { border-left: 4px solid #3b82f6; } 

.notif-content h4 { font-size: 15px; color: #f8fafc; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;}
.notif-content p { font-size: 12px; color: #94a3b8; }
.btn-action { background: rgba(56,189,248,0.1); color: #38bdf8; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; border: 1px solid rgba(56,189,248,0.2); transition: 0.3s;}
.btn-action:hover { background: #38bdf8; color: #0a0f1a; }
.btn-ticket-action { background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3); }
.btn-ticket-action:hover { background: #ef4444; color: #fff; }

.empty-state { text-align: center; color: #64748b; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px; font-style: italic; border: 1px dashed rgba(255,255,255,0.05); font-size: 13px;}

/* TIMELINE UI */
.timeline-container { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 25px; max-height: 600px; overflow-y: auto; }
.timeline-item { display: flex; gap: 15px; margin-bottom: 20px; position: relative; }
.timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 19px; top: 40px; bottom: -25px; width: 2px; background: rgba(255,255,255,0.05); }
.t-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0; color: #0a0f1a; z-index: 1;}
.t-content { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.03); flex: 1; }
.t-content h4 { font-size: 13px; color: #f8fafc; margin-bottom: 4px; }
.t-content p { font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-bottom: 6px; }
.t-content span { font-size: 11px; color: #64748b; font-weight: bold; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="settings-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; margin-bottom: 10px;">
            <h2 style="color: #38bdf8; font-size: 20px;"><i class="fa fa-sliders-h"></i> System Controls & Settings</h2>
            <form method="POST" id="settingsForm"><button type="submit" name="save_settings" class="btn-save"><i class="fa fa-save"></i> Save Changes</button></form>
        </div>
        <?= $msg ?>
        
        <div class="settings-grid">
            <div class="setting-box">
                <div class="setting-info">
                    <h3><i class="fa fa-user-plus" style="color:#38bdf8;"></i> Student Signups</h3>
                    <p>Allow new students to register from homepage.</p>
                </div>
                <label class="switch"><input type="checkbox" form="settingsForm" name="student_registration" <?= ($settings['student_registration'] == '1') ? 'checked' : '' ?>><span class="slider"></span></label>
            </div>
            
            <div class="setting-box">
                <div class="setting-info">
                    <h3><i class="fa fa-building" style="color:#f59e0b;"></i> Company Signups</h3>
                    <p>Allow new HRs to register on the platform.</p>
                </div>
                <label class="switch"><input type="checkbox" form="settingsForm" name="company_registration" <?= ($settings['company_registration'] == '1') ? 'checked' : '' ?>><span class="slider"></span></label>
            </div>

            <div class="setting-box">
                <div class="setting-info">
                    <h3><i class="fa fa-graduation-cap" style="color:#10b981;"></i> Current Academic Batch</h3>
                    <p>Set active placement year for reporting.</p>
                </div>
                <select form="settingsForm" name="current_batch" class="setting-select">
                    <option value="2025" <?= ($settings['current_batch'] == '2025') ? 'selected' : '' ?>>Batch 2025</option>
                    <option value="2026" <?= ($settings['current_batch'] == '2026') ? 'selected' : '' ?>>Batch 2026</option>
                    <option value="2027" <?= ($settings['current_batch'] == '2027') ? 'selected' : '' ?>>Batch 2027</option>
                </select>
            </div>

            <div class="setting-box">
                <div class="setting-info">
                    <h3><i class="fa fa-triangle-exclamation" style="color:#ef4444;"></i> Maintenance Mode</h3>
                    <p>Block access to everyone except Admin.</p>
                </div>
                <label class="switch"><input type="checkbox" form="settingsForm" name="maintenance_mode" <?= ($settings['maintenance_mode'] == '1') ? 'checked' : '' ?>><span class="slider"></span></label>
            </div>
        </div>
    </div>

    <div class="pending-section">
        <div class="section-title"><i class="fa fa-clock" style="color: #f59e0b;"></i> Action Required (Pending)</div>
        
        <?php if(mysqli_num_rows($pending_tickets) > 0): while($t = mysqli_fetch_assoc($pending_tickets)): ?>
            <div class="notif-card type-ticket">
                <div class="notif-content">
                    <h4><i class="fa fa-headset" style="color: #ef4444;"></i> Support Ticket: <?= htmlspecialchars($t['subject']) ?></h4>
                    <p>From <b style="color: #cbd5e1; text-transform: capitalize;"><?= htmlspecialchars($t['sender_type']) ?></b>: <?= htmlspecialchars($t['sender_name']) ?></p>
                </div>
                <a href="admin-helpdesk.php" class="btn-action btn-ticket-action"><i class="fa fa-reply"></i> Reply & Resolve</a>
            </div>
        <?php endwhile; endif; ?>

        <?php if(mysqli_num_rows($pending_apps) > 0): while($app = mysqli_fetch_assoc($pending_apps)): ?>
            <div class="notif-card type-application">
                <div class="notif-content">
                    <h4><i class="fa fa-file-signature" style="color: #3b82f6;"></i> Application: <?= htmlspecialchars($app['student_name']) ?></h4>
                    <p>Applied for <b style="color: #cbd5e1;"><?= htmlspecialchars($app['job_title']) ?></b> at <?= htmlspecialchars($app['company_name']) ?></p>
                </div>
                <a href="admin-job-applicants.php?job_id=<?= $app['job_id'] ?>" class="btn-action">Review</a>
            </div>
        <?php endwhile; endif; ?>

        <?php mysqli_data_seek($pending_students, 0); if(mysqli_num_rows($pending_students) > 0): while($s = mysqli_fetch_assoc($pending_students)): ?>
            <div class="notif-card type-student">
                <div class="notif-content">
                    <h4><i class="fa fa-user" style="color: #10b981;"></i> Student: <?= htmlspecialchars($s['name']) ?></h4>
                    <p><?= htmlspecialchars($s['email']) ?></p>
                </div>
                <a href="admin-students.php" class="btn-action">Verify</a>
            </div>
        <?php endwhile; endif; ?>

        <?php mysqli_data_seek($pending_companies, 0); if(mysqli_num_rows($pending_companies) > 0): while($c = mysqli_fetch_assoc($pending_companies)): ?>
            <div class="notif-card type-company">
                <div class="notif-content">
                    <h4><i class="fa fa-building" style="color: #f59e0b;"></i> Company: <?= htmlspecialchars($c['company_name']) ?></h4>
                    <p><?= htmlspecialchars($c['email']) ?></p>
                </div>
                <a href="admin-companies.php" class="btn-action">Verify</a>
            </div>
        <?php endwhile; endif; ?>

        <?php mysqli_data_seek($pending_jobs, 0); if(mysqli_num_rows($pending_jobs) > 0): while($j = mysqli_fetch_assoc($pending_jobs)): ?>
            <div class="notif-card type-job">
                <div class="notif-content">
                    <h4><i class="fa fa-briefcase" style="color: #8b5cf6;"></i> Job: <?= htmlspecialchars($j['title']) ?></h4>
                    <p><?= htmlspecialchars($j['company_name']) ?></p>
                </div>
                <a href="admin-jobs.php" class="btn-action">Verify</a>
            </div>
        <?php endwhile; endif; ?>

        <?php if($total_pending == 0): echo "<div class='empty-state'><i class='fa fa-check-circle' style='font-size:24px; color:#10b981; display:block; margin-bottom:10px;'></i> All caught up! No pending approvals.</div>"; endif; ?>
    </div>

    <div>
        <div class="section-title"><i class="fa fa-bolt" style="color: #38bdf8;"></i> Live System Activity Feed</div>
        <div class="timeline-container">
            <?php if(!empty($timeline)): ?>
                <?php foreach(array_slice($timeline, 0, 25) as $log): ?>
                    <div class="timeline-item">
                        <div class="t-icon" style="background: <?= $log['color'] ?>;"><i class="fa <?= $log['icon'] ?>"></i></div>
                        <div class="t-content">
                            <h4 style="color: <?= $log['color'] ?>;"><?= $log['title'] ?></h4>
                            <p><?= $log['desc'] ?></p>
                            <span><i class="fa fa-clock"></i> <?= date('d M Y, h:i A', strtotime($log['time'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class='empty-state'>No recent activity found in the system.</div>
            <?php endif; ?>
        </div>
    </div>

</main>
</body>
</html>