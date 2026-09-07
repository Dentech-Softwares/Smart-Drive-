<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'driver') {
    redirect('/login.php');
}
$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM drivers WHERE phone = ? OR email = ?");
$stmt->execute([$user['phone'], $user['email']]);
$driver = $stmt->fetch();
if (!$driver) {
    redirect('/login.php');
}
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (empty($fullName) || empty($phone)) {
        $error = 'Name and phone are required.';
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
        
        $stmt = $pdo->prepare("UPDATE drivers SET full_name=?, email=?, phone=?, profile_image=? WHERE id=?");
        if ($stmt->execute([$fullName, $email, $phone, $profileImage, $driver['id']])) {
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile.';
        }
    }
}
$pageTitle = 'My Profile - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
            <p>Driver Portal</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>driver/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>driver/trips.php"><i class="fas fa-road"></i> My Trips</a></li>
            <li><a href="<?php echo BASE_URL; ?>driver/profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
</aside>
            <div class="sidebar-overlay"></div>
            
    <main class="main-content">
        <div class="top-bar">
            <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>My Profile</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <img src="<?php echo $driver['profile_image'] ? UPLOAD_URL . 'drivers/' . $driver['profile_image'] : BASE_URL . '/assets/images/drivers/default.jpg'; ?>" 
                 alt="Profile" class="profile-avatar"
                 onerror="this.src='<?php echo BASE_URL; ?>assets/images/drivers/default.jpg'">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($driver['full_name']); ?></h2>
                <p><?php echo htmlspecialchars($driver['phone']); ?></p>
                <?php if ($driver['email']): ?>
                    <p><?php echo htmlspecialchars($driver['email']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Edit Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($driver['full_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required value="<?php echo htmlspecialchars($driver['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_image" accept="image/*" data-preview="profilePreview">
                        <img id="profilePreview" src="<?php echo $driver['profile_image'] ? UPLOAD_URL . 'drivers/' . $driver['profile_image'] : ''; ?>" 
                             style="width: 100px; height: 100px; border-radius: 50%; margin-top: 10px; object-fit: cover; display: <?php echo $driver['profile_image'] ? 'block' : 'none'; ?>;"
                             onerror="this.style.display='none'">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
