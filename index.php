<?php
session_start();
require_once "db.php";

/* 📊 FETCH LIVE STATISTICS */
$total_jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jobs WHERE status='approved'"))['total'];
$total_placed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE status='selected'"))['total'];
$total_companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM companies WHERE status='approved'"))['total'];

/* 💬 FETCH STUDENT REVIEWS (ONLY VISIBLE REVIEWS) */
$reviews_query = "
    SELECT r.*, s.name, c.company_name 
    FROM company_reviews r
    JOIN students s ON r.student_id = s.id
    JOIN companies c ON r.company_id = c.id
    JOIN applications a ON (a.student_id = s.id AND a.status = 'selected')
    WHERE r.status = 'visible'
    ORDER BY r.id DESC LIMIT 3";
$reviews = mysqli_query($conn, $reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Placement Portal - Connecting Talent & Opportunity</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    /* ✨ DARK THEME BACKGROUND ✨ */
    body{background:#0a0f1a; color: #f8fafc; scroll-behavior: smooth;}

    /* ========================================= */
    /* ✨ SPLASH SCREEN CSS ✨ */
    /* ========================================= */
    #splash-screen {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      background: #0a0f1a; 
      display: flex; flex-direction: column;
      justify-content: center; align-items: center;
      z-index: 999999;
      transition: opacity 0.8s ease, visibility 0.8s ease;
    }
    .splash-logo {
      font-size: 38px; color: #f8fafc; font-weight: 900;
      letter-spacing: 2px; text-transform: uppercase;
      display: flex; align-items: center; gap: 15px;
      animation: neon-glow 1.5s infinite alternate;
    }
    .splash-logo i {
      color: #38bdf8; font-size: 50px;
      text-shadow: 0 0 20px rgba(56, 189, 248, 0.5);
    }
    .loading-bar {
      width: 250px; height: 3px; background: rgba(255,255,255,0.05);
      margin-top: 25px; border-radius: 5px; overflow: hidden; position: relative;
    }
    .loading-bar::before {
      content: ""; position: absolute; top: 0; left: -100%;
      width: 100%; height: 100%; background: #38bdf8;
      box-shadow: 0 0 15px #38bdf8;
      animation: cyber-load 1.8s infinite ease-in-out;
    }
    .splash-text {
      color: #94a3b8; font-size: 11px; margin-top: 15px; 
      letter-spacing: 3px; text-transform: uppercase; font-weight: bold;
      animation: pulse-text 1.5s infinite;
    }
    @keyframes neon-glow {
      0% { text-shadow: 0 0 10px rgba(56, 189, 248, 0.1); transform: scale(1); }
      100% { text-shadow: 0 0 30px rgba(56, 189, 248, 0.8); transform: scale(1.05); }
    }
    @keyframes cyber-load {
      0% { left: -100%; } 50% { left: 0%; } 100% { left: 100%; }
    }
    @keyframes pulse-text {
      0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; }
    }
    .hide-splash { opacity: 0; visibility: hidden; }

    /* 📜 NEWS TICKER */
    .news-ticker { background: rgba(15, 23, 42, 0.8); color: #cbd5e1; padding: 12px 0; display: flex; align-items: center; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .ticker-label { background: #38bdf8; color: #0f172a; padding: 4px 12px; border-radius: 4px; font-weight: 800; margin-left: 20px; margin-right: 15px; white-space: nowrap; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;}

    /* 🌟 HERO SECTION */
    .hero { text-align: center; padding: 140px 20px 160px; background: radial-gradient(circle at center, #1e3a8a 0%, #0a0f1a 70%); position: relative; overflow: hidden;}
    .hero h1 { font-size: 56px; margin-bottom: 20px; font-weight: 800; letter-spacing: -1px; text-shadow: 0 4px 15px rgba(0,0,0,0.5);}
    .hero h1 span { color: #38bdf8; }
    .hero p { font-size: 20px; color: #94a3b8; max-width: 700px; margin: auto; line-height: 1.6; }

    /* 📊 STATS SECTION */
    .stats-bar { display: flex; justify-content: center; gap: 40px; background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); padding: 35px 20px; margin-top: -60px; width: 85%; max-width: 1000px; margin-left: auto; margin-right: auto; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 20px 40px rgba(0,0,0,0.5); position: relative; z-index: 10;}
    .stat-item { text-align: center; flex: 1; transition: 0.3s; cursor: pointer; border-right: 1px solid rgba(255,255,255,0.05); }
    .stat-item:last-child { border-right: none; }
    .stat-item:hover { transform: translateY(-5px); }
    .stat-item i { font-size: 28px; color: #38bdf8; margin-bottom: 15px; text-shadow: 0 0 15px rgba(56, 189, 248, 0.5); }
    .stat-item h2 { color: #f8fafc; font-size: 38px; font-weight: 800; }
    .stat-item p { color: #94a3b8; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }

    /* SECTIONS */
    .section{padding:80px 20px;max-width:1100px;margin:auto;}
    .section-title { text-align: center; margin-bottom: 50px; color: #f8fafc; font-size: 34px; font-weight: 800; letter-spacing: -0.5px;}

    /* 🎓 WHO CAN USE CARDS */
    .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;margin-top:20px;}
    .card{background: rgba(255,255,255,0.02); backdrop-filter: blur(10px); padding:45px 30px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.3);text-align:center; transition: 0.4s; border: 1px solid rgba(255,255,255,0.05); cursor: pointer; border-bottom: 5px solid transparent;}
    .card:hover { transform: translateY(-10px); background: rgba(255,255,255,0.05); box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .card.student:hover { border-bottom-color: #38bdf8; }
    .card.company:hover { border-bottom-color: #10b981; }

    /* 🏢 PARTNER LOGOS */
    .partner-card-container { background: rgba(255,255,255,0.02); backdrop-filter: blur(10px); padding: 50px 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin: 20px auto; max-width: 1000px; text-align: center; border: 1px solid rgba(255,255,255,0.05); border-top: 5px solid #1e3a8a;}
    .company-logos-flex { display: flex; justify-content: center; align-items: center; gap: 20px; flex-wrap: wrap; }
    
    .partner-chip { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 8px 20px 8px 8px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.08); transition: 0.3s ease; cursor: pointer; }
    .partner-chip:hover { background: rgba(255,255,255,0.08); transform: translateY(-3px); border-color: #38bdf8; box-shadow: 0 5px 15px rgba(56, 189, 248, 0.15); }
    .partner-img-wrapper { width: 45px; height: 45px; background: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; overflow: hidden; padding: 4px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
    .partner-img-wrapper img { width: 100%; height: 100%; object-fit: contain; }
    .partner-name { color: #f8fafc; font-weight: 600; font-size: 15px; letter-spacing: 0.5px; }

    /* 🌟 REVIEWS */
    .reviews-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
    .review-card { background: rgba(255,255,255,0.02); backdrop-filter: blur(10px); padding: 35px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-top: 5px solid #f59e0b; position: relative; transition: 0.3s;}
    .review-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
    .review-card i.quote { position: absolute; top: 25px; right: 25px; font-size: 35px; color: rgba(255,255,255,0.1); }
    .review-card p { font-style: italic; color: #cbd5e1; margin-bottom: 20px; line-height: 1.7; font-size: 15px;}
    .review-user { font-weight: 800; color: #f8fafc; font-size: 16px; }

    footer{text-align:center;padding:35px;background: #05080f;color:#64748b; border-top: 1px solid rgba(255,255,255,0.05); font-size: 15px;}
    footer strong { color: #38bdf8; }

    .confirm-modal { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); justify-content: center; align-items: center; z-index: 10001; backdrop-filter: blur(10px); }
    .confirm-box { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); padding: 40px; width: 400px; border-radius: 20px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: popUp 0.3s ease-out; }
    @keyframes popUp { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .confirm-box i { font-size: 55px; color: #f59e0b; margin-bottom: 20px; text-shadow: 0 0 20px rgba(245, 158, 11, 0.4); }
    .confirm-box h3 { color: #f8fafc; margin-bottom: 12px; font-size: 24px; font-weight: 800;}
    .confirm-box p { color: #cbd5e1; margin-bottom: 30px; line-height: 1.6; font-size: 15px;}
    .confirm-btns { display: flex; gap: 15px; }
    .btn-ok { flex: 1; background: #38bdf8; color: #0f172a; border: none; padding: 14px; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 16px; transition: 0.3s;}
    .btn-ok:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56,189,248,0.4); }
    .btn-cancel { flex: 1; background: rgba(255,255,255,0.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); padding: 14px; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 16px; transition: 0.3s;}
    .btn-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }

  </style>
</head>

<body>

<div id="splash-screen">
  <div class="splash-logo">
    <i class="fa-solid fa-graduation-cap"></i> Placement Portal
  </div>
  <div class="loading-bar"></div>
  <p class="splash-text">Initializing Secure System...</p>
</div>

<?php include 'header.php'; ?>

<div class="news-ticker">
    <div class="ticker-label">🔥 Live Updates</div>
    <marquee behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
        🚀 <strong>Latest Drive:</strong> Multiple MNCs visiting campus next week &nbsp;|&nbsp; 🏆 <strong>Success:</strong> <?= $total_placed ?>+ Students Placed this season! &nbsp;|&nbsp; 🏢 Partnered with <strong><?= $total_companies ?>+</strong> verified companies.
    </marquee>
</div>

<div class="hero">
  <h1>Launch Your Career <span>Today</span></h1>
  <p>Building the perfect bridge between top academic talent and industry-leading opportunities.</p>
</div>

<div class="stats-bar">
    <div class="stat-item" onclick="checkLogin('student/student-dashboard.php')">
        <i class="fa fa-briefcase"></i>
        <h2><?= $total_jobs ?>+</h2>
        <p>Active Jobs</p>
    </div>
    <div class="stat-item" onclick="checkLogin('student/dashboard.php')">
        <i class="fa fa-user-check" style="color: #10b981;"></i>
        <h2><?= $total_placed ?>+</h2>
        <p>Students Placed</p>
    </div>
    <div class="stat-item" onclick="checkLogin('company/company-dashboard.php')">
        <i class="fa fa-handshake" style="color: #f59e0b;"></i>
        <h2><?= $total_companies ?>+</h2>
        <p>Hiring Partners</p>
    </div>
</div>

<div class="section">
  <h2 class="section-title">Who is this portal for?</h2>
  <div class="cards">
    <div class="card student" onclick="checkLogin('student/dashboard.php')">
      <div style="width: 80px; height: 80px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: #38bdf8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 25px; box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);">
        <i class="fa fa-user-graduate"></i>
      </div>
      <h3 style="color: #f8fafc; font-size: 22px; margin-bottom: 10px; font-weight: 800;">For Students</h3>
      <p style="color: #94a3b8; font-size: 15px; line-height: 1.6;">Build a standout profile, upload your resume, and apply to jobs directly with a single click.</p>
    </div>
    <div class="card company" onclick="checkLogin('company/company-dashboard.php')">
      <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 25px; box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);">
        <i class="fa fa-building"></i>
      </div>
      <h3 style="color: #f8fafc; font-size: 22px; margin-bottom: 10px; font-weight: 800;">For Companies</h3>
      <p style="color: #94a3b8; font-size: 15px; line-height: 1.6;">Post vacancies, shortlist candidates, schedule interviews, and find your next best hire.</p>
    </div>
  </div>
</div>

<div class="section" style="background: rgba(255,255,255,0.01); max-width: 100%; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03);">
  <div style="max-width: 1100px; margin: auto;">
    <h2 class="section-title">Trusted By Top Organizations</h2>
    <div class="partner-card-container">
        <div class="company-logos-flex">
            <?php 
            $enrolled_query = "SELECT logo, company_name FROM companies WHERE status='approved' AND logo IS NOT NULL AND logo != '' LIMIT 12";
            $enrolled_res = mysqli_query($conn, $enrolled_query);
            if(mysqli_num_rows($enrolled_res) > 0) {
                while($comp = mysqli_fetch_assoc($enrolled_res)) {
                    $logoPath = "uploads/company_logos/" . $comp['logo'];
                    $cName = htmlspecialchars($comp['company_name']);
                    
                    echo "
                    <div class='partner-chip' title='{$cName}'>
                        <div class='partner-img-wrapper'>
                            <img src='{$logoPath}' alt='{$cName}' onerror=\"this.src='https://ui-avatars.com/api/?name=" . urlencode($cName) . "&background=random&color=fff';\">
                        </div>
                        <span class='partner-name'>{$cName}</span>
                    </div>";
                }
            } else { 
                echo "<p style='color:#94a3b8; font-style: italic; font-weight: 500;'>Top partners are actively joining our platform.</p>"; 
            }
            ?>
        </div>
    </div>
  </div>
</div>

<div class="section">
  <h2 class="section-title">Candidate Success Stories</h2>
  <div class="reviews-grid">
    <?php if(mysqli_num_rows($reviews) > 0): ?>
      <?php while($r = mysqli_fetch_assoc($reviews)): ?>
        <div class="review-card">
          <i class="fa fa-quote-right quote"></i>
          <p>"<?= htmlspecialchars($r['comment']) ?>"</p>
          <div class="review-user">
            <?= htmlspecialchars($r['name']) ?> <br>
            <span style="color: #f59e0b; font-size: 12px; margin-top: 4px; display: inline-block;"><?= str_repeat("⭐", $r['rating']) ?></span><br>
            <span style="color: #38bdf8; font-size: 13px; font-weight: 600;">Hired by <?= htmlspecialchars($r['company_name']) ?></span>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div style="text-align: center; grid-column: 1/-1; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1);">
          <i class="fa fa-comments" style="font-size: 40px; color: rgba(255,255,255,0.2); margin-bottom: 10px;"></i>
          <p style="color: #94a3b8; font-weight: 600;">Success stories will be displayed here soon.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="section" id="contact" style="background: rgba(0,0,0,0.2); max-width: 100%; border-top: 1px solid rgba(255,255,255,0.05); padding-bottom: 80px;">
  <div style="text-align: center; max-width: 900px; margin: auto;">
    <h2 class="section-title">Need Help? Contact Placement Cell</h2>
    <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; margin-top: 40px;">
        <div style="background: rgba(255,255,255,0.02); padding: 30px; border-radius: 16px; flex: 1; border-top: 4px solid #38bdf8; border: 1px solid rgba(255,255,255,0.05); border-top-width: 4px;">
            <i class="fa fa-envelope" style="font-size: 35px; color: #38bdf8; margin-bottom: 15px; text-shadow: 0 0 15px rgba(56, 189, 248, 0.4);"></i>
            <p style="font-size: 16px; font-weight: 600; color: #f8fafc;">placement@college.edu</p>
        </div>
        <div style="background: rgba(255,255,255,0.02); padding: 30px; border-radius: 16px; flex: 1; border-top: 4px solid #10b981; border: 1px solid rgba(255,255,255,0.05); border-top-width: 4px;">
            <i class="fa fa-phone" style="font-size: 35px; color: #10b981; margin-bottom: 15px; text-shadow: 0 0 15px rgba(16, 185, 129, 0.4);"></i>
            <p style="font-size: 16px; font-weight: 600; color: #f8fafc;">+91 98765 43210</p>
        </div>
        <div style="background: rgba(255,255,255,0.02); padding: 30px; border-radius: 16px; flex: 1; border-top: 4px solid #f59e0b; border: 1px solid rgba(255,255,255,0.05); border-top-width: 4px;">
            <i class="fa fa-location-dot" style="font-size: 35px; color: #f59e0b; margin-bottom: 15px; text-shadow: 0 0 15px rgba(245, 158, 11, 0.4);"></i>
            <p style="font-size: 16px; font-weight: 600; color: #f8fafc;">College Campus, Rajkot</p>
        </div>
    </div>
  </div>
</div>

<footer> 
    © <?= date('Y') ?> <strong>Placement Portal</strong> | Connecting Talent & Opportunity | Developed with ❤️ 
</footer>

<div class="confirm-modal" id="loginConfirmModal">
    <div class="confirm-box">
        <i class="fa fa-lock"></i>
        <h3>Authentication Required</h3>
        <p>You need to sign in to access the portal dashboards and detailed statistics.</p>
        <div class="confirm-btns">
            <button class="btn-ok" onclick="proceedToLogin()">Go to Login</button>
            <button class="btn-cancel" onclick="closeConfirmModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
window.addEventListener("load", function() {
    setTimeout(function() {
        var splash = document.getElementById("splash-screen");
        if(splash) {
            splash.classList.add("hide-splash");
            setTimeout(() => { splash.style.display = 'none'; }, 800);
        }
    }, 2200);
});

// Login Check Logic
let pendingTargetPage = 'login-selection.php';

function checkLogin(targetPage) {
    pendingTargetPage = targetPage;
    document.getElementById("loginConfirmModal").style.display = "flex";
}

function proceedToLogin() {
    window.location.href = pendingTargetPage;
}

function closeConfirmModal() {
    document.getElementById("loginConfirmModal").style.display = "none";
}
</script>
</body>
</html>