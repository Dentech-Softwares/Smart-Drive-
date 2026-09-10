<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}
$user = getCurrentUser();
$clientId = $_SESSION['user_id'];
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sql = "SELECT b.*, CONCAT(v.brand, ' ', v.model) as vehicle_name, v.brand, v.model, v.registration_number, 
               d.full_name as driver_name, (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id ORDER BY id ASC LIMIT 1) as vehicle_image
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN drivers d ON b.driver_id = d.id
        WHERE b.client_id = ?";
$params = [$clientId];
if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}
$sql .= " GROUP BY b.id ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
$pageTitle = 'My Bookings - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>My Bookings</h1>
            <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Booking</a>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Filter by Status</label>
                    <select name="status" class="auto-submit">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Awaiting Payment</option>
                        <option value="Payment Submitted" <?php echo $statusFilter === 'Payment Submitted' ? 'selected' : ''; ?>>Payment Submitted</option>
                        <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <a href="<?php echo BASE_URL; ?>client/bookings.php" class="btn btn-secondary btn-sm">Clear</a>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Found</h4>
                    <p>You haven't made any bookings yet. Start by browsing our available vehicles.</p>
                    <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary">Browse Vehicles</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Reference</th>
                            <th>Vehicle</th>
                            <th>Pickup</th>
                            <th>Return</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                <td><?php echo formatDateTime($booking['pickup_datetime']); ?></td>
                                <td><?php echo formatDateTime($booking['return_datetime']); ?></td>
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
