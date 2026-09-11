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

$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM bookings WHERE driver_id = d.id) as trips
        FROM drivers d WHERE 1=1";
$params = [];
$countSql = "SELECT COUNT(*) FROM drivers d WHERE 1=1";
$countParams = [];

if ($search) {
    $sql .= " AND (d.full_name LIKE ? OR d.phone LIKE ? OR d.email LIKE ? OR d.license_number LIKE ?)";
    $countSql .= " AND (d.full_name LIKE ? OR d.phone LIKE ? OR d.email LIKE ? OR d.license_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam;
    $countParams = $params;
}
if ($statusFilter) {
    $sql .= " AND d.status = ?";
    $countSql .= " AND d.status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$sql .= " ORDER BY d.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$drivers = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = 'Drivers - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php" class="active"><i class="fas fa-user-tie"></i> Drivers</a></li>
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
            <h1>Drivers Management</h1>
            <a href="<?php echo BASE_URL; ?>admin/drivers/add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Driver</a>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search drivers...">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="auto-submit">
                        <option value="">All Statuses</option>
                        <option value="Available" <?php echo $statusFilter === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Assigned" <?php echo $statusFilter === 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="On Trip" <?php echo $statusFilter === 'On Trip' ? 'selected' : ''; ?>>On Trip</option>
                        <option value="Off Duty" <?php echo $statusFilter === 'Off Duty' ? 'selected' : ''; ?>>Off Duty</option>
                        <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($drivers)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-tie"></i>
                    <h4>No Drivers Found</h4>
                    <p>No drivers have been registered yet. Add your first driver to get started.</p>
                    <a href="<?php echo BASE_URL; ?>admin/drivers/add.php" class="btn btn-primary">Add Driver</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Driver</th>
                            <th>Phone</th>
                            <th>License</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Trips</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($drivers as $driver): ?>
                            <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($driver['full_name']); ?></strong>
                                    <?php if ($driver['email']): ?>
                                    
                                    <br><small><?php echo htmlspecialchars($driver['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                <td><?php echo formatDate($driver['license_expiry']); ?></td>
                                <td><?php echo getDriverStatusLabel($driver['status']); ?></td>
                                <td><?php echo $driver['trips']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/drivers/edit.php?id=<?php echo $driver['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($driver['trips'] == 0): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>admin/drivers/delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this driver?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="driver_id" value="<?php echo $driver['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
<?php include __DIR__ . '/../../includes/scripts.php'; ?>
