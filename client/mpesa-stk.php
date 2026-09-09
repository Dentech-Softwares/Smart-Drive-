<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mpesa.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$phoneNumber = sanitize($_POST['phone_number'] ?? '');

if (!$bookingId || $amount <= 0 || empty($phoneNumber)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$clientId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND client_id = ?");
$stmt->execute([$bookingId, $clientId]);
$booking = $stmt->fetch();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

if ($booking['status'] !== 'Approved') {
    echo json_encode(['success' => false, 'message' => 'Payment can only be made for approved bookings']);
    exit();
}

$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) === '0') {
    $phoneNumber = '254' . substr($phoneNumber, 1);
}
if (strlen($phoneNumber) !== 12 || substr($phoneNumber, 0, 3) !== '254') {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use 07XXXXXXXX']);
    exit();
}

$stmt = $pdo->prepare("INSERT INTO payments (booking_id, client_id, amount, payment_method, transaction_reference, mpesa_phone_number, mpesa_status, status) 
                       VALUES (?, ?, ?, 'M-Pesa', ?, ?, 'Initiated', 'Pending')");
if ($stmt->execute([$bookingId, $clientId, $amount, $booking['booking_reference'], $phoneNumber])) {
    $paymentId = $pdo->lastInsertId();
    
    $result = initiateMpesaSTK($phoneNumber, $amount, $booking['booking_reference'], $booking['booking_reference']);
    
    if ($result['success']) {
        $pdo->prepare("UPDATE payments SET mpesa_checkout_request_id = ? WHERE id = ?")
           ->execute([$result['checkout_request_id'], $paymentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'STK Push sent. Complete the payment on your phone to Hayven CarHire.',
            'checkout_request_id' => $result['checkout_request_id']
        ]);
    } else {
        $pdo->prepare("UPDATE payments SET status = 'Rejected', rejection_reason = ? WHERE id = ?")
           ->execute([$result['message'], $paymentId]);
        
        $debugMessage = $result['message'];
        if (isset($result['http_code'])) {
            $debugMessage .= ' (HTTP ' . $result['http_code'] . ')';
        }
        if (isset($result['response']) && is_array($result['response'])) {
            $debugMessage .= ' Response: ' . json_encode($result['response']);
        }
        
        echo json_encode(['success' => false, 'message' => $debugMessage]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to initiate payment']);
}
