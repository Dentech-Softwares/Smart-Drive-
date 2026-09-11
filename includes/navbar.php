<nav class="navbar">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>" class="nav-brand">SMART <span>DRIVE</span></a>
        
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?php echo BASE_URL; ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>vehicles.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>">Vehicles</a></li>
            <li><a href="<?php echo BASE_URL; ?>how-it-works.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'how-it-works.php' ? 'active' : ''; ?>">How It Works</a></li>
            <li><a href="<?php echo BASE_URL; ?>about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li><a href="<?php echo BASE_URL; ?>contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            <li class="nav-mobile-auth">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo isAdmin() ? BASE_URL . 'admin/dashboard.php' : (isDriver() ? BASE_URL . 'driver/dashboard.php' : BASE_URL . 'client/dashboard.php'); ?>" class="btn btn-primary btn-sm">Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-secondary btn-sm">Login</a>
                    <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
            </li>
        </ul>
        
        <div class="nav-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo isAdmin() ? BASE_URL . 'admin/dashboard.php' : (isDriver() ? BASE_URL . 'driver/dashboard.php' : BASE_URL . 'client/dashboard.php'); ?>" class="btn btn-primary btn-sm">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-secondary btn-sm">Login</a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
