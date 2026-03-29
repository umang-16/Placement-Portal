<?php
session_start();
require_once "db.php";

/* 📊 FETCH LIVE STATS FOR DYNAMIC CONTENT */
$total_placed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE status='selected'"))['total'];
$total_companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM companies WHERE status='approved'"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us - Placement Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    /* ✨ DARK THEME BACKGROUND ✨ */
    body{background:#0a0f1a; color: #f8fafc; scroll-behavior: smooth;}

    /* HEADER SECTION */
    .about-header{text-align:center;padding:120px 20px;background: radial-gradient(circle at top, #1e3a8a 0%, #0a0f1a 70%);color:#fff; position: relative; overflow: hidden; border-bottom: 1px solid rgba(255,255,255,0.05);}
    .about-header h1{font-size:56px;margin-bottom:20px; font-weight: 800; letter-spacing: -1px; text-shadow: 0 4px 15px rgba(0,0,0,0.5);}
    .about-header h1 span { color: #38bdf8; }
    .about-header p{font-size:20px; color: #cbd5e1; max-width: 750px; margin: auto; line-height: 1.7;}
    
    /* CONTENT SECTIONS */
    .section{padding:80px 20px;max-width:1100px;margin:auto;}
    .card{background: rgba(255,255,255,0.02); backdrop-filter: blur(10px); padding:50px;border-radius:20px;box-shadow:0 15px 35px rgba(0,0,0,0.3);margin-bottom:50px; line-height: 1.8; transition: 0.3s; border: 1px solid rgba(255,255,255,0.05); border-top: 6px solid #38bdf8;}
    .card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.04); box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .card h2{color:#f8fafc; margin-bottom:25px; font-size: 30px; display: flex; align-items: center; gap: 15px; font-weight: 800;}
    .card h2 i { background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 15px; border-radius: 12px; font-size: 24px; border: 1px solid rgba(56, 189, 248, 0.2); }
    .card p { font-size: 16px; color: #cbd5e1; }
    .card strong { color: #38bdf8; }

    /* 📊 STATS BOXES (TRANSPARENT GLASS) */
    .stats-row{display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:30px; margin-top:20px; text-align: center;}
    .stat-box{padding:40px; background: rgba(255,255,255,0.02); backdrop-filter: blur(10px); border-radius:20px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 25px rgba(0,0,0,0.3); transition: 0.3s;}
    .stat-box:hover { transform: translateY(-10px); background: rgba(255,255,255,0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
    .stat-box i { font-size: 45px; color: #38bdf8; margin-bottom: 20px; text-shadow: 0 0 15px rgba(56, 189, 248, 0.5); }
    .stat-box h3{font-size:42px; color:#f8fafc; font-weight: 800; margin-bottom: 5px;}
    .stat-box p { color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 1.5px; }

    footer{text-align:center;padding:35px;background: #05080f;color:#64748b; border-top: 1px solid rgba(255,255,255,0.05); font-size: 15px; margin-top: 50px;}
    footer strong { color: #38bdf8; }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="about-header">
  <h1>Empowering <span>Careers</span></h1>
  <p>Dedicated to bridging the gap between exceptional academic talent and world-class industry opportunities.</p>
</div>

<div class="section">
  <div class="card">
    <h2><i class="fa fa-bullseye"></i> Our Mission</h2>
    <p>The primary goal of the <strong>Placement Portal</strong> is to simplify and accelerate the hiring process. We empower students by providing a robust digital platform to showcase their technical and soft skills, while giving companies an intuitive dashboard to find and recruit their next top performers. We believe in total transparency, efficiency, and unrestricted growth for every aspirant.</p>
  </div>

  <div class="stats-row">
      <div class="stat-box">
          <i class="fa fa-handshake"></i>
          <h3><?= $total_companies ?>+</h3>
          <p>Verified Partners</p>
      </div>
      <div class="stat-box">
          <i class="fa fa-user-graduate" style="color: #10b981; text-shadow: 0 0 15px rgba(16, 185, 129, 0.4);"></i>
          <h3><?= $total_placed ?>+</h3>
          <p>Careers Launched</p>
      </div>
      <div class="stat-box">
          <i class="fa fa-bolt" style="color: #f59e0b; text-shadow: 0 0 15px rgba(245, 158, 11, 0.4);"></i>
          <h3>100%</h3>
          <p>Automated Process</p>
      </div>
  </div>

  <div class="card" style="margin-top: 50px; border-top-color: #10b981;">
    <h2><i class="fa fa-shield-halved" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2);"></i> Why Choose Us?</h2>
    <p>Unlike traditional placement methods, our portal offers a highly centralized and real-time management system. Students can track their <strong>application status instantly</strong>, receive timely notifications for interviews, and maintain a 100% digital profile. For companies, we provide advanced filtering and a clean database of candidates, making the entire shortlisting process 5x faster and completely hassle-free.</p>
  </div>
</div>

<footer>
  © <?= date('Y') ?> <strong>Placement Portal</strong> | Rajkot, Gujarat | Built for the Future ❤️
</footer>

</body>
</html>