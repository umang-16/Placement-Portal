<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 
if (!isset($_SESSION['admin_id'])) { header("Location: admin-login.php"); exit(); }

$msg = "";

/* 🛠️ 1. AUTO-UPDATE DB SCHEME FOR OFFER LETTERS */
$check_letter = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'offer_letter'");
if(mysqli_num_rows($check_letter) == 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN offer_letter VARCHAR(255) DEFAULT NULL");
}
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'offer_status'");
if(mysqli_num_rows($check_status) == 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN offer_status VARCHAR(50) DEFAULT 'Pending Validation'");
}

/* ✅ 2. VERIFY OR REJECT OFFER LETTER */
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if($action == 'verify') {
        mysqli_query($conn, "UPDATE applications SET offer_status='Verified' WHERE id=$id");
        $msg = "<div class='alert-success'>✅ Offer Letter Verified Successfully! Student is now officially placed.</div>";
        mysqli_query($conn, "INSERT INTO admin_notifications (message) VALUES ('Admin verified an offer letter for application ID #$id.')");
    } elseif($action == 'reject') {
        mysqli_query($conn, "UPDATE applications SET offer_status='Rejected' WHERE id=$id");
        $msg = "<div class='alert-error'>❌ Offer Letter Rejected. (Invalid or Fake Document).</div>";
    }
}

/* 📊 3. FETCH ALL SELECTED STUDENTS */
$sql = "SELECT a.id as app_id, a.offer_letter, a.offer_status, s.name as student_name, s.department, c.company_name, j.title, j.salary 
        FROM applications a 
        JOIN students s ON a.student_id = s.id 
        JOIN jobs j ON a.job_id = j.id 
        JOIN companies c ON j.company_id = c.id 
        WHERE a.status = 'selected' 
        ORDER BY a.id DESC";
$offers = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Offer Letters Verification</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:40px;max-width:1200px;margin:auto}

.header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-flex h1 { font-size: 24px; font-weight: 800; color: #f8fafc; display: flex; align-items: center; gap: 10px;}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }

table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
td { color: #cbd5e1; }

.status-badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; border: 1px solid transparent; display: inline-block;}
.status-pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3); }
.status-verified { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3); }
.status-rejected { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

.doc-link { color: #38bdf8; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 13px; transition: 0.2s;}
.doc-link:hover { color: #0ea5e9; text-decoration: underline; }
.doc-missing { color: #64748b; font-size: 13px; font-style: italic; }

.btn-action { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; transition: 0.3s; border: 1px solid transparent;}
.btn-verify { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: #10b981; margin-right: 5px;}
.btn-verify:hover { background: rgba(16, 185, 129, 0.2); }
.btn-reject { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: #ef4444; }
.btn-reject:hover { background: rgba(239, 68, 68, 0.2); }

.alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 20px; font-weight: bold; }
.alert-error { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px; font-weight: bold; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="header-flex">
        <h1><i class="fa fa-file-signature" style="color:#38bdf8;"></i> Verify Offer Letters</h1>
    </div>

    <?= $msg ?>

    <div class="card-glass">
        <table>
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Company & Role</th>
                    <th>Package / CTC</th>
                    <th>Document</th>
                    <th>Validation Status</th>
                    <th>Admin Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($offers) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($offers)): ?>
                        <tr>
                            <td>
                                <b style="color: #f8fafc; font-size: 15px;"><?= htmlspecialchars($row['student_name']) ?></b>
                                <div style="color: #94a3b8; font-size: 12px; margin-top: 4px;"><i class="fa fa-graduation-cap"></i> <?= htmlspecialchars($row['department']) ?></div>
                            </td>
                            <td>
                                <span style="color: #10b981; font-weight: bold;"><?= htmlspecialchars($row['company_name']) ?></span>
                                <div style="color: #cbd5e1; font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($row['title']) ?></div>
                            </td>
                            <td><b style="color: #f59e0b;">₹ <?= htmlspecialchars($row['salary']) ?></b></td>
                            <td>
                                <?php if (!empty($row['offer_letter'])): ?>
                                    <a href="../uploads/offers/<?= $row['offer_letter'] ?>" target="_blank" class="doc-link"><i class="fa fa-file-pdf"></i> View PDF</a>
                                <?php else: ?>
                                    <span class="doc-missing"><i class="fa fa-clock"></i> Not Uploaded Yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $s_class = 'status-pending';
                                    if($row['offer_status'] == 'Verified') $s_class = 'status-verified';
                                    if($row['offer_status'] == 'Rejected') $s_class = 'status-rejected';
                                ?>
                                <span class="status-badge <?= $s_class ?>"><?= $row['offer_status'] ?></span>
                            </td>
                            <td>
                                <?php if ($row['offer_status'] !== 'Verified'): ?>
                                    <a href="?action=verify&id=<?= $row['app_id'] ?>" class="btn-action btn-verify"><i class="fa fa-check"></i> Verify</a>
                                <?php endif; ?>
                                <?php if ($row['offer_status'] !== 'Rejected'): ?>
                                    <a href="?action=reject&id=<?= $row['app_id'] ?>" class="btn-action btn-reject" onclick="return confirm('Reject this offer letter?');"><i class="fa fa-times"></i> Reject</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding: 40px; color: #64748b;"><i class="fa fa-folder-open" style="font-size: 30px; display:block; margin-bottom: 10px;"></i> No selected students found to verify.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>