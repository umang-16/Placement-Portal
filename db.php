<?php
// 🕒 Timezone Setting: Aa line tame token expiry na issue ne solve karva maate add kari che.
// Jethi PHP ane MySQL banne ma same Indian Standard Time (IST) rahe.
date_default_timezone_set('Asia/Kolkata');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "placement_portal";

// Connection create karvu
$conn = new mysqli($host, $user, $pass, $db);

// Connection check karvu
if($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// 🌐 MySQL ma pan timezone set kariye jethi NOW() function barabar chale
$conn->query("SET time_zone = '+05:30'");

// Ensure applications table has required columns for AI Job Matching and Admin vs Company Status
$check_admin_status = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'admin_status'");
if(mysqli_num_rows($check_admin_status) == 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN admin_status VARCHAR(50) DEFAULT 'pending'");
}
$check_match_score = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'match_score'");
if(mysqli_num_rows($check_match_score) == 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN match_score INT DEFAULT 0");
}

/**
 * ✨ COMMON SECURE QUERY FUNCTION
 * Aa function Prepared Statements vapri ne SQL Injection ne rokshe.
 */
function secureQuery($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("❌ SQL Error: " . $conn->error);
    }

    if ($params) {
        // Types automatically generate kare che (s = string, i = integer)
        if (empty($types)) {
            $types = "";
            foreach ($params as $param) {
                $types .= is_int($param) ? "i" : "s";
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}
?>
