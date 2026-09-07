<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'How It Works - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Booking your ride with Smart Drive is simple and hassle-free</p>
            <div class="divider"></div>
        </div>
        
        <div class="steps-grid">
            <div class="step-card animate-fadeInUp stagger-1">
                <div class="step-number">1</div>
                <h4>Choose Your Vehicle</h4>
                <p>Browse our diverse fleet of premium vehicles. Filter by category, price, transmission, and fuel type to find your perfect match.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-2">
                <div class="step-number">2</div>
                <h4>Select Dates & Locations</h4>
                <p>Choose your pickup and return dates, along with pickup and return locations. Our system checks availability in real-time.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-3">
                <div class="step-number">3</div>
                <h4>Make Payment</h4>
                <p>Select your preferred payment method - M-Pesa or Cash. Upload proof of payment if required.</p>
            </div>
            <div class="step-card animate-fadeInUp stagger-4">
                <div class="step-number">4</div>
                <h4>Enjoy Your Trip</h4>
                <p>Receive confirmation, pick up your vehicle, and enjoy a smooth, comfortable journey with our premium service.</p>
            </div>
        </div>
        
        <div class="faq-section">
            <h3 class="faq-heading">Frequently Asked Questions</h3>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                        What documents do I need to rent a car?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You need a valid driving license, national ID or passport, and a credit/debit card for the security deposit.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                        What is the minimum rental period?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        The minimum rental period is one day (24 hours). We also offer weekly and monthly rates for longer rentals.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                        Can I cancel my booking?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes, you can cancel your booking up to 24 hours before the pickup time for a full refund. Cancellations within 24 hours may incur a fee.
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                        Is insurance included in the rental price?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes, basic insurance is included in all rentals. Additional coverage options are available at an extra cost.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
