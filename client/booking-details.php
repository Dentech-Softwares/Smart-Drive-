<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    redirect('/login.php');
}
$user = getCurrentUser();
$clientId = $_SESSION['user_id'];
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$bookingId) {
    redirect('/client/bookings.php');
}
$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, v.brand, v.model, v.registration_number, 
                       v.transmission, v.fuel_type, v.seating_capacity, v.price_per_day,
                       d.full_name as driver_name, d.phone as driver_phone,
                       vi.image_path as vehicle_image
                       FROM bookings b
                       JOIN vehicles v ON b.vehicle_id = v.id
                       LEFT JOIN drivers d ON b.driver_id = d.id
                       LEFT JOIN vehicle_images vi ON v.id = vi.vehicle_id AND vi.is_primary = 1
                       WHERE b.id = ? AND b.client_id = ?
                       GROUP BY b.id");
$stmt->execute([$bookingId, $clientId]);
$booking = $stmt->fetch();
if (!$booking) {
    redirect('/client/bookings.php');
}
$pageTitle = 'Booking Details - ' . $booking['booking_reference'] . ' - Smart Drive Car Hire';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>SMART <span>DRIVE</span></h3>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>client/notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main-content">
        <div class="top-bar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1>Booking Details</h1>
            <div>
                <?php echo getBookingStatusLabel($booking['status']); ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-car"></i> Vehicle Information</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <img src="<?php echo $booking['vehicle_image'] ?: BASE_URL . 'assets/images/vehicles/default.jpg'; ?>" 
                             alt="Vehicle" style="width: 120px; height: 90px; object-fit: cover; border-radius: var(--radius);"
                             onerror="this.src=BASE_URL . 'assets/images/vehicles/default.jpg'">
                        <div>
                            <h4 style="font-weight: 700; margin-bottom: 5px;"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h4>
                            <p style="color: var(--text-muted); margin: 0;"><?php echo htmlspecialchars($booking['vehicle_name']); ?></p>
                        </div>
                    </div>
                    <div style="display: grid; gap: 10px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Registration:</span>
                            <strong><?php echo htmlspecialchars($booking['registration_number']); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Transmission:</span>
                            <strong><?php echo $booking['transmission']; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Fuel Type:</span>
                            <strong><?php echo $booking['fuel_type']; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Seating:</span>
                            <strong><?php echo $booking['seating_capacity']; ?> Persons</strong>
                        </div>
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
                    <?php if ($booking['driver_id'] && $booking['driver_name']): ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
                            <h5 style="font-weight: 700; margin-bottom: 10px;">Assigned Driver</h5>
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['driver_name']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['driver_phone']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-muted);">Rental Days:</span>
                        <strong><?php echo $booking['rental_days']; ?> days</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-muted);">Daily Rate:</span>
                        <strong><?php echo formatCurrency($booking['price_per_day']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-muted);">Vehicle Price:</span>
                        <strong><?php echo formatCurrency($booking['vehicle_price']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <span style="color: var(--text-muted);">Additional Cost:</span>
                        <strong><?php echo formatCurrency($booking['additional_cost']); ?></strong>
                    </div>
                    <?php if ($booking['penalty'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); color: var(--danger);">
                            <span>Late Return Penalty:</span>
                            <strong><?php echo formatCurrency($booking['penalty']); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; padding: 15px 0; font-size: 1.1rem;">
                        <strong>Total Amount:</strong>
                        <strong style="color: var(--primary);"><?php echo formatCurrency($booking['total_amount']); ?></strong>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <?php
                    $paymentStatus = $pdo->prepare("SELECT status FROM payments WHERE booking_id = ? AND status = 'Verified' LIMIT 1");
                    $paymentStatus->execute([$booking['id']]);
                    $verifiedPayment = $paymentStatus->fetch();
                    
                    if ($verifiedPayment): ?>
                        <span class="btn btn-success" style="padding: 11px 20px; border-radius: var(--radius-md); font-weight: 800; cursor: default;">
                            <i class="fas fa-check-circle"></i> Payment Approved
                        </span>
                    <?php elseif ($booking['status'] === 'Approved'): ?>
                        <a href="<?php echo BASE_URL; ?>client/payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> Make Payment
                        </a>
                        <?php if (in_array($booking['status'], ['Pending', 'Approved'])): ?>
                            <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" class="btn btn-outline" style="margin-left: 10px; border-color: var(--danger); color: var(--danger);">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'Confirmed'): ?>
                        <button onclick="startTrip(<?php echo $booking['id']; ?>)" class="btn btn-primary" style="margin-left: 10px;">
                            <i class="fas fa-play-circle"></i> Start Trip
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($booking['status'] === 'Active'): ?>
                        <button onclick="endTrip(<?php echo $booking['id']; ?>)" class="btn btn-danger" style="margin-left: 10px;">
                            <i class="fas fa-stop-circle"></i> End Trip
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($booking['notes']): ?>
            <div class="card mt-2">
                <div class="card-header">
                    <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
function endTrip(bookingId) {
    Swal.fire({
        title: 'End Trip?',
        text: 'Are you sure you want to end this trip? Late returns will incur a penalty of KSh 200 per hour.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C1121F',
        confirmButtonText: 'Yes, end trip!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'api/booking-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'end_trip', booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Trip Ended!', 
                        text: data.penalty > 0 ? 'Trip ended with penalty: KSh ' + data.penalty : 'Trip ended successfully.',
                        confirmButtonColor: '#C1121F' 
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#C1121F' });
                }
            });
        }
    });
}
function startTrip(bookingId) {
    Swal.fire({
        title: 'Start Trip?',
        text: 'Are you sure you want to start this trip?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#C1121F',
        confirmButtonText: 'Yes, start trip!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'api/booking-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'start_trip', booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Trip Started!', text: 'Your trip has started. Enjoy!', confirmButtonColor: '#C1121F' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#C1121F' });
                }
            });
        }
    });
}
function cancelBooking(bookingId) {
    Swal.fire({
        title: 'Cancel Booking?',
        text: 'Are you sure you want to cancel this booking? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C1121F',
        confirmButtonText: 'Yes, cancel!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(BASE_URL + 'api/booking-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'cancel_booking', booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Cancelled!', text: 'Booking has been cancelled.', confirmButtonColor: '#C1121F' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#C1121F' });
                }
            });
        }
    });
}
</script>
</body>
</html>
