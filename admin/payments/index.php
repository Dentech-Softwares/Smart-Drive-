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

$sql = "SELECT p.*, u.full_name as client_name, b.id as booking_id, b.booking_reference, b.total_amount,
               CONCAT(v.brand, ' ', v.model) as vehicle_name, v.brand, v.model
        FROM payments p
        JOIN users u ON p.client_id = u.id
        JOIN bookings b ON p.booking_id = b.id
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE 1=1";
$params = [];
$countSql = "SELECT COUNT(*) FROM payments p JOIN users u ON p.client_id = u.id JOIN bookings b ON p.booking_id = b.id WHERE 1=1";
$countParams = [];

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR p.transaction_reference LIKE ? OR b.booking_reference LIKE ?)";
    $countSql .= " AND (u.full_name LIKE ? OR p.transaction_reference LIKE ? OR b.booking_reference LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam; $params[] = $searchParam; $params[] = $searchParam;
    $countParams = $params;
}
if ($statusFilter) {
    $sql .= " AND p.status = ?";
    $countSql .= " AND p.status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$sql .= " ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = 'Payments - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php" class="active"><i class="fas fa-credit-card"></i> Payments</a></li>
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
            <h1>Payments Management</h1>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search payments...">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="auto-submit">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Verified" <?php echo $statusFilter === 'Verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <div class="data-table-container">
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <h4>No Payments Found</h4>
                    <p>No payments match your search criteria.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th>
                            <th>Client</th>
                            <th>Booking</th>
                            <th>Vehicle</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['booking_reference']); ?></td>
                                <td><?php echo htmlspecialchars($payment['brand'] . ' ' . $payment['model']); ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><?php echo $payment['payment_method']; ?></td>
                                <td><?php echo getPaymentStatusLabel($payment['status']); ?></td>
                                <td><?php echo formatDate($payment['created_at']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/bookings/details.php?id=<?php echo $payment['booking_id']; ?>" class="btn btn-sm btn-outline" title="View Booking">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($payment['status'] === 'Pending'): ?>
                                        <form method="POST" action="<?php echo BASE_URL; ?>admin/payments/verify.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <input type="hidden" name="action" value="verify">
                                            <button type="submit" class="btn btn-sm btn-success" title="Verify" onclick="return confirm('Verify this payment?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?php echo BASE_URL; ?>admin/payments/verify.php" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this payment?')">
                                                <i class="fas fa-times"></i>
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
