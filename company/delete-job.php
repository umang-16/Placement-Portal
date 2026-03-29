<?php
session_start();
require_once __DIR__ . "/../db.php";

// 🔐 COMPANY LOGIN CHECK
if(!isset($_SESSION['company_id'])){ 
    header("Location: company-login.php"); 
    exit(); 
}

$company_id = (int)$_SESSION['company_id'];

if(isset($_GET['id'])){
    $job_id = (int)$_GET['id'];
    
    // 🗑️ Delete only if the job belongs to this logged-in company
    $query = "DELETE FROM jobs WHERE id=$job_id AND company_id=$company_id";
    
    if(mysqli_query($conn, $query)){
        // Redirect back with a custom message
        header("Location: post-job.php?msg=✅ Job post deleted successfully!");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: post-job.php");
}
?>
