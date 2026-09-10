<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}
$user = getCurrentUser();
$clientId = $_SESSION['user_id'];
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$bookingId) {
    redirect('/client/bookings.php');
}
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND client_id = ?");
$stmt->execute([$bookingId, $clientId]);
$booking = $stmt->fetch();
if (!$booking) {
    redirect('/client/bookings.php');
}
$payPenalty = isset($_GET['pay_penalty']) && $booking['status'] === 'Completed' && $booking['penalty'] > 0;
if (!$payPenalty && $booking['status'] !== 'Approved') {
    $_SESSION['error'] = 'Payment can only be made for approved bookings.';
    redirect('/client/booking-details.php?id=' . $bookingId);
}
$success = '';
$error = '';
$selectedMethod = '';
$rawRegisteredPhone = preg_replace('/[^0-9]/', '', $user['phone']);
$formattedRegisteredPhone = (strlen($rawRegisteredPhone) === 12 && substr($rawRegisteredPhone, 0, 3) === '254') ? '0' . substr($rawRegisteredPhone, 3) : $user['phone'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMethod = sanitize($_POST['payment_method'] ?? '');
    
    if ($selectedMethod === 'Cash') {
        $amount = (float)($_POST['amount'] ?? 0);
        
        if ($amount <= 0) {
            $error = 'Please enter a valid amount.';
        } else {
            $paymentProof = null;
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                $result = uploadFile($_FILES['payment_proof'], 'payments');
                if ($result['success']) {
                    $paymentProof = $result['filename'];
                }
            }
            
            if ($payPenalty) {
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, client_id, amount, payment_method, payment_proof, status, verified_at) 
                                       VALUES (?, ?, ?, ?, ?, 'Verified', NOW())");
            } else {
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, client_id, amount, payment_method, payment_proof, status) 
                                       VALUES (?, ?, ?, ?, ?, 'Pending')");
            }
            if ($stmt->execute([$bookingId, $clientId, $amount, $selectedMethod, $paymentProof])) {
                if (!$payPenalty) {
                    $pdo->prepare("UPDATE bookings SET status = 'Payment Submitted' WHERE id = ?")->execute([$bookingId]);
                    $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'")->fetchAll();
                    foreach ($admins as $admin) {
                        createNotification($admin['id'], 'New Payment Submitted', 
                            'Cash payment of ' . formatCurrency($amount) . ' has been submitted for booking ' . $booking['booking_reference'],
                            'info', BASE_URL . 'admin/payments/index.php');
                    }
                    logActivity($clientId, 'Payment Submitted', 'Submitted cash payment of ' . formatCurrency($amount) . ' for booking ' . $booking['booking_reference']);
                    $success = 'Cash payment submitted successfully! Awaiting verification.';
                } else {
                    createNotification($clientId, 'Penalty Payment Verified', 
                        'Your penalty payment of ' . formatCurrency($amount) . ' has been verified.',
                        'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
                    logActivity($clientId, 'Penalty Payment', 'Paid penalty of ' . formatCurrency($amount) . ' for booking ' . $booking['booking_reference']);
                    $success = 'Penalty payment of ' . formatCurrency($amount) . ' submitted successfully! No approval required.';
                }
            } else {
                $error = 'Failed to submit payment. Please try again.';
            }
        }
    }
}
$pageTitle = $payPenalty ? 'Pay Penalty - Smart Drive Car Hire' : 'Make Payment - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Make Payment</h1>
            <a href="<?php echo BASE_URL; ?>client/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Booking
            </a>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Booking Information</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                        <p><strong>Status:</strong> <?php echo getBookingStatusLabel($booking['status']); ?></p>
                        <p><strong>Total Amount:</strong> <?php echo formatCurrency($booking['total_amount']); ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Submit Payment</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                            <a href="<?php echo BASE_URL; ?>client/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">View Booking</a>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data" id="paymentForm">
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <select name="payment_method" id="paymentMethod" required onchange="togglePaymentMethod()">
                                        <option value="">Select Payment Method</option>
                                        <option value="Cash" <?php echo $selectedMethod === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="M-Pesa" <?php echo $selectedMethod === 'M-Pesa' ? 'selected' : ''; ?>>M-Pesa</option>
                                    </select>
                                </div>
                                
                                <div id="cashFields" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                                    <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-dark);">
                                        <i class="fas fa-money-bill-wave"></i> Cash Payment
                                    </h4>
                                <div class="form-group">
                                    <label>Amount (KES) *</label>
                                    <input type="number" name="amount" value="<?php echo $payPenalty ? $booking['penalty'] : $booking['total_amount']; ?>" step="0.01" min="0" required>
                                    <?php if ($payPenalty): ?>
                                        <small style="color: var(--danger);">This is the penalty amount for late return.</small>
                                    <?php endif; ?>
                                </div>
                                    <div class="form-group">
                                        <label>Payment Proof *</label>
                                        <input type="file" name="payment_proof" accept="image/*,.pdf" required>
                                        <small style="color: var(--text-muted);">Upload screenshot or receipt (JPG, PNG, PDF)</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-paper-plane"></i> Submit Cash Payment
                                    </button>
                                </div>
                                
                                <div id="mpesaFields" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #4CAF50;">
                                    <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-dark);">
                                        <i class="fas fa-mobile-alt"></i> M-Pesa Payment
                                    </h4>
                                    <div class="form-group">
                                        <label>Select Phone Number</label>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; cursor: pointer; transition: all 0.2s; margin-bottom: 0;">
                                                <input type="radio" name="mpesa_phone_option" value="registered" checked onchange="togglePhoneInput()" style="margin: 0; width: 16px; height: 16px; accent-color: #4CAF50;">
                                                <span style="font-size: 14px; color: var(--text-dark);"><?php echo htmlspecialchars($formattedRegisteredPhone); ?></span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; cursor: pointer; transition: all 0.2s; margin-bottom: 0;">
                                                <input type="radio" name="mpesa_phone_option" value="other" onchange="togglePhoneInput()" style="margin: 0; width: 16px; height: 16px; accent-color: #4CAF50;">
                                                <span style="font-size: 14px; color: var(--text-dark);">Different number</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group" id="otherPhoneGroup" style="display: none; margin-top: 12px;">
                                        <label>Phone Number</label>
                                        <input type="tel" id="otherPhone" placeholder="e.g. 0712345678" maxlength="10" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                        <small style="color: var(--text-muted);">Format: 07XXXXXXXX</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Amount (KES)</label>
                                        <input type="number" id="mpesaAmount" value="<?php echo $payPenalty ? $booking['penalty'] : $booking['total_amount']; ?>" step="0.01" min="1" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #f5f5f5;">
                                        <?php if ($payPenalty): ?>
                                            <small style="color: var(--danger);">Penalty amount for late return.</small>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" onclick="initiateMpesaSTK()" class="btn" style="width: 100%; padding: 12px; background: #00a650; color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                        <i class="fas fa-mobile-alt"></i> Pay with M-Pesa
                                    </button>
                                    <div id="mpesaStatus" style="margin-top: 15px;"></div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
function togglePaymentMethod() {
    var method = document.getElementById('paymentMethod').value;
    var cashFields = document.getElementById('cashFields');
    var mpesaFields = document.getElementById('mpesaFields');
    
    if (method === 'Cash') {
        cashFields.style.display = 'block';
        mpesaFields.style.display = 'none';
    } else if (method === 'M-Pesa') {
        cashFields.style.display = 'none';
        mpesaFields.style.display = 'block';
    } else {
        cashFields.style.display = 'none';
        mpesaFields.style.display = 'none';
    }
}

function togglePhoneInput() {
    var selected = document.querySelector('input[name="mpesa_phone_option"]:checked').value;
    document.getElementById('otherPhoneGroup').style.display = selected === 'other' ? 'block' : 'none';
}

function initiateMpesaSTK() {
    var phoneOption = document.querySelector('input[name="mpesa_phone_option"]:checked').value;
    var phoneNumber = '';
    
    if (phoneOption === 'registered') {
        var rawRegistered = '<?php echo preg_replace('/[^0-9]/', '', $user['phone']); ?>';
        if (rawRegistered.length === 12 && rawRegistered.substring(0, 3) === '254') {
            phoneNumber = '0' + rawRegistered.substring(3);
        } else if (rawRegistered.length === 10 && rawRegistered.substring(0, 1) === '0') {
            phoneNumber = rawRegistered;
        } else {
            phoneNumber = '0' + rawRegistered;
        }
    } else {
        phoneNumber = document.getElementById('otherPhone').value.replace(/[^0-9]/g, '');
    }
    
    var amount = document.getElementById('mpesaAmount').value;
    var statusDiv = document.getElementById('mpesaStatus');
    
    if (!phoneNumber || phoneNumber.length !== 10 || phoneNumber.substring(0, 1) !== '0') {
        statusDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid phone number (07XXXXXXXX)</div>';
        return;
    }
    
    if (!amount || amount <= 0) {
        statusDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid amount</div>';
        return;
    }
    
    statusDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Initiating STK Push... Please check your phone.</div>';
    
    var formData = new FormData();
    formData.append('booking_id', '<?php echo $bookingId; ?>');
    formData.append('amount', amount);
    formData.append('phone_number', phoneNumber);
    formData.append('pay_penalty', '<?php echo $payPenalty ? "1" : "0"; ?>');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo BASE_URL; ?>client/mpesa-stk.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                statusDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + response.message + '</div>';
            } else {
                statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + response.message + '</div>';
            }
        } else {
            statusDiv.innerHTML = '<div class="alert alert-danger">Request failed. Please try again.</div>';
        }
    };
    xhr.send(formData);
}
</script>
</body>
</html>
