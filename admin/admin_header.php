<?php
// which page is open (checked)
$current_page = basename($_SERVER['PHP_SELF']);

// pending approval cunter for (Notifications Badge)
if (isset($_SESSION['admin_id']) && isset($conn)) {
    $pend_apps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM applications WHERE status='pending'"))['total'] ?? 0;
    $pend_jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jobs WHERE status='pending'"))['total'] ?? 0;
    $pend_comps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM companies WHERE status='pending'"))['total'] ?? 0;
    $pend_studs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE status='pending'"))['total'] ?? 0;

    $total_pending = $pend_apps + $pend_jobs + $pend_comps + $pend_studs;
} else {
    $total_pending = 0;
}
?>
<style>
    /* 🔴 ADMIN NAVBAR CSS */
    .navbar {
        background: rgba(10, 15, 26, 0.85) !important;
        backdrop-filter: blur(10px) !important;
        padding: 15px 35px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 1000 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5) !important;
    }

    .nav-logo {
        color: #f8fafc !important;
        font-size: 22px !important;
        font-weight: 800 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        text-decoration: none !important;
    }

    .nav-logo i {
        color: #38bdf8 !important;
        font-size: 26px !important;
    }

    .nav-links {
        list-style: none !important;
        display: flex !important;
        gap: 10px !important;
        margin: 0 !important;
        padding: 0 !important;
        align-items: center !important;
    }

    .nav-links a {
        color: #cbd5e1 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        transition: 0.3s !important;
        padding: 7px 14px !important;
        border-radius: 20px !important;
        display: block !important;
    }

    .nav-links a:hover {
        color: #f8fafc !important;
        background: rgba(255, 255, 255, 0.08) !important;
        transform: scale(1.05) !important;
    }

    .nav-links a.active {
        color: #38bdf8 !important;
        background: rgba(56, 189, 248, 0.15) !important;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2) !important;
        text-shadow: 0 0 8px rgba(56, 189, 248, 0.3) !important;
    }

    .nav-badge {
        background: #ef4444 !important;
        color: white !important;
        font-size: 10px !important;
        padding: 2px 7px !important;
        border-radius: 12px !important;
        font-weight: bold !important;
        margin-left: 5px !important;
        vertical-align: top !important;
        border: none !important;
    }
</style>

<nav class="navbar">
    <a href="admin-dashboard.php" class="nav-logo"><i class="fa-solid fa-shield-halved"></i> Admin Center </a>
    <ul class="nav-links">
        <li><a class="<?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>"
                href="admin-dashboard.php">Dashboard</a></li>
        <li>
            <a class="<?= $current_page == 'admin-notifications.php' ? 'active' : '' ?>" href="admin-notifications.php">
                <i class="fa fa-bell"></i> Alerts
                <?php if ($total_pending > 0): ?>
                    <span class="nav-badge"><?= $total_pending ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li><a class="<?= ($current_page == 'admin-students.php' || $current_page == 'admin-import-students.php') ? 'active' : '' ?>"
                href="admin-students.php">Students</a></li>
        <li><a class="<?= $current_page == 'admin-companies.php' ? 'active' : '' ?>"
                href="admin-companies.php">Companies</a></li>
        <li><a class="<?= ($current_page == 'admin-jobs.php' || $current_page == 'admin-job-applicants.php' || $current_page == 'admin-offers.php') ? 'active' : '' ?>"
                href="admin-jobs.php">Jobs</a></li>
        <li><a class="<?= $current_page == 'admin-reports.php' ? 'active' : '' ?>" href="admin-reports.php">Reports</a>
        </li>
        <li><a class="<?= $current_page == 'admin-notices.php' ? 'active' : '' ?>" href="admin-notices.php">Notices</a>
        </li>
        <li><a class="<?= $current_page == 'admin-helpdesk.php' ? 'active' : '' ?>"
                href="admin-helpdesk.php">Helpdesk</a></li>
    </ul>
</nav>