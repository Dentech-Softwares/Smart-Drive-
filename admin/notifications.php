<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}
$user = getCurrentUser();
$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$notifications->execute([$user['id']]);
$notifications = $notifications->fetchAll();
$pageTitle = 'Notifications - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
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
            <h1>Notifications</h1>
            <button onclick="markAllRead()" class="btn btn-sm btn-outline">Mark All as Read</button>
        </div>
        
        <div class="card">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h4>No Notifications</h4>
                    <p>You don't have any notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notification): 
                        $typeColors = [
                            'info' => '#0d6efd',
                            'success' => '#198754',
                            'warning' => '#ffc107',
                            'error' => '#dc3545'
                        ];
                        $typeIcons = [
                            'info' => 'fa-info-circle',
                            'success' => 'fa-check-circle',
                            'warning' => 'fa-exclamation-triangle',
                            'error' => 'fa-times-circle'
                        ];
                    ?>
                        <div class="notification-item notification-item-row <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                             onclick="<?php echo $notification['link'] ? "window.location.href='{$notification['link']}'" : ''; ?>">
                            <div class="icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: white; background: <?php echo $typeColors[$notification['type']] ?? '#6c757d'; ?>; flex-shrink: 0;">
                                <i class="fas <?php echo $typeIcons[$notification['type']] ?? 'fa-bell'; ?>"></i>
                            </div>
                            <div class="content">
                                <h5><?php echo htmlspecialchars($notification['title']); ?></h5>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            </div>
                            <div class="time">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>const BASE_URL = 'http://localhost/hayven_carhire/';
function markAllRead() {
    fetch(BASE_URL + 'api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_all_read' })
    }).then(() => location.reload());
}
</script>
</body>
</html>
