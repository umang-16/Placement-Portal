<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if(!isset($_SESSION['admin_id'])){ header("Location: admin-login.php"); exit(); }

/* 📝 UPDATE JOB DETAILS */
if(isset($_POST['update_job'])){
    $j_id = (int)$_POST['job_id'];
    $j_title = mysqli_real_escape_string($conn, $_POST['title']);
    $j_location = mysqli_real_escape_string($conn, $_POST['location']);
    $j_salary = mysqli_real_escape_string($conn, $_POST['salary']);
    $j_desc = mysqli_real_escape_string($conn, $_POST['description']);
    $j_skills = mysqli_real_escape_string($conn, $_POST['skills']);

    $sql_update = "UPDATE jobs SET title = '$j_title', location = '$j_location', salary = '$j_salary', description = '$j_desc', skills = '$j_skills' WHERE id = $j_id";
    mysqli_query($conn, $sql_update);
}

/* ✅ UPDATE JOB STATUS LOGIC */
if(isset($_GET['action'], $_GET['id'])){
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if($action === 'approve') {
        mysqli_query($conn, "UPDATE jobs SET status='approved' WHERE id=$id");
        $job_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id, title FROM jobs WHERE id=$id"));
        if($job_data) {
            $msg = mysqli_real_escape_string($conn, "✅ Your job posting '{$job_data['title']}' has been approved by the Admin.");
            mysqli_query($conn, "INSERT INTO notifications (company_id, message, created_at) VALUES ({$job_data['company_id']}, '$msg', NOW())");
        }
    }
    if($action === 'deny') {
        mysqli_query($conn, "UPDATE jobs SET status='rejected' WHERE id=$id");
        $job_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_id, title FROM jobs WHERE id=$id"));
        if($job_data) {
            $msg = mysqli_real_escape_string($conn, "❌ Your job posting '{$job_data['title']}' has been rejected by the Admin.");
            mysqli_query($conn, "INSERT INTO notifications (company_id, message, created_at) VALUES ({$job_data['company_id']}, '$msg', NOW())");
        }
    }
    header("Location: admin-jobs.php");
    exit();
}

$sql = "SELECT j.*, c.company_name FROM jobs j JOIN companies c ON j.company_id = c.id ORDER BY j.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Jobs</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1200px;margin:auto}
h1 { color: #f8fafc; font-weight: 800; margin-bottom: 25px; }

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
td { color: #cbd5e1; }

.status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
.approved { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
.pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.rejected { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

.btn { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; cursor: pointer; border: none; transition: 0.2s;}
.btn-view { background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.1); }
.btn-view:hover { background: rgba(255, 255, 255, 0.1); }
.btn-applicants { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
.btn-applicants:hover { background: rgba(56, 189, 248, 0.2); }

/* Modals */
.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: #0f172a; padding: 30px; width: 500px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
.modal h2 { color: #38bdf8; margin-bottom: 5px; font-size: 22px; font-weight: 800; }
.modal p { color: #cbd5e1; font-size: 14px; margin-bottom: 10px; line-height: 1.5; }
.modal-footer { margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end; }

.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 13px; font-weight: 600; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; font-family: inherit;}
.form-group input:focus, .form-group textarea:focus { border-color: #38bdf8; }
.save-btn { width: 100%; padding: 12px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <h1>💼 Job Postings</h1>

    <div class="card-glass">
        <table>
            <thead>
                <tr>
                    <th>Job Role</th>
                    <th>Company</th>
                    <th>Location / Salary</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $jobData = htmlspecialchars(json_encode([
                            'id' => $row['id'], 'title' => $row['title'], 'company' => $row['company_name'],
                            'location' => $row['location'], 'salary' => $row['salary'],
                            'desc' => nl2br(htmlspecialchars($row['description'])), 'req' => nl2br(htmlspecialchars($row['skills'])),
                            'raw_desc' => $row['description'], 'raw_req' => $row['skills'], 'status' => strtolower($row['status'])
                        ]));
                    ?>
                        <tr>
                            <td><b style="color:#f8fafc; font-size:15px;"><?= htmlspecialchars($row['title']) ?></b></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                            <td>
                                <div><i class="fa fa-location-dot" style="color:#94a3b8;"></i> <?= htmlspecialchars($row['location']) ?></div>
                                <div style="margin-top:4px;"><i class="fa fa-wallet" style="color:#10b981;"></i> <?= htmlspecialchars($row['salary']) ?></div>
                            </td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn btn-view" onclick='viewJob(<?= $jobData ?>)'><i class="fa fa-eye"></i> Details</button>
                                <?php if($row['status'] == 'approved'): ?>
                                    <a href="admin-job-applicants.php?job_id=<?= $row['id'] ?>" class="btn btn-applicants"><i class="fa fa-users"></i> Applicants</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:30px;">No jobs posted yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="jobModal" class="modal">
    <div class="modal-content">
        <h2 id="m_title"></h2>
        <p><b style="color:#f8fafc;">Company:</b> <span id="m_company"></span></p>
        <p><b style="color:#f8fafc;">Location:</b> <span id="m_location"></span> | <b style="color:#10b981;">Salary:</b> <span id="m_salary"></span></p>
        <h4 style="margin-top:15px; color:#38bdf8;">Description</h4>
        <p id="m_desc" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);"></p>
        <h4 style="margin-top:10px; color:#38bdf8;">Skills Required</h4>
        <p id="m_req" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);"></p>
        <div class="modal-footer" id="m_footer"></div>
    </div>
</div>

<div id="editJobModal" class="modal">
    <div class="modal-content">
        <span onclick="closeEditModal()" style="float:right; cursor:pointer; color:#ef4444; font-size:20px;">&times;</span>
        <h2 style="color: #f8fafc;">Edit Job Details</h2>
        <form method="POST">
            <input type="hidden" name="job_id" id="edit_id">
            <div class="form-group"><label>Job Title</label><input type="text" name="title" id="edit_title" required></div>
            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;"><label>Location</label><input type="text" name="location" id="edit_location"></div>
                <div class="form-group" style="flex:1;"><label>Salary</label><input type="text" name="salary" id="edit_salary"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" id="edit_desc" rows="3" required></textarea></div>
            <div class="form-group"><label>Skills</label><input type="text" name="skills" id="edit_skills" required></div>
            <button type="submit" name="update_job" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
let currentJobData = null;
function viewJob(job) {
    currentJobData = job;
    document.getElementById('m_title').innerText = job.title;
    document.getElementById('m_company').innerText = job.company;
    document.getElementById('m_location').innerText = job.location;
    document.getElementById('m_salary').innerText = job.salary;
    document.getElementById('m_desc').innerHTML = job.desc;
    document.getElementById('m_req').innerHTML = job.req;

    let footer = document.getElementById('m_footer');
    let editBtn = `<button onclick="openEditModal()" class="btn" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8;">Edit Details</button>`;
    let closeBtn = `<button onclick="closeModal()" class="btn" style="background: rgba(255, 255, 255, 0.1); color: #fff;">Close</button>`;

    if(job.status === 'pending') {
        footer.innerHTML = `<a href="?action=approve&id=${job.id}" class="btn" style="background: #10b981; color: #fff;">Approve</a> <a href="?action=deny&id=${job.id}" class="btn" style="background: #ef4444; color: #fff;">Deny</a> ${editBtn} ${closeBtn}`;
    } else { footer.innerHTML = `${editBtn} ${closeBtn}`; }
    
    document.getElementById('jobModal').style.display = 'flex';
}
function openEditModal() {
    closeModal(); 
    document.getElementById('edit_id').value = currentJobData.id;
    document.getElementById('edit_title').value = currentJobData.title;
    document.getElementById('edit_location').value = currentJobData.location;
    document.getElementById('edit_salary').value = currentJobData.salary;
    document.getElementById('edit_desc').value = currentJobData.raw_desc; 
    document.getElementById('edit_skills').value = currentJobData.raw_req; 
    document.getElementById('editJobModal').style.display = 'flex';
}
function closeModal() { document.getElementById('jobModal').style.display = 'none'; }
function closeEditModal() { document.getElementById('editJobModal').style.display = 'none'; }
</script>
</body>
</html>