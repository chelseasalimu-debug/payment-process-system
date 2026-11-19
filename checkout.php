<?php
require_once 'config.php';

// Clear any previous payment session
unset($_SESSION['payment_success']);
unset($_SESSION['transaction_id']);

$errors = [];
$form_data = [
    'full_name' => '',
    'email' => '',
    'amount' => '49.99',
    'card_number' => '',
    'card_name' => '',
    'expiry' => '',
    'cvv' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['preview'])) {
    // Sanitize and validate inputs
    $form_data['full_name'] = trim($_POST['full_name']);
    $form_data['email'] = trim($_POST['email']);
    $form_data['amount'] = trim($_POST['amount']);
    $form_data['card_name'] = trim($_POST['card_name']);
    $form_data['card_number'] = preg_replace('/\s+/', '', trim($_POST['card_number']));
    $form_data['expiry'] = trim($_POST['expiry']);
    $form_data['cvv'] = trim($_POST['cvv']);
    
    // Validate full name
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = "Full name is required";
    } elseif (strlen($form_data['full_name']) < 3) {
        $errors['full_name'] = "Name must be at least 3 characters";
    }
    
    // Validate email
    if (empty($form_data['email'])) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    // Validate amount
    if (empty($form_data['amount']) || $form_data['amount'] <= 0) {
        $errors['amount'] = "Valid amount is required";
    } elseif (!is_numeric($form_data['amount'])) {
        $errors['amount'] = "Amount must be a number";
    }
    
    // Validate card name
    if (empty($form_data['card_name'])) {
        $errors['card_name'] = "Cardholder name is required";
    }
    
    // Validate card number (16 digits)
    if (empty($form_data['card_number'])) {
        $errors['card_number'] = "Card number is required";
    } elseif (!preg_match('/^\d{16}$/', $form_data['card_number'])) {
        $errors['card_number'] = "Card number must be 16 digits";
    }
    
    // Validate expiry date (MM/YY)
    if (empty($form_data['expiry'])) {
        $errors['expiry'] = "Expiry date is required";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $form_data['expiry'])) {
        $errors['expiry'] = "Use format MM/YY";
    } else {
        $expiry_parts = explode('/', $form_data['expiry']);
        $expiry_month = $expiry_parts[0];
        $expiry_year = 2000 + intval($expiry_parts[1]); // Convert YY to YYYY
        $expiry_date = mktime(0, 0, 0, $expiry_month, 1, $expiry_year);
        $current_date = strtotime(date('Y-m-01'));
        
        if ($expiry_date < $current_date) {
            $errors['expiry'] = "Card has expired";
        }
    }
    
    // Validate CVV (3 or 4 digits)
    if (empty($form_data['cvv'])) {
        $errors['cvv'] = "CVV is required";
    } elseif (!preg_match('/^\d{3,4}$/', $form_data['cvv'])) {
        $errors['cvv'] = "CVV must be 3-4 digits";
    }
    
    // If no errors, store in session and show preview
    if (empty($errors)) {
        $_SESSION['payment_data'] = $form_data;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>💳 Secure Payment Gateway</h1>
            <p>Digital Product: <strong>Complete Web Development Course</strong></p>
        </div>


            <!-- Payment Form -->
            <div class="payment-form">
                <h2>Checkout Details</h2>
                
                <?php if (isset($_SESSION['payment_data']) && empty($errors) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
                    <!-- Preview Mode -->
                    <div class="preview-box">
                        <h3>📋 Review Your Order</h3>
                        <div class="preview-details">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($form_data['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($form_data['email']); ?></p>
                            <p><strong>Amount:</strong> $<?php echo number_format($form_data['amount'], 2); ?></p>
                            <p><strong>Card:</strong> •••• •••• •••• <?php echo substr($form_data['card_number'], -4); ?></p>
                        </div>
                        <form method="POST" action="process_payment.php">
                            <button type="submit" name="confirm_payment" class="btn-confirm">
                                ✅ Confirm & Pay
                            </button>
                            <button type="button" onclick="window.location.href='checkout.php'" class="btn-edit">
                                ✏️ Edit Details
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Payment Form -->
                    <form method="POST" action="" id="paymentForm">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" id="full_name" 
                                   value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                                   onkeyup="validateField('full_name')">
                            <span class="error" id="full_name_error"><?php echo $errors['full_name'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" id="email" 
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                   onkeyup="validateField('email')">
                            <span class="error" id="email_error"><?php echo $errors['email'] ?? ''; ?></span>
                        </div>

                        <div class="form-group">
                            <label>Amount (USD) *</label>
                            <input type="number" name="amount" id="amount" step="0.01" 
                                   value="<?php echo htmlspecialchars($form_data['amount']); ?>"
                                   onkeyup="validateField('amount')">
                            <span class="error" id="amount_error"><?php echo $errors['amount'] ?? ''; ?></span>
                        </div>

                        <div class="card-section">
                            <h3>💳 Card Details</h3>
                            
                            <div class="form-group">
                                <label>Cardholder Name *</label>
                                <input type="text" name="card_name" id="card_name" 
                                       value="<?php echo htmlspecialchars($form_data['card_name']); ?>"
                                       onkeyup="validateField('card_name')">
                                <span class="error" id="card_name_error"><?php echo $errors['card_name'] ?? ''; ?></span>
                            </div>

                            <div class="form-group">
                                <label>Card Number *</label>
                                <input type="text" name="card_number" id="card_number" 
                                       value="<?php echo htmlspecialchars($form_data['card_number']); ?>"
                                       placeholder="1234 5678 9012 3456"
                                       onkeyup="formatCardNumber(this); validateField('card_number')"
                                       maxlength="19">
                                <span class="error" id="card_number_error"><?php echo $errors['card_number'] ?? ''; ?></span>
                            </div>

                            <div class="form-row">
                                <div class="form-group half">
                                    <label>Expiry Date *</label>
                                    <input type="text" name="expiry" id="expiry" 
                                           value="<?php echo htmlspecialchars($form_data['expiry']); ?>"
                                           placeholder="MM/YY"
                                           onkeyup="formatExpiry(this); validateField('expiry')"
                                           maxlength="5">
                                    <span class="error" id="expiry_error"><?php echo $errors['expiry'] ?? ''; ?></span>
                                </div>

                                <div class="form-group half">
                                    <label>CVV *</label>
                                    <input type="password" name="cvv" id="cvv" 
                                           value="<?php echo htmlspecialchars($form_data['cvv']); ?>"
                                           placeholder="123"
                                           onkeyup="validateField('cvv')"
                                           maxlength="4">
                                    <span class="error" id="cvv_error"><?php echo $errors['cvv'] ?? ''; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="security-badge">
                            🔒 Payment secured with 256-bit SSL encryption
                        </div>

                        <button type="submit" name="preview" class="btn-preview">
                            🔍 Review Order
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Real-time validation functions
        function validateField(fieldName) {
            let value = document.getElementById(fieldName)?.value || '';
            let errorSpan = document.getElementById(fieldName + '_error');
            if (!errorSpan) return;
            
            switch(fieldName) {
                case 'full_name':
                    if (value.length < 3) {
                        errorSpan.textContent = 'Name must be at least 3 characters';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        errorSpan.textContent = 'Enter a valid email';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'amount':
                    if (value <= 0 || isNaN(value)) {
                        errorSpan.textContent = 'Enter a valid amount';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'card_name':
                    if (value.length < 3) {
                        errorSpan.textContent = 'Enter cardholder name';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'card_number':
                    const cleanNumber = value.replace(/\s/g, '');
                    if (cleanNumber.length !== 16) {
                        errorSpan.textContent = 'Card number must be 16 digits';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'expiry':
                    const expiryRegex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
                    if (!expiryRegex.test(value)) {
                        errorSpan.textContent = 'Use MM/YY format';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
                    
                case 'cvv':
                    if (!/^\d{3,4}$/.test(value)) {
                        errorSpan.textContent = 'CVV must be 3-4 digits';
                    } else {
                        errorSpan.textContent = '';
                    }
                    break;
            }
        }
        
        function formatCardNumber(input) {
            let value = input.value.replace(/\s/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formatted = value.replace(/(\d{4})/g, '$1 ').trim();
            input.value = formatted;
            validateField('card_number');
        }
        
        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            input.value = value;
            validateField('expiry');
        }
    </script>
</body>
</html>