<?php
// Start session for payment status
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP, 'root' for MAMP
define('DB_NAME', 'payment_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone to Zimbabwe/Harare
date_default_timezone_set('Africa/Harare');
?>