<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];

/* 📊 FETCH APPLICATIONS QUERY */
$sql = "SELECT 
            a.id AS app_id, 
            a.status, 
            a.applied_at, 
            a.offer_letter,
            j.title AS job_title, 
            c.company_name 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE a.student_id = $student_id
        ORDER BY a.applied_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Application Status</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; margin: 0; }
    main { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
    
    .status-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 30px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }
    
    /* 🛠️ TABLE CSS FIX */
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
    th { color: #38bdf8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 12px; background: rgba(255,255,255,0.02); }
    td { color: #cbd5e1; }

    .status-badge { padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 11px; display: inline-block; text-transform: uppercase; border: 1px solid transparent; letter-spacing: 0.5px;}
    .pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
    .shortlisted { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); }
    .selected { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3); }
    .rejected { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }
    .withdrawn { background: rgba(255, 255, 255, 0.05); color: #94a3b8; border-color: rgba(255, 255, 255, 0.1); }

    .btn-download { display: inline-flex; align-items: center; gap: 6px; background: #38bdf8; color: #0a0f1a; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 800; transition: 0.3s;}
    .btn-download:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(56, 189, 248, 0.3);}
    .error-text { color: #f87171; font-size: 11px; font-weight: bold; background: rgba(239, 68, 68, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.2);}
  </style>
  <script src="prevent_back.js"></script>
</head>
<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'student_header.php'; ?>

<main>
  <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
  <div style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; border: 1px solid rgba(16, 185, 129, 0.3); display:flex; justify-content:space-between; align-items:center;">
      <span><i class="fa fa-circle-check"></i> 🎉 Application submitted successfully! Best of luck.</span>
      <span style="cursor:pointer; font-size: 20px; line-height: 1;" onclick="this.parentElement.style.display='none'">&times;</span>
  </div>
  <?php endif; ?>

  <div class="status-card">
    <h1 style="margin-top:0; color:#f8fafc; font-weight:800; font-size:24px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;"><i class="fa fa-list-check" style="color: #38bdf8;"></i> Application Status</h1>
    <table>
      <thead>
        <tr>
          <th>Job Role</th>
          <th>Company</th>
          <th>Applied Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(mysqli_num_rows($result) > 0): ?>
          <?php while($row = mysqli_fetch_assoc($result)): 
              $current_status = strtolower(trim($row['status'])); 
              $file_path = "../uploads/offer_letters/" . $row['offer_letter'];
          ?>
            <tr>
              <td><b style="color: #f8fafc; font-size: 15px;"><?= htmlspecialchars($row['job_title']) ?></b></td>
              <td style="color: #94a3b8;"><i class="fa fa-building" style="font-size: 12px;"></i> <?= htmlspecialchars($row['company_name']) ?></td>
              <td style="font-size: 13px;"><i class="fa fa-clock" style="color: #38bdf8;"></i> <?= date('d M Y', strtotime($row['applied_at'])) ?></td>
              <td><span class="status-badge <?= $current_status ?>"><?= htmlspecialchars($row['status']) ?></span></td>
              <td>
                <?php if($current_status == 'selected' && !empty($row['offer_letter'])): ?>
                    <?php if(file_exists($file_path)): ?>
                        <a href="<?= $file_path ?>" class="btn-download" download><i class="fa fa-download"></i> Get Offer</a>
                    <?php else: ?>
                        <span class="error-text"><i class="fa fa-triangle-exclamation"></i> File Missing</span>
                    <?php endif; ?>
                <?php else: ?>
                  <span style="color: #64748b; font-size: 12px; font-weight: bold;">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center; padding: 50px; color:#64748b;"><i class="fa fa-folder-open" style="font-size: 40px; margin-bottom: 15px; display:block;"></i>No applications found. Start Applying!</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
