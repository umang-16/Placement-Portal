<?php
session_start();
require_once "../db.php";

/* 🔐 COMPANY LOGIN CHECK */
if(!isset($_SESSION['company_id'])){
    header("Location: ../login-selection.php");
    exit();
}

if(isset($_POST['upload'])){
    $app_id = (int)$_POST['app_id'];
    
    if(!empty($_FILES['offer_pdf']['name'])){
        // 1. File Upload Logic
        $filename = "Offer_" . $app_id . "_" . time() . ".pdf";
        
        // Folder check (Khatri karo ke aa path barabar che)
        $upload_path = "../uploads/offer_letters/" . $filename; 
        
        if(move_uploaded_file($_FILES['offer_pdf']['tmp_name'], $upload_path)){
            
            // 2. Update application status to 'selected'
            mysqli_query($conn, "UPDATE applications SET status='selected', offer_letter='$filename' WHERE id=$app_id");
            
            // 3. Get Student ID for Notification
            $student_query = mysqli_query($conn, "SELECT student_id, job_id FROM applications WHERE id = $app_id");
            $app_data = mysqli_fetch_assoc($student_query);
            $s_id = $app_data['student_id'];
            $j_id = $app_data['job_id'];

            // Job Title fetch karo notification message mate
            $job_query = mysqli_query($conn, "SELECT title FROM jobs WHERE id = $j_id");
            $job_data = mysqli_fetch_assoc($job_query);
            $job_title = $job_data['title'];

            // 4. Insert Notification for Student
            $msg = "Congratulations! You have been selected for the position of " . mysqli_real_escape_string($conn, $job_title) . ". Download your offer letter from the Status page.";
            
            // Note: Khatri karjo ke tame 'student_notifications' table banavi didhu che
            mysqli_query($conn, "INSERT INTO notifications (student_id, message, created_at) 
                                 VALUES ($s_id, '$msg', NOW())");
            
            echo "<script>alert('Offer Letter Uploaded & Student Selected!'); window.location.href='applicants.php';</script>";
        } else {
            echo "<script>alert('Error: File upload fail thai che. Folder permissions check karo.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Please select a PDF file first.'); window.history.back();</script>";
    }
}
?>
