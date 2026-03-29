<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];

/* 🗑️ DELETE LOGIC */
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $delete_id AND student_id = $student_id");
    header("Location: notifications.php");
    exit();
}

// Mark read
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE student_id = $student_id AND is_read = 0");

$sql = "
    (SELECT id, title, message, notice_date as date_time, 'Notice' as type, 1 as is_read 
     FROM notices)
    UNION ALL
    (SELECT id, 'Update' as title, message, created_at as date_time, 'Personal' as type, is_read 
     FROM notifications 
     WHERE student_id = $student_id)
    ORDER BY date_time DESC
";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications - Placement Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; }
    main { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    
    .notif-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.2); margin-bottom: 20px; position: relative; transition: 0.3s; }
    .notif-card:hover { transform: translateX(5px); background: rgba(255, 255, 255, 0.04); }
    
    .type-notice { border-left: 5px solid #f59e0b; }
    .type-personal { border-left: 5px solid #38bdf8; }
    
    .delete-btn { position: absolute; top: 25px; right: 25px; color: #64748b; font-size: 18px; cursor: pointer; transition: 0.2s; background: none; border: none; }
    .delete-btn:hover { color: #ef4444; transform: scale(1.1); }
    
    .badge { padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; display: inline-block; border: 1px solid transparent; letter-spacing: 0.5px;}
    .badge-notice { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.2); }
    .badge-personal { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border-color: rgba(56, 189, 248, 0.2); }
    
    .notif-title { font-size: 18px; color: #f8fafc; margin: 5px 0 8px; font-weight: 800; }
    .notif-msg { font-size: 14px; color: #cbd5e1; margin: 5px 0 15px 0; line-height: 1.6; }
    .notif-time { font-size: 12px; color: #94a3b8; font-weight: bold;}
    
    .empty-state { background: rgba(255,255,255,0.02); backdrop-filter: blur(15px); border: 1px dashed rgba(255,255,255,0.1); text-align: center; padding: 60px 20px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
  </style>
</head>
<body>

<?php include 'student_header.php'; ?>

<main>
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="margin:0; font-weight: 800; font-size: 26px; color: #f8fafc;">🔔 Notifications & Alerts</h1>
        <span style="font-size: 12px; color: #34d399; font-weight: bold; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 6px 15px; border-radius: 20px;"><i class="fa fa-check-double"></i> All caught up!</span>
    </div>

    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): 
            $isNotice = ($row['type'] == 'Notice');
            $cardClass = $isNotice ? 'type-notice' : 'type-personal';
            $badgeClass = $isNotice ? 'badge-notice' : 'badge-personal';
        ?>
            <div class="notif-card <?= $cardClass ?>">
                <?php if(!$isNotice): ?>
                    <a href="?delete_id=<?= $row['id'] ?>" class="delete-btn" title="Delete Notification" onclick="return confirm('Are you sure you want to delete this?')"><i class="fa fa-trash-can"></i></a>
                <?php endif; ?>

                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['type']) ?></span>
                <div class="notif-title"><?= htmlspecialchars($row['title']) ?></div>
                <div class="notif-msg"><?= nl2br(htmlspecialchars($row['message'])) ?></div>
                <div class="notif-time"><i class="fa fa-calendar-days"></i> <?= date('d M Y, h:i A', strtotime($row['date_time'])) ?></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div style="width: 80px; height: 80px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fa fa-bell-slash" style="font-size: 35px; color: #64748b;"></i>
            </div>
            <h3 style="color: #f8fafc; margin-bottom: 10px; font-size: 20px;">No new notifications</h3>
            <p style="color: #94a3b8; font-size: 14px; margin: 0;">We'll let you know when something new arrives.</p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
