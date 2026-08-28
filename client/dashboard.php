<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}
$user = getCurrentUser();
$clientId = $_SESSION['user_id'];
$totalBookings = getCount('bookings', "client_id = $clientId");
$activeBookings = getCount('bookings', "client_id = $clientId AND status IN ('Confirmed', 'Assigned', 'Active')");
$completedBookings = getCount('bookings', "client_id = $clientId AND status = 'Completed'");
$pendingPayments = getCount('bookings', "client_id = $clientId AND status = 'Awaiting Payment'");
$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, v.brand, v.model, v.registration_number, 
                       d.full_name as driver_name FROM bookings b
                       JOIN vehicles v ON b.vehicle_id = v.id
                       LEFT JOIN drivers d ON b.driver_id = d.id
                       WHERE b.client_id = ? 
                       ORDER BY b.created_at DESC 
                       LIMIT 5");
$stmt->execute([$clientId]);
$recentBookings = $stmt->fetchAll();
$unreadNotifications = getUnreadNotifications($clientId);
$pageTitle = 'Dashboard - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/bookings.php"><i class="fas fa-calendar-check"></i> My Bookings <?php if ($pendingPayments > 0): ?><span style="background: #16A34A; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $pendingPayments; ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo BASE_URL; ?>client/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/notifications.php"><i class="fas fa-bell"></i> Notifications <?php if ($unreadNotifications > 0): ?><span style="background: #16A34A; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $unreadNotifications; ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Dashboard</h1>
            <div class="top-bar-actions">
                <a href="<?php echo BASE_URL; ?>client/notifications.php" class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
                <div class="user-menu">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-role">Client</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $totalBookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $activeBookings; ?></h3>
                    <p>Active Bookings</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $completedBookings; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $pendingPayments; ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>vehicles.php" class="quick-action">
                <div class="icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fas fa-car"></i>
                </div>
                <div class="info">
                    <h5>Book a Vehicle</h5>
                    <span>Browse available vehicles</span>
                </div>
            </a>
            <a href="<?php echo BASE_URL; ?>client/bookings.php" class="quick-action">
                <div class="icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="info">
                    <h5>My Bookings</h5>
                    <span>View booking history</span>
                </div>
            </a>
            <a href="<?php echo BASE_URL; ?>client/profile.php" class="quick-action">
                <div class="icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="info">
                    <h5>Edit Profile</h5>
                    <span>Update your information</span>
                </div>
            </a>
        </div>
        
        <div class="recent-activity">
            <div class="card-header">
                <h4><i class="fas fa-history"></i> Recent Bookings</h4>
                <a href="<?php echo BASE_URL; ?>client/bookings.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <?php if (empty($recentBookings)): ?>
                <div class="empty-state" style="padding: 40px;">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Yet</h4>
                    <p>Start by browsing our available vehicles and make your first booking.</p>
                    <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary">Browse Vehicles</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Vehicle</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                <td><?php echo formatDate($booking['pickup_datetime']); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td><?php echo getBookingStatusLabel($booking['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>client/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
