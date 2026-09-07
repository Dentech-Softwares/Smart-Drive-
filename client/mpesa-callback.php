<?php
require_once __DIR__ . '/../config/database.php';

$callbackData = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

if (!$callbackData || !isset($callbackData['Body']['stkCallback'])) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
    exit();
}

$stkCallback = $callbackData['Body']['stkCallback'];
$checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
$resultCode = $stkCallback['ResultCode'] ?? 1;
$resultDesc = $stkCallback['ResultDesc'] ?? 'Unknown';
$callbackMetadata = $stkCallback['CallbackMetadata'] ?? [];

if (!$checkoutRequestId) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Missing CheckoutRequestID']);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM payments WHERE mpesa_checkout_request_id = ?");
$stmt->execute([$checkoutRequestId]);
$payment = $stmt->fetch();

if (!$payment) {
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Payment not found']);
    exit();
}

if ($resultCode == 0) {
    $mpesaReceiptNumber = '';
    $mpesaPhoneNumber = '';
    $mpesaAmount = '';
    
    if (isset($callbackMetadata['Item'])) {
        foreach ($callbackMetadata['Item'] as $item) {
            if ($item['Name'] === 'MpesaReceiptNumber') {
                $mpesaReceiptNumber = $item['Value'] ?? '';
            } elseif ($item['Name'] === 'PhoneNumber') {
                $mpesaPhoneNumber = $item['Value'] ?? '';
            } elseif ($item['Name'] === 'Amount') {
                $mpesaAmount = $item['Value'] ?? '';
            }
        }
    }
    
    $pdo->prepare("UPDATE payments SET 
        status = 'Verified', 
        mpesa_transaction_id = ?, 
        mpesa_phone_number = ?, 
        mpesa_status = 'Completed',
        transaction_reference = COALESCE(transaction_reference, ?),
        verified_at = NOW()
        WHERE id = ?")
       ->execute([$mpesaReceiptNumber, $mpesaPhoneNumber, $mpesaReceiptNumber, $payment['id']]);
    
    $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ?")
       ->execute([$payment['booking_id']]);
    
    $vehicleStmt = $pdo->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
    $vehicleStmt->execute([$payment['booking_id']]);
    $bookingData = $vehicleStmt->fetch();
    
    if ($bookingData && $bookingData['vehicle_id']) {
        $pdo->prepare("UPDATE vehicles SET status = 'Booked' WHERE id = ?")
           ->execute([$bookingData['vehicle_id']]);
    }
    
    $clientStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $clientStmt->execute([$payment['booking_id']]);
    $clientData = $clientStmt->fetch();
    
    if ($clientData && $clientData['user_id']) {
        createNotification($clientData['user_id'], 'Payment Verified', 
            'Your M-Pesa payment of ' . formatCurrency($payment['amount']) . ' has been verified. Booking confirmed.',
            'success', BASE_URL . 'client/booking-details.php?id=' . $payment['booking_id']);
    }
    
    $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'")->fetchAll();
    foreach ($admins as $admin) {
        createNotification($admin['id'], 'Payment Verified', 
            'M-Pesa payment of ' . formatCurrency($payment['amount']) . ' received for booking ' . $payment['booking_id'],
            'success', BASE_URL . 'admin/payments/index.php');
    }
    
    logActivity($payment['client_id'], 'Payment Verified', 'M-Pesa payment of ' . formatCurrency($payment['amount']) . ' verified');
} else {
    $pdo->prepare("UPDATE payments SET status = 'Rejected', mpesa_status = 'Failed', rejection_reason = ? WHERE id = ?")
       ->execute([$resultDesc, $payment['id']]);
    
    $clientStmt = $pdo->prepare("SELECT user_id FROM bookings WHERE id = ?");
    $clientStmt->execute([$payment['booking_id']]);
    $clientData = $clientStmt->fetch();
    
    if ($clientData && $clientData['user_id']) {
        createNotification($clientData['user_id'], 'Payment Failed', 
            'Your M-Pesa payment failed: ' . $resultDesc,
            'error', BASE_URL . 'client/payment.php?booking_id=' . $payment['booking_id']);
    }
    
    logActivity($payment['client_id'], 'Payment Failed', 'M-Pesa payment failed: ' . $resultDesc);
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback processed successfully']);
