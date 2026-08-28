<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 1000;
$offset = ($page - 1) * $perPage;

$sql = "SELECT v.*, c.name as category_name FROM vehicles v 
        JOIN vehicle_categories c ON v.category_id = c.id 
        WHERE 1=1";
$params = [];
$countSql = "SELECT COUNT(*) FROM vehicles v JOIN vehicle_categories c ON v.category_id = c.id WHERE 1=1";
$countParams = [];

if ($search) {
    $sql .= " AND (v.name LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.registration_number LIKE ?)";
    $countSql .= " AND (v.name LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.registration_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $countParams = $params;
}
if ($categoryFilter > 0) {
    $sql .= " AND v.category_id = ?";
    $countSql .= " AND v.category_id = ?";
    $params[] = $categoryFilter;
    $countParams[] = $categoryFilter;
}
if ($statusFilter) {
    $sql .= " AND v.status = ?";
    $countSql .= " AND v.status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$sql .= " ORDER BY v.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$categories = $pdo->query("SELECT * FROM vehicle_categories WHERE status = 'active' ORDER BY name")->fetchAll();

$pageTitle = 'Vehicles - Smart Drive Car Hire';
include __DIR__ . '/../../includes/header.php';
?>


<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
            <p><?php echo ucfirst($user['role']); ?> Panel</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php" class="active"><i class="fas fa-car"></i> Vehicles</a></li>
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
            <h1>Vehicles Management</h1>
            <a href="<?php echo BASE_URL; ?>admin/vehicles/add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Vehicle</a>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search vehicles...">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="auto-submit">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="auto-submit">
                        <option value="">All Statuses</option>
                        <option value="Available" <?php echo $statusFilter === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Booked" <?php echo $statusFilter === 'Booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="On Trip" <?php echo $statusFilter === 'On Trip' ? 'selected' : ''; ?>>On Trip</option>
                        <option value="Maintenance" <?php echo $statusFilter === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <h4>No Vehicles Found</h4>
                    <p>No vehicles match your search criteria. Try adjusting your filters.</p>
                    <a href="<?php echo BASE_URL; ?>admin/vehicles/index.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Vehicle</th>
                            <th>Category</th>
                            <th>Registration</th>
                            <th>Transmission</th>
                            <th>Price/Day</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></strong>
                                    <small><?php echo htmlspecialchars($vehicle['name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                                <td><?php echo $vehicle['transmission']; ?></td>
                                <td><?php echo formatCurrency($vehicle['price_per_day']); ?></td>
                                <td><?php echo getVehicleStatusLabel($vehicle['status']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/vehicles/edit.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                     <form method="POST" action="<?php echo BASE_URL; ?>admin/vehicles/delete.php" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>">&laquo;</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <span class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                            </span>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

