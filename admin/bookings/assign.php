<?php
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php');
}

$user = getCurrentUser();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$bookingId) {
    redirect('/admin/bookings/index.php');
}

$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, v.brand, v.model FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('/admin/bookings/index.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverId = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : 0;
    
    if (!$driverId) {
        $error = 'Please select a driver.';
    } else {
        $driverStmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ? AND status = 'Available'");
        $driverStmt->execute([$driverId]);
        $driver = $driverStmt->fetch();
        
        if (!$driver) {
            $error = 'Selected driver is not available.';
        } elseif (!checkVehicleAvailability($booking['vehicle_id'], $booking['pickup_datetime'], $booking['return_datetime'], $bookingId)) {
            $error = 'Vehicle is not available for this booking period.';
        } else {
            $overlapCheck = $pdo->prepare("SELECT COUNT(*) as count FROM bookings 
                                           WHERE driver_id = ? AND status NOT IN ('Cancelled', 'Completed') 
                                           AND pickup_datetime < ? AND return_datetime > ? AND id != ?");
            $overlapCheck->execute([$driverId, $booking['return_datetime'], $booking['pickup_datetime'], $bookingId]);
            
            if ($overlapCheck->fetch()['count'] > 0) {
                $error = 'Driver has overlapping bookings during this period.';
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET driver_id = ?, status = 'Assigned' WHERE id = ?");
                if ($stmt->execute([$driverId, $bookingId])) {
                    $pdo->prepare("UPDATE drivers SET status = 'Assigned' WHERE id = ?")->execute([$driverId]);
                    
                    $clientStmt = $pdo->prepare("SELECT user_id FROM users WHERE id = ?");
                    $clientStmt->execute([$booking['client_id']]);
                    $client = $clientStmt->fetch();
                    
                    if ($client) {
                        createNotification($client['user_id'], 'Driver Assigned', 
                            'A driver has been assigned to your booking ' . $booking['booking_reference'],
                            'success', '/client/booking-details.php?id=' . $bookingId);
                    }
                    
                    createNotification($driverId, 'New Trip Assignment', 
                        'You have been assigned to booking ' . $booking['booking_reference'],
                        'info', '/driver/trip-details.php?id=' . $bookingId);
                    
                    logActivity($_SESSION['user_id'], 'Driver Assigned', "Assigned driver to booking {$booking['booking_reference']}");
                    $message = 'Driver assigned successfully!';
                } else {
                    $error = 'Failed to assign driver.';
                }
            }
        }
    }
}

$availableDrivers = $pdo->query("SELECT * FROM drivers WHERE status = 'Available' ORDER BY full_name")->fetchAll();

$pageTitle = 'Assign Driver - Smart Drive Car Hire';
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
            <li><a href="<?php echo BASE_URL; ?>admin/vehicles/index.php"><i class="fas fa-car"></i> Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/bookings/index.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/drivers/index.php"><i class="fas fa-user-tie"></i> Drivers</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/payments/index.php"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/clients/index.php"><i class="fas fa-users"></i> Clients</a></li>
            <li><a href="<?php echo BASE_URL; ?>admin/reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
            <h1>Assign Driver</h1>
            <a href="<?php echo BASE_URL; ?>admin/bookings/index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Booking Information</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                        <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
                        <p><strong>Pickup:</strong> <?php echo formatDateTime($booking['pickup_datetime']); ?></p>
                        <p><strong>Return:</strong> <?php echo formatDateTime($booking['return_datetime']); ?></p>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Select Driver</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availableDrivers)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-tie"></i>
                                <h4>No Available Drivers</h4>
                                <p>All drivers are currently busy. Please try again later.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Available Drivers</label>
                                    <select name="driver_id" required>
                                        <option value="">Select a driver</option>
                                        <?php foreach ($availableDrivers as $driver): ?>
                                            <option value="<?php echo $driver['id']; ?>">
                                                <?php echo htmlspecialchars($driver['full_name']); ?> - <?php echo htmlspecialchars($driver['phone']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-check"></i> Assign Driver
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
