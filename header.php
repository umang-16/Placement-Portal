<?php
// આ નાનકડો કોડ જાતે જ શોધી લેશે કે કયું પેજ ખુલ્લું છે, જેથી તે મેનુમાં "active" કલર સેટ કરી શકે.
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* 🔵 NAVBAR CSS (હવે આ બધા પેજમાં આપોઆપ લાગુ પડશે) */
    .navbar { background: rgba(10, 15, 26, 0.85); backdrop-filter: blur(10px); padding: 12px 35px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 30px rgba(0,0,0,0.5); }
    .nav-logo-container { display: flex; align-items: center; text-decoration: none; }
    
    /* લોગોની સાઈઝ 2.2 વાળી છે */
    .nav-logo-img { height: 48px; width: auto; transform: scale(1.5); transform-origin: left center; transition: transform 0.3s ease; }
    .nav-logo-container:hover .nav-logo-img { transform: scale(1.6); }

    .nav-links { list-style: none; display: flex; gap: 15px; align-items: center; margin: 0; }
    .nav-links li a { 
        color: #cbd5e1; 
        text-decoration: none; 
        font-size: 15px; 
        font-weight: 600; 
        transition: all 0.3s ease; 
        padding: 8px 18px; 
        border-radius: 20px;
        display: block;
    }
    .nav-links li a:hover { 
        color: #f8fafc; 
        background: rgba(255, 255, 255, 0.08); 
        transform: scale(1.05); 
    }
    .nav-links li a.active { 
        color: #38bdf8; 
        background: rgba(56, 189, 248, 0.15); 
        box-shadow: 0 4px 15px rgba(56, 189, 248, 0.2);
        text-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
    }
</style>

<nav class="navbar">
  <a href="index.php" class="nav-logo-container">
    <img src="assets/logo.png" onerror="this.src='assets/logo.jpg'" alt="Placement Portal Logo" class="nav-logo-img">
  </a>

  <ul class="nav-links">
    <li><a href="index.php" class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>">Home</a></li>
    <li><a href="about.php" class="<?= ($currentPage == 'about.php') ? 'active' : '' ?>">About Us</a></li>
    <li><a href="login-selection.php" class="<?= ($currentPage == 'login-selection.php') ? 'active' : '' ?>">Login</a></li>
    <li><a href="register-selection.php" class="<?= ($currentPage == 'register-selection.php') ? 'active' : '' ?>">Register</a></li>
    <li><a href="index.php#contact">Contact</a></li>
  </ul>
</nav>