<?php
session_start();
require_once __DIR__ . "/../db.php";

if(isset($_POST['submit_review'])){
    $student_id = $_SESSION['student_id'];
    $company_id = (int)$_POST['company_id'];
    $rating     = (int)$_POST['rating'];
    $comment    = mysqli_real_escape_string($conn, $_POST['comment']);

    // Check if student already reviewed this company
    $check = mysqli_query($conn, "SELECT id FROM company_reviews WHERE student_id=$student_id AND company_id=$company_id");
    
    if(mysqli_num_rows($check) > 0){
        echo "<script>alert('You have already reviewed this company!'); window.location.href='companies.php';</script>";
    } else {
        $sql = "INSERT INTO company_reviews (company_id, student_id, rating, comment) 
                VALUES ($company_id, $student_id, $rating, '$comment')";
        
        if(mysqli_query($conn, $sql)){
            echo "<script>alert('Review submitted successfully!'); window.location.href='companies.php';</script>";
        }
    }
} else {
    header("Location: companies.php");
}
?>
