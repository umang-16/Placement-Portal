<?php
session_start();
require_once __DIR__ . "/../db.php";

if (!isset($_SESSION['company_id'])) {
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];
$job_id = (int)$_GET['id'];

/* 🚀 FETCH REAL DATA FROM DB */
$job_query = mysqli_query($conn, "SELECT * FROM jobs WHERE id=$job_id AND company_id=$company_id");
$job = mysqli_fetch_assoc($job_query);

if (!$job) { die("<h2 style='color:#ef4444; text-align:center; margin-top:50px;'>Job not found or access denied!</h2>"); }

/* 🚀 UPDATE LOGIC */
if (isset($_POST['update_job'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $skills = mysqli_real_escape_string($conn, $_POST['skills']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $salary = mysqli_real_escape_string($conn, $_POST['salary']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);

    $update_sql = "UPDATE jobs SET 
                   title='$title', description='$desc', skills='$skills', 
                   location='$location', salary='$salary', deadline='$deadline' 
                   WHERE id=$job_id AND company_id=$company_id";

    if (mysqli_query($conn, $update_sql)) {
        header("Location: post-job.php?msg=Job Updated Successfully");
        exit();
    }
}
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Job - Company Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body{background:#0a0f1a; color: #f8fafc;}

    main { padding: 40px; display: flex; justify-content: center; }
    
    .card-glass { 
        background: rgba(255, 255, 255, 0.02); 
        backdrop-filter: blur(15px); 
        padding: 40px; border-radius: 16px; 
        border: 1px solid rgba(255, 255, 255, 0.05); 
        box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        width: 100%; max-width: 700px;
    }
    
    h2 { color: #f8fafc; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; margin-bottom: 20px; font-size: 24px; font-weight: 800;}
    
    label { font-weight: 800; display: block; margin-top: 20px; color: #cbd5e1; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
    
    input, textarea { 
        width: 100%; padding: 14px; margin-top: 8px; 
        border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); 
        outline: none; background: rgba(0,0,0,0.2); color: #fff; 
        transition: 0.3s; font-family: inherit; font-size: 14px;
    }
    input:focus, textarea:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4); }
    textarea { height: 120px; resize: none; }

    .btn-submit { 
        width: 100%; padding: 16px; margin-top: 30px; 
        background: #38bdf8; color: #0a0f1a; border: none; 
        border-radius: 8px; font-weight: 800; font-size: 16px; 
        cursor: pointer; transition: 0.3s; 
    }
    .btn-submit:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }

    .cancel-link { display: block; text-align: center; margin-top: 20px; color: #94a3b8; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.3s; }
    .cancel-link:hover { color: #ef4444; }
</style>
<script src="prevent_back.js"></script>
</head>
<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();">

<?php 
// To keep "Post Job" active in header
$_SERVER['PHP_SELF'] = 'post-job.php'; 
include 'company_header.php'; 
?>

<main>
    <div class="card-glass">
        <h2><i class="fa fa-pen-to-square" style="color: #38bdf8;"></i> Edit Job Post</h2>
        
        <form method="POST">
            <label>Job Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>

            <label>Detailed Description</label>
            <textarea name="description" required><?= htmlspecialchars($job['description']) ?></textarea>

            <label>Required Skills</label>
            <input type="text" name="skills" value="<?= htmlspecialchars($job['skills']) ?>">

            <div style="display:flex;gap:15px">
                <div style="flex:1">
                    <label>Work Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($job['location']) ?>">
                </div>
                <div style="flex:1">
                    <label>Salary Package</label>
                    <input type="text" name="salary" value="<?= htmlspecialchars($job['salary']) ?>">
                </div>
            </div>

            <label>Application Deadline</label>
            <input type="date" name="deadline" value="<?= $job['deadline'] ?>" required>

            <button type="submit" name="update_job" class="btn-submit"><i class="fa fa-save"></i> Save Changes</button>
            <a href="post-job.php" class="cancel-link">Cancel Edit</a>
        </form>
    </div>
</main>

</body>
</html>
