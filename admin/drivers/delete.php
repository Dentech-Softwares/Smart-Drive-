<?php
require_once __DIR__ . '/../../config/database.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        redirect('/admin/drivers/index.php');
    }
    
    $driverId = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : 0;
    
    if ($driverId) {
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch();
        
        if ($driver) {
            $activeTrips = getCount('bookings', "driver_id = $driverId AND status IN ('Assigned', 'Active')");
            
            if ($activeTrips > 0) {
                $_SESSION['error'] = 'Cannot delete driver with active trips.';
            } else {
                if ($driver['profile_image']) {
                    deleteFile($driver['profile_image'], 'drivers');
                }
                
                $pdo->prepare("DELETE FROM drivers WHERE id = ?")->execute([$driverId]);
                logActivity($_SESSION['user_id'], 'Driver Deleted', "Deleted driver: {$driver['full_name']}");
            }
        }
    }
}
redirect('/admin/drivers/index.php');
</body>
</html>
