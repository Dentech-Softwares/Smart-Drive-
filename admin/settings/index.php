<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
    }
    logActivity($_SESSION['user_id'], 'Settings Updated', 'Updated system settings');
    $message = 'Settings updated successfully!';
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Settings - Smart Drive Car Hire';
include __DIR__ . '/../../includes/header.php';
?>


<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php"><i class="fas fa-car"></i> Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/categories/index.php"><i class="fas fa-tags"></i> Categories</a></li>
            
            <li><a href="<?php echo BASE_URL; ?>admin/settings/index.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay"></div>

    <main class="main-content">
        <div class="top-bar">
            <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>System Settings</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>General Settings</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="settings[site_name]" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Tagline</label>
                        <input type="text" name="settings[site_tagline]" value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Email</label>
                        <input type="email" name="settings[site_email]" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Phone</label>
                        <input type="text" name="settings[site_phone]" value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Site Address</label>
                        <input type="text" name="settings[site_address]" value="<?php echo htmlspecialchars($settings['site_address'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <input type="text" name="settings[currency]" value="<?php echo htmlspecialchars($settings['currency'] ?? 'KES'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="settings[currency_symbol]" value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? 'KSh '); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-2">
            <div class="card-header">
                <h3>M-Pesa Configuration</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Consumer Key</label>
                        <input type="text" name="settings[mpesa_consumer_key]" value="<?php echo htmlspecialchars($settings['mpesa_consumer_key'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Consumer Secret</label>
                        <input type="password" name="settings[mpesa_consumer_secret]" value="<?php echo htmlspecialchars($settings['mpesa_consumer_secret'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Passkey</label>
                        <input type="text" name="settings[mpesa_passkey]" value="<?php echo htmlspecialchars($settings['mpesa_passkey'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Shortcode</label>
                        <input type="text" name="settings[mpesa_shortcode]" value="<?php echo htmlspecialchars($settings['mpesa_shortcode'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Environment</label>
                        <select name="settings[mpesa_environment]">
                            <option value="sandbox" <?php echo ($settings['mpesa_environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                            <option value="production" <?php echo ($settings['mpesa_environment'] ?? '') === 'production' ? 'selected' : ''; ?>>Production</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save M-Pesa Settings
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../../includes/scripts.php'; ?>
