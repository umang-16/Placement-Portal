<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: company-login.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];
$msg = "";
$current_date = date('Y-m-d');

/* =========================
   MARK ATTENDANCE LOGIC
   ========================= */
if(isset($_POST['mark_attendance'])){
    $int_id = (int)$_POST['int_id'];
    $status = mysqli_real_escape_string($conn, $_POST['attendance_status']); // 'present' or 'absent'
    
    $update_att = "UPDATE interviews SET attendance='$status' WHERE id=$int_id AND company_id=$company_id";
    if(mysqli_query($conn, $update_att)){
        $msg = "<div class='msg-box success'><i class='fa fa-circle-check'></i> Attendance updated successfully.</div>";
    }
}

/* =========================
   SCHEDULE INTERVIEW Logic
   ========================= */
if(isset($_POST['schedule'])){
    $application_id = (int)$_POST['application_id'];
    $date     = mysqli_real_escape_string($conn, $_POST['date']);
    $time     = mysqli_real_escape_string($conn, $_POST['time']);
    $mode     = mysqli_real_escape_string($conn, $_POST['mode']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    if(strtotime($date) < strtotime(date('Y-m-d'))){
        $msg = "<div class='msg-box error'><i class='fa fa-triangle-exclamation'></i> Error: Past date cannot be selected.</div>";
    } else {
        $sql_interview = "INSERT INTO interviews 
                          (company_id, application_id, interview_date, interview_time, mode, location)
                          VALUES ($company_id, $application_id, '$date', '$time', '$mode', '$location')";
        
        if(mysqli_query($conn, $sql_interview)){
            mysqli_query($conn, "UPDATE applications SET stage='interview' WHERE id=$application_id");

            $notif_data = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT a.student_id, j.title 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = $application_id
            "));
            $s_id = $notif_data['student_id'];
            $job_title = $notif_data['title'];

            $notif_msg = "Your interview for $job_title has been scheduled on $date at $time ($mode). Location: $location";
            mysqli_query($conn, "INSERT INTO notifications (student_id, message, is_read, created_at) 
                                 VALUES ($s_id, '$notif_msg', 0, NOW())");

            // ✨ NEW: Notify the Company module itself for the system's record
            $comp_notif_msg = "You scheduled an interview for student (ID: $s_id) for $job_title on $date at $time.";
            mysqli_query($conn, "INSERT INTO notifications (company_id, message, created_at) VALUES ($company_id, '$comp_notif_msg', NOW())");

            $msg = "<div class='msg-box success'><i class='fa fa-circle-check'></i> Interview scheduled and student notified!</div>";
        }
    }
}

/* =========================
   UPDATE INTERVIEW Logic
   ========================= */
if(isset($_POST['update_interview'])){
    $int_id = (int)$_POST['interview_id'];
    $date   = mysqli_real_escape_string($conn, $_POST['edit_date']);
    $time   = mysqli_real_escape_string($conn, $_POST['edit_time']);
    $mode   = mysqli_real_escape_string($conn, $_POST['edit_mode']);
    $loc    = mysqli_real_escape_string($conn, $_POST['edit_location']);

    $update_sql = "UPDATE interviews SET 
                   interview_date='$date', 
                   interview_time='$time', 
                   mode='$mode', 
                   location='$loc' 
                   WHERE id=$int_id AND company_id=$company_id";

    if(mysqli_query($conn, $update_sql)){
        $res = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT a.student_id, j.title FROM interviews i 
            JOIN applications a ON i.application_id = a.id 
            JOIN jobs j ON a.job_id = j.id WHERE i.id=$int_id"));
        
        $st_id = $res['student_id'];
        $j_title = $res['title'];
        $re_notif = "UPDATE: Your interview for $j_title has been rescheduled to $date at $time ($mode).";
        
        mysqli_query($conn, "INSERT INTO notifications (student_id, message, is_read, created_at) 
                             VALUES ($st_id, '$re_notif', 0, NOW())");

        // ✨ NEW: Notify the Company module itself for the system's record
        $comp_notif_msg = "You rescheduled the interview for student (ID: $st_id) for $j_title to $date at $time.";
        mysqli_query($conn, "INSERT INTO notifications (company_id, message, created_at) VALUES ($company_id, '$comp_notif_msg', NOW())");

        $msg = "<div class='msg-box success'><i class='fa fa-circle-check'></i> Interview updated and student notified!</div>";
    }
}

/* =========================
   FETCH DATA
   ========================= */
$sql = "
SELECT 
  i.id AS interview_id, i.interview_date, i.interview_time, i.mode, i.location, i.attendance,
  s.name AS student_name, j.title AS job_title
FROM interviews i
JOIN applications a ON i.application_id = a.id
JOIN students s ON a.student_id = s.id
JOIN jobs j ON a.job_id = j.id
WHERE i.company_id = $company_id
ORDER BY i.interview_date DESC, i.interview_time ASC
";
$result = mysqli_query($conn, $sql);

$applications = mysqli_query($conn,"
SELECT a.id, s.name AS student_name, j.title AS job_title
FROM applications a
JOIN students s ON a.student_id = s.id
JOIN jobs j ON a.job_id = j.id
WHERE j.company_id = $company_id AND a.status='shortlisted'
");
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule Interviews - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body{background:#0a0f1a; color: #f8fafc;}

    main{padding:40px; max-width:1200px; margin:auto}
    h1{color: #f8fafc; margin-bottom: 25px; font-weight: 800; font-size: 26px;}
    .grid{display:grid;grid-template-columns:1fr 1.2fr;gap:30px}
    
    .card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 30px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);}
    h2{margin-bottom: 20px; color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; font-weight: 800; font-size: 20px;}
    
    label{font-weight:800; margin-top:20px; display:block; color: #cbd5e1; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
    input,select{width:100%; padding:14px; margin-top:8px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); font-size: 14px; outline: none; background: rgba(0,0,0,0.2); color: #fff; font-family: inherit; transition: 0.3s;}
    input:focus, select:focus{border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4);}
    option { background: #0f172a; color: #fff; }
    
    .btn-main{margin-top:30px; width:100%; padding:16px; background:#38bdf8; color:#0a0f1a; border:none; border-radius:8px; cursor:pointer; font-weight:800; font-size: 16px; transition: 0.3s;}
    .btn-main:hover{background:#0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}

    .interview{padding:20px; background:rgba(0,0,0,0.2); border-left:4px solid #38bdf8; border-radius:10px; margin-bottom:15px; position: relative; border-top:1px solid rgba(255,255,255,0.02); border-right:1px solid rgba(255,255,255,0.02); border-bottom:1px solid rgba(255,255,255,0.02); transition: 0.3s;}
    .interview:hover{background:rgba(255,255,255,0.03);}
    .interview strong{color: #f8fafc; font-size: 16px; display: block; margin-bottom: 5px; font-weight: 800;}
    .interview small{display: block; margin-top: 6px; color: #cbd5e1; font-size: 13px;}
    .interview small i { color: #38bdf8; width: 15px;}
    
    .edit-icon{position: absolute; top: 15px; right: 15px; color: #38bdf8; cursor: pointer; background: rgba(56,189,248,0.1); padding: 10px; border-radius: 6px; transition: 0.3s; border: 1px solid rgba(56,189,248,0.2);}
    .edit-icon:hover{background: #38bdf8; color: #0a0f1a;}

    .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; float: right; border: 1px solid transparent;}
    .status-past { background: rgba(255,255,255,0.05); color: #94a3b8; border-color: rgba(255,255,255,0.1);}
    .status-missing { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3);}
    .status-attended { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3);}

    .attendance-btns { margin-top: 15px; display: flex; gap: 10px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 15px; }
    .btn-att { flex:1; padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.3s; border: 1px solid transparent;}
    .btn-present { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: #10b981;} 
    .btn-present:hover { background: #10b981; color: #fff; }
    .btn-absent { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: #ef4444;} 
    .btn-absent:hover { background: #ef4444; color: #fff; }

    .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); justify-content: center; align-items: center; z-index: 2000; }
    .modal-content { background: #0f172a; padding: 35px; border-radius: 16px; width: 450px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1);}
    .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; transition: 0.3s;}
    .close-modal:hover { background: #ef4444; color: #fff; transform: scale(1.1);}

    .msg-box{padding:15px;border-radius:8px;margin-bottom:25px;font-weight:bold; font-size: 14px; display: flex; align-items: center; gap: 8px;}
    .success{background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);}
    .error{background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);}
</style>
<script src="prevent_back.js"></script>
</head>
<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
    <h1><i class="fa fa-calendar-check" style="color: #38bdf8;"></i> Manage Interviews</h1>
    <?= $msg ?>

    <div class="grid">
        <div class="card-glass">
            <h2><i class="fa fa-calendar-plus" style="color: #10b981;"></i> Schedule New Interview</h2>
            <form method="post">
                <label>Select Shortlisted Student</label>
                <select name="application_id" required>
                    <option value="">-- Select Applicant --</option>
                    <?php while($a=mysqli_fetch_assoc($applications)){ ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['student_name']) ?> – <?= htmlspecialchars($a['job_title']) ?></option>
                    <?php } ?>
                </select>

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Date</label>
                        <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Time</label>
                        <input type="time" name="time" required>
                    </div>
                </div>

                <label>Interview Mode</label>
                <select name="mode">
                    <option value="Online">Online (Meet/Zoom)</option>
                    <option value="Offline">Offline (In-Person)</option>
                </select>

                <label>Location / Meeting Link</label>
                <input type="text" name="location" placeholder="Office Address or Google Meet Link" required>
                <button type="submit" name="schedule" class="btn-main"><i class="fa fa-paper-plane"></i> Schedule & Notify Student</button>
            </form>
        </div>

        <div class="card-glass">
            <h2><i class="fa fa-clock-rotate-left" style="color: #f59e0b;"></i> Interview History & Upcoming</h2>
            <div style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                <?php
                if(mysqli_num_rows($result)>0){
                    while($i=mysqli_fetch_assoc($result)){
                        $is_past = (strtotime($i['interview_date']) < strtotime($current_date));
                        ?>
                        <div class='interview' style="<?= $is_past ? 'border-color: rgba(255,255,255,0.05); opacity: 0.8;' : '' ?>">
                            
                            <?php if($i['attendance'] == 'absent'): ?>
                                <span class="status-badge status-missing">Missing</span>
                            <?php elseif($i['attendance'] == 'present'): ?>
                                <span class="status-badge status-attended">Attended</span>
                            <?php elseif($is_past): ?>
                                <span class="status-badge status-past">Past</span>
                            <?php endif; ?>

                            <?php if(!$is_past): ?>
                            <i class="fa fa-edit edit-icon" title="Edit Schedule" onclick="openEditModal('<?= $i['interview_id'] ?>', '<?= $i['interview_date'] ?>', '<?= $i['interview_time'] ?>', '<?= $i['mode'] ?>', '<?= htmlspecialchars(addslashes($i['location'])) ?>')"></i>
                            <?php endif; ?>
                            
                            <strong><?= htmlspecialchars($i['student_name']) ?></strong>
                            <small><i class="fa fa-briefcase"></i> Role: <?= htmlspecialchars($i['job_title']) ?></small>
                            <small><i class="fa fa-calendar-day"></i> <?= date('d M Y', strtotime($i['interview_date'])) ?> at <?= date('h:i A', strtotime($i['interview_time'])) ?> (<?= $i['mode'] ?>)</small>
                            <small><i class="fa fa-location-dot"></i> <?= htmlspecialchars($i['location']) ?></small>

                            <?php if($is_past && empty($i['attendance'])): ?>
                                <div class="attendance-btns">
                                    <form method="post" style="flex:1;">
                                        <input type="hidden" name="int_id" value="<?= $i['interview_id'] ?>">
                                        <input type="hidden" name="attendance_status" value="present">
                                        <button type="submit" name="mark_attendance" class="btn-att btn-present"><i class="fa fa-check"></i> Mark Present</button>
                                    </form>
                                    <form method="post" style="flex:1;">
                                        <input type="hidden" name="int_id" value="<?= $i['interview_id'] ?>">
                                        <input type="hidden" name="attendance_status" value="absent">
                                        <button type="submit" name="mark_attendance" class="btn-att btn-absent"><i class="fa fa-xmark"></i> Mark Absent</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }else{
                    echo "<div style='text-align:center; padding: 40px;'><i class='fa fa-calendar-xmark' style='font-size:40px; color:#64748b; margin-bottom:10px;'></i><p style='color:#94a3b8; font-weight:600;'>No interviews scheduled yet.</p></div>";
                }
                ?>
            </div>
        </div>
    </div>
</main>

<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeEditModal()"><i class="fa fa-times"></i></span>
        <h2 style="color: #f8fafc; font-weight: 800; font-size: 22px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;"><i class="fa fa-pen-to-square" style="color:#38bdf8;"></i> Edit Interview</h2>
        <form method="post">
            <input type="hidden" name="interview_id" id="edit_id">
            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;"><label>Date</label><input type="date" name="edit_date" id="edit_date" min="<?= date('Y-m-d') ?>" required></div>
                <div style="flex: 1;"><label>Time</label><input type="time" name="edit_time" id="edit_time" required></div>
            </div>
            <label>Mode</label>
            <select name="edit_mode" id="edit_mode">
                <option value="Online">Online (Meet/Zoom)</option>
                <option value="Offline">Offline (In-Person)</option>
            </select>
            <label>Location / Link</label>
            <input type="text" name="edit_location" id="edit_location" required>
            <button type="submit" name="update_interview" class="btn-main"><i class="fa fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, date, time, mode, loc) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_time').value = time;
    document.getElementById('edit_mode').value = mode;
    document.getElementById('edit_location').value = loc;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(event) { if (event.target == document.getElementById('editModal')) { closeEditModal(); } }
</script>
</body>
</html>