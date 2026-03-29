<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: company-login.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];

/* ✅ UPDATE STATUS (Selected/Rejected) */
if(isset($_GET['app_id']) && isset($_GET['status'])){
    $app_id = (int)$_GET['app_id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);

    if(in_array($status, ['selected','rejected'])){
        mysqli_query($conn,
            "UPDATE applications 
             SET status='$status' 
             WHERE id=$app_id"
        );
    }
    header("Location: applicants.php");
    exit();
}

/* 📝 FETCH APPLICANTS */
$sql = "
SELECT 
    applications.id AS app_id,
    students.name,
    students.email,
    jobs.title,
    applications.status,
    applications.offer_letter,
    applications.resume,
    applications.skills,
    applications.match_score
FROM applications
JOIN students ON applications.student_id = students.id
JOIN jobs ON applications.job_id = jobs.id
WHERE jobs.company_id = $company_id 
AND applications.admin_status = 'selected'
AND applications.status != 'withdrawn' 
ORDER BY applications.match_score DESC, applications.id DESC
";

$result = mysqli_query($conn, $sql);
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Applicants - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}

main{padding:40px; max-width:1200px; margin:auto}
h1{color: #f8fafc; margin-bottom: 25px; font-weight: 800; font-size: 24px; display: flex; align-items: center; gap: 10px;}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }

table{width:100%; border-collapse:collapse;}
th,td{padding:15px; text-align:center; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px;}
th{color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; background: rgba(255,255,255,0.02);}

.btn{ padding:8px 14px; border:none; border-radius:6px; cursor:pointer; color:#fff; font-size:12px; text-decoration:none; display:inline-block; font-weight: bold; transition: 0.3s; border: 1px solid transparent;}
.shortlist{background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: #10b981;} 
.shortlist:hover{background: #10b981; color: #fff;}
.reject{background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: #ef4444;} 
.reject:hover{background: #ef4444; color: #fff;}

.upload-btn{background: #38bdf8; color: #0a0f1a; margin-top:10px; width: 100%; padding: 10px;} 
.upload-btn:hover{background: #0ea5e9; transform: translateY(-2px);}

.resume-btn{background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: #38bdf8; margin-bottom: 5px;} 
.resume-btn:hover{background: #38bdf8; color: #0a0f1a;}

.status{font-weight:800; text-transform:uppercase; font-size: 12px;}
.status.pending{color:#fbbf24} .status.shortlisted{color:#38bdf8}
.status.rejected{color:#f87171} .status.selected{color:#34d399}

.offer-form { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-top: 10px; border: 1px dashed rgba(255,255,255,0.1); text-align: left; }
.offer-form input[type="file"] { width: 100%; padding: 8px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; font-size: 12px; }
.skills-text { font-size: 13px; color: #cbd5e1; margin-top: 5px; display: block; line-height: 1.5;}
</style>
<script src="prevent_back.js"></script>
</head>

<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
<h1><i class="fa fa-users" style="color: #38bdf8;"></i> Review Student Applications</h1>

<div class="card-glass">
<table>
<thead>
<tr>
  <th>Student Info</th>
  <th style="width: 25%;">Skills</th>
  <th>Applied Job</th>
  <th>Resume</th>
  <th>Status</th>
  <th style="width: 22%;">Action</th>
</tr>
</thead>
<tbody>
<?php
if(mysqli_num_rows($result) > 0){
  while($row = mysqli_fetch_assoc($result)){
    echo "<tr>";
    echo "<td><b style='color:#f8fafc; font-size:15px;'>" . htmlspecialchars($row['name']) . "</b><br><small style='color:#94a3b8;'><i class='fa fa-envelope'></i> " . htmlspecialchars($row['email']) . "</small></td>";
    echo "<td><span class='skills-text'>" . nl2br(htmlspecialchars($row['skills'])) . "</span>";
    if($row['match_score'] > 0){
        echo "<div style='font-size: 11px; font-weight:bold; color: #10b981; margin-top:5px;'><i class='fa fa-robot'></i> AI Match: " . htmlspecialchars($row['match_score']) . "%</div>";
    }
    echo "</td>";
    echo "<td><b style='color:#38bdf8;'>" . htmlspecialchars($row['title']) . "</b></td>";
    
    echo "<td>";
    if(!empty($row['resume'])){
        echo "<a href='../uploads/resumes/{$row['resume']}' class='btn resume-btn' download><i class='fa fa-file-pdf'></i> View</a>";
    } else {
        echo "<span style='color:#64748b; font-style: italic; font-size:12px;'>Not Uploaded</span>";
    }
    echo "</td>";

    echo "<td class='status {$row['status']}'>{$row['status']}</td>";

    echo "<td>";
    if($row['status'] == 'pending'){
      echo "
      <a class='btn shortlist' href='?app_id={$row['app_id']}&status=shortlisted' onclick='return confirm(\"Shortlist this student?\")'><i class='fa fa-star'></i> Shortlist</a>
      <a class='btn reject' href='?app_id={$row['app_id']}&status=rejected' onclick='return confirm(\"Reject application?\")'><i class='fa fa-times'></i> Reject</a>";
    }
    elseif($row['status'] == 'shortlisted'){
      echo "
      <a class='btn shortlist' href='?app_id={$row['app_id']}&status=selected' onclick='return confirm(\"Select this student?\")'><i class='fa fa-check'></i> Select</a>
      <a class='btn reject' href='?app_id={$row['app_id']}&status=rejected' onclick='return confirm(\"Reject application?\")'><i class='fa fa-times'></i> Reject</a>
      
      <div class='offer-form'>
        <p style='font-size:11px; margin-bottom:8px; font-weight:800; color:#cbd5e1; text-transform:uppercase;'>Upload Offer (PDF):</p>
        <form method='POST' action='upload-offer.php' enctype='multipart/form-data'>
            <input type='hidden' name='app_id' value='{$row['app_id']}'>
            <input type='file' name='offer_pdf' accept='.pdf' required>
            <button type='submit' name='upload' class='btn upload-btn'><i class='fa fa-upload'></i> Upload & Select</button>
        </form>
      </div>";
    }
    elseif($row['status'] == 'selected'){
        echo "<span style='color:#10b981; font-weight:bold; font-size: 14px;'><i class='fa fa-circle-check'></i> Selected</span><br>";
        if(!empty($row['offer_letter'])){
            echo "<a href='../uploads/offer_letters/{$row['offer_letter']}' target='_blank' style='font-size:12px; color:#38bdf8; font-weight:bold; display:inline-block; margin-top:8px;'><i class='fa fa-download'></i> Download Offer</a>";
        }
    }
    else { echo "<span style='color:#475569; font-size: 18px;'>-</span>"; }
    echo "</td></tr>";
  }
} else {
  echo "<tr><td colspan='6' style='padding:60px; color:#64748b; font-weight: 600;'><i class='fa fa-folder-open' style='font-size: 40px; margin-bottom: 15px; display:block;'></i> No active applications found yet.</td></tr>";
}
?>
</tbody>
</table>
</div>
</main>
</body>
</html>