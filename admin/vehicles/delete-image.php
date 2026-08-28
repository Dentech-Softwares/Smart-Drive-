<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
    $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    
    if ($imageId) {
        $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();
        
        if ($image) {
            deleteFile($image['image_path'], 'vehicles');
            $pdo->prepare("DELETE FROM vehicle_images WHERE id = ?")->execute([$imageId]);
            logActivity($_SESSION['user_id'], 'Image Deleted', "Deleted vehicle image #$imageId");
        }
    }
}

redirect('/admin/vehicles/edit.php?id=' . $vehicleId);
