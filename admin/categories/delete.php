<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        redirect('/admin/categories/index.php');
    }
    
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    if ($categoryId) {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        
        if ($category) {
            $vehicleCount = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE category_id = ?");
            $vehicleCount->execute([$categoryId]);
            $count = $vehicleCount->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['error'] = 'Cannot delete category with associated vehicles.';
            } else {
                $pdo->prepare("DELETE FROM vehicle_categories WHERE id = ?")->execute([$categoryId]);
                logActivity($_SESSION['user_id'], 'Category Deleted', "Deleted category: {$category['name']}");
            }
        }
    }
}

redirect('/admin/categories/index.php');
