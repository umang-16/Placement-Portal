<?php
session_start();
require_once __DIR__ . "/../db.php";

if(!isset($_SESSION['student_id'])){
    if(isset($_GET['ajax'])) { echo "Session Expired. Please login again."; exit; }
    header("Location: student-login.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$is_ajax = isset($_GET['ajax']);

// 📝 UPDATE REVIEW LOGIC (Feedback Edit)
if(isset($_POST['update_review'])){
    $edit_comp_id = (int)$_POST['company_id'];
    $edit_rating = (int)$_POST['rating'];
    $edit_comment = mysqli_real_escape_string($conn, $_POST['comment']);

    mysqli_query($conn, "UPDATE company_reviews SET rating=$edit_rating, comment='$edit_comment' WHERE student_id=$student_id AND company_id=$edit_comp_id");
    
    header("Location: companies.php?msg=Review Updated Successfully!");
    exit();
}

// 📄 FETCH PROFILE DATA
$stu_res = mysqli_query($conn, "SELECT name, email, contact, resume, skills FROM students WHERE id=$student_id");
$student_info = mysqli_fetch_assoc($stu_res);
$has_resume = !empty($student_info['resume']);

// 🔍 Search Logic
$search_title = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_loc   = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';

$sql = "SELECT jobs.*, companies.company_name, companies.id AS comp_id
        FROM jobs
        JOIN companies ON jobs.company_id = companies.id
        WHERE jobs.status='approved'
        AND jobs.title LIKE '%$search_title%'
        AND jobs.location LIKE '%$search_loc%'
        ORDER BY jobs.id DESC";

$jobs_query = mysqli_query($conn, $sql);

if (!$is_ajax) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Jobs & Reviews - Placement Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ✨ DARK & GLASS THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body{background:#0a0f1a; color: #f8fafc;}

    .search-container { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; gap: 15px; align-items: center; }
    .search-input { flex: 1; padding: 14px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; outline: none; font-size: 15px; transition: 0.3s;}
    .search-input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4); }
    .btn-search { background: #38bdf8; color: #0a0f1a; padding: 14px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 15px; transition: 0.3s; }
    .btn-search:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}
    
    .job-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; transition: opacity 0.3s ease;}
    .card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; border-top: 4px solid #38bdf8; position: relative; display: flex; flex-direction: column; transition: 0.3s;}
    .card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.04); box-shadow: 0 15px 35px rgba(0,0,0,0.3);}
    
    .rating-badge { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; margin-left: 10px; display: inline-block;}
    .review-section { margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.1); }
    .review-section select, .review-section textarea { width: 100%; padding: 10px; margin-top: 8px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; transition: 0.3s;}
    .review-section select:focus, .review-section textarea:focus { border-color: #f59e0b; }
    .review-section textarea { resize: none; }
    .btn-review { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid #f59e0b; padding: 12px; width: 100%; margin-top: 10px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s;}
    .btn-review:hover { background: #f59e0b; color: #0a0f1a; }

    .btn-group { display: flex; gap: 10px; margin-top: auto; padding-top: 15px; }
    .btn-view { background: rgba(255,255,255,0.05); color:#f8fafc; border: 1px solid rgba(255,255,255,0.1); padding:12px; border-radius:8px; cursor:pointer; flex: 1; font-weight: bold; transition: 0.3s; }
    .btn-view:hover { background: rgba(255,255,255,0.1); color: #38bdf8; border-color: #38bdf8;}
    
    .btn-apply { background:#38bdf8; color:#0a0f1a; padding:12px; border:none; border-radius:8px; cursor:pointer; flex: 1; font-weight: 800; transition: 0.3s;}
    .btn-apply:hover { background:#0ea5e9; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3); transform: translateY(-2px);}
    
    .btn-reapply { background: rgba(245, 158, 11, 0.2); color:#f59e0b; border: 1px solid #f59e0b; padding:12px; border-radius:8px; cursor:pointer; flex: 1; font-weight: bold; transition: 0.3s;}
    .btn-reapply:hover { background:#f59e0b; color: #0a0f1a;}

    .btn-withdraw { background: rgba(239, 68, 68, 0.1); color:#ef4444; border: 1px solid #ef4444; padding:12px; border-radius:8px; cursor:pointer; width: 100%; font-weight: bold; margin-top: 15px; transition: 0.3s;}
    .btn-withdraw:hover { background:#ef4444; color: #fff;}
    
    .success-badge { display: block; text-align: center; padding: 12px; border-radius: 8px; font-weight: bold; margin-top: 15px; font-size: 13px; }

    /* Modals CSS */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); justify-content:center; align-items:center; z-index: 2000; }
    .modal-content { background: #0f172a; padding: 35px; width: 550px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
    .close-btn { position:absolute; right:20px; top:15px; cursor:pointer; font-size:24px; color:#ef4444; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%; transition: 0.3s;}
    .close-btn:hover { background: #ef4444; color: #fff; transform: scale(1.1);}
    
    .modal-content label { display:block; margin-top:15px; font-weight:800; font-size: 12px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px;}
    .form-input { width: 100%; padding: 14px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; margin-top: 8px; font-family: inherit; font-size: 14px; outline: none; transition: 0.3s; }
    .form-input:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); background: rgba(0,0,0,0.4); }
    .readonly-input { background: rgba(255,255,255,0.02); color: #94a3b8; cursor: not-allowed; }
    .job-meta { color: #94a3b8; font-size: 13px; margin: 10px 0; font-weight: 500; display: flex; align-items: center; gap: 15px;}
    .job-meta i { font-size: 16px; }
  </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<main style="padding: 40px; max-width: 1200px; margin: auto;">

<?php if(isset($_GET['msg'])): ?>
<div style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; border: 1px solid rgba(16, 185, 129, 0.3); display:flex; justify-content:space-between; align-items:center;">
  <span><i class="fa fa-circle-check"></i> <?= htmlspecialchars($_GET['msg']) ?></span>
  <span style="cursor:pointer; font-size: 20px; color: #10b981;" onclick="this.parentElement.style.display='none'">&times;</span>
</div>
<?php endif; ?>

<h1 style="color: #f8fafc; font-weight: 800; font-size: 26px; margin-bottom: 20px;"><i class="fa fa-search" style="color: #38bdf8;"></i> Find Your Dream Job</h1>

<form id="searchForm" class="search-container">
    <input type="text" id="searchTitle" name="search" class="search-input" placeholder="Search Job Title or Skills..." autocomplete="off">
    <input type="text" id="searchLoc" name="location" class="search-input" placeholder="Location (e.g. Mumbai, Remote)..." autocomplete="off">
    <button type="submit" class="btn-search"><i class="fa fa-search"></i> Search</button>
</form>

<div class="job-grid" id="jobGrid">
<?php
} // END IF (!$is_ajax)
?>

<?php
if(mysqli_num_rows($jobs_query) > 0){
  while($job = mysqli_fetch_assoc($jobs_query)){
    $job_id = $job['id'];
    $comp_id = $job['comp_id'];

    // ✨ 1. FETCH ONLY VISIBLE REVIEWS ✨
    $avg_res = mysqli_query($conn, "SELECT AVG(rating) as avg_r FROM company_reviews WHERE company_id = $comp_id AND status='visible'");
    $avg_data = mysqli_fetch_assoc($avg_res);
    $rating_display = $avg_data['avg_r'] ? round($avg_data['avg_r'], 1) . " ⭐" : "No ratings";

    // ✨ 2. FETCH REVIEW LIST FOR DETAILS MODAL ✨
    $rev_sql = "SELECT r.rating, r.comment, s.name FROM company_reviews r JOIN students s ON r.student_id = s.id WHERE r.company_id = $comp_id AND r.status = 'visible' ORDER BY r.id DESC";
    $rev_res = mysqli_query($conn, $rev_sql);
    $reviews_html = "";
    
    if(mysqli_num_rows($rev_res) > 0) {
        while($rev = mysqli_fetch_assoc($rev_res)) {
            $stars = str_repeat("⭐", $rev['rating']);
            $reviews_html .= "<div style='background:rgba(0,0,0,0.2); padding:12px; border-radius:8px; margin-top:10px; border:1px solid rgba(255,255,255,0.05);'>";
            $reviews_html .= "<strong style='color:#38bdf8; font-size:13px;'>" . htmlspecialchars($rev['name']) . "</strong> <span style='font-size:12px; margin-left:8px;'>$stars</span>";
            $reviews_html .= "<p style='color:#cbd5e1; font-size:13px; margin:5px 0 0 0; font-style:italic;'>\"" . htmlspecialchars($rev['comment']) . "\"</p>";
            $reviews_html .= "</div>";
        }
    } else {
        $reviews_html = "<p style='color:#64748b; font-size:13px; font-style:italic;'>No visible reviews for this company yet.</p>";
    }

    $jobData = htmlspecialchars(json_encode([
        'title' => $job['title'], 'company' => $job['company_name'],
        'location' => $job['location'], 'salary' => $job['salary'],
        'desc' => nl2br(htmlspecialchars($job['description'] ?? 'Not provided')),
        'req' => nl2br(htmlspecialchars($job['skills'] ?? 'Not specified')),
        'reviews' => $reviews_html // 👈 Review HTML passed here
    ]), ENT_QUOTES, 'UTF-8');

    // App Check Logic
    $check_app = mysqli_query($conn, "SELECT status FROM applications WHERE student_id = $student_id AND job_id = $job_id ORDER BY id DESC LIMIT 1");
    $app_data = mysqli_fetch_assoc($check_app);
    $applied = mysqli_num_rows($check_app) > 0;
    $current_status = $applied ? strtolower($app_data['status']) : '';

    $user_review_res = mysqli_query($conn, "SELECT * FROM company_reviews WHERE student_id=$student_id AND company_id=$comp_id");
    $has_reviewed = mysqli_num_rows($user_review_res) > 0;
    $user_review_data = mysqli_fetch_assoc($user_review_res);
?>

  <div class="card">
    <h2 style="color: #38bdf8; margin-bottom:8px; font-weight: 800; font-size: 20px;"><?php echo htmlspecialchars($job['title']); ?></h2>
    <p style="color: #f8fafc; font-size: 15px;"><b><i class="fa fa-building" style="color: #94a3b8;"></i> <?php echo htmlspecialchars($job['company_name']); ?></b> <span class="rating-badge"><?= $rating_display ?></span></p>
    
    <div class="job-meta">
        <span><i class="fa fa-location-dot" style="color: #ef4444;"></i> <?php echo htmlspecialchars($job['location']); ?></span>
        <span><i class="fa fa-wallet" style="color: #10b981;"></i> <?php echo htmlspecialchars($job['salary']); ?></span>
    </div>
    
    <div class="btn-group">
        <button class="btn-view" onclick='viewJobDetails(<?= $jobData ?>)'><i class="fa fa-eye"></i> Details</button>
        
        <?php if(!$applied || $current_status == 'withdrawn'): ?>
            <button class="<?= $current_status == 'withdrawn' ? 'btn-reapply' : 'btn-apply' ?>" onclick="openApplyModal(<?= $job_id ?>, '<?= htmlspecialchars(addslashes($job['title'])) ?>', '<?= htmlspecialchars(addslashes($job['company_name'])) ?>')">
                <i class="fa <?= $current_status == 'withdrawn' ? 'fa-rotate-right' : 'fa-paper-plane' ?>"></i> 
                <?= $current_status == 'withdrawn' ? 'Re-Apply' : 'Apply Now' ?>
            </button>
        <?php endif; ?>
    </div>

    <div style="margin-top: 10px;">
      <?php if($applied){ ?>
          <?php if($current_status == 'selected'){ ?>
            <span class="success-badge" style="background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">🎉 Congratulations! You are Selected.</span>
          <?php } elseif($current_status == 'rejected'){ ?>
            <span class="success-badge" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">❌ Not Selected</span>
          <?php } elseif($current_status == 'withdrawn'){ ?>
            <span class="success-badge" style="background: rgba(255, 255, 255, 0.05); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.1);">🚫 Application Withdrawn</span>
            <span style="font-size:11px; color:#64748b; display:block; text-align:center; margin-top:8px;">You can re-apply using the button above.</span>
          <?php } else { ?>
            <?php if($current_status == 'shortlisted'){ ?>
                <span class="success-badge" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">🌟 Shortlisted for Interview!</span>
            <?php } else { ?>
                <span class="success-badge" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">⏳ Application is Pending</span>
            <?php } ?>
            
            <button class="btn-withdraw" onclick="openWithdrawModal(<?= $job_id ?>)"><i class="fa fa-ban"></i> Withdraw Application</button>
            <span style="font-size:11px; color:#64748b; display:block; text-align:center; margin-top:8px;">You can withdraw anytime before selection.</span>
          <?php } ?>
      <?php } ?>
    </div>

    <div class="review-section">
      <?php if($current_status == 'selected'): ?>
        <?php if(!$has_reviewed): ?>
          <p style="font-size: 13px; font-weight: 800; color: #f59e0b; text-transform: uppercase;"><i class="fa fa-star"></i> Rate your experience:</p>
          <form method="POST" action="submit-review.php">
            <input type="hidden" name="company_id" value="<?= $comp_id ?>">
            <select name="rating" required>
              <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
              <option value="4">⭐⭐⭐⭐ (Good)</option>
              <option value="3">⭐⭐⭐ (Average)</option>
              <option value="2">⭐⭐ (Poor)</option>
              <option value="1">⭐ (Terrible)</option>
            </select>
            <textarea name="comment" rows="2" placeholder="Tell us how it was working there..." required></textarea>
            <button type="submit" name="submit_review" class="btn-review"><i class="fa fa-paper-plane"></i> Submit Feedback</button>
          </form>
        <?php else: ?>
          <div id="view-review-<?= $comp_id ?>" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); position: relative;">
            <button onclick="toggleEditReview(<?= $comp_id ?>)" title="Edit Feedback" style="position: absolute; top: 12px; right: 12px; background: none; border: none; color: #38bdf8; cursor: pointer; font-size: 14px; transition: 0.2s;"><i class="fa fa-pen-to-square"></i></button>
            <p style="font-size: 12px; font-weight: 800; color: #94a3b8; margin:0 0 5px 0; text-transform: uppercase;">Your Feedback:</p>
            <p style="font-size: 14px; margin: 0 0 5px 0;"><?= str_repeat("⭐", $user_review_data['rating']) ?></p>
            <p style="font-size: 13px; font-style: italic; color: #cbd5e1; margin:0;">"<?= htmlspecialchars($user_review_data['comment']) ?>"</p>
          </div>

          <div id="edit-review-<?= $comp_id ?>" style="display: none; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px dashed rgba(255,255,255,0.2); position: relative;">
            <button onclick="toggleEditReview(<?= $comp_id ?>)" title="Cancel Edit" style="position: absolute; top: 12px; right: 12px; background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px; transition: 0.2s;"><i class="fa fa-times-circle"></i></button>
            <p style="font-size: 12px; font-weight: 800; color: #94a3b8; margin:0 0 10px 0; text-transform: uppercase;">Edit Feedback:</p>
            <form method="POST" action="companies.php">
              <input type="hidden" name="company_id" value="<?= $comp_id ?>">
              <select name="rating" required>
                <option value="5" <?= $user_review_data['rating'] == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (Excellent)</option>
                <option value="4" <?= $user_review_data['rating'] == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (Good)</option>
                <option value="3" <?= $user_review_data['rating'] == 3 ? 'selected' : '' ?>>⭐⭐⭐ (Average)</option>
                <option value="2" <?= $user_review_data['rating'] == 2 ? 'selected' : '' ?>>⭐⭐ (Poor)</option>
                <option value="1" <?= $user_review_data['rating'] == 1 ? 'selected' : '' ?>>⭐ (Terrible)</option>
              </select>
              <textarea name="comment" rows="2" required><?= htmlspecialchars($user_review_data['comment']) ?></textarea>
              <button type="submit" name="update_review" class="btn-review" style="background: rgba(56, 189, 248, 0.1); border-color: #38bdf8; color: #38bdf8;"><i class="fa fa-save"></i> Update Changes</button>
            </form>
          </div>
        <?php endif; ?>
      <?php else: ?>
          <p style="font-size: 12px; color: #64748b; font-style: italic; font-weight: 500; text-align: center;"><i class="fa fa-lock" style="font-size: 10px;"></i> Feedback unlocks after selection.</p>
      <?php endif; ?>
    </div>
  </div>

<?php 
  } // End While Loop
} else {
  echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px; color:#94a3b8;'><i class='fa fa-folder-open' style='font-size: 40px; margin-bottom: 15px; display:block;'></i><p>No jobs found matching your search.</p></div>";
} 

if ($is_ajax) { exit(); }
?>
</div>
</main>

<div id="jobViewModal" class="modal">
    <div class="modal-content">
        <span onclick="closeJobView()" class="close-btn">&times;</span>
        <h2 id="v_title" style="color:#38bdf8; margin-bottom: 5px; font-weight: 800; font-size: 22px;"></h2>
        <p id="v_company" style="font-weight:bold; color:#f8fafc; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;"></p>
        <p style="font-size: 14px; margin-bottom: 8px;"><b style="color: #94a3b8;"><i class="fa fa-location-dot" style="color:#ef4444;"></i> Location:</b> <span id="v_location" style="color: #cbd5e1;"></span></p>
        <p style="font-size: 14px;"><b style="color: #94a3b8;"><i class="fa fa-wallet" style="color:#10b981;"></i> Salary:</b> <span id="v_salary" style="color:#10b981; font-weight:bold;"></span></p>
        
        <h4 style="margin-top:25px; color:#cbd5e1; text-transform: uppercase; font-size: 12px; font-weight: 800;">📝 Job Description</h4>
        <p id="v_desc" style="font-size:14px; color:#94a3b8; background:rgba(0,0,0,0.2); padding:15px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05); margin-top:8px; max-height: 120px; overflow-y:auto; line-height: 1.6;"></p>
        
        <h4 style="margin-top:20px; color:#cbd5e1; text-transform: uppercase; font-size: 12px; font-weight: 800;">🛠️ Required Skills</h4>
        <p id="v_req" style="font-size:14px; color:#38bdf8; background:rgba(56, 189, 248, 0.05); padding:15px; border-radius:8px; border: 1px dashed rgba(56, 189, 248, 0.2); margin-top:8px; line-height: 1.6;"></p>

        <h4 style="margin-top:25px; color:#f59e0b; text-transform: uppercase; font-size: 12px; font-weight: 800;"><i class="fa fa-star"></i> Alumni Reviews & Feedback</h4>
        <div id="v_reviews" style="max-height: 150px; overflow-y:auto; margin-top:8px; padding-right: 5px;"></div>
    </div>
</div>

<div id="applyModal" class="modal">
    <div class="modal-content">
        <span onclick="closeApplyModal()" class="close-btn">&times;</span>
        <h2 style="color:#38bdf8; font-size: 22px; margin-bottom: 5px; font-weight: 800;"><i class="fa fa-paper-plane"></i> Submit Application</h2>
        <p id="apply_job_title_display" style="font-weight:bold; color:#cbd5e1; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;"></p>
        
        <form method="POST" action="apply.php" enctype="multipart/form-data" onsubmit="return confirm('🚀 Are you sure you want to submit this application?');">
            <input type="hidden" name="job_id" id="apply_job_id">
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <p style="margin:0 0 15px 0; font-size:11px; color:#38bdf8; font-weight:800; text-transform:uppercase;"><i class="fa fa-user-check"></i> Your Verified Details</p>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;"><label style="margin-top:0;">Full Name</label><input type="text" class="form-input readonly-input" value="<?= htmlspecialchars($student_info['name']) ?>" readonly></div>
                    <div style="flex:1;"><label style="margin-top:0;">Contact</label><input type="text" class="form-input readonly-input" value="<?= htmlspecialchars($student_info['contact'] ?? 'Not Added') ?>" readonly></div>
                </div>
                <label>Email Address</label><input type="email" class="form-input readonly-input" value="<?= htmlspecialchars($student_info['email']) ?>" readonly>
            </div>
            <label><i class="fa fa-code" style="color: #10b981;"></i> Highlight Your Skills</label>
            <textarea name="skills" class="form-input" rows="3" required placeholder="E.g. PHP, HTML, CSS..."><?= htmlspecialchars($student_info['skills'] ?? '') ?></textarea>
            <label style="margin-top: 20px;"><i class="fa fa-file-pdf" style="color: #ef4444;"></i> Resume Selection</label>
            <?php if($has_resume): ?>
                <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-top: 8px;">
                    <p style="font-size: 13px; color: #34d399; font-weight: bold; margin:0;"><i class="fa fa-check-circle"></i> Profile Resume Selected</p>
                    <p style="font-size: 12px; color: #10b981; margin-top: 4px; word-break: break-all;"><?= htmlspecialchars($student_info['resume']) ?></p>
                    <input type="hidden" name="existing_resume" value="<?= htmlspecialchars($student_info['resume']) ?>">
                </div>
                <label style="margin-top: 15px; font-size: 11px; color: #94a3b8;">Or upload a different resume (Optional):</label>
                <input type="file" name="new_resume" accept=".pdf" class="form-input" style="padding: 10px;">
            <?php else: ?>
                <input type="file" name="new_resume" accept=".pdf" class="form-input" required style="padding: 10px;">
            <?php endif; ?>
            <button type="submit" name="submit_application" class="btn-apply" style="width: 100%; margin-top: 30px; padding: 16px; font-size: 16px;"><i class="fa fa-paper-plane"></i> Final Submit</button>
        </form>
    </div>
</div>

<div class="modal" id="withdrawModal">
  <div class="modal-content" style="width: 400px; text-align:center;">
    <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px;"><i class="fa fa-triangle-exclamation"></i></div>
    <h3 style="color:#f8fafc; font-weight: 800; font-size: 22px;">Withdraw Application?</h3>
    <p style="margin: 15px 0 30px 0; color:#94a3b8; font-size: 14px; line-height: 1.6;">Are you sure you want to pull back your application? You can re-apply later if needed.</p>
    <form method="POST" action="withdraw.php">
      <input type="hidden" name="job_id" id="withdraw_job_id">
      <div style="display:flex; gap:15px;">
        <button type="submit" name="confirm" value="yes" style="flex:1; background:#ef4444; color:#fff; padding:14px; border:none; border-radius:8px; cursor:pointer; font-weight:800; font-size: 15px; transition: 0.3s;">Yes, Withdraw</button>
        <button type="button" onclick="closeWithdrawModal()" style="flex:1; background:rgba(255,255,255,0.05); color:#f8fafc; border: 1px solid rgba(255,255,255,0.1); padding:14px; border-radius:8px; cursor:pointer; font-weight:800; font-size: 15px; transition: 0.3s;">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
// AJAX SEARCH LOGIC
document.addEventListener("DOMContentLoaded", function() {
    const searchTitle = document.getElementById('searchTitle');
    const searchLoc = document.getElementById('searchLoc');
    const jobGrid = document.getElementById('jobGrid');
    const searchForm = document.getElementById('searchForm');
    let timeout = null;
    function fetchLiveJobs() {
        const titleVal = encodeURIComponent(searchTitle.value);
        const locVal = encodeURIComponent(searchLoc.value);
        jobGrid.style.opacity = '0.3';
        fetch(`companies.php?ajax=1&search=${titleVal}&location=${locVal}`)
            .then(response => response.text())
            .then(html => { jobGrid.innerHTML = html; jobGrid.style.opacity = '1'; })
            .catch(error => { console.error('Error fetching jobs:', error); jobGrid.style.opacity = '1'; });
    }
    function liveSearch() { clearTimeout(timeout); timeout = setTimeout(fetchLiveJobs, 400); }
    searchTitle.addEventListener('input', liveSearch);
    searchLoc.addEventListener('input', liveSearch);
    searchForm.addEventListener('submit', function(e) { e.preventDefault(); fetchLiveJobs(); });
});

// MODALS LOGIC
function toggleEditReview(compId) {
    var viewDiv = document.getElementById('view-review-' + compId);
    var editDiv = document.getElementById('edit-review-' + compId);
    if (viewDiv.style.display === 'none') { viewDiv.style.display = 'block'; editDiv.style.display = 'none';
    } else { viewDiv.style.display = 'none'; editDiv.style.display = 'block'; }
}

function viewJobDetails(job) {
    document.getElementById('v_title').innerText = job.title; 
    document.getElementById('v_company').innerText = "🏢 " + job.company;
    document.getElementById('v_location').innerText = job.location; 
    document.getElementById('v_salary').innerText = job.salary;
    document.getElementById('v_desc').innerHTML = job.desc; 
    document.getElementById('v_req').innerHTML = job.req;
    
    // ✨ SHOW REVIEWS IN DETAILS MODAL ✨
    document.getElementById('v_reviews').innerHTML = job.reviews;
    
    document.getElementById('jobViewModal').style.display = 'flex';
}
function closeJobView() { document.getElementById('jobViewModal').style.display = 'none'; }

function openApplyModal(jobId, jobTitle, companyName) {
    document.getElementById('apply_job_id').value = jobId;
    document.getElementById('apply_job_title_display').innerText = jobTitle + " at " + companyName;
    document.getElementById('applyModal').style.display = 'flex';
}
function closeApplyModal() { document.getElementById('applyModal').style.display = 'none'; }
function openWithdrawModal(jobId) { document.getElementById("withdraw_job_id").value = jobId; document.getElementById("withdrawModal").style.display = "flex"; }
function closeWithdrawModal() { document.getElementById("withdrawModal").style.display = "none"; }
window.onclick = function(event) {
    if (event.target == document.getElementById('jobViewModal')) closeJobView();
    if (event.target == document.getElementById('applyModal')) closeApplyModal();
    if (event.target == document.getElementById('withdrawModal')) closeWithdrawModal();
}
</script>
</body>
</html>