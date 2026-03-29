<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$current_date = date('Y-m-d');
?>
<?php include('auth_check.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Interview Schedule</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; }
    main { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    h1 { color: #f8fafc; margin-bottom: 25px; font-size: 26px; font-weight: 800; }
    
    .card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); overflow-x: auto; }
    
    /* 🛠️ TABLE CSS FIX */
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 15px !important; text-align: left !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; font-size: 14px !important; color: #cbd5e1 !important;}
    th { background: rgba(255,255,255,0.02) !important; color: #38bdf8 !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 1px !important; font-size: 12px !important; border-bottom: none !important;}
    
    .badge { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; display: inline-block; text-transform: uppercase; border: 1px solid transparent;}
    .status-attended { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3);}
    .status-missed { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3);}
    .status-past { background: rgba(255, 255, 255, 0.05); color: #94a3b8; border-color: rgba(255, 255, 255, 0.1);}
    .status-upcoming { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: rgba(56, 189, 248, 0.3); animation: pulse 2s infinite; }
    
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(56, 189, 248, 0); } 100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); } }
    
    .btn-join { background: #38bdf8; color: #0a0f1a; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: 800; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; transition: 0.3s; }
    .btn-join:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3);}
  </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<main>
  <h1>📅 My Interview Schedule</h1>
  <div class="card-glass">
    <table>
      <thead>
        <tr>
          <th>Company & Role</th>
          <th>Date & Time</th>
          <th>Location / Link</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT i.*, j.title, c.company_name 
                FROM interviews i 
                JOIN applications a ON i.application_id = a.id 
                JOIN jobs j ON a.job_id = j.id 
                JOIN companies c ON j.company_id = c.id 
                WHERE a.student_id = $student_id 
                ORDER BY i.interview_date DESC";
        $result = mysqli_query($conn, $sql);

        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $i_date = $row['interview_date'];
                $isPast = strtotime($i_date) < strtotime($current_date);
                $isOnline = (stripos($row['location'], 'http') !== false);
                $isToday = (date('Y-m-d', strtotime($i_date)) == $current_date);
                ?>
                <tr>
                  <td>
                    <b style="color:#f8fafc; font-size:15px;"><?= htmlspecialchars($row['title']) ?></b><br>
                    <span style="color:#94a3b8; font-size:13px;"><i class="fa fa-building"></i> <?= htmlspecialchars($row['company_name']) ?></span>
                  </td>
                  <td>
                    <b style="color: <?= $isToday ? '#ef4444' : '#f8fafc' ?>;"><?= date('d M Y', strtotime($i_date)) ?></b><br>
                    <span style="color:#94a3b8; font-size:13px;"><i class="fa fa-clock"></i> <?= date('h:i A', strtotime($i_date)) ?></span>
                    <?php if($isToday && !$isPast): ?>
                        <br><span style="color:#ef4444; font-size:10px; font-weight:bold; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px;">🕒 TODAY!</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($isOnline && !$isPast): ?>
                        <a href="<?= htmlspecialchars($row['location']) ?>" target="_blank" class="btn-join"><i class="fa fa-video"></i> Join Meeting</a>
                    <?php else: ?>
                        <span style="font-weight: 500; color: #cbd5e1;"><i class="fa fa-location-dot" style="color: #ef4444;"></i> <?= htmlspecialchars($row['location']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($row['attendance'] == 'present'): ?>
                        <span class="badge status-attended"><i class="fa fa-check-circle"></i> Attended</span>
                    <?php elseif($row['attendance'] == 'absent'): ?>
                        <span class="badge status-missed"><i class="fa fa-circle-xmark"></i> Missed</span>
                    <?php elseif($isPast): ?>
                        <span class="badge status-past">Past Interview</span>
                    <?php else: ?>
                        <span class="badge status-upcoming"><i class="fa fa-hourglass-half"></i> Upcoming</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='4' style='text-align:center; padding:50px; color:#64748b;'><i class='fa fa-calendar-xmark' style='font-size: 40px; margin-bottom:15px; display:block;'></i> <p>No interviews scheduled yet.</p></td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
