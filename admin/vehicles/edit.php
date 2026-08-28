<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$message = '';
$error = '';

$vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$vehicleId) {
    redirect('/admin/vehicles/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('/admin/vehicles/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_vehicle'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $registrationNumber = sanitize($_POST['registration_number'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $transmission = sanitize($_POST['transmission'] ?? '');
    $fuelType = sanitize($_POST['fuel_type'] ?? '');
    $seatingCapacity = (int)($_POST['seating_capacity'] ?? 0);
    $pricePerDay = (float)($_POST['price_per_day'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'Available');
    
    if (empty($categoryId) || empty($name) || empty($brand) || empty($model) || empty($registrationNumber) || empty($transmission) || empty($fuelType)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare("UPDATE vehicles SET category_id=?, name=?, brand=?, model=?, registration_number=?, year=?, transmission=?, fuel_type=?, seating_capacity=?, price_per_day=?, description=?, status=? WHERE id=?");
        if ($stmt->execute([$categoryId, $name, $brand, $model, $registrationNumber, $year, $transmission, $fuelType, $seatingCapacity, $pricePerDay, $description, $status, $vehicleId])) {
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        $result = uploadFile($file, 'vehicles');
                        if ($result['success']) {
                            $isPrimary = ($key === 0) ? 1 : 0;
                            $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_path, is_primary) VALUES (?, ?, ?)")
                                ->execute([$vehicleId, $result['filename'], $isPrimary]);
                        }
                    }
                }
            }
            
            logActivity($_SESSION['user_id'], 'Vehicle Updated', "Updated vehicle: $brand $model ($registrationNumber)");
            $message = 'Vehicle updated successfully!';
            
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicleId]);
            $vehicle = $stmt->fetch();
        } else {
            $error = 'Failed to update vehicle. Please try again.';
        }
    }
}

$categories = $pdo->query("SELECT * FROM vehicle_categories WHERE status = 'active' ORDER BY name")->fetchAll();
$images = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ?");
$images->execute([$vehicleId]);
$vehicleImages = $images->fetchAll();

$pageTitle = 'Edit Vehicle - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php" class="active"><i class="fas fa-car"></i> Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <?php if (isSuperAdmin()): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
            <?php endif; ?>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay"></div>

    <main class="main-content">
        <div class="top-bar">
            <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>Edit Vehicle</h1>
            <a href="<?php echo BASE_URL; ?>admin/vehicles/index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Vehicles
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="dashboard-grid">
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $vehicle['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vehicle Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($vehicle['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Brand *</label>
                            <input type="text" name="brand" required value="<?php echo htmlspecialchars($vehicle['brand']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Model *</label>
                            <input type="text" name="model" required value="<?php echo htmlspecialchars($vehicle['model']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Registration Number *</label>
                            <input type="text" name="registration_number" required value="<?php echo htmlspecialchars($vehicle['registration_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Year *</label>
                            <input type="number" name="year" required min="1900" max="2099" value="<?php echo $vehicle['year']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Transmission *</label>
                            <select name="transmission" required>
                                <option value="Automatic" <?php echo $vehicle['transmission'] === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                <option value="Manual" <?php echo $vehicle['transmission'] === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fuel Type *</label>
                            <select name="fuel_type" required>
                                <option value="Petrol" <?php echo $vehicle['fuel_type'] === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                <option value="Diesel" <?php echo $vehicle['fuel_type'] === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                <option value="Electric" <?php echo $vehicle['fuel_type'] === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                <option value="Hybrid" <?php echo $vehicle['fuel_type'] === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Seating Capacity *</label>
                            <input type="number" name="seating_capacity" required min="1" max="50" value="<?php echo $vehicle['seating_capacity']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Price Per Day (KES) *</label>
                            <input type="number" name="price_per_day" required min="0" step="0.01" value="<?php echo $vehicle['price_per_day']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Available" <?php echo $vehicle['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Booked" <?php echo $vehicle['status'] === 'Booked' ? 'selected' : ''; ?>>Booked</option>
                                <option value="On Trip" <?php echo $vehicle['status'] === 'On Trip' ? 'selected' : ''; ?>>On Trip</option>
                                <option value="Maintenance" <?php echo $vehicle['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Inactive" <?php echo $vehicle['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Add More Images</label>
                        <input type="file" name="images[]" multiple accept="image/*">
                    </div>
                    <button type="submit" name="edit_vehicle" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Vehicle
                    </button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($vehicleImages)): ?>
            <div class="card mt-2">
                <div class="card-header">
                    <h3>Current Images</h3>
                </div>
                <div class="card-body">
                    <div class="image-gallery">
                        <?php foreach ($vehicleImages as $img): ?>
                            <div class="image-item">
                                <img src="<?php echo UPLOAD_URL . 'vehicles/' . $img['image_path']; ?>" 
                                     class="img-thumb"
                                     onerror="this.style.display='none'">
                                <form method="POST" action="<?php echo BASE_URL; ?>admin/vehicles/delete-image.php" class="delete-image-form">
                                    <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-delete-image" 
                                            onclick="return confirm('Delete this image?')">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
