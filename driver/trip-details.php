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
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$bookingId) {
    redirect('/driver/trips.php');
}
$stmt = $pdo->prepare("SELECT b.*, u.full_name as client_name, u.phone as client_phone,
                       CONCAT(v.brand, ' ', v.model) as vehicle_name, v.brand, v.model, v.registration_number
                       FROM bookings b
                       JOIN users u ON b.client_id = u.id
                       JOIN vehicles v ON b.vehicle_id = v.id
                       WHERE b.id = ? AND b.driver_id = ?");
$stmt->execute([$bookingId, $driver['id']]);
$booking = $stmt->fetch();
if (!$booking) {
    redirect('/driver/trips.php');
}
$pageTitle = 'Trip Details - Smart Drive Car Hire';
$extraScripts = ['assets/js/booking-actions.js'];
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
            <h1>Trip Details</h1>
            <div class="booking-status-badge" data-booking-status>
                <?php echo getBookingStatusLabel($booking['status']); ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Client Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
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
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-bottom: 25px;">
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
                <h3><i class="fas fa-actions"></i> Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 15px; flex-wrap: wrap;" id="booking-actions">
                    <?php if ($booking['status'] === 'Confirmed'): ?>
                        <button onclick="BookingActions.updateTripStatus(<?php echo $booking['id']; ?>, 'Active')" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Trip
                        </button>
                    <?php elseif ($booking['status'] === 'Active'): ?>
                        <button onclick="BookingActions.updateTripStatus(<?php echo $booking['id']; ?>, 'Completed')" class="btn btn-success">
                            <i class="fas fa-stop"></i> End Trip
                        </button>
                    <?php else: ?>
                        <span class="text-muted">No actions available for current status: <strong><?php echo htmlspecialchars($booking['status']); ?></strong></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-top: 25px;">
                    <label>Trip Notes</label>
                    <textarea id="tripNotes" rows="3" placeholder="Add any notes about this trip..."><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                    <button onclick="saveNotes(<?php echo $booking['id']; ?>)" class="btn btn-outline btn-sm mt-1">
                        <i class="fas fa-save"></i> Save Notes
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    BookingActions.init({
        baseUrl: '<?php echo BASE_URL; ?>',
        csrfToken: '<?php echo csrf_token(); ?>',
        userRole: '<?php echo $_SESSION['role'] ?? ''; ?>',
        userId: <?php echo $_SESSION['user_id'] ?? 0; ?>
    });
});

function saveNotes(bookingId) {
    const notes = document.getElementById('tripNotes').value;
    fetch(BASE_URL + 'api/booking-actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save_notes', booking_id: bookingId, notes: notes, csrf_token: '<?php echo csrf_token(); ?>' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            BookingActions.showToast('success', 'Saved', 'Notes saved successfully!');
        } else {
            BookingActions.showToast('error', 'Error', data.message || 'Failed to save notes');
        }
    })
    .catch(error => {
        BookingActions.showToast('error', 'Error', 'Network error: ' + error.message);
    });
}
</script>
</body>
</html>
