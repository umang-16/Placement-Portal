<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 COMPANY LOGIN CHECK */
if (!isset($_SESSION['company_id'])) {
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int) $_SESSION['company_id'];

/* 📊 FETCH CURRENT COMPANY NAME */
$company_data = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT company_name FROM companies WHERE id=$company_id LIMIT 1")
);
$c_name = $company_data['company_name'] ?? 'Company';

/* 📝 ADD JOB LOGIC */
if (isset($_POST['add_job'])) {
    $title     = mysqli_real_escape_string($conn, $_POST['title']);
    $desc      = mysqli_real_escape_string($conn, $_POST['description']);
    $skills    = mysqli_real_escape_string($conn, $_POST['skills']);
    $location  = mysqli_real_escape_string($conn, $_POST['location']);
    $salary    = mysqli_real_escape_string($conn, $_POST['salary']);
    $deadline  = mysqli_real_escape_string($conn, $_POST['deadline']); 

    $insert_query = "INSERT INTO jobs (company_id, title, description, skills, location, salary, deadline, status, created_at) 
                     VALUES ($company_id, '$title', '$desc', '$skills', '$location', '$salary', '$deadline', 'pending', NOW())";

    if (mysqli_query($conn, $insert_query)) {
        $notif_msg = mysqli_real_escape_string($conn, "New job post '$title' from $c_name needs approval.");
        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('$notif_msg')");
        $success = "Job posted successfully! Waiting for admin approval.";
    } else {
        $error = "SQL Error: " . mysqli_error($conn);
    }
}

/* 📊 FETCH COMPANY JOBS */
$jobs = mysqli_query($conn, "SELECT id, title, deadline, status, created_at FROM jobs WHERE company_id=$company_id ORDER BY id DESC");
?>

<?php include('auth_check.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post Job - Company Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}

/* MAIN */
main{padding:40px;max-width:1200px;margin:auto}
h1{margin-bottom:25px;color:#f8fafc;font-size:26px; font-weight: 800;}
.grid{display:grid;grid-template-columns:1.2fr .8fr;gap:30px}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 30px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
.card-glass h2{ margin-bottom:20px; color:#f8fafc; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:15px; font-weight: 800; font-size: 20px;}

/* FORM */
label{font-weight:800;margin-top:15px;display:block;color:#cbd5e1; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
input,textarea{ width:100%; padding:14px; margin-top:8px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); outline: none; transition: 0.3s; font-family: inherit; font-size: 14px; background: rgba(0,0,0,0.2); color: #fff;}
input:focus, textarea:focus{ border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4);}
textarea{height:120px;resize:none}
.btn-submit{ margin-top:25px; width:100%; padding:16px; background:#38bdf8; color:#0a0f1a; border:none; border-radius:8px; font-weight:800; font-size: 16px; cursor: pointer; transition: 0.3s; }
.btn-submit:hover{background:#0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}

/* JOB LIST */
.job-item{ padding:20px; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.05); border-left:4px solid #38bdf8; border-radius:10px; margin-bottom:15px; overflow: hidden; transition: 0.3s;}
.job-item:hover{ background:rgba(255,255,255,0.03); }
.job-item strong{font-size:16px;color:#f8fafc; display: block; margin-bottom: 5px;}
.job-item small{display:block;margin-top:5px;color:#94a3b8; font-weight: 600; font-size: 12px;}
.status-badge{ display: inline-block; margin-top: 10px; font-size:10px; padding:4px 10px; border-radius:6px; font-weight:800; text-transform: uppercase; border: 1px solid transparent;}
.status-pending{background:rgba(245, 158, 11, 0.1);color:#fbbf24; border-color: rgba(245, 158, 11, 0.3);}
.status-approved{background:rgba(16, 185, 129, 0.1);color:#34d399; border-color: rgba(16, 185, 129, 0.3);}
.status-rejected{background:rgba(239, 68, 68, 0.1);color:#f87171; border-color: rgba(239, 68, 68, 0.3);}

/* ACTION BUTTONS */
.action-links { float: right; display: flex; gap: 10px; align-items: center; }
.edit-link { background: rgba(56,189,248,0.1); color: #38bdf8; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; border: 1px solid rgba(56,189,248,0.2);}
.edit-link:hover { background: #38bdf8; color: #0a0f1a; }
.delete-link { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; cursor: pointer; transition: 0.3s; border: 1px solid rgba(239, 68, 68, 0.2);}
.delete-link:hover { background: #ef4444; color: #fff; }

/* 🗑️ MODAL STYLES */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 9999; }
.modal-box { background: #0f172a; padding: 40px; width: 420px; border-radius: 16px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); text-align: center; border: 1px solid rgba(255,255,255,0.1); }
.modal-icon { width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 35px; margin: 0 auto 20px; border: 1px solid rgba(239, 68, 68, 0.2); }
.modal-box h2 { color: #f8fafc; margin-bottom: 12px; font-size: 24px; font-weight: 800; }
.modal-box p { font-size: 15px; color: #94a3b8; margin-bottom: 30px; }
.modal-buttons { display: flex; gap: 15px; }
.m-btn { flex: 1; padding: 14px; font-size: 15px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s; text-align: center; border: 1px solid transparent; }
.m-yes { background: #ef4444; color: white; border-color: #dc2626;} .m-yes:hover { background: #dc2626; }
.m-no { background: rgba(255,255,255,0.05); color: #f8fafc; border-color: rgba(255,255,255,0.1);} .m-no:hover { background: rgba(255,255,255,0.1); }

.msg-box{padding:15px;border-radius:8px;margin-bottom:25px;font-weight:bold; font-size: 14px; display: flex; align-items: center; gap: 8px;}
.success{background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);}
.error{background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);}
</style>
<script src="prevent_back.js"></script>
</head>

<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();">

<?php include 'company_header.php'; ?>

<main>
<h1><i class="fa fa-briefcase" style="color:#38bdf8;"></i> Manage Job Openings</h1>

<?php if(isset($success)) echo "<div class='msg-box success'><i class='fa fa-circle-check'></i> $success</div>"; ?>
<?php if(isset($_GET['msg'])) echo "<div class='msg-box success'><i class='fa fa-circle-check'></i> ".htmlspecialchars($_GET['msg'])."</div>"; ?>
<?php if(isset($error)) echo "<div class='msg-box error'><i class='fa fa-triangle-exclamation'></i> $error</div>"; ?>

<div class="grid">

    <div class="card-glass">
        <h2><i class="fa fa-plus-circle" style="color: #10b981;"></i> Post a New Vacancy</h2>
        <form method="POST">
            <label>Job Title</label>
            <input type="text" name="title" placeholder="e.g. Senior Software Engineer" required>
            
            <label>Detailed Job Description</label>
            <textarea name="description" placeholder="Describe the role, responsibilities, and perks..." required></textarea>
            
            <label>Required Skills & Technologies</label>
            <input type="text" name="skills" placeholder="e.g. PHP, React, Node.js" required>
            
            <div style="display:flex;gap:15px">
                <div style="flex:1">
                    <label>Work Location</label>
                    <input type="text" name="location" placeholder="e.g. Mumbai / Remote">
                </div>
                <div style="flex:1">
                    <label>Salary Package (LPA)</label>
                    <input type="text" name="salary" placeholder="e.g. 5.0 LPA - 8.0 LPA">
                </div>
            </div>
            
            <label>Application Deadline</label>
            <input type="date" name="deadline" required> 
            
            <button type="submit" name="add_job" class="btn-submit"><i class="fa fa-paper-plane"></i> Publish Job Post</button>
        </form>
    </div>

    <div class="card-glass">
        <h2><i class="fa fa-list" style="color: #f59e0b;"></i> My Posted Jobs</h2>
        <div style="max-height: 650px; overflow-y: auto; padding-right: 10px;">
        <?php
        if(mysqli_num_rows($jobs)){
          while($j=mysqli_fetch_assoc($jobs)){
            $cls="status-".strtolower($j['status']);
            echo "
            <div class='job-item'>
              <div class='action-links'>
                <a href='edit-job.php?id=".$j['id']."' class='edit-link'><i class='fa fa-edit'></i> Edit</a>
                <a href='javascript:void(0)' class='delete-link' onclick=\"openDeleteModal(".$j['id'].")\"><i class='fa fa-trash'></i></a>
              </div>
              <strong>".htmlspecialchars($j['title'])."</strong>
              <small><i class='fa fa-calendar-plus' style='color:#38bdf8;'></i> Posted: ".date('d M Y',strtotime($j['created_at']))."</small>
              <small style='color:#f87171;'><i class='fa fa-hourglass-end'></i> Deadline: ".date('d M Y',strtotime($j['deadline']))."</small>
              <span class='status-badge $cls'>".$j['status']."</span>
            </div>";
          }
        } else { echo "<div style='text-align:center; padding: 40px;'><i class='fa fa-folder-open' style='font-size:40px; color:#64748b; margin-bottom:10px; display:block;'></i><p style='color:#94a3b8; font-weight:600;'>No jobs posted yet.</p></div>"; }
        ?>
        </div>
    </div>
</div>
</main>

<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete this job post? This action cannot be undone.</p>
        <div class="modal-buttons">
            <a id="confirmDeleteBtn" href="#" class="m-btn m-yes">Yes, Delete</a>
            <a href="javascript:void(0)" onclick="closeDeleteModal()" class="m-btn m-no">Cancel</a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(jobId) {
    document.getElementById('confirmDeleteBtn').href = 'delete-job.php?id=' + jobId;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>
</body>
</html>
