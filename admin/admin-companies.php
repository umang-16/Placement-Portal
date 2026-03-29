<?php 
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if(!isset($_SESSION['admin_id'])){ header("Location: admin-login.php"); exit(); }

$msg = "";

/* 🛠️ 1. AUTO-UPDATE DB SCHEME (Review Status Column) */
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM company_reviews LIKE 'status'");
if($check_status && mysqli_num_rows($check_status) == 0) {
    mysqli_query($conn, "ALTER TABLE company_reviews ADD COLUMN status ENUM('visible', 'hidden') DEFAULT 'visible'");
}

/* ✅ 2. COMPANY STATUS LOGIC */
if(isset($_GET['action'], $_GET['id'])){
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if($action === 'approve'){ mysqli_query($conn,"UPDATE companies SET status='approved' WHERE id=$id"); }
    if($action === 'block'){ mysqli_query($conn,"UPDATE companies SET status='blocked' WHERE id=$id"); }
    header("Location: admin-companies.php");
    exit();
}

/* 📝 3. UPDATE COMPANY DETAILS */
if(isset($_POST['update_company'])){
    $c_id = (int)$_POST['company_id'];
    $c_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $c_email = mysqli_real_escape_string($conn, $_POST['email']);
    $c_phone = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $c_addr = mysqli_real_escape_string($conn, $_POST['address']);

    $sql_update = "UPDATE companies SET company_name = '$c_name', email = '$c_email', contact_no = '$c_phone', address = '$c_addr' WHERE id = $c_id";
    if(mysqli_query($conn, $sql_update)){
        $msg = "<div class='alert-success'><i class='fa fa-circle-check'></i> Company details updated successfully!</div>";
    }
}

/* ⭐ 4. REVIEW MODERATION ACTIONS */
if(isset($_GET['review_action']) && isset($_GET['review_id']) && isset($_GET['c_id'])) {
    $r_id = (int)$_GET['review_id'];
    $c_id = (int)$_GET['c_id'];
    $r_act = $_GET['review_action'];
    
    if($r_act == 'hide') mysqli_query($conn, "UPDATE company_reviews SET status='hidden' WHERE id=$r_id");
    if($r_act == 'show') mysqli_query($conn, "UPDATE company_reviews SET status='visible' WHERE id=$r_id");
    if($r_act == 'delete') mysqli_query($conn, "DELETE FROM company_reviews WHERE id=$r_id");
    
    header("Location: admin-companies.php?view_reviews=$c_id");
    exit();
}

/* 📤 5. BULK CSV UPLOAD LOGIC */
if(isset($_POST['upload_csv']) && isset($_FILES['csv_file'])){
    $filename = $_FILES['csv_file']['tmp_name'];
    
    if($_FILES['csv_file']['size'] > 0){
        $file = fopen($filename, "r");
        $count = 0;
        $default_password = password_hash('Company@123', PASSWORD_DEFAULT);
        
        fgetcsv($file); 

        while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
            $c_name = mysqli_real_escape_string($conn, trim($data[0]));
            $c_email = mysqli_real_escape_string($conn, trim($data[1]));
            $c_contact = mysqli_real_escape_string($conn, trim($data[2] ?? ''));
            
            if(!empty($c_name) && !empty($c_email)) {
                $check = mysqli_query($conn, "SELECT id FROM companies WHERE email='$c_email'");
                if(mysqli_num_rows($check) == 0){
                    $sql = "INSERT INTO companies (company_name, email, contact_no, password, status) 
                            VALUES ('$c_name', '$c_email', '$c_contact', '$default_password', 'approved')";
                    if(mysqli_query($conn, $sql)){ $count++; }
                }
            }
        }
        fclose($file);
        $msg = "<div class='alert-success'><i class='fa fa-circle-check'></i> Successfully registered <b>$count</b> new companies!</div>";
    } else {
        $msg = "<div class='alert-error'><i class='fa fa-circle-exclamation'></i> Invalid file uploaded.</div>";
    }
}

/* 🔍 6. SEARCH & FETCH COMPANIES */
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql = "SELECT c.id, c.company_name, c.email, c.contact_no, c.address, c.status, c.website, 
        (SELECT COUNT(*) FROM company_reviews WHERE company_id = c.id) as review_count 
        FROM companies c";
if($search_query !== ''){ 
    $sql .= " WHERE c.company_name LIKE '%$search_query%' OR c.email LIKE '%$search_query%'"; 
}
$sql .= " ORDER BY c.id DESC";
$companies = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Companies</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1200px;margin:auto}

.header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-flex h1 { color: #f8fafc; font-weight: 800; font-size: 24px;}
.actions-flex { display: flex; gap: 10px; align-items: center; }

.search-form { display: flex; gap: 10px; }
.search-input { padding: 10px 15px; width: 250px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; }
.search-input:focus { border-color: #38bdf8; }
.btn-search { background: #38bdf8; color: #0a0f1a; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; }

.btn-csv { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px dashed #10b981; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
.btn-csv:hover { background: #10b981; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
td { color: #cbd5e1; }

.status { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
.approved { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
.pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.blocked { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

.btn { padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; margin-right: 5px; cursor: pointer; border: none; transition: 0.3s;}
.btn-approve { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; }
.btn-approve:hover { background: #10b981; color: #fff; }
.btn-block { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; }
.btn-block:hover { background: #ef4444; color: #fff; }
.btn-edit { background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid #38bdf8; }
.btn-edit:hover { background: #38bdf8; color: #0f172a; }

.btn-review-modal { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 11px; text-decoration: none; font-weight: bold; margin-top: 5px; display: inline-block; transition: 0.3s;}
.btn-review-modal:hover { background: #f59e0b; color: #fff;}

/* 🛠️ Modals CSS */
.edit-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; z-index: 2000; }
.edit-modal-content { background: #0f172a; padding: 30px; width: 400px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
.edit-modal h2 { color: #f8fafc; margin-bottom: 20px; font-size: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 13px; font-weight: 600; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: #fff; outline: none; font-family: inherit;}
.form-group input:focus { border-color: #38bdf8; }
.save-btn { width: 100%; padding: 12px; background: #38bdf8; color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
.save-btn:hover { background: #0ea5e9; }

.review-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 2000; display: flex; justify-content: center; align-items: center; }
.review-modal-content { background: #0f172a; padding: 30px; border-radius: 16px; width: 800px; max-width: 90%; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.5); max-height: 80vh; overflow-y: auto;}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;}
.close-modal { color: #ef4444; font-size: 24px; cursor: pointer; text-decoration: none; transition: 0.3s; background: rgba(239, 68, 68, 0.1); width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; border-radius: 50%;}
.close-modal:hover { background: #ef4444; color: #fff; transform: scale(1.1);}
.review-item { background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.05);}
.star { color: #f59e0b; }
.hidden-tag { font-size: 10px; background: #ef4444; color: #fff; padding: 2px 6px; border-radius: 4px; margin-left: 10px;}

.alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 15px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 20px; font-weight: bold; font-size: 14px;}
.alert-error { background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 15px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); margin-bottom: 20px; font-weight: bold; font-size: 14px;}
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="header-flex">
        <h1>🏢 Manage Companies</h1>
        <div class="actions-flex">
            <button class="btn-csv" onclick="openCsvModal()"><i class="fa fa-file-csv"></i> Bulk Upload</button>
            
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn-search"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </div>

    <?= $msg ?>

    <div class="card-glass">
        <table>
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact Details</th>
                    <th>Status</th>
                    <th>Website</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($companies) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($companies)): ?>
                        <tr>
                            <td>
                                <b style="color: #f8fafc; font-size:15px;"><?= htmlspecialchars($row['company_name']) ?></b>
                                <div style="color:#94a3b8; font-size:12px; margin-top:3px;"><i class="fa fa-map-marker-alt"></i> <?= htmlspecialchars($row['address'] ?? 'N/A') ?></div>
                                
                                <?php if($row['review_count'] > 0): ?>
                                    <a href="?view_reviews=<?= $row['id'] ?>" class="btn-review-modal"><i class="fa fa-star"></i> <?= $row['review_count'] ?> Reviews</a>
                                <?php else: ?>
                                    <span style="font-size:11px; color:#64748b; display:block; margin-top:5px; font-style: italic;">No reviews yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><i class="fa fa-envelope" style="color:#94a3b8;"></i> <?= htmlspecialchars($row['email']) ?></div>
                                <div style="margin-top:4px;"><i class="fa fa-phone" style="color:#94a3b8;"></i> <?= htmlspecialchars($row['contact_no']) ?></div>
                            </td>
                            <td><span class="status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <?php if(!empty($row['website'])): ?>
                                    <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank" style="color: #38bdf8; text-decoration: none;"><i class="fa fa-link"></i> Link</a>
                                <?php else: ?>
                                    <span style="color: #64748b;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-edit" title="Edit Company Details" onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['company_name'])) ?>', '<?= htmlspecialchars(addslashes($row['email'])) ?>', '<?= htmlspecialchars(addslashes($row['contact_no'])) ?>', '<?= htmlspecialchars(addslashes($row['address'])) ?>')"><i class="fa fa-pen"></i></button>
                                <?php if($row['status'] !== 'approved'): ?>
                                    <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-approve" title="Approve"><i class="fa fa-check"></i></a>
                                <?php endif; ?>
                                <?php if($row['status'] !== 'blocked'): ?>
                                    <a href="?action=block&id=<?= $row['id'] ?>" class="btn btn-block" title="Block" onclick="return confirm('Block this company?')"><i class="fa fa-ban"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding: 30px;">No companies found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="csvModal" class="edit-modal">
    <div class="edit-modal-content">
        <span onclick="closeCsvModal()" style="float:right; cursor:pointer; color:#ef4444; font-size:24px;">&times;</span>
        <h2 style="color: #10b981;"><i class="fa fa-file-csv"></i> Bulk Upload Companies</h2>
        <p style="color:#94a3b8; font-size:13px; margin-bottom:15px;">Upload a CSV file to register multiple companies instantly. Default password will be <b style="color:#f8fafc;">Company@123</b>.</p>
        
        <div style="background: rgba(16,185,129,0.1); padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px dashed rgba(16,185,129,0.3);">
            <span style="font-size: 12px; color: #34d399; font-weight: bold;">Required CSV Format (Columns):</span><br>
            <span style="font-size: 12px; color: #f8fafc; font-family: monospace; letter-spacing: 1px;">Company Name, Email, Contact No</span>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select .CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required style="padding: 10px;">
            </div>
            <button type="submit" name="upload_csv" class="save-btn" style="background: #10b981; color: #fff;"><i class="fa fa-upload"></i> Start Upload</button>
        </form>
    </div>
</div>

<div id="editModal" class="edit-modal">
    <div class="edit-modal-content">
        <span onclick="closeModal()" style="float:right; cursor:pointer; color:#ef4444; font-size:24px;">&times;</span>
        <h2><i class="fa fa-pen" style="color:#38bdf8;"></i> Edit Company</h2>
        <form method="POST">
            <input type="hidden" name="company_id" id="edit_id">
            <div class="form-group"><label>Company Name</label><input type="text" name="company_name" id="edit_name" required></div>
            <div class="form-group"><label>Official Email</label><input type="email" name="email" id="edit_email" required></div>
            <div class="form-group"><label>Contact Number</label><input type="text" name="contact_no" id="edit_phone" required></div>
            <div class="form-group"><label>Office Address</label><textarea name="address" id="edit_address" required rows="3"></textarea></div>
            <button type="submit" name="update_company" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<?php if(isset($_GET['view_reviews'])): 
    $view_id = (int)$_GET['view_reviews'];
    $c_q = mysqli_query($conn, "SELECT company_name FROM companies WHERE id=$view_id");
    $c_name = mysqli_fetch_assoc($c_q)['company_name'] ?? 'Company';
    $rev_q = mysqli_query($conn, "SELECT r.*, s.name as student_name FROM company_reviews r JOIN students s ON r.student_id = s.id WHERE r.company_id = $view_id ORDER BY r.id DESC");
?>
    <div class="review-modal-overlay">
        <div class="review-modal-content">
            <div class="modal-header">
                <h2 style="color:#f8fafc; font-size: 20px;"><i class="fa fa-star" style="color:#f59e0b;"></i> Reviews for <?= htmlspecialchars($c_name) ?></h2>
                <a href="admin-companies.php" class="close-modal"><i class="fa fa-times"></i></a>
            </div>
            
            <?php if(mysqli_num_rows($rev_q) > 0): ?>
                <?php while($r = mysqli_fetch_assoc($rev_q)): ?>
                    <div class="review-item">
                        <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                            <b style="color:#38bdf8;"><?= htmlspecialchars($r['student_name']) ?></b>
                            <div>
                                <span class="star"><?php for($i=1; $i<=5; $i++) echo ($i <= $r['rating']) ? "★" : "☆"; ?></span>
                                <?php if($r['status'] == 'hidden'): ?><span class="hidden-tag">Hidden</span><?php endif; ?>
                            </div>
                        </div>
                        <p style="color:#cbd5e1; font-size:14px; margin-bottom:12px; line-height:1.5;">"<?= nl2br(htmlspecialchars($r['comment'])) ?>"</p>
                        
                        <div style="text-align: right;">
                            <?php if($r['status'] == 'visible'): ?>
                                <a href="?review_action=hide&review_id=<?= $r['id'] ?>&c_id=<?= $view_id ?>" class="btn" style="color:#f59e0b; border:1px solid #f59e0b;"><i class="fa fa-eye-slash"></i> Hide</a>
                            <?php else: ?>
                                <a href="?review_action=show&review_id=<?= $r['id'] ?>&c_id=<?= $view_id ?>" class="btn" style="color:#10b981; border:1px solid #10b981;"><i class="fa fa-eye"></i> Show</a>
                            <?php endif; ?>
                            <a href="?review_action=delete&review_id=<?= $r['id'] ?>&c_id=<?= $view_id ?>" class="btn btn-block" onclick="return confirm('Delete this review?')"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#94a3b8; text-align:center;">No reviews found for this company.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function openCsvModal() { document.getElementById('csvModal').style.display = 'flex'; }
function closeCsvModal() { document.getElementById('csvModal').style.display = 'none'; }

function openEditModal(id, name, email, phone, address) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_address').value = address;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
window.onclick = function(event) { 
    if (event.target == document.getElementById('editModal')) closeModal(); 
    if (event.target == document.getElementById('csvModal')) closeCsvModal(); 
}
</script>
</body>
</html>