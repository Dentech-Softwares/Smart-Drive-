<footer class="footer" style="padding: 20px 0; background: #061426;">
    <div class="container">
        <div class="footer-links" style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
            <ul style="list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; flex-wrap: wrap; justify-content: center;">
                <li><a href="<?php echo BASE_URL; ?>" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>vehicles.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;">Vehicles</a></li>
                <li><a href="<?php echo BASE_URL; ?>how-it-works.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;">How It Works</a></li>
                <li><a href="<?php echo BASE_URL; ?>about.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;">About</a></li>
                <li><a href="<?php echo BASE_URL; ?>contact.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: color 0.3s;">Contact</a></li>
            </ul>
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
