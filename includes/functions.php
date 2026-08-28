<?php
require_once __DIR__ . '/../config/database.php';
function redirect($url) {
    if ($url && !preg_match('/^(https?:)?\/\//i', $url) && $url[0] !== '#') {
        $url = BASE_URL . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}
function isAdmin() {
    return isLoggedIn() && in_array($_SESSION['role'], ['admin', 'super_admin']);
}
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'super_admin';
}
function isDriver() {
    return isLoggedIn() && $_SESSION['role'] === 'driver';
}
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
function generateBookingReference() {
    return 'SD-' . strtoupper(uniqid());
}
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
function uploadFile($file, $directory = 'general') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $maxSize = 5 * 1024 * 1024;
    
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = UPLOAD_DIR . $directory . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $destination = $uploadPath . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => UPLOAD_URL . $directory . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}
function deleteFile($filename, $directory = 'general') {
    $path = UPLOAD_DIR . $directory . '/' . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}
function logActivity($userId, $action, $description) {
    global $pdo;
    $user = getCurrentUser();
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, action, description, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $user['full_name'] ?? 'System', $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
}
function createNotification($userId, $title, $message, $type = 'info', $link = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $title, $message, $type, $link]);
}
function getUnreadNotifications($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}
function markNotificationRead($notificationId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$notificationId]);
}
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}
function formatCurrency($amount) {
    $symbol = getSetting('currency_symbol', 'KSh ');
    return $symbol . number_format($amount, 2);
}
function calculateRentalDays($pickup, $return) {
    $start = new DateTime($pickup);
    $end = new DateTime($return);
    $interval = $start->diff($end);
    return max(1, (int)$interval->format('%a'));
}
function checkVehicleAvailability($vehicleId, $pickup, $return, $excludeBookingId = null) {
    global $pdo;
    $sql = "SELECT COUNT(*) as count FROM bookings 
            WHERE vehicle_id = ? 
            AND status NOT IN ('Cancelled', 'Completed')
            AND (pickup_datetime < ? AND return_datetime > ?)";
    
    $params = [$vehicleId, $return, $pickup];
    
    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['count'] == 0;
}
function flashMessage($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $alertClass = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        echo '<div class="alert alert-' . $alertClass . '" style="position: relative;">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" onclick="this.parentElement.style.display=\'none\'" style="position: absolute; right: 15px; top: 15px; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: inherit;">&times;</button>';
        echo '</div>';
    }
}
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function paginate($query, $perPage = 10, $page = 1) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare($query . " LIMIT $perPage OFFSET $offset");
    $stmt->execute();
    return $stmt->fetchAll();
}
function getPageCount($total, $perPage) {
    return ceil($total / $perPage);
}
function getVehicleImage($vehicleId, $isPrimary = true) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ? AND is_primary = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$vehicleId, $isPrimary ? 1 : 0]);
    $result = $stmt->fetch();
    if ($result) return $result['image_path'];
    
    $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$vehicleId]);
    $result = $stmt->fetch();
    return $result ? $result['image_path'] : BASE_URL . 'assets/images/vehicles/default.jpg';
}
function getVehiclePrimaryImage($vehicleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ? AND is_primary = 1 LIMIT 1");
    $stmt->execute([$vehicleId]);
    $result = $stmt->fetch();
    return $result ? UPLOAD_URL . 'vehicles/' . $result['image_path'] : null;
}
function getDriverStatusLabel($status) {
    $labels = [
        'Available' => '<span class="badge bg-success">Available</span>',
        'Assigned' => '<span class="badge bg-primary">Assigned</span>',
        'On Trip' => '<span class="badge bg-warning text-dark">On Trip</span>',
        'Off Duty' => '<span class="badge bg-secondary">Off Duty</span>',
        'Inactive' => '<span class="badge bg-danger">Inactive</span>'
    ];
    return $labels[$status] ?? $status;
}
function getBookingStatusLabel($status) {
    $labels = [
        'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'Awaiting Payment' => '<span class="badge bg-info">Awaiting Payment</span>',
        'Payment Submitted' => '<span class="badge bg-primary">Payment Submitted</span>',
        'Confirmed' => '<span class="badge bg-success">Confirmed</span>',
        'Assigned' => '<span class="badge bg-primary">Assigned</span>',
        'Active' => '<span class="badge bg-primary">Active</span>',
        'Completed' => '<span class="badge bg-success">Completed</span>',
        'Cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    return $labels[$status] ?? $status;
}
function getVehicleStatusLabel($status) {
    $labels = [
        'Available' => '<span class="badge bg-success">Available</span>',
        'Booked' => '<span class="badge bg-info">Booked</span>',
        'On Trip' => '<span class="badge bg-warning text-dark">On Trip</span>',
        'Maintenance' => '<span class="badge bg-secondary">Maintenance</span>',
        'Inactive' => '<span class="badge bg-danger">Inactive</span>'
    ];
    return $labels[$status] ?? $status;
}
function getPaymentStatusLabel($status) {
    $labels = [
        'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'Verified' => '<span class="badge bg-success">Verified</span>',
        'Rejected' => '<span class="badge bg-danger">Rejected</span>'
    ];
    return $labels[$status] ?? $status;
}
function getCount($table, $condition = '') {
    global $pdo;
    $sql = "SELECT COUNT(*) as count FROM $table";
    if ($condition) {
        $sql .= " WHERE $condition";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetch()['count'];
}
function getSum($table, $column, $condition = '') {
    global $pdo;
    $sql = "SELECT SUM($column) as total FROM $table";
    if ($condition) {
        $sql .= " WHERE $condition";
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetch()['total'] ?? 0;
}
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
function formatDateTime($date) {
    return date('M d, Y h:i A', strtotime($date));
}
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' min ago';
    if ($time < 86400) return floor($time / 3600) . ' hr ago';
    if ($time < 604800) return floor($time / 86400) . ' days ago';
    return date('M d, Y', strtotime($datetime));
}
