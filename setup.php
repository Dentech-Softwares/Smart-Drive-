<?php
require_once __DIR__ . '/config/database.php';

$hasSuperAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn() > 0;

if ($hasSuperAdmin) {
    redirect('/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'super_admin', 'active')");
            if ($stmt->execute([$fullName, $email, $phone, $hashedPassword])) {
                $success = 'Super Admin account created successfully! You can now login.';
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>


<div class="setup-container">
    <div class="setup-card">
        <h1>SMART <span style="color: var(--primary);">DRIVE</span></h1>
        <p>First Time Setup - Create Super Admin</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary">Go to Login</a>
        <?php else: ?>
            <form method="POST" action="">
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
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Super Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>
