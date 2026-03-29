<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 LOGIN CHECK */
if(!isset($_SESSION['student_id'])){ 
    header("Location: ../login-selection.php"); 
    exit(); 
}
$student_id = (int)$_SESSION['student_id'];

$student_res = mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id");
$student = mysqli_fetch_assoc($student_res);

if(isset($_POST['update'])){
    $name    = $_POST['name'];
    $contact = $_POST['contact'];
    $dept    = $_POST['department'];
    $skills  = $_POST['skills'];
    $bio     = $_POST['bio'];
    
    $final_avatar = $_POST['selected_avatar']; 

    if(!empty($_FILES['photo']['name'])){
        $photo_name = time().'_'.str_replace(' ', '_', $_FILES['photo']['name']);
        if(move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/photos/".$photo_name)){
            $old_photo = $student['avatar'];
            if($old_photo && $old_photo != 'boy.png' && $old_photo != 'girl.png' && file_exists("../uploads/photos/".$old_photo)){
                unlink("../uploads/photos/".$old_photo);
            }
            $final_avatar = $photo_name;
        }
    }

    $resume_file = $student['resume']; 
    if(!empty($_FILES['resume']['name'])){
        $new_resume = time().'_'.str_replace(' ', '_', $_FILES['resume']['name']);
        if(move_uploaded_file($_FILES['resume']['tmp_name'], "../uploads/resumes/".$new_resume)){
            if(!empty($student['resume']) && file_exists("../uploads/resumes/".$student['resume'])){
                unlink("../uploads/resumes/".$student['resume']);
            }
            $resume_file = $new_resume;
        }
    }

    $name = mysqli_real_escape_string($conn, $name);
    $contact = mysqli_real_escape_string($conn, $contact);
    $dept = mysqli_real_escape_string($conn, $dept);
    $skills = mysqli_real_escape_string($conn, $skills);
    $bio = mysqli_real_escape_string($conn, $bio);

    $update_sql = "UPDATE students SET name='$name', contact='$contact', department='$dept', skills='$skills', bio='$bio', avatar='$final_avatar', resume='$resume_file' WHERE id=$student_id";
    mysqli_query($conn, $update_sql);
    
    header("Location: profile.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile - Placement Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
  /* ✨ DARK & GLASS THEME ✨ */
  *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif}
  body { background:#0a0f1a; color: #f8fafc; margin:0; }
  
  .edit-container { max-width:650px; margin:40px auto; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.05); padding:40px; border-radius:24px; box-shadow:0 15px 40px rgba(0,0,0,0.2); }
  .section-title { font-size: 13px; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; }
  
  .avatar-grid { display:flex; gap:15px; justify-content:center; margin-bottom:25px; }
  .avatar-item { text-align:center; cursor:pointer; padding:12px; border-radius:16px; border:2px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); transition:0.3s; flex: 1; }
  .avatar-item img { width:65px; height:65px; border-radius:50%; margin-bottom: 5px; background: transparent; pointer-events: none; }
  .avatar-item p { font-size: 12px; font-weight: 700; color: #94a3b8; pointer-events: none; }
  .avatar-item.selected { border-color:#38bdf8; background:rgba(56, 189, 248, 0.1); }
  .avatar-item.selected p { color: #38bdf8; }
  
  label { font-weight:800; display:block; margin-top:18px; color: #cbd5e1; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
  input, select, textarea { width:100%; padding:14px; margin-top:8px; border:1px solid rgba(255,255,255,0.1); border-radius:8px; background: rgba(0,0,0,0.2); color: #fff; font-size: 14px; transition: 0.3s; font-family: inherit; }
  input:focus, select:focus, textarea:focus { outline: none; border-color: #38bdf8; background: rgba(0,0,0,0.4); box-shadow: 0 0 15px rgba(56, 189, 248, 0.2); }
  select option { background: #0f172a; color: #fff; }
  
  .file-input-wrapper { background: rgba(255,255,255,0.02); padding: 22px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1); margin-top: 8px; text-align: center; position: relative; transition: 0.3s; }
  .file-input-wrapper:hover { background: rgba(255,255,255,0.05); border-color: #38bdf8; }
  .file-input-wrapper input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10; }
  
  /* ✨ NEW BUILDER BUTTON CSS ✨ */
  .btn-build-resume { display: block; text-align: center; margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.1); border: 1px dashed #10b981; color: #34d399; border-radius: 8px; text-decoration: none; font-weight: 800; font-size: 13px; transition: 0.3s; }
  .btn-build-resume:hover { background: #10b981; color: #fff; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }

  .btn-update { width:100%; padding:16px; background:#38bdf8; color:#0a0f1a; border:none; margin-top:35px; cursor:pointer; font-size:16px; border-radius:8px; font-weight:800; transition: 0.3s; }
  .btn-update:hover { background: #0ea5e9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 189, 248, 0.3); }
  .cancel-link { display:block; text-align:center; margin-top:20px; color:#94a3b8; text-decoration:none; font-size: 13px; font-weight: bold; transition: 0.3s; }
  .cancel-link:hover { color: #ef4444; }
</style>
</head>
<body>

<?php include 'student_header.php'; ?>

<main class="edit-container">
  <h2 style="text-align:center; margin-bottom: 30px; font-weight: 800; font-size: 26px;"><i class="fa fa-user-edit" style="color: #38bdf8;"></i> Edit My Profile</h2>
  
  <form method="POST" enctype="multipart/form-data">
    <div class="section-title">Visual Identity</div>
    <label>Choose Avatar</label>
    <div class="avatar-grid">
      <div class="avatar-item <?= ($student['avatar'] == 'boy.png') ? 'selected' : '' ?>" onclick="setAvatar('boy.png', this)">
        <img src="../assets/avatars/boy.png">
        <p>Male Professional</p>
      </div>
      <div class="avatar-item <?= ($student['avatar'] == 'girl.png') ? 'selected' : '' ?>" onclick="setAvatar('girl.png', this)">
        <img src="../assets/avatars/girl.png">
        <p>Female Professional</p>
      </div>
    </div>
    <input type="hidden" name="selected_avatar" id="selected_avatar" value="<?= $student['avatar'] ?>">

    <label>Or Upload Custom Professional JPG/PNG</label>
    <div class="file-input-wrapper">
        <i class="fa fa-camera" style="font-size: 24px; color: #94a3b8; margin-bottom: 8px;"></i> <br>
        <span id="photo_label" style="font-size: 13px; font-weight: bold; color: #cbd5e1;">Click to Browse Image</span>
        <input type="file" name="photo" id="photo_input" accept="image/*" onchange="handlePhotoSelection(this)">
    </div>

    <div class="section-title" style="margin-top: 30px;">Contact Information</div>
    <label>Full Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
    <label>Contact Number (Mobile)</label>
    <input type="text" name="contact" value="<?= htmlspecialchars($student['contact'] ?? '') ?>" placeholder="e.g. +91 98765 43210">

    <div class="section-title" style="margin-top: 30px;">Academic & Skills</div>
    <label>Department</label>
    <select name="department" required>
      <option value="Computer Engineering" <?= ($student['department'] == 'Computer Engineering') ? 'selected' : '' ?>>Computer Engineering</option>
      <option value="Information Technology" <?= ($student['department'] == 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
      <option value="Mechanical Engineering" <?= ($student['department'] == 'Mechanical Engineering') ? 'selected' : '' ?>>Mechanical Engineering</option>
    </select>
    <label>Technical Skills (Comma separated)</label>
    <input type="text" name="skills" value="<?= htmlspecialchars($student['skills'] ?? '') ?>" placeholder="PHP, Java, Python, SQL">
    
    <label>Upload Professional Resume (PDF)</label>
    <div class="file-input-wrapper">
        <i class="fa fa-file-pdf" style="font-size: 24px; color: #ef4444; margin-bottom: 8px;"></i> <br>
        <span id="resume_label" style="font-size: 13px; font-weight: bold; color: #cbd5e1;">Select Updated PDF File</span>
        <input type="file" name="resume" accept=".pdf" onchange="updateFileName(this, 'resume_label')">
    </div>

    <a href="resume-builder.php" class="btn-build-resume">
        <i class="fa fa-wand-magic-sparkles"></i> Don't have a Resume? Auto-Build it Now!
    </a>

    <label>Summary / Career Goal</label>
    <textarea name="bio" rows="4" placeholder="Tell companies about your professional journey..."><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
    
    <button type="submit" name="update" class="btn-update"><i class="fa fa-save"></i> Save Changes & Update Profile</button>
    <a href="profile.php" class="cancel-link">Discard and Back</a>
  </form>
</main>

<script>
function setAvatar(avatarName, element) {
    document.getElementById('photo_input').value = "";
    document.getElementById('photo_label').innerText = "Click to Browse Image";
    document.getElementById('selected_avatar').value = avatarName;
    document.querySelectorAll('.avatar-item').forEach(item => item.classList.remove('selected'));
    element.classList.add('selected');
}
function handlePhotoSelection(input) {
    if (input.files.length > 0) {
        document.getElementById('selected_avatar').value = "";
        document.querySelectorAll('.avatar-item').forEach(item => item.classList.remove('selected'));
        document.getElementById('photo_label').innerText = "Image: " + input.files[0].name;
    }
}
function updateFileName(input, labelId) {
    if (input.files.length > 0) {
        document.getElementById(labelId).innerText = "File: " + input.files[0].name;
    }
}
</script>
</body>
</html>
