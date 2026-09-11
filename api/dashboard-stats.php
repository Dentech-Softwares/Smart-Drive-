<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unauthorized'];

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode($response);
    exit();
}

try {
    $stats = [
        'totalVehicles' => (int)getCount('vehicles'),
        'availableVehicles' => (int)getCount('vehicles', "status = 'Available'"),
        'activeBookings' => (int)getCount('bookings', "status IN ('Confirmed', 'Active')"),
        'pendingBookings' => (int)getCount('bookings', "status = 'Pending'"),
        'totalClients' => (int)getCount('users', "role = 'client' AND status = 'active'"),
        'totalDrivers' => (int)getCount('drivers'),
        'pendingPayments' => (int)getCount('payments', "status = 'Pending'"),
        'revenue' => (float)getSum('payments', 'amount', "status = 'Verified'"),
        'activeTrips' => (int)getCount('bookings', "status = 'Active'"),
        'unreadNotifications' => (int)getUnreadNotifications(getCurrentUser()['id'])
    ];
    
    $stmt = $pdo->query("SELECT b.*, u.full_name as client_name, CONCAT(v.brand, ' ', v.model) as vehicle_name, v.brand, v.model 
                         FROM bookings b 
                         JOIN users u ON b.client_id = u.id 
                         JOIN vehicles v ON b.vehicle_id = v.id 
                         ORDER BY b.created_at DESC 
                         LIMIT 5");
    $recentBookings = $stmt->fetchAll();
    foreach ($recentBookings as &$booking) {
        $booking['formatted_amount'] = formatCurrency($booking['total_amount']);
        $booking['status_label'] = getBookingStatusLabel($booking['status']);
    }
    
    $response = [
        'success' => true,
        'stats' => $stats,
        'recentBookings' => $recentBookings
    ];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error loading dashboard data'];
}

echo json_encode($response);
