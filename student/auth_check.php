<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Redirect if not logged in
if (!isset($_SESSION['student_id']) && !isset($_SESSION['company_id'])) {
    header("Location: ../index.php"); 
    exit();
}

// 2. Clear Cache Headers
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
?>

<script type="text/javascript">
    function preventBack() {
        window.history.forward();
    }
    setTimeout("preventBack()", 0);
    window.onunload = function () { null };
</script>
