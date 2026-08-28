<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>SMART <span>DRIVE</span></h3>
                <p><?php echo getSetting('site_tagline', 'Drive Your Journey With Confidence'); ?></p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php">Vehicles</a></li>
                    <li><a href="<?php echo BASE_URL; ?>how-it-works.php">How It Works</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Vehicle Categories</h4>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php?category=1">Sedan</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php?category=2">SUV</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php?category=3">Hatchback</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php?category=4">Van</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vehicles.php?category=5">Luxury</a></li>
                </ul>
            </div>
            
            <div class="footer-links footer-contact">
                <h4>Contact Info</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> <?php echo getSetting('site_address', 'Nairobi, Kenya'); ?></li>
                    <li><i class="fas fa-phone"></i> <?php echo getSetting('site_phone', '+254 700 000 000'); ?></li>
                    <li><i class="fas fa-envelope"></i> <?php echo getSetting('site_email', 'info@smartdrive.co.ke'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name', 'Smart Drive Car Hire'); ?>. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/dashboard.js"></script>
<?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>
