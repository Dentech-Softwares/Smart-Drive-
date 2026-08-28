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
                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                             style="display: flex; gap: 15px; padding: 20px; border-bottom: 1px solid var(--border); cursor: pointer;"
                             onclick="<?php echo $notification['link'] ? "window.location.href='{$notification['link']}'" : ''; ?>">
                            <div class="icon" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: white; background: <?php echo $typeColors[$notification['type']] ?? '#6c757d'; ?>; flex-shrink: 0;">
                                <i class="fas <?php echo $typeIcons[$notification['type']] ?? 'fa-bell'; ?>"></i>
                            </div>
                            <div class="content" style="flex: 1;">
                                <h5><?php echo htmlspecialchars($notification['title']); ?></h5>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            </div>
                            <div class="time" style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function markAllRead() {
    fetch('/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'mark_all_read' })
    }).then(() => location.reload());
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>