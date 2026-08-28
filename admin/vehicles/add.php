<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
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
        $stmt = $pdo->prepare("INSERT INTO vehicles (category_id, name, brand, model, registration_number, year, transmission, fuel_type, seating_capacity, price_per_day, description, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$categoryId, $name, $brand, $model, $registrationNumber, $year, $transmission, $fuelType, $seatingCapacity, $pricePerDay, $description, $status])) {
            $vehicleId = $pdo->lastInsertId();
            
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
            
            logActivity($_SESSION['user_id'], 'Vehicle Added', "Added vehicle: $brand $model ($registrationNumber)");
            $message = 'Vehicle added successfully!';
        } else {
            $error = 'Failed to add vehicle. Please try again.';
        }
    }
}

$categories = $pdo->query("SELECT * FROM vehicle_categories WHERE status = 'active' ORDER BY name")->fetchAll();

$pageTitle = 'Add Vehicle - Smart Drive Car Hire';
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
            <h1>Add New Vehicle</h1>
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
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vehicle Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Toyota Corolla">
                        </div>
                        <div class="form-group">
                            <label>Brand *</label>
                            <input type="text" name="brand" required placeholder="e.g. Toyota">
                        </div>
                        <div class="form-group">
                            <label>Model *</label>
                            <input type="text" name="model" required placeholder="e.g. Corolla">
                        </div>
                        <div class="form-group">
                            <label>Registration Number *</label>
                            <input type="text" name="registration_number" required placeholder="e.g. KDA 123A">
                        </div>
                        <div class="form-group">
                            <label>Year *</label>
                            <input type="number" name="year" required min="1900" max="2099" value="<?php echo date('Y'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Transmission *</label>
                            <select name="transmission" required>
                                <option value="">Select Transmission</option>
                                <option value="Automatic">Automatic</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fuel Type *</label>
                            <select name="fuel_type" required>
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol">Petrol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Electric">Electric</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Seating Capacity *</label>
                            <input type="number" name="seating_capacity" required min="1" max="50" value="5">
                        </div>
                        <div class="form-group">
                            <label>Price Per Day (KES) *</label>
                            <input type="number" name="price_per_day" required min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Available">Available</option>
                                <option value="Booked">Booked</option>
                                <option value="On Trip">On Trip</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Vehicle description..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Images</label>
                        <input type="file" name="images[]" multiple accept="image/*">
                        <small>You can upload multiple images. First image will be the primary image.</small>
                    </div>
                    <button type="submit" name="add_vehicle" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>