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
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    $transactionReference = sanitize($_POST['transaction_reference'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    
    if (empty($paymentMethod) || $amount <= 0) {
        $error = 'Please select a payment method and enter a valid amount.';
    } elseif ($paymentMethod !== 'Cash' && empty($transactionReference)) {
        $error = 'Please enter the transaction reference number.';
    } else {
        $paymentProof = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['payment_proof'], 'payments');
            if ($result['success']) {
                $paymentProof = $result['filename'];
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, client_id, amount, payment_method, transaction_reference, payment_proof, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        if ($stmt->execute([$bookingId, $clientId, $amount, $paymentMethod, $transactionReference, $paymentProof])) {
            $pdo->prepare("UPDATE bookings SET status = 'Payment Submitted' WHERE id = ?")->execute([$bookingId]);
            
            $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'")->fetchAll();
            foreach ($admins as $admin) {
                createNotification($admin['id'], 'New Payment Submitted', 
                    'Payment of ' . formatCurrency($amount) . ' has been submitted for booking ' . $booking['booking_reference'],
                    'info', BASE_URL . 'admin/payments/index.php');
            }
            
            logActivity($clientId, 'Payment Submitted', 'Submitted payment of ' . formatCurrency($amount) . ' for booking ' . $booking['booking_reference']);
            
            $success = 'Payment submitted successfully! Awaiting verification.';
        } else {
            $error = 'Failed to submit payment. Please try again.';
        }
    }
}
$pageTitle = 'Make Payment - Smart Drive Car Hire';
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
                                    <select name="payment_method" required>
                                        <option value="">Select Payment Method</option>
                                        <option value="M-Pesa">M-Pesa</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Amount (KES) *</label>
                                    <input type="number" name="amount" required value="<?php echo $booking['total_amount']; ?>" step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label>Transaction Reference</label>
                                    <input type="text" name="transaction_reference" placeholder="Enter transaction reference">
                                    <small style="color: var(--text-muted);">Required for M-Pesa and Bank Transfer</small>
                                </div>
                                <div class="form-group">
                                    <label>Payment Proof *</label>
                                    <input type="file" name="payment_proof" accept="image/*,.pdf" required>
                                    <small style="color: var(--text-muted);">Upload screenshot or receipt (JPG, PNG, PDF)</small>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-paper-plane"></i> Submit Payment
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
