<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if (!isLoggedIn()) {
    $response['message'] = 'Authentication required';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_read' && isset($input['notification_id'])) {
        $notificationId = (int)$input['notification_id'];
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            markNotificationRead($notificationId);
            $response = ['success' => true];
        } else {
            $response['message'] = 'Notification not found';
        }
    } elseif ($action === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE")
            ->execute([$_SESSION['user_id']]);
        $response = ['success' => true];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'count') {
    $count = getUnreadNotifications($_SESSION['user_id']);
    $response = ['success' => true, 'count' => $count];
}

echo json_encode($response);
