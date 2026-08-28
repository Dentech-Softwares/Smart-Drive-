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
$assignedTrips = getCount('bookings', "driver_id = {$driver['id']} AND status = 'Assigned'");
$activeTrips = getCount('bookings', "driver_id = {$driver['id']} AND status = 'Active'");
$completedTrips = getCount('bookings', "driver_id = {$driver['id']} AND status = 'Completed'");
$stmt = $pdo->prepare("SELECT b.*, u.full_name as client_name, u.phone as client_phone,
                       v.name as vehicle_name, v.brand, v.model, v.registration_number
                       FROM bookings b
                       JOIN users u ON b.client_id = u.id
                       JOIN vehicles v ON b.vehicle_id = v.id
                       WHERE b.driver_id = ? AND b.status IN ('Assigned', 'Active')
                       ORDER BY b.pickup_datetime DESC
                       LIMIT 5");
$stmt->execute([$driver['id']]);
$upcomingTrips = $stmt->fetchAll();
$pageTitle = 'Dashboard - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
            <p>Driver Portal</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>driver/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>driver/trips.php"><i class="fas fa-road"></i> My Trips <?php if ($assignedTrips > 0): ?><span style="background: #16A34A; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $assignedTrips; ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo BASE_URL; ?>driver/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
</aside>
            <div class="sidebar-overlay"></div>
            
    <main class="main-content">
        <div class="top-bar">
            <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>Driver Dashboard</h1>
            <div class="top-bar-actions">
                <div class="user-menu">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($driver['full_name']); ?></div>
                        <div class="user-role">Driver</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $assignedTrips; ?></h3>
                    <p>Assigned Trips</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $activeTrips; ?></h3>
                    <p>Active Trips</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $completedTrips; ?></h3>
                    <p>Completed Trips</p>
                </div>
            </div>
        </div>
        
        <div class="recent-activity">
            <div class="card-header">
                <h4><i class="fas fa-calendar-alt"></i> Upcoming & Active Trips</h4>
                <a href="<?php echo BASE_URL; ?>driver/trips.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <?php if (empty($upcomingTrips)): ?>
                <div class="empty-state" style="padding: 40px;">
                    <i class="fas fa-road"></i>
                    <h4>No Active Trips</h4>
                    <p>You don't have any assigned or active trips at the moment.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Vehicle</th>
                            <th>Pickup</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingTrips as $trip): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($trip['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($trip['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($trip['brand'] . ' ' . $trip['model']); ?></td>
                                <td><?php echo formatDateTime($trip['pickup_datetime']); ?></td>
                                <td><?php echo getBookingStatusLabel($trip['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>driver/trip-details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline">View</a>
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
