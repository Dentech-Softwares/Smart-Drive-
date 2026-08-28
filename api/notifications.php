<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_read' && isset($input['notification_id'])) {
        markNotificationRead($input['notification_id']);
        $response = ['success' => true];
    } elseif ($action === 'mark_all_read' && isLoggedIn()) {
        $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE")
            ->execute([$_SESSION['user_id']]);
        $response = ['success' => true];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'count') {
    if (isLoggedIn()) {
        $count = getUnreadNotifications($_SESSION['user_id']);
        $response = ['success' => true, 'count' => $count];
    }
}

echo json_encode($response);
