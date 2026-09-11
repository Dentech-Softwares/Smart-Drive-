<?php
require_once __DIR__ . '/config/database.php';
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } elseif (isDriver()) {
        redirect('/driver/dashboard.php');
    } else {
        redirect('/client/dashboard.php');
    }
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkRateLimit('register_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'))) {
        $error = 'Too many registration attempts. Please try again in 1 minute.';
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'client', 'active')");
            if ($stmt->execute([$fullName, $email, $phone, $hashedPassword])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
    }
}
$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="auth-section">
    <div class="auth-container">
        <div class="auth-info">
            <h2>Join Smart Drive</h2>
            <p>Create an account and start booking premium vehicles today.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Easy online booking</li>
                <li><i class="fas fa-check-circle"></i> Premium vehicles</li>
                <li><i class="fas fa-check-circle"></i> 24/7 support</li>
                <li><i class="fas fa-check-circle"></i> Secure payments</li>
            </ul>
        </div>
        
        <div class="auth-form">
            <h2>Create Account</h2>
            <p class="subtitle">Fill in your details to get started</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary" style="width: 100%;">Go to Login</a>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required placeholder="Enter your full name">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Create a password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm your password" minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-footer">
                Already have an account? <a href="<?php echo BASE_URL; ?>login.php">Login here</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/scripts.php'; ?>
