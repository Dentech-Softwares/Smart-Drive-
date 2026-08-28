<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        redirect('/admin/payments/index.php');
    }
    
    $paymentId = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
    $action = sanitize($_POST['action'] ?? '');
    
    if ($paymentId && in_array($action, ['verify', 'reject'])) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            if ($action === 'verify') {
                $pdo->prepare("UPDATE payments SET status = 'Verified', verified_by = ?, verified_at = NOW() WHERE id = ?")
                   ->execute([$_SESSION['user_id'], $paymentId]);
                
                $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ?")->execute([$payment['booking_id']]);
                
                $clientStmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
                $clientStmt->execute([$payment['client_id']]);
                $client = $clientStmt->fetch();
                
                if ($client) {
                    createNotification($client['user_id'], 'Payment Verified', 
                        'Your payment of ' . formatCurrency($payment['amount']) . ' has been verified.',
                        'success', '/client/booking-details.php?id=' . $payment['booking_id']);
                }
                
                logActivity($_SESSION['user_id'], 'Payment Verified', "Verified payment #$paymentId of " . formatCurrency($payment['amount']));
            } else {
                $rejectionReason = sanitize($_POST['rejection_reason'] ?? 'Payment rejected');
                $pdo->prepare("UPDATE payments SET status = 'Rejected', rejection_reason = ? WHERE id = ?")
                   ->execute([$rejectionReason, $paymentId]);
                
                $pdo->prepare("UPDATE bookings SET status = 'Awaiting Payment' WHERE id = ?")->execute([$payment['booking_id']]);
                
                $clientStmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
                $clientStmt->execute([$payment['client_id']]);
                $client = $clientStmt->fetch();
                
                if ($client) {
                    createNotification($client['user_id'], 'Payment Rejected', 
                        'Your payment of ' . formatCurrency($payment['amount']) . ' has been rejected. Reason: ' . $rejectionReason,
                        'error', '/client/payment.php?booking_id=' . $payment['booking_id']);
                }
                
                logActivity($_SESSION['user_id'], 'Payment Rejected', "Rejected payment #$paymentId");
            }
        }
    }
}

redirect('/admin/payments/index.php');
