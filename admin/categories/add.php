<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');
    
    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO vehicle_categories (name, description, status) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $status])) {
            logActivity($_SESSION['user_id'], 'Category Added', "Added new category: $name");
            $success = 'Category added successfully!';
        } else {
            $error = 'Failed to add category. Please try again.';
        }
    }
}

$pageTitle = 'Add Category - Smart Drive Car Hire';
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
            <p><?php echo ucfirst($user['role']); ?> Panel</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php"><i class="fas fa-car"></i> Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            
            <li><a href="<?php echo BASE_URL; ?>admin/categories/index.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
            <?php if (isSuperAdmin()): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
            <?php endif; ?>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Add Category</h1>
            <div class="top-bar-actions">
                <a href="<?php echo BASE_URL; ?>admin/categories/index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="dashboard-grid">
                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Sedan, SUV, Luxury">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Enter category description..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
<div class="sidebar-overlay"></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
