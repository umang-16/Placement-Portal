<?php
session_start();
require_once __DIR__ . "/../db.php";

if(isset($_POST['confirm']) && $_POST['confirm'] == 'yes'){
    $student_id = $_SESSION['student_id'];
    $job_id = (int)$_POST['job_id'];

    // Database ma status update thavu joiye
    $sql = "UPDATE applications 
            SET status = 'withdrawn' 
            WHERE student_id = $student_id 
            AND job_id = $job_id 
            AND status != 'withdrawn'";
            
    mysqli_query($conn, $sql);
}

// Pachu companies.php par moklo
header("Location: companies.php");
exit();
?>
