<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$reportType = isset($_GET['type']) ? sanitize($_GET['type']) : 'bookings';
$period = isset($_GET['period']) ? sanitize($_GET['period']) : 'month';
$startDate = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';

if (!$startDate) {
    if ($period === 'today') {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
    } elseif ($period === 'week') {
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d');
    } else {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    }
}

$data = [];
$labels = [];

if ($reportType === 'bookings') {
    $stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue 
                           FROM bookings 
                           WHERE created_at BETWEEN ? AND ? 
                           GROUP BY DATE(created_at) 
                           ORDER BY date ASC");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $row) {
        $labels[] = date('M d', strtotime($row['date']));
        $data[] = (int)$row['count'];
    }
    
    $chartLabels = json_encode($labels);
    $chartValues = json_encode($data);
    $totalBookings = array_sum($data);
    $totalRevenue = array_sum(array_column($rows, 'revenue'));
} elseif ($reportType === 'revenue') {
    $stmt = $pdo->prepare("SELECT DATE(created_at) as date, SUM(amount) as revenue 
                           FROM payments 
                           WHERE status = 'Verified' AND created_at BETWEEN ? AND ? 
                           GROUP BY DATE(created_at) 
                           ORDER BY date ASC");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $row) {
        $labels[] = date('M d', strtotime($row['date']));
        $data[] = (float)$row['revenue'];
    }
    
    $chartLabels = json_encode($labels);
    $chartValues = json_encode($data);
    $totalRevenue = array_sum($data);
    $totalBookings = count($rows);
} elseif ($reportType === 'vehicles') {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $row) {
        $labels[] = $row['status'];
        $data[] = (int)$row['count'];
    }
    
    $chartLabels = json_encode($labels);
    $chartValues = json_encode($data);
    $totalBookings = getCount('vehicles');
    $totalRevenue = getSum('vehicles', 'price_per_day');
}

$pageTitle = 'Reports - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
            <h1>Reports</h1>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="type">
                        <option value="bookings" <?php echo $reportType === 'bookings' ? 'selected' : ''; ?>>Bookings</option>
                        <option value="revenue" <?php echo $reportType === 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="vehicles" <?php echo $reportType === 'vehicles' ? 'selected' : ''; ?>>Vehicles</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Period</label>
                    <select name="period" class="auto-submit">
                        <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generate</button>
            </form>
        </div>
        
        <div class="stats-cards">
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo $totalBookings ?? 0; ?></h3>
                    <p>Total <?php echo ucfirst($reportType); ?></p>
                </div>
            </div>
            <div class="stat-dash-card">
                <div class="stat-dash-icon" style="background: linear-gradient(135deg, #198754, #146c43);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-dash-info">
                    <h3><?php echo formatCurrency($totalRevenue ?? 0); ?></h3>
                    <p>Revenue</p>
                </div>
            </div>
        </div>
        
        <div class="chart-container">
            <h4><?php echo ucfirst($reportType); ?> Report (<?php echo formatDate($startDate); ?> - <?php echo formatDate($endDate); ?>)</h4>
            <canvas id="reportChart"
                data-labels="<?php echo htmlspecialchars(json_encode($labels), ENT_QUOTES); ?>"
                data-values="<?php echo htmlspecialchars(implode(',', $data), ENT_QUOTES); ?>">
            </canvas>
        </div>
        
        <div class="card mt-2">
            <div class="card-header">
                <h3>Export Report</h3>
            </div>
            <div class="card-body">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </main>
</div>

