<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 COMPANY LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];

/* 🗑️ DELETE LOGIC */
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $delete_id AND company_id = $company_id");
    header("Location: company-notifications.php");
    exit();
}

/* ✅ AUTOMATIC MARK ALL AS READ */
mysqli_query($conn, "
    UPDATE notifications 
    SET is_read = 1 
    WHERE company_id = $company_id 
    AND student_id = 0 
    AND is_read = 0
");

/* 📊 FETCH ALL NOTIFICATIONS */
$sql = "SELECT * FROM notifications 
        WHERE company_id = $company_id 
        AND student_id = 0 
        ORDER BY id DESC";

$notifications = mysqli_query($conn, $sql);
?>
<?php include('auth_check.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Notifications - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}

/* MAIN CONTENT */
main{padding:40px;max-width:850px;margin:auto}
h1{color: #f8fafc; font-size: 26px; font-weight: 800; margin: 0;}

.notif-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); padding: 25px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.05); border-left: 6px solid #38bdf8; margin-bottom: 20px; position: relative; transition: 0.3s ease; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.notif-card:hover { transform: translateX(5px); background: rgba(255, 255, 255, 0.04); }

.delete-btn { position: absolute; top: 25px; right: 25px; color: #64748b; font-size: 18px; cursor: pointer; transition: 0.2s; background: transparent; border: none; }
.delete-btn:hover { color: #ef4444; transform: scale(1.1); }

.badge { background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; display: inline-block; letter-spacing: 0.5px; border: 1px solid rgba(56, 189, 248, 0.2);}

.notif-title { font-size: 18px; color: #f8fafc; margin: 5px 0 8px 0; font-weight: 800; }
.notif-msg { font-size: 14px; color: #cbd5e1; margin: 5px 0 15px 0; line-height: 1.6; }
.notif-time { font-size: 12px; color: #94a3b8; font-weight: 600; }

.empty-state { text-align: center; padding: 60px 20px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
.empty-icon { width: 80px; height: 80px; background: rgba(0,0,0,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; border: 1px solid rgba(255,255,255,0.05); }
</style>
<script src="prevent_back.js"></script>
</head>

<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1>🔔 System Alerts & Updates</h1>
        <span style="font-size: 12px; color: #34d399; font-weight: bold; background: rgba(16, 185, 129, 0.1); padding: 8px 15px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.3);"><i class="fa fa-check-double"></i> All caught up!</span>
    </div>

    <?php if(mysqli_num_rows($notifications) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($notifications)): ?>
            <div class="notif-card">
                <a href="?delete_id=<?= $row['id'] ?>" class="delete-btn" title="Delete Alert" onclick="return confirm('Are you sure you want to delete this alert?')">
                    <i class="fa fa-trash-can"></i>
                </a>

                <span class="badge"><i class="fa fa-bolt"></i> System Alert</span>
                <div class="notif-title">Application Status / Job Update</div>
                <div class="notif-msg"><?= nl2br(htmlspecialchars($row['message'])) ?></div>
                <div class="notif-time"><i class="fa fa-calendar-days"></i> <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fa fa-bell-slash" style="font-size: 35px; color: #64748b;"></i>
            </div>
            <h3 style="color: #f8fafc; margin-bottom: 8px; font-size: 20px;">No new alerts</h3>
            <p style="color: #94a3b8; font-size: 14px; margin: 0;">We'll notify you here when candidates apply or admins approve your jobs.</p>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
