<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'About Us - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>About Smart Drive Car Hire</h2>
            <p>Your trusted partner for premium car rental services in Kenya</p>
            <div class="divider"></div>
        </div>
        
        <div class="about-grid">
            <div class="about-content">
                <h3 class="about-heading">Driving Excellence Since Day One</h3>
                <p class="about-text">Smart Drive Car Hire was established with a vision to provide premium, reliable, and affordable car rental services across Kenya. We understand that every journey matters, and we are committed to making your travel experience exceptional.</p>
                <p class="about-text">With a diverse fleet of well-maintained vehicles, professional drivers, and customer-centric approach, we have become the preferred choice for individuals, families, and corporate clients seeking quality transportation solutions.</p>
                
                <div class="mission-vision-grid">
                    <div class="mission-vision-card">
                        <h4 class="mv-title"><i class="fas fa-bullseye"></i> Our Mission</h4>
                        <p class="mv-text">To provide safe, reliable, and premium car rental services that exceed customer expectations.</p>
                    </div>
                    <div class="mission-vision-card">
                        <h4 class="mv-title"><i class="fas fa-eye"></i> Our Vision</h4>
                        <p class="mv-text">To be the leading car hire company in East Africa, known for quality and trust.</p>
                    </div>
                </div>
            </div>
            
            <div class="about-image">
                <img src="<?php echo BASE_URL; ?>assets/images/vehicles/default.jpg" alt="About Smart Drive" 
                     onerror="this.style.display='none'">
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-number">50+</div>
                <div class="stat-label">Vehicles</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number">1000+</div>
                <div class="stat-label">Happy Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-number">30+</div>
                <div class="stat-label">Professional Drivers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-number">Nationwide</div>
                <div class="stat-label">Coverage</div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
