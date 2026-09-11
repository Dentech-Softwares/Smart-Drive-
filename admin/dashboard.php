<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}
$user = getCurrentUser();
$totalVehicles = getCount('vehicles');
$availableVehicles = getCount('vehicles', "status = 'Available'");
$activeBookings = getCount('bookings', "status IN ('Confirmed', 'Active')");
$pendingBookings = getCount('bookings', "status = 'Pending'");
$totalClients = getCount('users', "role = 'client' AND status = 'active'");
$totalDrivers = getCount('drivers');
$pendingPayments = getCount('payments', "status = 'Pending'");
$revenue = getSum('payments', 'amount', "status = 'Verified'");
$activeTrips = getCount('bookings', "status = 'Active'");
$stmt = $pdo->query("SELECT b.*, u.full_name as client_name, CONCAT(v.brand, ' ', v.model) as vehicle_name, v.brand, v.model 
                     FROM bookings b 
                     JOIN users u ON b.client_id = u.id 
                     JOIN vehicles v ON b.vehicle_id = v.id 
                     ORDER BY b.created_at DESC 
                     LIMIT 5");
$recentBookings = $stmt->fetchAll();
$unreadNotifications = getUnreadNotifications($user['id']);

$trendStartDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : (new DateTime('first day of previous month'))->format('Y-m-d');
$trendEndDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');
$trendGroup = (strtotime($trendEndDate) - strtotime($trendStartDate)) > 604800 ? '%b %d' : '%b %d';
$trendFormat = (strtotime($trendEndDate) - strtotime($trendStartDate)) > 604800 ? "DATE_FORMAT(created_at, '%%b %Y')" : "DATE_FORMAT(created_at, '%%b %d')";

$trendLabels = [];
$trendValues = [];
$trendStmt = $pdo->prepare("SELECT $trendFormat as period, COUNT(*) as count 
                           FROM bookings 
                           WHERE created_at BETWEEN ? AND ? 
                           GROUP BY period 
                           ORDER BY period");
$trendStmt->execute([$trendStartDate . ' 00:00:00', $trendEndDate . ' 23:59:59']);
$trendRows = $trendStmt->fetchAll();
foreach ($trendRows as $row) {
    $trendLabels[] = $row['period'];
    $trendValues[] = (int)$row['count'];
}
$pageTitle = 'Dashboard - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
            </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php"><i class="fas fa-car"></i> Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php"><i class="fas fa-calendar-check"></i> Bookings <?php if ($pendingBookings > 0): ?><span style="background: #16A34A; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $pendingBookings; ?></span><?php endif; ?></a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments <?php if ($pendingPayments > 0): ?><span style="background: #16A34A; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $pendingPayments; ?></span><?php endif; ?></a></li>
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
            <h1>Dashboard</h1>
            <div class="top-bar-actions">
                <a href="<?php echo BASE_URL; ?>admin/notifications.php" class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                    <?php endif; ?>
                </a>
                <div class="user-menu">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-dash-card stat-total-vehicles">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $totalVehicles; ?></h3>
                    <p>Total Vehicles</p>
                </div>
            </div>
            <div class="stat-dash-card stat-available-vehicles">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $availableVehicles; ?></h3>
                    <p>Available Vehicles</p>
                </div>
            </div>
            <div class="stat-dash-card stat-active-bookings">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $activeBookings; ?></h3>
                    <p>Active Bookings</p>
                </div>
            </div>
            <div class="stat-dash-card stat-pending-bookings">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $pendingBookings; ?></h3>
                    <p>Pending Bookings</p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #20c997, #0ca678);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo formatCurrency($revenue); ?></h3>
                    <p>Revenue</p>
                </div>
            </div>
            <div class="stat-dash-card stat-pending-payments">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #fd7e14, #e8590c);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $pendingPayments; ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
            <div class="stat-dash-card stat-active-trips">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #6610f2, #520dc2);">
                    <i class="fas fa-road"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $activeTrips; ?></h3>
                    <p>Active Trips</p>
                </div>
            </div>
            <div class="stat-dash-card stat-total-clients">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #d63384, #a61e4d);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $totalClients; ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div class="chart-container">
                <h4>Booking Status Distribution</h4>
                 <canvas id="bookingStatsChart"
                    data-labels="Pending,Approved,Confirmed,Active,Completed,Cancelled"
                    data-values="<?php 
                        echo getCount('bookings', "status = 'Pending'") . ',';
                        echo getCount('bookings', "status = 'Approved'") . ',';
                        echo getCount('bookings', "status = 'Confirmed'") . ',';
                        echo getCount('bookings', "status = 'Active'") . ',';
                        echo getCount('bookings', "status = 'Completed'") . ',';
                        echo getCount('bookings', "status = 'Cancelled'");
                    ?>">
                </canvas>
            </div>
            <div class="chart-container">
                <h4>Vehicle Status Distribution</h4>
                <canvas id="vehicleStatusChart"
                    data-labels="Available,Booked,On Trip,Maintenance,Inactive"
                    data-values="<?php 
                        echo getCount('vehicles', "status = 'Available'") . ',';
                        echo getCount('vehicles', "status = 'Booked'") . ',';
                        echo getCount('vehicles', "status = 'On Trip'") . ',';
                        echo getCount('vehicles', "status = 'Maintenance'") . ',';
                        echo getCount('vehicles', "status = 'Inactive'");
                    ?>">
                </canvas>
            </div>
        </div>
        
        <div class="chart-container trend-section">
            <div class="trend-header">
                <h4><i class="fas fa-chart-line"></i> Bookings Trend (Last 6 Months)</h4>
                <div class="trend-filters">
                    <input type="date" id="trendStartDate" value="<?php echo $trendStartDate; ?>">
                    <input type="date" id="trendEndDate" value="<?php echo $trendEndDate; ?>">
                    <button onclick="filterTrend()" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
            <div class="trend-chart-wrapper">
                <canvas id="bookingTrendChart"
                    data-labels="<?php echo htmlspecialchars(implode(',', $trendLabels)); ?>"
                    data-values="<?php echo implode(',', $trendValues); ?>">
                </canvas>
            </div>
        </div>
        
        <div class="recent-activity">
            <div class="card-header">
                <h4><i class="fas fa-history"></i> Recent Bookings</h4>
                <a href="<?php echo BASE_URL; ?>admin/bookings/index.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <?php if (empty($recentBookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Yet</h4>
                    <p>Bookings will appear here once clients start making reservations.</p>
                </div>
            <?php else: ?>
                <table class="data-table" id="recentBookingsTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Vehicle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td><?php echo getBookingStatusLabel($booking['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/bookings/details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/scripts.php'; ?>
