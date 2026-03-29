<?php
require_once "db.php"; // Database connection file

// Set your new admin details here
$new_admin_username = "vaghela";
$new_admin_password = "uv1618";

// Hash the password for security
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);

// Insert into the database (Assuming your table name is 'admin')
$sql = "INSERT INTO admins (username, password) VALUES ('$new_admin_username', '$hashed_password')";

if(mysqli_query($conn, $sql)){
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: #10b981;'>✅ New Admin Created Successfully!</h1>";
    echo "<p style='color: #475569; font-size: 18px;'>You can now login using the username: <b style='color: #38bdf8;'>$new_admin_username</b></p>";
    echo "<p style='color: #ef4444; font-size: 14px;'><strong>Security Warning:</strong> Please delete this 'create-admin.php' file immediately for security reasons.</p>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: #ef4444;'>❌ Error Creating Admin!</h1>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
    echo "</div>";
}
?>