<?php
require_once __DIR__ . '/../../config/database.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        redirect('/admin/vehicles/index.php');
    }
    
    $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    
    if ($vehicleId) {
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();
        
        if ($vehicle) {
            $images = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ?");
            $images->execute([$vehicleId]);
            foreach ($images->fetchAll() as $img) {
                deleteFile($img['image_path'], 'vehicles');
            }
            
            $pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$vehicleId]);
            logActivity($_SESSION['user_id'], 'Vehicle Deleted', "Deleted vehicle: {$vehicle['brand']} {$vehicle['model']}");
        }
    }
}
redirect('/admin/vehicles/index.php');
</body>
</html>
