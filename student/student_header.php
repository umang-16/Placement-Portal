<?php
// કયું પેજ ખુલ્લું છે તે ચેક કરવા માટે
$current_page = basename($_SERVER['PHP_SELF']);

// જો સ્ટુડન્ટ લોગીન હોય તો નોટિફિકેશન કાઉન્ટ લાવો
if(isset($_SESSION['student_id']) && isset($conn)){
    $student_id = (int)$_SESSION['student_id'];
    $notif_res = mysqli_query($conn, "SELECT COUNT(*) as unread FROM notifications WHERE student_id = $student_id AND is_read = 0");
    $unread_count = $notif_res ? mysqli_fetch_assoc($notif_res)['unread'] : 0;
} else {
    $unread_count = 0;
}
?>
<style>
  /* 🔵 STUDENT NAVBAR CSS */
  .navbar { background: rgba(10, 15, 26, 0.85) !important; backdrop-filter: blur(10px) !important; padding: 15px 35px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; box-shadow: 0 4px 30px rgba(0,0,0,0.5) !important; position: sticky !important; top: 0 !important; z-index: 1000 !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important;}
  .nav-logo-container { display: flex !important; align-items: center !important; text-decoration: none !important; }
  
  /* 👇 અહી મેં લોગોની સાઈઝ 2.2 ગણી કરી છે 👇 */
  .nav-logo-img { height: 45px !important; width: auto !important; transform: scale(1.5); transform-origin: left center; transition: transform 0.3s ease !important; }
  .nav-logo-container:hover .nav-logo-img { transform: scale(1.6) !important; }

  .nav-links { list-style: none !important; display: flex !important; gap: 12px !important; align-items: center !important; margin: 0 !important; padding: 0 !important; }
  .nav-links li a { 
      color: #cbd5e1 !important; 
      text-decoration: none !important; 
      font-size: 14px !important; 
      font-weight: 600 !important; 
      transition: all 0.3s ease !important; 
      padding: 8px 16px !important; 
      border-radius: 20px !important;
      display: block !important;
  }
  .nav-links li a:hover { 
      color: #f8fafc !important; 
      background: rgba(255, 255, 255, 0.08) !important; 
      transform: scale(1.05) !important; 
  }
  .nav-links li a.active { 
      color: #38bdf8 !important; 
      background: rgba(56, 189, 248, 0.15) !important; 
      box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2) !important;
      text-shadow: 0 0 8px rgba(56, 189, 248, 0.3) !important;
  }
  
  .nav-badge { background: #ef4444 !important; color: white !important; padding: 2px 7px !important; border-radius: 12px !important; font-size: 10px !important; margin-left: 5px !important; font-weight: bold !important; vertical-align: top !important; border: none !important;}
</style>

<nav class="navbar">
  <a href="dashboard.php" class="nav-logo-container">
    <img src="../assets/logo.png" alt="Placement Portal Logo" class="nav-logo-img" onerror="this.src='../assets/logo.jpg'">
  </a>
  <ul class="nav-links">
    <li><a class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
    <li>
        <a class="<?= $current_page == 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
            Notifications <?php if($unread_count > 0): ?> <span class="nav-badge"><?= $unread_count ?></span> <?php endif; ?>
        </a>
    </li>
    <li><a class="<?= $current_page == 'companies.php' ? 'active' : '' ?>" href="companies.php">Jobs</a></li>
    <li><a class="<?= $current_page == 'status.php' ? 'active' : '' ?>" href="status.php">Status</a></li>
    <li><a class="<?= $current_page == 'interviews.php' ? 'active' : '' ?>" href="interviews.php">📅 Interviews</a></li>
    <li><a class="<?= ($current_page == 'profile.php' || $current_page == 'edit-profile.php') ? 'active' : '' ?>" href="profile.php">Profile</a></li>
    <li><a href="student-logout.php" style="color: #ef4444 !important; border:none !important;"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</nav>
