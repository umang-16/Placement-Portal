<?php
session_start();

/* 🔐 Security: If not logged in */
if(!isset($_SESSION['company_id'])){
    header("Location: http://localhost/place_portal/index.php");
    exit();
}

/* ✅ IF YES clicked → Logout and Clear Cache */
if(isset($_GET['confirm']) && $_GET['confirm'] === "yes"){
    session_unset();
    session_destroy();

    // 🔥 CLEAR BROWSER CACHE - Security headers for company side
    header("Cache-Control: no-cache, no-store, must-revalidate"); 
    header("Pragma: no-cache"); 
    header("Expires: 0"); 

    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Logout - Company Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ✨ DARK & GLASS THEME ✨ */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  background: #0a0f1a; 
}
.blur-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(10, 15, 26, 0.6);
  backdrop-filter: blur(12px);
  z-index: 1;
}
.box {
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(15px);
  padding: 40px;
  width: 420px;
  border-radius: 20px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  text-align: center;
  position: relative;
  z-index: 10;
}
.icon-circle {
  width: 80px; height: 80px;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 35px;
  margin: 0 auto 20px;
  animation: pulse 2s infinite;
}
h2 { color: #f8fafc; margin-bottom: 12px; font-size: 26px; font-weight: 800; }
p { font-size: 15px; color: #94a3b8; margin-bottom: 30px; }
.buttons { display: flex; gap: 15px; }
.btn {
  flex: 1;
  padding: 14px;
  font-size: 15px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 800;
  transition: 0.3s;
  display: block;
  text-align: center;
  border: 1px solid transparent;
}
.yes { background: #ef4444; color: white; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
.yes:hover { background: #dc2626; transform: translateY(-2px); }
.no { background: rgba(255,255,255,0.05); color: #f8fafc; border-color: rgba(255,255,255,0.1); }
.no:hover { background: rgba(255,255,255,0.1); transform: translateY(-2px); }
@keyframes pulse { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); } 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
</style>
</head>
<body>
<div class="blur-overlay"></div>
<div class="box">
  <div class="icon-circle"><i class="fas fa-sign-out-alt"></i></div>
  <h2>Confirm Logout</h2>
  <p>Are you sure you want to end your company session?</p>
  <div class="buttons">
    <a class="btn yes" href="company-logout.php?confirm=yes">Yes, Logout</a>
    <a class="btn no" href="company-dashboard.php">No, Stay</a>
  </div>
</div>
</body>
</html>