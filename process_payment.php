<?php
require_once 'config.php';

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    header("Location: checkout.php");
    exit();
}

$payment_data = $_SESSION['payment_data'];

// Generate unique transaction ID
function generateTransactionId() {
    $prefix = 'TXN';
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(uniqid(), -6));
    return $prefix . $timestamp . $random;
}

$transaction_id = generateTransactionId();
$status = 'completed';
$payment_method = 'VISA'; // Simulated payment method

// DEBUG: Check what data we have
error_log("Processing payment for: " . $payment_data['email']);
error_log("Transaction ID: " . $transaction_id);

// FIRST: Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'transactions'");
if ($table_check->num_rows == 0) {
    die("Error: 'transactions' table does not exist. Please run the database.sql file in phpMyAdmin.");
}

// Prepare the INSERT statement - MATCHING EXACT COLUMN NAMES
$sql = "INSERT INTO transactions (transaction_id, full_name, email, amount, payment_method, status, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

// Check if prepare failed
if ($stmt === false) {
    die("MySQL Prepare Error: " . $conn->error);
}

// Bind parameters (7 parameters: s, s, s, d, s, s)
$stmt->bind_param("sssdss", 
    $transaction_id, 
    $payment_data['full_name'], 
    $payment_data['email'], 
    $payment_data['amount'], 
    $payment_method, 
    $status
);

// Execute the statement
if ($stmt->execute()) {
    // Store success info in session
    $_SESSION['payment_success'] = true;
    $_SESSION['transaction_id'] = $transaction_id;
    $_SESSION['transaction_amount'] = $payment_data['amount'];
    $_SESSION['customer_email'] = $payment_data['email'];
    
    // Clear the payment data from session
    unset($_SESSION['payment_data']);
    
    // Redirect to success page
    header("Location: download.php");
    exit();
} else {
    // Log error and show user-friendly message
    error_log("Payment insertion failed: " . $stmt->error);
    $_SESSION['payment_error'] = "Transaction could not be processed: " . $stmt->error;
    header("Location: checkout.php");
    exit();
}

$stmt->close();
$conn->close();
?>