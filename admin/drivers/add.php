<?php
require_once __DIR__ . '/../../config/database.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}
$user = getCurrentUser();
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_driver'])) {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $licenseNumber = sanitize($_POST['license_number'] ?? '');
    $licenseExpiry = sanitize($_POST['license_expiry'] ?? '');
    $nationalId = sanitize($_POST['national_id'] ?? '');
    $status = sanitize($_POST['status'] ?? 'Available');
    
    if (empty($fullName) || empty($phone) || empty($licenseNumber) || empty($licenseExpiry) || empty($nationalId)) {
        $error = 'Please fill in all required fields.';
    } else {
        $profileImage = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['profile_image'], 'drivers');
            if ($result['success']) {
                $profileImage = $result['filename'];
            }
        }
        
        $pdo->beginTransaction();
        try {
            $driverEmail = $email ?: $phone . '@driver.local';
            $defaultPassword = password_hash($phone, PASSWORD_DEFAULT);
            
            $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $checkEmail->execute([$driverEmail]);
            if ($checkEmail->fetchColumn() > 0) {
                $pdo->rollBack();
                $error = "A user with email '$driverEmail' already exists.";
            } else {
                $userStmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'driver', 'active')");
                $userStmt->execute([$fullName, $driverEmail, $phone, $defaultPassword]);
                $userId = $pdo->lastInsertId();
                
                $checkLicense = $pdo->prepare("SELECT COUNT(*) FROM drivers WHERE license_number = ?");
                $checkLicense->execute([$licenseNumber]);
                if ($checkLicense->fetchColumn() > 0) {
                    $pdo->rollBack();
                    $error = "A driver with license number '$licenseNumber' already exists.";
                } else {
                    $checkNationalId = $pdo->prepare("SELECT COUNT(*) FROM drivers WHERE national_id = ?");
                    $checkNationalId->execute([$nationalId]);
                    if ($checkNationalId->fetchColumn() > 0) {
                        $pdo->rollBack();
                        $error = "A driver with National ID '$nationalId' already exists.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO drivers (user_id, full_name, email, phone, license_number, license_expiry, national_id, profile_image, status) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt->execute([$userId, $fullName, $driverEmail, $phone, $licenseNumber, $licenseExpiry, $nationalId, $profileImage, $status])) {
                            $pdo->commit();
                            logActivity($_SESSION['user_id'], 'Driver Added', "Added driver: $fullName");
                            $message = 'Driver added successfully! Driver can login with phone number as password.';
                        } else {
                            $pdo->rollBack();
                            $error = 'Failed to add driver. Please try again.';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
$categories = $pdo->query("SELECT * FROM vehicle_categories WHERE status = 'active' ORDER BY name")->fetchAll();
$pageTitle = 'Add Driver - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php" class="active"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/categories/index.php"><i class="fas fa-tags"></i> Categories</a></li>
            
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
            <h1>Add New Driver</h1>
            <a href="<?php echo BASE_URL; ?>admin/drivers/index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Drivers
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
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Enter driver's full name">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Enter email address">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" required placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label>License Number *</label>
                            <input type="text" name="license_number" required placeholder="Enter license number">
                        </div>
                        <div class="form-group">
                            <label>License Expiry Date *</label>
                            <input type="date" name="license_expiry" required>
                        </div>
                        <div class="form-group">
                            <label>National ID / Passport *</label>
                            <input type="text" name="national_id" required placeholder="Enter ID number">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Available">Available</option>
                                <option value="Assigned">Assigned</option>
                                <option value="On Trip">On Trip</option>
                                <option value="Off Duty">Off Duty</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Profile Image</label>
                            <input type="file" name="profile_image" accept="image/*">
                        </div>
                    </div>
                    <button type="submit" name="add_driver" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Driver
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
