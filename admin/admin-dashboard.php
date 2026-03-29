<?php
$lifetime = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params($lifetime);
session_start();
require_once __DIR__ . "/../db.php"; 

if(!isset($_SESSION['admin_id'])){
    header("Location: admin-login.php");
    exit();
}

/* 📊 FETCH BASIC STATISTICS */
$students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students"))['total'];
$companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM companies"))['total'];
$jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jobs"))['total'];

/* ✅ FETCH PENDING APPLICATIONS FOR BOX */
$pending_apps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE status='pending'"))['total'];

/* 🎓 PLACEMENT STATISTICS */
$placed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT student_id) as total FROM applications WHERE status='selected'"))['total'];
$unplaced_count = $students - $placed_count;
if($unplaced_count < 0) $unplaced_count = 0;

/* 📊 CHART DATA: DEPARTMENT WISE REGISTRATIONS */
$dept_query = mysqli_query($conn, "SELECT department, COUNT(*) as total FROM students GROUP BY department");
$dept_labels = [];
$dept_data = [];
while($row = mysqli_fetch_assoc($dept_query)){
    $dept_labels[] = $row['department'] ?: 'Unknown';
    $dept_data[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
body{background:#0a0f1a; color: #f8fafc;}
main{padding:30px;max-width:1200px;margin:auto}

.card-glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
.stat-box{text-align:center; transition: 0.3s; border-bottom: 4px solid #38bdf8; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding: 25px; border-radius: 16px;}
.stat-box:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.04); box-shadow: 0 10px 20px rgba(0,0,0,0.3);}
.stat-box i{font-size:35px;color:#38bdf8;margin-bottom:10px; text-shadow: 0 0 15px rgba(56, 189, 248, 0.4);}
.stat-box h3{font-size:32px;color:#f8fafc; margin-bottom: 5px;}
.stat-box p{color:#94a3b8;font-weight:600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;}

.clickable-box { text-decoration: none; display: block; }
.clickable-box .stat-box { border-bottom-color: #8b5cf6; position: relative;}
.clickable-box .stat-box:hover { border-color: #a855f7; box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);}
.view-details { font-size: 11px; color: #8b5cf6; font-weight: bold; margin-top: 10px; display: inline-block; padding: 4px 8px; border-radius: 10px; background: rgba(139, 92, 246, 0.1); transition: 0.3s;}
.clickable-box:hover .view-details { background: #8b5cf6; color: #fff;}

.charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:20px; margin-bottom: 30px;}
h2 { color: #f8fafc; font-size: 18px; margin-bottom: 20px; font-weight: 800; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;}

.quick-links{list-style:none; padding: 0;}
.quick-links li{margin-bottom:15px;}
.quick-links a{display:block;padding:15px;background:rgba(255,255,255,0.03);color:#cbd5e1;text-decoration:none;border-radius:10px;font-weight:600;transition:0.3s; border: 1px solid rgba(255,255,255,0.05);}
.quick-links a:hover{background:rgba(56,189,248,0.1); color:#38bdf8; border-color: #38bdf8; transform: translateX(5px);}
.quick-links i{margin-right:10px; color: #38bdf8;}
</style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<main>
    <div class="stats-grid">
        <div class="stat-box">
            <i class="fa fa-user-graduate"></i>
            <h3><?= $students ?></h3>
            <p>Total Students</p>
        </div>
        <div class="stat-box" style="border-bottom-color: #10b981;">
            <i class="fa fa-building" style="color: #10b981;"></i>
            <h3><?= $companies ?></h3>
            <p>Total Companies</p>
        </div>
        <div class="stat-box" style="border-bottom-color: #f59e0b;">
            <i class="fa fa-briefcase" style="color: #f59e0b;"></i>
            <h3><?= $jobs ?></h3>
            <p>Total Jobs</p>
        </div>
        
        <a href="admin-notifications.php" class="clickable-box" title="View details in Alerts">
            <div class="stat-box">
                <i class="fa fa-file-signature" style="color: #8b5cf6;"></i>
                <h3><?= $pending_apps ?></h3>
                <p style="color: #cbd5e1;">Pending Applications</p>
                <span class="view-details"><i class="fa fa-arrow-right"></i> View Details in Alerts</span>
            </div>
        </a>
    </div>

    <div class="charts-grid">
        <div class="card-glass" style="text-align: center;">
            <h2><i class="fa fa-chart-pie" style="color: #10b981;"></i> Placement Status</h2>
            <div style="width: 250px; margin: auto;">
                <canvas id="placementChart"></canvas>
            </div>
        </div>
        <div class="card-glass">
            <h2><i class="fa fa-chart-bar" style="color: #38bdf8;"></i> Department-wise Registrations</h2>
            <canvas id="deptChart"></canvas>
        </div>
        <div class="card-glass">
            <h2><i class="fa fa-bolt" style="color: #f59e0b;"></i> Quick Actions</h2>
            <ul class="quick-links">
                <li><a href="admin-notifications.php" style="border-color: #f59e0b; color: #f59e0b;"><i class="fa fa-bell" style="color: #f59e0b;"></i> Check Pending Approvals</a></li>
                <li><a href="admin-companies.php"><i class="fa fa-building-circle-check"></i> Verify Companies</a></li>
                <li><a href="admin-jobs.php"><i class="fa fa-check-double"></i> Verify Job Posts</a></li>
                <li><a href="admin-notices.php"><i class="fa fa-bullhorn"></i> Broadcast Notice</a></li>
            </ul>
        </div>
    </div>
</main>

<script>
Chart.defaults.color = '#cbd5e1';
Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';

// Placement Doughnut Chart
const placedCount = <?= $placed_count ?>;
const unplacedCount = <?= $unplaced_count ?>;
const ctx1 = document.getElementById('placementChart').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: ['Placed', 'Unplaced'],
        datasets: [{ data: [placedCount, unplacedCount], backgroundColor: ['#10b981', '#cbd5e1'], hoverOffset: 4, borderWidth: 0 }]
    },
    options: { plugins: { legend: { labels: { color: '#f8fafc' } } } }
});

// Department Bar Chart
const deptLabels = <?= json_encode($dept_labels) ?>;
const deptData = <?= json_encode($dept_data) ?>;
const ctx2 = document.getElementById('deptChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: deptLabels,
        datasets: [{
            label: 'Registered Students',
            data: deptData,
            backgroundColor: 'rgba(56, 189, 248, 0.8)',
            borderRadius: 6
        }]
    },
    options: { 
        plugins: { legend: { display: false } },
        scales: { 
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
        }
    }
});
</script>
</body>
</html>