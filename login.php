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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            logActivity($user['id'], 'Login', 'User logged in successfully');
            
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                redirect('/admin/dashboard.php');
            } elseif ($user['role'] === 'driver') {
                redirect('/driver/dashboard.php');
            } else {
                redirect('/client/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="auth-section">
    <div class="auth-container">
        <div class="auth-info">
            <h2>Welcome Back!</h2>
            <p>Login to manage your bookings and explore our premium vehicles.</p>
            <ul class="features">
                <li><i class="fas fa-check-circle"></i> Easy online booking</li>
                <li><i class="fas fa-check-circle"></i> Premium vehicles</li>
                <li><i class="fas fa-check-circle"></i> 24/7 support</li>
                <li><i class="fas fa-check-circle"></i> Secure payments</li>
            </ul>
        </div>
        
        <div class="auth-form">
            <h2>Login</h2>
            <p class="subtitle">Enter your credentials to access your account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <div class="auth-footer">
                Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Register here</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
