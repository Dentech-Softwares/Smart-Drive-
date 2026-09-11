<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 1000;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM users WHERE role IN ('admin', 'super_admin')";
$params = [];
$countSql = "SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin')";
$countParams = [];

if ($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $countSql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam;
    $countParams = $params;
}

$sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = 'Manage Admins - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/categories/index.php"><i class="fas fa-tags"></i> Categories</a></li>
            
            <?php if (isSuperAdmin()): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/activity-logs/index.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/admins/index.php" class="active"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
            <?php endif; ?>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Manage Admins</h1>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search Admins</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or phone...">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($admins)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-shield"></i>
                    <h4>No Admins Found</h4>
                    <p>No administrators match your search criteria.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($admin['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                                <td>
                                    <span class="badge <?php echo $admin['role'] === 'super_admin' ? 'badge-primary' : 'badge-info'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($admin['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($admin['created_at']); ?></td>
                                <td>
                                    <?php if ($admin['id'] != $user['id']): ?>
                                        <button onclick="toggleStatus(<?php echo $admin['id']; ?>, '<?php echo $admin['status']; ?>')" class="btn btn-sm btn-outline">
                                            <?php echo $admin['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                        <?php if ($admin['role'] === 'admin'): ?>
                                            <button onclick="promoteAdmin(<?php echo $admin['id']; ?>)" class="btn btn-sm btn-primary">
                                                <i class="fas fa-arrow-up"></i> Promote
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <span class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </span>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<div class="sidebar-overlay"></div>

<script>const BASE_URL = '<?php echo BASE_URL; ?>';
function toggleStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    Swal.fire({
        title: 'Change Status?',
        text: 'Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this user?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#C1121F',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'api/booking-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_user_status', user_id: userId, status: newStatus })
            }).then(() => location.reload());
        }
    });
}

function promoteAdmin(userId) {
    Swal.fire({
        title: 'Promote to Super Admin?',
        text: 'This will grant full system access to this user.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#C1121F',
        confirmButtonText: 'Yes, promote!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'api/booking-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'promote_admin', user_id: userId })
            }).then(() => location.reload());
        }
    });
}
</script>
<?php include __DIR__ . '/../../includes/scripts.php'; ?>

