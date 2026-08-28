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

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = "SELECT b.*, u.full_name as client_name, u.phone as client_phone,
               v.name as vehicle_name, v.brand, v.model, v.registration_number
        FROM bookings b
        JOIN users u ON b.client_id = u.id
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.driver_id = ?";
$params = [$driver['id']];
$countSql = "SELECT COUNT(*) FROM bookings WHERE driver_id = ?";
$countParams = [$driver['id']];

if ($search) {
    $sql .= " AND (b.booking_reference LIKE ? OR u.full_name LIKE ? OR v.brand LIKE ?)";
    $countSql .= " AND (booking_reference LIKE ? OR (SELECT full_name FROM users WHERE id = client_id) LIKE ? OR (SELECT brand FROM vehicles WHERE id = vehicle_id) LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam;
    $countParams = [$searchParam, $searchParam, $searchParam];
}
if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $countSql .= " AND status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = 'My Trips - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>driver/trips.php" class="active"><i class="fas fa-road"></i> My Trips</a></li>
            <li><a href="<?php echo BASE_URL; ?>driver/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
</aside>
            <div class="sidebar-overlay"></div>
            
    <main class="main-content">
        <div class="top-bar">
            <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
            <h1>My Trips</h1>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search trips...">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="auto-submit">
                        <option value="">All Statuses</option>
                        <option value="Assigned" <?php echo $statusFilter === 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($trips)): ?>
                <div class="empty-state">
                    <i class="fas fa-road"></i>
                    <h4>No Trips Found</h4>
                    <p>You don't have any trips assigned yet.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Vehicle</th>
                            <th>Pickup</th>
                            <th>Return</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trips as $trip): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($trip['booking_reference']); ?></strong></td>
                                <td><?php echo htmlspecialchars($trip['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($trip['brand'] . ' ' . $trip['model']); ?></td>
                                <td><?php echo formatDateTime($trip['pickup_datetime']); ?></td>
                                <td><?php echo formatDateTime($trip['return_datetime']); ?></td>
                                <td><?php echo getBookingStatusLabel($trip['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>driver/trip-details.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-outline">View</a>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
