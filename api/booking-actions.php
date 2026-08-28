<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'update_status' && isset($input['booking_id'], $input['status'])) {
        $bookingId = (int)$input['booking_id'];
        $status = sanitize($input['status']);
        
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $response = ['success' => false, 'message' => 'Booking not found'];
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $bookingId])) {
                if ($status === 'Active' && $booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'On Trip' WHERE id = ?")->execute([$booking['vehicle_id']]);
                    if ($booking['driver_id']) {
                        $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?")->execute([$booking['driver_id']]);
                    }
                } elseif ($status === 'Completed' && $booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                    if ($booking['driver_id']) {
                        $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                    }
                }
                
                logActivity($_SESSION['user_id'] ?? null, 'Booking Status Updated', "Booking {$booking['booking_reference']} status changed to $status");
                $response = ['success' => true, 'message' => 'Booking status updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update status'];
            }
        }
    } elseif ($action === 'save_notes' && isset($input['booking_id'], $input['notes'])) {
        $bookingId = (int)$input['booking_id'];
        $notes = sanitize($input['notes']);
        
        $stmt = $pdo->prepare("UPDATE bookings SET notes = ? WHERE id = ?");
        if ($stmt->execute([$notes, $bookingId])) {
            $response = ['success' => true, 'message' => 'Notes saved'];
        }
    } elseif ($action === 'toggle_user_status' && isset($input['user_id'], $input['status'])) {
        $userId = (int)$input['user_id'];
        $status = sanitize($input['status']);
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $userId])) {
            logActivity($_SESSION['user_id'], 'User Status Changed', "Changed user #$userId status to $status");
            $response = ['success' => true];
        }
    } elseif ($action === 'promote_admin' && isset($input['user_id'])) {
        $userId = (int)$input['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin' WHERE id = ?");
        if ($stmt->execute([$userId])) {
            logActivity($_SESSION['user_id'], 'Admin Promoted', "Promoted user #$userId to super_admin");
            $response = ['success' => true, 'message' => 'User promoted to Super Admin'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to promote user'];
        }
    }
}

echo json_encode($response);
