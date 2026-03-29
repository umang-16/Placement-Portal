<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if (!isset($_SESSION['admin_id'])) { header("Location: admin-login.php"); exit(); }

/* 📝 UPDATE STUDENT DETAILS */
if(isset($_POST['update_student'])){
    $s_id = (int)$_POST['student_id'];
    $s_name = mysqli_real_escape_string($conn, $_POST['name']);
    $s_email = mysqli_real_escape_string($conn, $_POST['email']);
    $s_phone = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $s_dept = mysqli_real_escape_string($conn, $_POST['department']);

    $sql_update = "UPDATE students SET name = '$s_name', email = '$s_email', contact = '$s_phone', department = '$s_dept' WHERE id = $s_id";
    mysqli_query($conn, $sql_update);
}

/* ✅ ACTION LOGIC */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') { mysqli_query($conn, "UPDATE students SET status='approved' WHERE id=$id"); }
    elseif ($action === 'block') { mysqli_query($conn, "UPDATE students SET status='blocked' WHERE id=$id"); }
    header("Location: admin-students.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT * FROM students";
if (!empty($search)) { $sql .= " WHERE name LIKE '%$search%' OR email LIKE '%$search%'"; }
$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1200px;margin:auto}

.header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-flex h1 { color: #f8fafc; font-weight: 800; }
.search-form { display: flex; gap: 10px; }
.search-input { padding: 10px 15px; width: 250px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; }
.search-input:focus { border-color: #38bdf8; }
.btn-search { background: #38bdf8; color: #0a0f1a; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
td { color: #cbd5e1; }

.status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
.approved { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
.pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.blocked { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

.btn { padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; margin-right: 5px; cursor: pointer; border: none;}
.btn-approve { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
.btn-block { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }
.btn-edit { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid #38bdf8; }

.modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: #0f172a; padding: 30px; width: 400px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
.modal h2 { color: #f8fafc; margin-bottom: 20px; font-size: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 13px; font-weight: 600; }
.form-group input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; }
.form-group input:focus { border-color: #38bdf8; }
.save-btn { width: 100%; padding: 12px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
.save-btn:hover { background: #0ea5e9; }
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="header-flex">
        <h1>🎓 Manage Students</h1>
        <div style="display: flex; gap: 15px;">
            <a href="admin-import-students.php" style="background: #10b981; color: #fff; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 1px solid #059669; display: flex; align-items: center; gap: 8px;"><i class="fa fa-file-csv"></i> Bulk Import</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-search"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </div>

    <div class="card-glass">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Details</th>
                    <th>Contact / Branch</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>
                            <td>
                                <b style="color: #f8fafc;"><?= htmlspecialchars($row['name']) ?></b>
                                <div style="color: #94a3b8; font-size: 12px; margin-top: 4px;"><?= htmlspecialchars($row['email']) ?></div>
                            </td>
                            <td>
                                <div><i class="fa fa-phone" style="color:#38bdf8;"></i> <?= htmlspecialchars($row['contact'] ?? 'N/A') ?></div>
                                <div style="margin-top: 4px; font-weight: bold; font-size: 12px;"><i class="fa fa-book"></i> <?= htmlspecialchars($row['department'] ?? 'N/A') ?></div>
                            </td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn btn-edit" onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars(addslashes($row['email'])) ?>', '<?= htmlspecialchars(addslashes($row['contact'])) ?>', '<?= htmlspecialchars(addslashes($row['department'])) ?>')"><i class="fa fa-pen"></i></button>
                                <?php if ($row['status'] !== 'approved'): ?>
                                    <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-approve" title="Approve"><i class="fa fa-check"></i></a>
                                <?php endif; ?>
                                <?php if ($row['status'] !== 'blocked'): ?>
                                    <a href="?action=block&id=<?= $row['id'] ?>" class="btn btn-block" title="Block" onclick="return confirm('Block this student?')"><i class="fa fa-ban"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding: 30px;">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span onclick="closeModal()" style="float:right; cursor:pointer; color:#ef4444; font-size:20px;">&times;</span>
        <h2>Edit Student</h2>
        <form method="POST">
            <input type="hidden" name="student_id" id="edit_id">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required></div>
            <div class="form-group"><label>Email Address</label><input type="email" name="email" id="edit_email" required></div>
            <div class="form-group"><label>Contact Number</label><input type="text" name="contact_no" id="edit_phone" required></div>
            <div class="form-group"><label>Department</label><input type="text" name="department" id="edit_department" required></div>
            <button type="submit" name="update_student" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, email, phone, department) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_department').value = department;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(event) { if (event.target == document.getElementById('editModal')) closeModal(); }
</script>
</body>
</html>