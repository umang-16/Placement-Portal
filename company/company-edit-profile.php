<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 COMPANY LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: ../login-selection.php");
    exit();
}

$company_id = (int)$_SESSION['company_id'];
$success_msg = "";

/* 📊 FETCH CURRENT COMPANY DATA */
$company = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM companies WHERE id=$company_id"));

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

/* 💾 SAVE CHANGES LOGIC */
if(isset($_POST['save_changes'])){
    $name    = mysqli_real_escape_string($conn, $_POST['company_name']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $about   = mysqli_real_escape_string($conn, $_POST['about']);

    // 🖼️ Logo Upload Logic
    if(!empty($_FILES['logo']['name'])){
        $logo_name = time() . '_company_' . $company_id . '_' . $_FILES['logo']['name'];
        $path = "../uploads/company_logos/" . $logo_name;

        if(move_uploaded_file($_FILES['logo']['tmp_name'], $path)){
            mysqli_query($conn, "UPDATE companies SET logo='$logo_name' WHERE id=$company_id");
            $logo_file = $logo_name; 
        }
    }

    $sql = "UPDATE companies SET 
            company_name='$name', 
            contact_no='$contact', 
            website='$website', 
            address='$address', 
            about='$about' 
            WHERE id=$company_id";

    if(mysqli_query($conn, $sql)){
        header("Location: company-profile.php?updated=1");
        exit();
    }
}
?>
<?php include('auth_check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
    body{background:#0a0f1a; color: #f8fafc;}

    main { padding: 40px; }
    .edit-card { 
        max-width: 700px; margin: auto; 
        background: rgba(255, 255, 255, 0.02); 
        backdrop-filter: blur(15px); 
        padding: 40px; border-radius: 16px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
        border: 1px solid rgba(255,255,255,0.05);
    }
    h2 { color: #f8fafc; margin-bottom: 25px; text-align: center; font-weight: 800; font-size: 26px;}
    
    .logo-upload-section { text-align: center; margin-bottom: 30px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);}
    .logo-preview-box { width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; color: #0a0f1a; background: #38bdf8; box-shadow: 0 0 20px rgba(56, 189, 248, 0.3); border: 3px solid #0f172a; overflow: hidden; }
    .logo-preview-img { width: 100%; height: 100%; object-fit: cover; }
    .logo-upload-section label { display: block; margin-bottom: 10px; font-size: 12px; color: #94a3b8; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }

    label { display: block; margin-top: 20px; font-weight: 800; color: #cbd5e1; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;}
    input, textarea { 
        width: 100%; padding: 14px; margin-top: 8px; 
        background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); 
        border-radius: 8px; outline: none; font-size: 14px; color: #fff;
        transition: 0.3s; font-family: inherit;
    }
    input:focus, textarea:focus { border-color: #38bdf8; box-shadow: 0 0 15px rgba(56,189,248,0.2); background: rgba(0,0,0,0.4);}
    
    .btn-group { display: flex; gap: 15px; margin-top: 30px; }
    .btn-save { flex: 2; background: #38bdf8; color: #0a0f1a; border: none; padding: 16px; border-radius: 8px; cursor: pointer; font-weight: 800; transition: 0.3s; font-size: 16px; }
    .btn-save:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }
    .btn-cancel { flex: 1; background: rgba(255,255,255,0.05); color: #f8fafc; border: 1px solid rgba(255,255,255,0.1); padding: 16px; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; font-weight: bold; transition: 0.3s; }
    .btn-cancel:hover { background: rgba(255,255,255,0.1); }
</style>
<script src="prevent_back.js"></script>
</head>
<body onload="preventBack();" onpageshow="if (event.persisted) preventBack();" onunload="">

<?php include 'company_header.php'; ?>

<main>
    <div class="edit-card">
        <h2><i class="fa fa-pen-to-square" style="color: #38bdf8;"></i> Edit Profile Details</h2>
        
        <form method="POST" enctype="multipart/form-data">
            
            <div class="logo-upload-section">
                <div class="logo-preview-box">
                    <?php if (!empty($logo_file) && file_exists("../uploads/company_logos/" . $logo_file)): ?>
                        <img src="../uploads/company_logos/<?= $logo_file ?>" class="logo-preview-img" alt="Logo">
                    <?php else: ?>
                        <?= getCompanyInitials($company_name) ?>
                    <?php endif; ?>
                </div>
                <label>Update Company Logo (JPG/PNG)</label>
                <input type="file" name="logo" accept="image/*" style="border:none; padding:0; width: auto; font-size: 13px; background: transparent; font-weight: bold; color: #38bdf8;">
            </div>

            <label>Company Name</label>
            <input type="text" name="company_name" value="<?= htmlspecialchars($company_name) ?>" required>

            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Contact Number</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($company['contact_no'] ?? '') ?>" placeholder="+91 00000 00000">
                </div>
                <div style="flex: 1;">
                    <label>Website URL</label>
                    <input type="url" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>" placeholder="https://example.com">
                </div>
            </div>

            <label>About Company</label>
            <textarea name="about" rows="4" placeholder="Tell us about your company vision and services..."><?= htmlspecialchars($company['about'] ?? '') ?></textarea>

            <label>Headquarters Address</label>
            <textarea name="address" rows="2" placeholder="Full office address..."><?= htmlspecialchars($company['address'] ?? '') ?></textarea>

            <div class="btn-group">
                <button type="submit" name="save_changes" class="btn-save"><i class="fa fa-check-circle"></i> Save Changes</button>
                <a href="company-profile.php" class="btn-cancel">Discard</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
