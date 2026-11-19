<?php
require_once 'config.php';

// Check if payment was successful
if (!isset($_SESSION['payment_success']) || $_SESSION['payment_success'] !== true) {
    header("Location: checkout.php");
    exit();
}

// Get transaction details from session
$transaction_id = $_SESSION['transaction_id'];
$amount = $_SESSION['transaction_amount'];
$email = $_SESSION['customer_email'];

// Verify transaction exists in database
$stmt = $conn->prepare("SELECT * FROM transactions WHERE transaction_id = ? AND status = 'completed'");
$stmt->bind_param("s", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Transaction not found, redirect to checkout
    header("Location: checkout.php");
    exit();
}

$transaction = $result->fetch_assoc();
$stmt->close();

// Handle download request
$download_success = false;
if (isset($_GET['download']) && $_GET['download'] == 'product') {
    // Simulate file download
    $download_success = true;
    
    // For demo, we'll create a fake PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Web_Development_Course.pdf"');
    
    // Create a simple PDF-like content (for demo)
    echo "%PDF-1.4\n";
    echo "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n";
    echo "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n";
    echo "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n>>\nendobj\n";
    echo "4 0 obj\n<<\n/Length 100\n>>\nstream\n";
    echo "BT\n/F1 24 Tf\n100 700 Td\n(Web Development Course Certificate) Tj\nET\n";
    echo "BT\n/F1 12 Tf\n100 650 Td\n(This certifies that " . htmlspecialchars($email) . " has completed) Tj\nET\n";
    echo "BT\n/F1 12 Tf\n100 630 Td\n(the Complete Web Development Course.) Tj\nET\n";
    echo "endstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000212 00000 n\ntrailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n363\n%%EOF";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Download Your Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your transaction has been completed.</p>
            
            <div class="transaction-details">
                <h3>Transaction Details</h3>
                <div class="detail-row">
                    <span class="label">Transaction ID:</span>
                    <span class="value"><?php echo htmlspecialchars($transaction['transaction_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($transaction['email']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Amount Paid:</span>
                    <span class="value">$<?php echo number_format($transaction['amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo date('F j, Y g:i A', strtotime($transaction['timestamp'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-completed"><?php echo ucfirst($transaction['status']); ?></span>
                </div>
            </div>
            
            <!-- Download Button - Shows only after successful payment -->
            <div class="download-section">
                <h3>📥 Your Digital Product</h3>
                <p>Complete Web Development Course (includes certificate)</p>
                <a href="?download=product" class="btn-download">
                    ⬇️ Download Course Materials
                </a>
                <p class="download-note">* Download link will expire in 24 hours</p>
            </div>
            
            <div class="action-buttons">
                <a href="checkout.php" class="btn-secondary">Make Another Purchase</a>
                <button onclick="window.print()" class="btn-secondary">Print Receipt</button>
            </div>
        </div>
    </div>
</body>
</html>