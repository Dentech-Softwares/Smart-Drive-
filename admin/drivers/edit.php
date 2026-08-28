<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$message = '';
$error = '';

$driverId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$driverId) {
    redirect('/admin/drivers/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
$stmt->execute([$driverId]);
$driver = $stmt->fetch();

if (!$driver) {
    redirect('/admin/drivers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_driver'])) {
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
        $profileImage = $driver['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['profile_image'], 'drivers');
            if ($result['success']) {
                if ($profileImage && file_exists(UPLOAD_DIR . 'drivers/' . $profileImage)) {
                    unlink(UPLOAD_DIR . 'drivers/' . $profileImage);
                }
                $profileImage = $result['filename'];
            }
        }
        
        $stmt = $pdo->prepare("UPDATE drivers SET full_name=?, email=?, phone=?, license_number=?, license_expiry=?, national_id=?, profile_image=?, status=? WHERE id=?");
        if ($stmt->execute([$fullName, $email, $phone, $licenseNumber, $licenseExpiry, $nationalId, $profileImage, $status, $driverId])) {
            if ($driver['user_id']) {
                $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?")
                    ->execute([$fullName, $email, $phone, $driver['user_id']]);
            }
            logActivity($_SESSION['user_id'], 'Driver Updated', "Updated driver: $fullName");
            $message = 'Driver updated successfully!';
            
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch();
        } else {
            $error = 'Failed to update driver. Please try again.';
        }
    }
}

$pageTitle = 'Edit Driver - Smart Drive Car Hire';
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
            <?php if (isSuperAdmin()): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
            <?php endif; ?>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay"></div>
    
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Edit Driver</h1>
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
                            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($driver['full_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" required value="<?php echo htmlspecialchars($driver['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>License Number *</label>
                            <input type="text" name="license_number" required value="<?php echo htmlspecialchars($driver['license_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>License Expiry Date *</label>
                            <input type="date" name="license_expiry" required value="<?php echo $driver['license_expiry']; ?>">
                        </div>
                        <div class="form-group">
                            <label>National ID / Passport *</label>
                            <input type="text" name="national_id" required value="<?php echo htmlspecialchars($driver['national_id']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Available" <?php echo $driver['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                <option value="Assigned" <?php echo $driver['status'] === 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="On Trip" <?php echo $driver['status'] === 'On Trip' ? 'selected' : ''; ?>>On Trip</option>
                                <option value="Off Duty" <?php echo $driver['status'] === 'Off Duty' ? 'selected' : ''; ?>>Off Duty</option>
                                <option value="Inactive" <?php echo $driver['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Profile Image</label>
                            <input type="file" name="profile_image" accept="image/*">
                            <?php if ($driver['profile_image']): ?>
                                 <img src="<?php echo UPLOAD_URL . 'drivers/' . $driver['profile_image']; ?>" 
                                      class="driver-profile-preview"
                                      onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" name="edit_driver" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Driver
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
