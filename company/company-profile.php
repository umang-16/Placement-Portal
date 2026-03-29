<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 COMPANY LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];

/* 📊 FETCH CURRENT COMPANY DATA */
$company_query = "SELECT * FROM companies WHERE id = $company_id LIMIT 1";
$company_result = mysqli_query($conn, $company_query);
$company = mysqli_fetch_assoc($company_result);

function getCompanyInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $initials = strtoupper(substr($name, 0, 2));
    }
    return $initials;
}

$company_name = $company['company_name'] ?? 'Company Name';
$logo_file = $company['logo'] ?? '';
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Company Profile - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body{background:#0a0f1a; color: #f8fafc;}

    main{padding:40px;max-width:850px;margin:auto}
    .profile-card { 
        background: rgba(255, 255, 255, 0.02); 
        backdrop-filter: blur(15px); 
        padding: 40px; border-radius: 16px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        text-align: center; 
        border: 1px solid rgba(255,255,255,0.05);
        border-top: 5px solid #38bdf8;
    }
    
    .logo-container {
        width: 140px; height: 140px; border-radius: 50%;
        margin: 0 auto 20px auto; display: flex; align-items: center;
        justify-content: center; font-size: 48px; font-weight: bold;
        color: #0a0f1a; background: #38bdf8; 
        box-shadow: 0 0 25px rgba(56, 189, 248, 0.4); border: 5px solid #0f172a;
        overflow: hidden;
    }
    .company-logo-img { width: 100%; height: 100%; object-fit: cover; }
    
    .company-name { font-size: 32px; color: #f8fafc; font-weight: 800; margin-bottom: 5px; }
    .company-email { color: #94a3b8; font-size: 15px; margin-bottom: 25px; font-weight: 600;}
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; text-align: left; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 30px; }
    .info-item { margin-bottom: 15px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
    .info-item label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .info-item p { color: #f8fafc; font-size: 15px; font-weight: 600; margin: 0;}
    
    .about-section { text-align: left; margin-top: 25px; background: rgba(0,0,0,0.2); padding: 25px; border-radius: 12px; border-left: 5px solid #38bdf8; border: 1px solid rgba(255,255,255,0.05); border-left-width: 5px;}
    .about-section h3 { font-size: 18px; color: #f8fafc; margin-bottom: 10px; font-weight: 800;}
    .about-section p { color: #cbd5e1; line-height: 1.7; font-size: 15px; }

    .btn-edit { display: inline-block; margin-top: 35px; padding: 16px 40px; background: #38bdf8; border: none; color: #0a0f1a; text-decoration: none; border-radius: 8px; font-weight: 800; font-size: 16px; transition: 0.3s; }
    .btn-edit:hover { background: #0ea5e9; transform: translateY(-3px); box-shadow: 0 6px 20px rgba(56, 189, 248, 0.4); }
</style>
<script src="prevent_back.js"></script>
</head>

<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
    <div class="profile-card">
        
        <div class="logo-container">
            <?php if (!empty($logo_file) && file_exists("../uploads/company_logos/" . $logo_file)): ?>
                <img src="../uploads/company_logos/<?= $logo_file ?>" class="company-logo-img" alt="Company Logo">
            <?php else: ?>
                <?= getCompanyInitials($company_name) ?>
            <?php endif; ?>
        </div>
        
        <h1 class="company-name"><?= htmlspecialchars($company_name) ?></h1>
        <p class="company-email"><i class="fa fa-envelope" style="color:#38bdf8;"></i> <?= htmlspecialchars($company['email'] ?? 'Not Added') ?></p>

        <div class="info-grid">
            <div class="info-item">
                <label>Contact Number</label>
                <p><i class="fa fa-phone" style="color:#38bdf8; width: 20px;"></i> <?= htmlspecialchars($company['contact'] ?? 'Not Provided') ?></p>
            </div>
            <div class="info-item">
                <label>Official Website</label>
                <p><i class="fa fa-globe" style="color:#38bdf8; width: 20px;"></i> 
                    <?php if(!empty($company['website'])): ?>
                        <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" style="color:#38bdf8; text-decoration:none; transition: 0.3s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($company['website']) ?></a>
                    <?php else: ?>
                        Not Provided
                    <?php endif; ?>
                </p>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <label>Headquarters Address</label>
                <p><i class="fa fa-location-dot" style="color:#38bdf8; width: 20px;"></i> <?= htmlspecialchars($company['address'] ?? 'Not Provided') ?></p>
            </div>
        </div>

        <div class="about-section">
            <h3><i class="fa fa-building" style="color:#38bdf8; margin-right: 8px;"></i> About the Company</h3>
            <p><?= nl2br(htmlspecialchars($company['about'] ?? 'No description provided yet.')) ?></p>
        </div>

        <a href="company-edit-profile.php" class="btn-edit"><i class="fa fa-pen-to-square"></i> Edit Profile</a>
    </div>
</main>

</body>
</html>
