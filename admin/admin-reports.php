<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if(!isset($_SESSION['admin_id'])){ header("Location: admin-login.php"); exit(); }

/* 📥 EXPORT LOGIC */
if(isset($_GET['export'])){
    $branch_filter = isset($_GET['branch']) ? mysqli_real_escape_string($conn, $_GET['branch']) : 'all';
    $salary_filter = isset($_GET['min_salary']) ? (int)$_GET['min_salary'] : 0;
    
    $where_clause = "WHERE a.status='selected'";
    if($branch_filter != 'all') { $where_clause .= " AND s.department = '$branch_filter'"; }
    if($salary_filter > 0) { $where_clause .= " AND j.salary >= $salary_filter"; }
    
    $sql = "SELECT s.name AS student_name, s.department, s.email, s.contact, c.company_name, j.title AS job_title, j.salary FROM applications a JOIN students s ON a.student_id = s.id JOIN jobs j ON a.job_id = j.id JOIN companies c ON j.company_id = c.id $where_clause ORDER BY j.salary DESC";
    $res = mysqli_query($conn, $sql);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Placement_Report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Student Name', 'Department', 'Email', 'Contact', 'Company', 'Job Role', 'Salary/Package'));
    while($row = mysqli_fetch_assoc($res)){ fputcsv($output, $row); }
    fclose($output); exit();
}

// 🛠️ FILTER LOGIC RESTORED
$branch_filter = isset($_GET['branch']) ? mysqli_real_escape_string($conn, $_GET['branch']) : 'all';
$salary_filter = isset($_GET['min_salary']) ? (int)$_GET['min_salary'] : 0;

$where_clause = "WHERE a.status='selected'";
if($branch_filter != 'all') { $where_clause .= " AND s.department = '$branch_filter'"; }
if($salary_filter > 0) { $where_clause .= " AND j.salary >= $salary_filter"; }

$sql = "SELECT s.name AS student_name, s.department, c.company_name, j.title AS job_title, j.salary FROM applications a JOIN students s ON a.student_id = s.id JOIN jobs j ON a.job_id = j.id JOIN companies c ON j.company_id = c.id $where_clause ORDER BY j.salary DESC";
$placed = mysqli_query($conn, $sql);
$total_filtered = mysqli_num_rows($placed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Placement Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1100px;margin:auto}

.filter-bar { background: rgba(255,255,255,0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.05); padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;}
.filter-form { display: flex; gap: 15px; align-items: center; flex-wrap: wrap;}
select, input[type="number"] { padding: 10px 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: #0f172a; color: #fff; outline: none; }
input[type="number"]::placeholder { color: #64748b; }
.btn-filter { background: #38bdf8; color: #0a0f1a; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;}
.btn-filter:hover { background: #0ea5e9; }
.btn-export { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 10px 20px; border-radius: 8px; font-weight: bold; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 8px;}
.btn-export:hover { background: rgba(16, 185, 129, 0.2); }

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin-top: 15px;}
th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
th { background: rgba(255,255,255,0.02); color: #38bdf8; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
td { color: #cbd5e1; }
.salary-tag { background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; border: 1px solid rgba(16, 185, 129, 0.2);}
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <h1 style="margin-bottom: 20px;"><i class="fa fa-chart-line" style="color: #38bdf8;"></i> Placement Reports</h1>

    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <select name="branch">
                <option value="all" <?= $branch_filter == 'all' ? 'selected' : '' ?>>All Branches</option>
                <option value="Computer Engineering" <?= $branch_filter == 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                <option value="Information Technology" <?= $branch_filter == 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                <option value="Mechanical Engineering" <?= $branch_filter == 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
            </select>
            <input type="number" name="min_salary" placeholder="Min Salary (e.g. 300000)" value="<?= $salary_filter > 0 ? $salary_filter : '' ?>">
            <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> Apply</button>
        </form>
        <a href="?export=1&branch=<?= $branch_filter ?>&min_salary=<?= $salary_filter ?>" class="btn-export"><i class="fa fa-file-csv"></i> Download CSV</a>
    </div>

    <div class="card-glass">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <h2 style="font-size: 18px; color: #f8fafc;">Detailed Placement List</h2>
            <span style="font-size: 13px; color: #38bdf8; font-weight: bold;"><?= $total_filtered ?> Records Found</span>
        </div>
        <table>
        <thead>
        <tr>
          <th>Student Name</th>
          <th>Department</th>
          <th>Company</th>
          <th>Job Role</th>
          <th>Package</th>
        </tr>
        </thead>
        <tbody>
        <?php if($total_filtered > 0): ?>
            <?php while($p = mysqli_fetch_assoc($placed)): ?>
                <tr>
                  <td><b style="color:#f8fafc;"><?= htmlspecialchars($p['student_name']) ?></b></td>
                  <td><?= htmlspecialchars($p['department']) ?></td>
                  <td><?= htmlspecialchars($p['company_name']) ?></td>
                  <td><?= htmlspecialchars($p['job_title']) ?></td>
                  <td><span class='salary-tag'>₹ <?= htmlspecialchars($p['salary']) ?></span></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan='5' style='text-align:center; padding: 40px; color: #64748b;'><i class="fa fa-folder-open" style="font-size: 30px; margin-bottom: 10px; display: block;"></i> No placement records found.</td></tr>
        <?php endif; ?>
        </tbody>
        </table>
    </div>
</main>
</body>
</html>