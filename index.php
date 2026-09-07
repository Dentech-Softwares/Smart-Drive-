<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Home - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content animate-fadeInLeft">
            <h1>Drive Your Journey <span>With Confidence.</span></h1>
            <p>Experience premium car rental services with well-maintained vehicles, professional drivers, and unbeatable prices.</p>
            <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary btn-lg">Browse Vehicles</a>
        </div>
        
        <div class="hero-form animate-fadeInRight">
            <h3><i class="fas fa-search"></i> Find Your Perfect Ride</h3>
            <form action="<?php echo BASE_URL; ?>vehicles.php" method="GET">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Pickup Location</label>
                    <input type="text" name="location" placeholder="Enter pickup location">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Pickup Date</label>
                        <input type="date" name="pickup_date" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Return Date</label>
                        <input type="date" name="return_date" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-search"></i> Search Availability
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card animate-fadeInUp stagger-1">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-number counter" data-target="<?php echo getCount('vehicles', "status = 'Available'"); ?>">0</div>
                <div class="stat-label">Available Vehicles</div>
            </div>
            <div class="stat-card animate-fadeInUp stagger-2">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number counter" data-target="<?php echo getCount('users', "role = 'client' AND status = 'active'"); ?>">0</div>
                <div class="stat-label">Happy Clients</div>
            </div>
            <div class="stat-card animate-fadeInUp stagger-3">
                <div class="stat-icon"><i class="fas fa-road"></i></div>
                <div class="stat-number counter" data-target="<?php echo getCount('bookings', "status = 'Completed'"); ?>">0</div>
                <div class="stat-label">Completed Trips</div>
            </div>
            <div class="stat-card animate-fadeInUp stagger-4">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-number counter" data-target="<?php echo getCount('drivers', "status IN ('Available', 'On Trip')"); ?>">0</div>
                <div class="stat-label">Professional Drivers</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Vehicles -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Vehicles</h2>
            <p>Explore our premium fleet of well-maintained vehicles</p>
            <div class="divider"></div>
        </div>
        
        <?php
        $vehicles = $pdo->query("SELECT v.*, c.name as category_name FROM vehicles v 
                                  JOIN vehicle_categories c ON v.category_id = c.id 
                                  ORDER BY v.created_at DESC 
                                  LIMIT 6")->fetchAll();
        
        if (empty($vehicles)):
        ?>
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <h4>No Vehicles Available</h4>
                <p>Vehicles will appear here once they are added by the admin.</p>
            </div>
        <?php else: ?>
            <div class="vehicles-grid">
                <?php foreach ($vehicles as $vehicle): 
                    $image = getVehiclePrimaryImage($vehicle['id']);
                ?>
                    <div class="vehicle-card hover-lift">
                        <div class="vehicle-card-image">
                            <img src="<?php echo $image ?: BASE_URL . 'assets/images/vehicles/default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>assets/images/vehicles/default.jpg'">
                             <span class="vehicle-badge"><?php echo htmlspecialchars($vehicle['category_name']); ?></span>
                             <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $vehicle['status'])); ?>">
                                 <?php echo htmlspecialchars($vehicle['status']); ?>
                             </span>
                        </div>
                        <div class="vehicle-card-body">
                            <h3><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h3>
                            <div class="category"><?php echo htmlspecialchars($vehicle['name']); ?></div>
                            <div class="vehicle-features">
                                <span class="vehicle-feature"><i class="fas fa-cog"></i> <?php echo $vehicle['transmission']; ?></span>
                                <span class="vehicle-feature"><i class="fas fa-gas-pump"></i> <?php echo $vehicle['fuel_type']; ?></span>
                                <span class="vehicle-feature"><i class="fas fa-users"></i> <?php echo $vehicle['seating_capacity']; ?> Seats</span>
                            </div>
                            <div class="vehicle-card-footer">
                                <div class="vehicle-price">
                                    KSh <?php echo number_format($vehicle['price_per_day'], 2); ?>
                                    <span>/day</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-outline btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-3">
                <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-outline">View All Vehicles</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section section-alt">
    <div class="container">
        <div class="section-title">
            <h2>Why Choose Smart Drive</h2>
            <p>We provide the best car rental experience in Kenya</p>
            <div class="divider"></div>
        </div>
        
        <div class="features-grid">
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-car-crash"></i></div>
                <h4>Well Maintained Vehicles</h4>
                <p>All our vehicles are regularly serviced and inspected to ensure your safety and comfort.</p>
            </div>
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-tags"></i></div>
                <h4>Affordable Rates</h4>
                <p>Competitive pricing with no hidden charges. Get the best value for your money.</p>
            </div>
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-user-tie"></i></div>
                <h4>Professional Drivers</h4>
                <p>Our drivers are experienced, licensed, and committed to providing excellent service.</p>
            </div>
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-mobile-alt"></i></div>
                <h4>Easy Booking</h4>
                <p>Book your vehicle online in minutes with our simple and intuitive booking system.</p>
            </div>
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Secure Payments</h4>
                <p>Multiple payment options including M-Pesa, cash, and bank transfer for your convenience.</p>
            </div>
            <div class="feature-card hover-lift">
                <div class="icon"><i class="fas fa-headset"></i></div>
                <h4>Reliable Support</h4>
                <p>24/7 customer support to assist you with any queries or issues during your trip.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Booking your ride is simple and straightforward</p>
            <div class="divider"></div>
        </div>
        
        <div class="steps-grid">
            <div class="step-card animate-fadeInUp stagger-1">
                <div class="step-number">1</div>
                <h4>Choose Vehicle</h4>
                <p>Browse our fleet and select the perfect vehicle for your needs from our wide range of options.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-2">
                <div class="step-number">2</div>
                <h4>Book Your Dates</h4>
                <p>Select your pickup and return dates along with location details for your trip.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-3">
                <div class="step-number">3</div>
                <h4>Make Payment</h4>
                <p>Choose your preferred payment method and complete the payment securely.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-4">
                <div class="step-number">4</div>
                <h4>Enjoy Your Trip</h4>
                <p>Pick up your vehicle and enjoy a smooth, comfortable journey to your destination.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6")->fetchAll();
if (!empty($testimonials)):
?>
<section class="section section-alt">
    <div class="container">
        <div class="section-title">
            <h2>What Our Clients Say</h2>
            <p>Real feedback from our valued customers</p>
            <div class="divider"></div>
        </div>
        
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card animate-fadeInUp">
                    <div class="stars">
                        <?php for ($i = 1; $i <= $testimonial['rating']; $i++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <p><?php echo htmlspecialchars($testimonial['message']); ?></p>
                    <div class="testimonial-author">
                        <div class="stat-icon" style="width: 50px; height: 50px; font-size: 1.2rem;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5><?php echo htmlspecialchars($testimonial['full_name']); ?></h5>
                            <?php if ($testimonial['position']): ?>
                                <span><?php echo htmlspecialchars($testimonial['position']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Your Journey?</h2>
        <p>Book your premium vehicle today and experience the Smart Drive difference.</p>
        <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-lg" style="background: white; color: var(--primary); font-weight: 700;">
            <i class="fas fa-car"></i> Browse Vehicles
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
