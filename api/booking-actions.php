<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

ob_start();
$response = ['success' => false, 'message' => 'Invalid request'];

if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'Unauthorized - please login'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response = ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()];
    } elseif (!$input || !is_array($input)) {
        $response = ['success' => false, 'message' => 'Empty request body'];
    } else {
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
                if ($status === 'Confirmed' && $booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'Booked' WHERE id = ?")->execute([$booking['vehicle_id']]);
                } elseif ($status === 'Active' && $booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'On Trip' WHERE id = ?")->execute([$booking['vehicle_id']]);
                    if ($booking['driver_id']) {
                        $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?")->execute([$booking['driver_id']]);
                    }
                    createNotification($booking['client_id'], 'Trip Started', 
                        'Your booking ' . $booking['booking_reference'] . ' has started. Enjoy your trip!',
                        'info', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
                } elseif (in_array($status, ['Completed', 'Cancelled']) && $booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                    if ($booking['driver_id']) {
                        $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                    }
                    if ($status === 'Completed') {
                        createNotification($booking['client_id'], 'Trip Completed', 
                            'Your booking ' . $booking['booking_reference'] . ' has been completed. Thank you for choosing Smart Drive!',
                            'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
                    } elseif ($status === 'Cancelled') {
                        createNotification($booking['client_id'], 'Booking Cancelled', 
                            'Your booking ' . $booking['booking_reference'] . ' has been cancelled.',
                            'error', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
                    }
                }
                
                logActivity($_SESSION['user_id'] ?? null, 'Booking Status Updated', "Booking {$booking['booking_reference']} status changed to $status");
                $response = ['success' => true, 'message' => 'Booking status updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update status'];
            }
        }
    } elseif ($action === 'start_trip' && isset($input['booking_id'])) {
        $bookingId = (int)$input['booking_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND client_id = ?");
        $stmt->execute([$bookingId, $_SESSION['user_id']]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $response = ['success' => false, 'message' => 'Booking not found or unauthorized'];
        } elseif ($booking['status'] !== 'Confirmed') {
            $response = ['success' => false, 'message' => 'Booking must be confirmed before starting trip'];
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Active' WHERE id = ?");
            if ($stmt->execute([$bookingId])) {
                if ($booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'On Trip' WHERE id = ?")->execute([$booking['vehicle_id']]);
                }
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                
                logActivity($_SESSION['user_id'], 'Trip Started', "Started trip for booking {$booking['booking_reference']}");
                $response = ['success' => true, 'message' => 'Trip started successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to start trip'];
            }
        }
    } elseif ($action === 'end_trip' && isset($input['booking_id'])) {
        $bookingId = (int)$input['booking_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $response = ['success' => false, 'message' => 'Booking not found'];
        } else {
            $penalty = 0;
            $returnDatetime = new DateTime($booking['return_datetime']);
            $now = new DateTime();
            
            if ($now > $returnDatetime) {
                $interval = $now->diff($returnDatetime);
                $hoursLate = $interval->h + ($interval->days * 24);
                $hoursLate = max(1, $hoursLate);
                $penalty = $hoursLate * 200;
            }
            
            $newAdditionalCost = $booking['additional_cost'] + $penalty;
            $newTotalAmount = $booking['vehicle_price'] + $newAdditionalCost;
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Completed', penalty = ?, additional_cost = ?, total_amount = ? WHERE id = ?");
            if ($stmt->execute([$penalty, $newAdditionalCost, $newTotalAmount, $bookingId])) {
                if ($booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                }
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                
                createNotification($booking['client_id'], 'Trip Completed', 
                    'Your booking ' . $booking['booking_reference'] . ' has been completed.' . 
                    ($penalty > 0 ? ' A late return penalty of ' . formatCurrency($penalty) . ' has been applied.' : '') .
                    ' Thank you for choosing Smart Drive!',
                    'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
                
                logActivity($_SESSION['user_id'] ?? null, 'Trip Ended', "Booking {$booking['booking_reference']} ended by client" . ($penalty > 0 ? " with penalty $penalty" : ""));
                $response = ['success' => true, 'message' => 'Trip ended successfully', 'penalty' => $penalty];
            } else {
                $response = ['success' => false, 'message' => 'Failed to end trip'];
            }
        }
    } elseif ($action === 'cancel_booking' && isset($input['booking_id'])) {
        $bookingId = (int)$input['booking_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND client_id = ?");
        $stmt->execute([$bookingId, $_SESSION['user_id']]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $response = ['success' => false, 'message' => 'Booking not found or unauthorized'];
        } elseif (in_array($booking['status'], ['Active', 'Completed'])) {
            $response = ['success' => false, 'message' => 'Cannot cancel a trip that has already started or completed'];
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
            if ($stmt->execute([$bookingId])) {
                if ($booking['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                }
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                
                createNotification($_SESSION['user_id'], 'Booking Cancelled', 
                    'Your booking ' . $booking['booking_reference'] . ' has been cancelled.',
                    'error', BASE_URL . 'client/bookings.php');
                
                logActivity($_SESSION['user_id'], 'Booking Cancelled', "Booking {$booking['booking_reference']} was cancelled");
                $response = ['success' => true, 'message' => 'Booking cancelled successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to cancel booking'];
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
}}

ob_clean();
echo json_encode($response);
