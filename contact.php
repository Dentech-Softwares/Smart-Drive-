<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Contact Us - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$fullName, $email, $phone, $subject, $message])) {
            $success = 'Thank you for contacting us! We will get back to you soon.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Contact Us</h2>
            <p>Get in touch with our team for any inquiries</p>
            <div class="divider"></div>
        </div>
        
        <div class="contact-grid">
            <div class="card contact-form-card">
                <div class="card-header">
                    <h3>Send Us a Message</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required placeholder="Enter your email">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label>Subject *</label>
                            <input type="text" name="subject" required placeholder="What is this regarding?">
                        </div>
                        <div class="form-group">
                            <label>Message *</label>
                            <textarea name="message" rows="5" required placeholder="Type your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="contact-info-col">
                <div class="card contact-info-card">
                    <div class="card-header">
                        <h3>Contact Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="contact-info-list">
                            <div class="contact-info-item">
                                <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div class="contact-detail">
                                    <h5>Our Office</h5>
                                    <p><?php echo getSetting('site_address', 'Nairobi, Kenya'); ?></p>
                                </div>
                            </div>
                            <div class="contact-info-item">
                                <div class="contact-icon"><i class="fas fa-phone"></i></div>
                                <div class="contact-detail">
                                    <h5>Phone</h5>
                                    <p><?php echo getSetting('site_phone', '+254 700 000 000'); ?></p>
                                </div>
                            </div>
                            <div class="contact-info-item">
                                <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                                <div class="contact-detail">
                                    <h5>Email</h5>
                                    <p><?php echo getSetting('site_email', 'info@smartdrive.co.ke'); ?></p>
                                </div>
                            </div>
                            <div class="contact-info-item">
                                <div class="contact-icon"><i class="fas fa-clock"></i></div>
                                <div class="contact-detail">
                                    <h5>Business Hours</h5>
                                    <p>Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 4:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card contact-cta-card">
                    <div class="card-body">
                        <h4>Need Immediate Assistance?</h4>
                        <p>Call us directly for urgent bookings and inquiries.</p>
                        <a href="tel:<?php echo getSetting('site_phone', '+254700000000'); ?>" class="btn btn-lg btn-primary">
                            <i class="fas fa-phone"></i> Call Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
