<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}

$user = getCurrentUser();
$clientId = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $pickupLocation = sanitize($_POST['pickup_location'] ?? '');
    $returnLocation = sanitize($_POST['return_location'] ?? '');
    $pickupDatetime = sanitize($_POST['pickup_datetime'] ?? '');
    $returnDatetime = sanitize($_POST['return_datetime'] ?? '');
    $rentalDays = (int)($_POST['rental_days'] ?? 0);
    $vehiclePrice = (float)($_POST['vehicle_price'] ?? 0);
    $additionalCost = (float)($_POST['additional_cost'] ?? 0);
    $totalAmount = (float)($_POST['total_amount'] ?? 0);
    
    if (!$vehicleId || empty($pickupLocation) || empty($returnLocation) || empty($pickupDatetime) || empty($returnDatetime)) {
        $error = 'Please fill in all required fields.';
    } elseif ($rentalDays <= 0) {
        $error = 'Invalid rental period.';
    } elseif ($totalAmount <= 0) {
        $error = 'Invalid total amount.';
    } else {
        if (!checkVehicleAvailability($vehicleId, $pickupDatetime, $returnDatetime)) {
            $error = 'Vehicle is not available for the selected dates. Please choose different dates.';
        } else {
            $bookingReference = generateBookingReference();
            $status = 'Pending';
            
            $stmt = $pdo->prepare("INSERT INTO bookings (booking_reference, client_id, vehicle_id, pickup_location, return_location, pickup_datetime, return_datetime, rental_days, vehicle_price, additional_cost, total_amount, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$bookingReference, $clientId, $vehicleId, $pickupLocation, $returnLocation, $pickupDatetime, $returnDatetime, $rentalDays, $vehiclePrice, $additionalCost, $totalAmount, $status])) {
                $bookingId = $pdo->lastInsertId();
                
                $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'")->fetchAll();
                foreach ($admins as $admin) {
                    createNotification($admin['id'], 'New Booking', 
                        "New booking $bookingReference has been created by " . $user['full_name'],
                        'info', '/admin/bookings/index.php');
                }
                
                logActivity($clientId, 'Booking Created', "Created booking $bookingReference");
                $success = 'Booking created successfully! Booking reference: ' . $bookingReference;
            } else {
                $error = 'Failed to create booking. Please try again.';
            }
        }
    }
}

if ($error) {
    $_SESSION['flash'] = ['message' => $error, 'type' => 'error'];
} elseif ($success) {
    $_SESSION['flash'] = ['message' => $success, 'type' => 'success'];
}

redirect('/client/bookings.php');
