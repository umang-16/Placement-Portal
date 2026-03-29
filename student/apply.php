<?php
session_start();
require_once __DIR__ . "/../db.php";

/* 🔐 STUDENT LOGIN CHECK */
if(!isset($_SESSION['student_id'])){
    header("Location: student-login.php");
    exit();
}

$student_id = (int)$_SESSION['student_id'];

/* HANDLE FORM SUBMIT */
if(isset($_POST['submit_application'])){

    $job_id = intval($_POST['job_id']);
    $skills = mysqli_real_escape_string($conn, $_POST['skills']); 
    
    $final_resume = "";

    // Existing profile resume check
    if(isset($_POST['existing_resume']) && !empty($_POST['existing_resume'])){
        $final_resume = mysqli_real_escape_string($conn, $_POST['existing_resume']);
    }

    // New resume upload override
    if(isset($_FILES['new_resume']) && $_FILES['new_resume']['error'] === 0){
        $ext = strtolower(pathinfo($_FILES['new_resume']['name'], PATHINFO_EXTENSION));
        
        if($ext !== 'pdf'){
            die("<script>alert('❌ Only PDF resumes allowed'); window.history.back();</script>");
        }

        $resume_name = time()."_".preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['new_resume']['name']);
        
        if (!is_dir("../uploads/resumes/")) { mkdir("../uploads/resumes/", 0777, true); }
        
        if(move_uploaded_file($_FILES['new_resume']['tmp_name'], "../uploads/resumes/".$resume_name)){
            $final_resume = $resume_name; 
        } else {
            die("<script>alert('❌ Error uploading file'); window.history.back();</script>");
        }
    }

    // Must have a resume
    if(empty($final_resume)){
        die("<script>alert('❌ Resume is required to apply.'); window.history.back();</script>");
    }

    // Fetch Job Skills to calculate match score
    $job_res = mysqli_query($conn,"SELECT company_id, title, skills FROM jobs WHERE id=$job_id");
    $job_data = mysqli_fetch_assoc($job_res);
    $company_id = $job_data['company_id'];
    $job_title = mysqli_real_escape_string($conn, $job_data['title']);
    $job_skills_str = strtolower($job_data['skills']);

    // Calculate AI Match Score (Simple Keyword Intersection logic)
    $student_skills_arr = array_map('trim', explode(',', strtolower($skills)));
    $job_skills_arr = array_map('trim', explode(',', $job_skills_str));
    $matched_skills = 0;
    foreach($job_skills_arr as $js) {
        if(!empty($js) && in_array($js, $student_skills_arr)) {
            $matched_skills++;
        }
    }
    $total_required_skills = count(array_filter($job_skills_arr));
    $match_score = ($total_required_skills > 0) ? round(($matched_skills / $total_required_skills) * 100) : 0;
    if($match_score > 100) $match_score = 100;

    // ✨ RE-APPLY LOGIC: Check if record already exists
    $check = mysqli_query($conn, "SELECT id, status FROM applications WHERE student_id=$student_id AND job_id=$job_id");
    
    if(mysqli_num_rows($check) > 0){
        $app_row = mysqli_fetch_assoc($check);
        
        // Agar withdraw kiya tha, toh update kardo
        if($app_row['status'] == 'withdrawn') {
            $app_id = $app_row['id'];
            $update_query = "UPDATE applications SET skills='$skills', resume='$final_resume', status='pending', admin_status='pending', match_score=$match_score, applied_at=NOW() WHERE id=$app_id";
            mysqli_query($conn, $update_query);
            
            // We do NOT notify the company. We just redirect to status.
            header("Location: status.php?msg=success");
            exit();
        } else {
            // Agar pehle se pending/shortlisted hai, toh rok do
            echo "<script>alert('⚠️ You have already applied for this job and it is active.'); window.location.href='companies.php';</script>";
            exit();
        }
    }

    // 5. Insert Application (Fresh Application)
    $insert_query = "INSERT INTO applications (student_id, job_id, skills, resume, status, admin_status, match_score, applied_at)
                     VALUES ($student_id, $job_id, '$skills', '$final_resume', 'pending', 'pending', $match_score, NOW())";
    
    if(mysqli_query($conn, $insert_query)){
        // We do not notify the company directly here, only admin will notify company when forwarding.
        header("Location: status.php?msg=success");
        exit();
    } else {
        die("❌ Database Error: " . mysqli_error($conn));
    }
} else {
    header("Location: companies.php");
    exit();
}
?>