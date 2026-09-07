<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 1000;
$offset = ($page - 1) * $perPage;

$sql = "SELECT b.*, u.full_name as client_name, u.email as client_email, 
               v.name as vehicle_name, v.brand, v.model, v.registration_number,
               d.full_name as driver_name
        FROM bookings b
        JOIN users u ON b.client_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN drivers d ON b.driver_id = d.id
        WHERE 1=1";
$params = [];
$countSql = "SELECT COUNT(*) FROM bookings b JOIN users u ON b.client_id = u.id JOIN vehicles v ON b.vehicle_id = v.id LEFT JOIN drivers d ON b.driver_id = d.id WHERE 1=1";
$countParams = [];

if ($search) {
    $sql .= " AND (b.booking_reference LIKE ? OR u.full_name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
    $countSql .= " AND (b.booking_reference LIKE ? OR u.full_name LIKE ? OR v.brand LIKE ? OR v.model LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam;
    $countParams = $params;
}
if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $countSql .= " AND b.status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = 'Bookings - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
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
            <h1>Bookings Management</h1>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search bookings...">
                </div>
                <div class="form-group">
                    <label>Status</label>
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Bookings Found</h4>
                    <p>No bookings match your search criteria.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Pickup</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                <td><?php echo htmlspecialchars($booking['driver_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo formatDate($booking['pickup_datetime']); ?></td>
                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td><?php echo getBookingStatusLabel($booking['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/bookings/details.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <?php if (in_array($booking['status'], ['Confirmed', 'Approved', 'Payment Submitted']) && !$booking['driver_id']): ?>
                                        <a href="<?php echo BASE_URL; ?>admin/bookings/assign.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-plus"></i> Assign
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">&laquo;</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <span class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                            </span>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
