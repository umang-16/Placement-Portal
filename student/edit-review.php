<?php
session_start();
require_once __DIR__ . "/../db.php";

if(isset($_POST['update_review'])){
    $student_id = $_SESSION['student_id'];
    $company_id = (int)$_POST['company_id'];
    $rating     = (int)$_POST['rating'];
    $comment    = mysqli_real_escape_string($conn, $_POST['comment']);

    $sql = "UPDATE company_reviews 
            SET rating = $rating, comment = '$comment', created_at = NOW() 
            WHERE student_id = $student_id AND company_id = $company_id";
    
    if(mysqli_query($conn, $sql)){
        echo "<script>alert('Review updated successfully!'); window.location.href='companies.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: companies.php");
}
?>
