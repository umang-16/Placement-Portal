<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){
    header("Location: login-selection.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$result = mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id");
$student = mysqli_fetch_assoc($result);

$profile_score = 0;
if(!empty($student['name'])) $profile_score += 20;
if(!empty($student['email'])) $profile_score += 20;
if(!empty($student['contact'])) $profile_score += 20;
if(!empty($student['skills'])) $profile_score += 20;
if(!empty($student['resume'])) $profile_score += 20;

$progress_color = $profile_score == 100 ? '#10b981' : '#f59e0b';

$photoFilename = !empty($student['avatar']) ? $student['avatar'] : 'boy.png';
if ($photoFilename === 'boy.png' || $photoFilename === 'girl.png') {
    $photoPath = "../assets/avatars/" . $photoFilename;
} else {
    $photoPath = "../uploads/photos/" . $photoFilename;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* ✨ DARK & GLASS THEME ✨ */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background: #0a0f1a; color: #f8fafc; margin:0; }
    main { max-width: 900px; margin: 40px auto; padding: 0 20px; }
    
    .profile-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
    
    .profile-header { background: rgba(0,0,0,0.3); padding: 40px 30px; display: flex; align-items: center; gap: 25px; border-bottom: 1px solid rgba(255,255,255,0.05);}
    .profile-img { width: 120px; height: 120px; border-radius: 50%; border: 4px solid #38bdf8; object-fit: cover; box-shadow: 0 0 20px rgba(56,189,248,0.4); background: #0f172a; padding: 3px;}
    .profile-info h1 { margin: 0 0 5px 0; font-size: 28px; color: #f8fafc; font-weight: 800;}
    .profile-info p { margin: 0; font-size: 14px; color: #cbd5e1; }
    .profile-info p i { color: #38bdf8; width: 20px; }

    .progress-container { background: rgba(0,0,0,0.2); padding: 20px 30px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .progress-text { display: flex; justify-content: space-between; font-size: 13px; font-weight: 800; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;}
    .progress-bg { background: rgba(255,255,255,0.1); height: 10px; border-radius: 5px; overflow: hidden; }
    .progress-bar { height: 100%; background: <?= $progress_color ?>; transition: width 0.5s ease; box-shadow: 0 0 10px <?= $progress_color ?>;}

    .profile-body { padding: 30px; }
    .section-title { font-size: 18px; color: #f8fafc; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; font-weight: 800; }
    
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-item { background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.03); }
    .info-item label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; letter-spacing: 0.5px;}
    .info-item p { margin: 0; font-size: 15px; color: #f8fafc; font-weight: bold; }

    .skills-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
    .skill-badge { background: rgba(56, 189, 248, 0.1); color: #38bdf8; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; border: 1px solid rgba(56, 189, 248, 0.3); text-transform: uppercase; letter-spacing: 0.5px;}
    
    .bio-text { color: #cbd5e1; font-size: 14px; line-height: 1.6; margin-bottom: 30px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; border-left: 4px solid #38bdf8; font-style: italic;}
    
    .action-btns { display: flex; gap: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; }
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 8px; font-weight: 800; text-decoration: none; font-size: 14px; transition: 0.3s; border: 1px solid transparent;}
    .btn-resume { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }
    .btn-resume:hover { background: #ef4444; color: #fff; }
    .btn-edit { background: #38bdf8; color: #0a0f1a; margin-left: auto; }
    .btn-edit:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }
</style>
</head>
<body>

<?php include 'student_header.php'; ?>

<main>
    <div class="profile-card">
        <div class="profile-header">
            <img src="<?= $photoPath ?>" alt="Avatar" class="profile-img" onerror="this.src='../assets/avatars/boy.png'">
            <div class="profile-info">
                <h1><?= htmlspecialchars($student['name']) ?></h1>
                <p><i class="fa fa-envelope"></i> <?= htmlspecialchars($student['email']) ?></p>
                <p style="margin-top: 5px;"><i class="fa fa-phone"></i> <?= htmlspecialchars($student['contact'] ?? 'Contact not added') ?></p>
            </div>
        </div>

        <div class="progress-container">
            <div class="progress-text">
                <span>Profile Completeness</span>
                <span style="color: <?= $progress_color ?>"><?= $profile_score ?>%</span>
            </div>
            <div class="progress-bg">
                <div class="progress-bar" style="width: <?= $profile_score ?>%;"></div>
            </div>
            <?php if($profile_score < 100): ?>
                <p style="font-size: 11px; color: #f87171; margin: 8px 0 0 0; font-weight: bold;"><i class="fa fa-triangle-exclamation"></i> Complete your profile to 100% to increase hiring chances!</p>
            <?php endif; ?>
        </div>

        <div class="profile-body">
            <?php if(!empty($student['bio'])): ?>
                <div class="bio-text">
                    "<?= nl2br(htmlspecialchars($student['bio'])) ?>"
                </div>
            <?php endif; ?>

            <h3 class="section-title"><i class="fa fa-user-tie" style="color:#38bdf8;"></i> Professional Details</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Department</label>
                    <p><?= htmlspecialchars($student['department']) ?: '<span style="color:#64748b; font-size:13px; font-weight:normal;">Not Specified</span>' ?></p>
                </div>
                <div class="info-item">
                    <label>Account Status</label>
                    <p style="color: #34d399;"><i class="fa fa-circle-check"></i> <?= ucfirst($student['status']) ?></p>
                </div>
            </div>

            <h3 class="section-title"><i class="fa fa-code" style="color:#10b981;"></i> Highlighted Skills</h3>
            <div class="skills-container">
                <?php 
                if(!empty($student['skills'])){
                    $skills = explode(',', $student['skills']);
                    foreach($skills as $skill){
                        if(trim($skill) != "") {
                            echo "<span class='skill-badge'>" . trim(htmlspecialchars($skill)) . "</span>";
                        }
                    }
                } else {
                    echo "<p style='color: #64748b; font-style: italic; font-size: 13px;'>No skills added yet. Please edit your profile.</p>";
                }
                ?>
            </div>

            <div class="action-btns">
                <?php if(!empty($student['resume'])): ?>
                    <a href="../uploads/resumes/<?= $student['resume'] ?>" target="_blank" class="btn btn-resume">
                        <i class="fa fa-file-pdf"></i> View Resume
                    </a>
                <?php else: ?>
                    <span style="color: #f87171; font-size: 13px; font-weight:bold; align-self: center;"><i class="fa fa-xmark"></i> Resume Not Uploaded</span>
                <?php endif; ?>
                
                <a href="edit-profile.php" class="btn btn-edit">
                    <i class="fa fa-pen"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
