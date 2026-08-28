<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['vehicle_id'], $input['pickup_datetime'], $input['return_datetime'])) {
        $vehicleId = (int)$input['vehicle_id'];
        $pickup = $input['pickup_datetime'];
        $return = $input['return_datetime'];
        $excludeId = isset($input['exclude_booking_id']) ? (int)$input['exclude_booking_id'] : null;
        
        if (checkVehicleAvailability($vehicleId, $pickup, $return, $excludeId)) {
            $response = ['success' => true, 'available' => true, 'message' => 'Vehicle is available'];
        } else {
            $response = ['success' => true, 'available' => false, 'message' => 'Vehicle is not available for the selected dates'];
        }
    }
}

echo json_encode($response);
