<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}
$user = getCurrentUser();
$clientId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$clientId]);
$notifications = $stmt->fetchAll();
$pageTitle = 'Notifications - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
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
                            'info' => 'bg-info',
                            'success' => 'bg-success',
                            'warning' => 'bg-warning',
                            'error' => 'bg-danger'
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
                            <div class="icon <?php echo $typeColors[$notification['type']] ?? 'bg-secondary'; ?>">
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
<script>const BASE_URL = '<?php echo BASE_URL; ?>';
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
