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
$stmt = $pdo->prepare("SELECT b.*, u.full_name as client_name, u.email as client_email, u.phone as client_phone,
                       v.name as vehicle_name, v.brand, v.model, v.registration_number, v.transmission, v.fuel_type,
                       d.full_name as driver_name, d.phone as driver_phone
                       FROM bookings b
                       JOIN users u ON b.client_id = u.id
                       JOIN vehicles v ON b.vehicle_id = v.id
                       LEFT JOIN drivers d ON b.driver_id = d.id
                       WHERE b.id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();
if (!$booking) {
    redirect('/admin/bookings/index.php');
}
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!empty($status)) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ?, notes = ? WHERE id = ?");
        if ($stmt->execute([$status, $notes, $bookingId])) {
            if ($status === 'Confirmed' && $booking['vehicle_id']) {
                $pdo->prepare("UPDATE vehicles SET status = 'Booked' WHERE id = ?")->execute([$booking['vehicle_id']]);
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'Assigned' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                createNotification($booking['client_id'], 'Booking Confirmed', 
                    'Your booking ' . $booking['booking_reference'] . ' has been confirmed.',
                    'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
            } elseif ($status === 'Active' && $booking['vehicle_id']) {
                $pdo->prepare("UPDATE vehicles SET status = 'On Trip' WHERE id = ?")->execute([$booking['vehicle_id']]);
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                createNotification($booking['client_id'], 'Trip Started', 
                    'Your booking ' . $booking['booking_reference'] . ' has started. Enjoy your trip!',
                    'info', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
            } elseif ($status === 'Completed' && $booking['vehicle_id']) {
                $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                createNotification($booking['client_id'], 'Trip Completed', 
                    'Your booking ' . $booking['booking_reference'] . ' has been completed. Thank you for choosing Smart Drive!',
                    'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
            } elseif ($status === 'Cancelled' && $booking['vehicle_id']) {
                $pdo->prepare("UPDATE vehicles SET status = 'Available' WHERE id = ?")->execute([$booking['vehicle_id']]);
                if ($booking['driver_id']) {
                    $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?")->execute([$booking['driver_id']]);
                }
                createNotification($booking['client_id'], 'Booking Cancelled', 
                    'Your booking ' . $booking['booking_reference'] . ' has been cancelled.',
                    'error', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
            } elseif ($status === 'Approved' && $booking['status'] === 'Pending') {
                createNotification($booking['client_id'], 'Booking Approved', 
                    'Your booking ' . $booking['booking_reference'] . ' has been approved. You can now make a payment.',
                    'success', BASE_URL . 'client/booking-details.php?id=' . $bookingId);
            }
            
            logActivity($_SESSION['user_id'], 'Booking Updated', "Updated booking {$booking['booking_reference']} to $status");
            $message = 'Booking updated successfully!';
            
            $stmt = $pdo->prepare("SELECT b.*, u.full_name as client_name, u.email as client_email, u.phone as client_phone,
                                   v.name as vehicle_name, v.brand, v.model, v.registration_number, v.transmission, v.fuel_type,
                                   d.full_name as driver_name, d.phone as driver_phone
                                   FROM bookings b
                                   JOIN users u ON b.client_id = u.id
                                   JOIN vehicles v ON b.vehicle_id = v.id
                                   LEFT JOIN drivers d ON b.driver_id = d.id
                                   WHERE b.id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
        } else {
            $error = 'Failed to update booking.';
        }
    }
}
$payments = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ?");
$payments->execute([$bookingId]);
$bookingPayments = $payments->fetchAll();
$pageTitle = 'Booking Details - Smart Drive Car Hire';
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
            <h1>Booking Details</h1>
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
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Client Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['client_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['client_phone']); ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-car"></i> Vehicle Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
                    <p><strong>Registration:</strong> <?php echo htmlspecialchars($booking['registration_number']); ?></p>
                    <p><strong>Transmission:</strong> <?php echo $booking['transmission']; ?></p>
                    <p><strong>Fuel:</strong> <?php echo $booking['fuel_type']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-route"></i> Trip Details</h3>
            </div>
            <div class="card-body">
                <div class="booking-timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="fas fa-sign-in-alt"></i></div>
                        <h5>Pickup</h5>
                        <p><?php echo formatDateTime($booking['pickup_datetime']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <h5>Return</h5>
                        <p><?php echo formatDateTime($booking['return_datetime']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['return_location']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
            </div>
            <div class="card-body">
                <div class="dashboard-grid">
                    <div>
                        <span>Rental Days:</span>
                        <strong><?php echo $booking['rental_days']; ?> days</strong>
                    </div>
                    <div>
                        <span>Vehicle Price:</span>
                        <strong><?php echo formatCurrency($booking['vehicle_price']); ?></strong>
                    </div>
                    <div>
                        <span>Additional Cost:</span>
                        <strong><?php echo formatCurrency($booking['additional_cost']); ?></strong>
                    </div>
                    <?php if ($booking['penalty'] > 0): ?>
                        <div>
                            <span style="color: var(--danger);">Late Return Penalty:</span>
                            <strong style="color: var(--danger);"><?php echo formatCurrency($booking['penalty']); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div>
                        <strong>Total Amount:</strong>
                        <strong><?php echo formatCurrency($booking['total_amount']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($booking['status'] === 'Pending'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle"></i> Approve Booking</h3>
                </div>
                <div class="card-body">
                    <p>This booking is pending your approval.</p>
                    <form method="POST" action="" style="display: flex; gap: 10px;">
                        <input type="hidden" name="status" value="Approved">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Approve Booking
                        </button>
                        <button type="submit" name="reject" class="btn btn-danger" onclick="document.querySelector('input[name=\"status\"]').value='Cancelled'">
                            <i class="fas fa-times"></i> Reject Booking
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (in_array($booking['status'], ['Confirmed', 'Approved', 'Payment Submitted']) && !$booking['driver_id']): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus"></i> Assign Driver</h3>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>admin/bookings/assign.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-user-check"></i> Assign Driver to This Booking
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog"></i> Update Booking</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Pending" <?php echo $booking['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $booking['status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Payment Submitted" <?php echo $booking['status'] === 'Payment Submitted' ? 'selected' : ''; ?>>Payment Submitted</option>
                            <option value="Confirmed" <?php echo $booking['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Active" <?php echo $booking['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Completed" <?php echo $booking['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $booking['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Booking
                    </button>
                </form>
            </div>
        </div>
        
        <?php if ($bookingPayments): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-credit-card"></i> Payments</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookingPayments as $payment): ?>
                                <tr>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                    <td><?php echo $payment['payment_method']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['transaction_reference'] ?? '-'); ?></td>
                                    <td><?php echo getPaymentStatusLabel($payment['status']); ?></td>
                                    <td><?php echo formatDate($payment['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
