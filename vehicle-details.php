<?php
require_once __DIR__ . '/config/database.php';

$vehicleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$vehicleId) {
    redirect('/vehicles.php');
}

$stmt = $pdo->prepare("SELECT v.*, c.name as category_name FROM vehicles v 
                        JOIN vehicle_categories c ON v.category_id = c.id 
                        WHERE v.id = ? AND v.status = 'Available'");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('/vehicles.php');
}

$pageTitle = $vehicle['brand'] . ' ' . $vehicle['model'] . ' - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';

$images = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC");
$images->execute([$vehicleId]);
$vehicleImages = $images->fetchAll();

$today = date('Y-m-d\TH:i');
?>

<section class="section">
    <div class="container">
        <div class="vehicle-details-grid">
            <div>
                <div class="vehicle-gallery-main">
                    <img id="mainImage" src="<?php echo $vehicleImages[0]['image_path'] ?? BASE_URL . 'assets/images/vehicles/default.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($vehicle['name']); ?>" 
                         onerror="this.src=BASE_URL . 'assets/images/vehicles/default.jpg'">
                    <span class="vehicle-badge"><?php echo htmlspecialchars($vehicle['category_name']); ?></span>
                </div>
                
                <?php if (count($vehicleImages) > 1): ?>
                    <div class="image-gallery">
                        <?php foreach ($vehicleImages as $img): ?>
                            <img src="<?php echo $img['image_path']; ?>" 
                                 alt="Vehicle image"
                                 class="img-thumb"
                                 onclick="document.getElementById('mainImage').src=this.src; this.parentElement.querySelectorAll('img').forEach(i => i.style.borderColor='transparent'); this.style.borderColor='var(--primary)';"
                                 onerror="this.style.display='none'">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card vehicle-info-card">
                    <h2 class="vehicle-title"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h2>
                    <p class="vehicle-subtitle"><?php echo htmlspecialchars($vehicle['name']); ?></p>
                    
                    <div class="divider"></div>
                    
                    <div class="specs-grid">
                        <div class="spec-item"><span class="spec-label">Category</span><span class="spec-value"><?php echo htmlspecialchars($vehicle['category_name']); ?></span></div>
                        <div class="spec-item"><span class="spec-label">Transmission</span><span class="spec-value"><?php echo $vehicle['transmission']; ?></span></div>
                        <div class="spec-item"><span class="spec-label">Fuel Type</span><span class="spec-value"><?php echo $vehicle['fuel_type']; ?></span></div>
                        <div class="spec-item"><span class="spec-label">Seating Capacity</span><span class="spec-value"><?php echo $vehicle['seating_capacity']; ?> Persons</span></div>
                        <div class="spec-item"><span class="spec-label">Year</span><span class="spec-value"><?php echo $vehicle['year']; ?></span></div>
                        <div class="spec-item"><span class="spec-label">Registration</span><span class="spec-value"><?php echo htmlspecialchars($vehicle['registration_number']); ?></span></div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="vehicle-description">
                        <h4 class="description-title">Description</h4>
                        <p class="description-text"><?php echo nl2br(htmlspecialchars($vehicle['description'] ?: 'No description available for this vehicle.')); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="booking-sidebar">
                <div class="card booking-card">
                    <h3 class="booking-card-title">
                        <i class="fas fa-calendar-check"></i> Book This Vehicle
                    </h3>
                    
                    <div class="price-highlight">
                        <div class="price-amount">KSh <?php echo number_format($vehicle['price_per_day'], 2); ?></div>
                        <div class="price-period">per day</div>
                    </div>
                    
                    <?php if (!isLoggedIn()): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            Please <a href="<?php echo BASE_URL; ?>login.php">login</a> or <a href="<?php echo BASE_URL; ?>register.php">register</a> to make a booking.
                        </div>
                    <?php else: ?>
                        <form id="bookingForm" method="POST" action="<?php echo BASE_URL; ?>client/create-booking.php">
                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                            <input type="hidden" name="price_per_day" id="price_per_day" value="<?php echo $vehicle['price_per_day']; ?>">
                            <input type="hidden" name="additional_cost" id="additional_cost" value="0">
                            
                            <div class="form-group">
                                <label>Pickup Location</label>
                                <input type="text" name="pickup_location" required placeholder="Enter pickup location">
                            </div>
                            <div class="form-group">
                                <label>Return Location</label>
                                <input type="text" name="return_location" required placeholder="Enter return location">
                            </div>
                            <div class="form-group">
                                <label>Pickup Date & Time</label>
                                <input type="datetime-local" name="pickup_datetime" id="pickup_datetime" required min="<?php echo $today; ?>">
                            </div>
                            <div class="form-group">
                                <label>Return Date & Time</label>
                                <input type="datetime-local" name="return_datetime" id="return_datetime" required min="<?php echo $today; ?>">
                            </div>
                            
                            <div class="booking-summary">
                                <div class="summary-row">
                                    <span>Rental Days:</span>
                                    <input type="number" name="rental_days" id="rental_days" readonly class="summary-input">
                                </div>
                                <div class="summary-row">
                                    <span>Vehicle Price:</span>
                                    <input type="text" name="vehicle_price" id="vehicle_price" readonly class="summary-input">
                                </div>
                                <div class="summary-row summary-total">
                                    <strong>Total Amount:</strong>
                                    <input type="text" name="total_amount" id="total_amount" readonly class="summary-input total-value">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-check"></i> Confirm Booking
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function calculateRental() {
    const pickup = document.getElementById('pickup_datetime').value;
    const return_ = document.getElementById('return_datetime').value;
    const pricePerDay = parseFloat(document.getElementById('price_per_day').value) || 0;
    
    if (pickup && return_ && pricePerDay > 0) {
        const start = new Date(pickup);
        const end = new Date(return_);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 0) {
            document.getElementById('rental_days').value = diffDays;
            document.getElementById('vehicle_price').value = (diffDays * pricePerDay).toFixed(2);
            updateTotal();
        }
    }
}

function updateTotal() {
    const vehiclePrice = parseFloat(document.getElementById('vehicle_price').value) || 0;
    const additionalCost = parseFloat(document.getElementById('additional_cost').value) || 0;
    const total = vehiclePrice + additionalCost;
    document.getElementById('total_amount').value = total.toFixed(2);
}

document.getElementById('pickup_datetime')?.addEventListener('change', calculateRental);
document.getElementById('return_datetime')?.addEventListener('change', calculateRental);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
