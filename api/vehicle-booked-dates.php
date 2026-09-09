<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'dates' => [], 'message' => ''];

if (isset($_GET['vehicle_id']) && is_numeric($_GET['vehicle_id'])) {
    $vehicleId = (int)$_GET['vehicle_id'];
    
    $stmt = $pdo->prepare("SELECT pickup_datetime, return_datetime FROM bookings 
                           WHERE vehicle_id = ? AND status NOT IN ('Cancelled', 'Completed')
                           ORDER BY pickup_datetime ASC");
    $stmt->execute([$vehicleId]);
    $bookings = $stmt->fetchAll();
    
    if ($bookings) {
        foreach ($bookings as $booking) {
            $response['dates'][] = [
                'from' => date('Y-m-d H:i', strtotime($booking['pickup_datetime'])),
                'to' => date('Y-m-d H:i', strtotime($booking['return_datetime']))
            ];
        }
    }
    
    $response['success'] = true;
} else {
    $response['message'] = 'Vehicle ID is required';
}

echo json_encode($response);
