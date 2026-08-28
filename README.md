# SMART DRIVE CAR HIRE

A complete, fully functional Car Hire Management System built with PHP 8+, MySQL, HTML5, CSS3, and Vanilla JavaScript.

## Features

### Public Website
- Modern homepage with hero section and booking search
- Vehicle listing with advanced filtering
- Vehicle details with booking form
- How It Works section
- About Us page
- Contact form
- User registration and login

### Client Portal
- Dashboard with booking statistics
- Profile management
- Booking history and details
- Online payment submission
- Notifications

### Admin / Super Admin Portal
- Comprehensive dashboard with charts
- Vehicle management (CRUD)
- Driver management (CRUD)
- Booking management
- Payment verification
- Client management
- Reports with charts
- System settings

### Driver Portal
- Driver dashboard
- Trip management
- Trip start/end functionality
- Profile management

## Technology Stack

- **Backend**: PHP 8+, PDO, MySQL
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Libraries**: Font Awesome, Chart.js, SweetAlert2
- **Security**: Password hashing, prepared statements, CSRF protection, session authentication, role-based access control

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- XAMPP / WAMP / LAMP server
- Web browser with JavaScript enabled

## Installation

### 1. Download and Setup

1. Extract the project files to your web server root directory:
   - XAMPP: `C:\xampp\htdocs\smart-drive\`
   - Linux: `/opt/lampp/htdocs/smart-drive/`

### 2. Database Setup

1. Open phpMyAdmin or MySQL command line
2. Create a new database named `smart_drive_db`
3. Import the database schema from `database/smart_drive.sql`

Or run the SQL file directly:

```bash
mysql -u root -p smart_drive_db < database/smart_drive.sql
```

### 3. Configure Database Connection

Edit `config/database.php` if needed:

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=smart_drive_db;charset=utf8mb4',
    'root',
    'your_password',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);
```

### 4. First Super Admin Setup

1. Navigate to `http://localhost/smart-drive/setup.php`
2. Create the first Super Admin account
3. Once created, the setup page will be disabled

### 5. Access the System

- **Public Website**: `http://localhost/smart-drive/`
- **Client Login**: `http://localhost/smart-drive/login.php`
- **Admin Panel**: `http://localhost/smart-drive/admin/dashboard.php`

## Folder Structure

```
smart-drive/
├── index.php                 # Home page
├── about.php                 # About page
├── vehicles.php              # Vehicle listing
├── vehicle-details.php       # Vehicle details & booking
├── how-it-works.php          # How it works page
├── contact.php               # Contact page
├── login.php                 # Login page
├── register.php              # Registration page
├── logout.php                # Logout handler
├── setup.php                 # First-time setup
│
├── config/
│   └── database.php          # Database connection
│
├── includes/
│   ├── header.php            # HTML header
│   ├── footer.php            # HTML footer
│   ├── navbar.php            # Navigation bar
│   ├── auth.php              # Client authentication
│   ├── admin_auth.php        # Admin authentication
│   ├── driver_auth.php       # Driver authentication
│   └── functions.php         # Helper functions
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Main styles
│   │   ├── responsive.css    # Responsive styles
│   │   ├── dashboard.css     # Dashboard styles
│   │   └── animations.css    # Animations
│   │
│   ├── js/
│   │   ├── main.js           # Main JavaScript
│   │   ├── booking.js        # Booking functionality
│   │   ├── dashboard.js      # Dashboard charts
│   │   └── notifications.js  # Notifications
│   │
│   ├── images/
│   │   ├── vehicles/         # Vehicle images
│   │   ├── users/            # User profile images
│   │   └── drivers/          # Driver profile images
│   │
│   └── uploads/              # Uploaded files
│
├── client/                   # Client portal
│   ├── dashboard.php
│   ├── profile.php
│   ├── bookings.php
│   ├── booking-details.php
│   ├── payment.php
│   └── notifications.php
│
├── admin/                    # Admin portal
│   ├── dashboard.php
│   ├── vehicles/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   ├── delete.php
│   │   └── delete-image.php
│   ├── bookings/
│   │   ├── index.php
│   │   ├── details.php
│   │   └── assign.php
│   ├── drivers/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── payments/
│   │   ├── index.php
│   │   └── verify.php
│   ├── clients/
│   │   └── index.php
│   ├── reports/
│   │   └── index.php
│   └── settings/
│       └── index.php
│
├── driver/                   # Driver portal
│   ├── dashboard.php
│   ├── trips.php
│   ├── trip-details.php
│   └── profile.php
│
├── api/                      # API endpoints
│   ├── check-availability.php
│   ├── notifications.php
│   └── booking-actions.php
│
├── database/
│   └── smart_drive.sql       # Database schema
│
└── README.md
```

## User Roles

### Super Admin
- Full system access
- Manage admins
- System settings
- Activity logs
- All admin features

### Admin
- Vehicle management
- Booking management
- Driver management
- Payment verification
- Client management
- Reports
- Notifications

### Client
- Browse vehicles
- Make bookings
- Make payments
- View booking history
- Profile management

### Driver
- View assigned trips
- Start/end trips
- Trip notes
- Profile management

## Security Features

- Password hashing with `password_hash()`
- Password verification with `password_verify()`
- Prepared statements for all database queries
- CSRF protection
- Session-based authentication
- Role-based access control
- Input sanitization
- File upload validation
- SQL injection prevention
- Unauthorized access prevention

## Database Tables

- `users` - System users
- `vehicle_categories` - Vehicle categories
- `vehicles` - Vehicle information
- `vehicle_images` - Vehicle images
- `drivers` - Driver information
- `bookings` - Booking records
- `additional_services` - Additional services
- `booking_services` - Booking services
- `payments` - Payment records
- `notifications` - User notifications
- `activity_logs` - System activity logs
- `system_settings` - System configuration
- `contact_messages` - Contact form messages
- `testimonials` - Client testimonials

## Booking Workflow

1. Client selects vehicle and enters booking details
2. System checks vehicle availability
3. Booking created with "Pending" status
4. Client makes payment
5. Admin verifies payment
6. Booking status changes to "Confirmed"
7. Admin assigns driver
8. Driver starts trip (status: Active)
9. Driver ends trip (status: Completed)
10. System updates vehicle and driver status

## Payment Methods

- M-Pesa (ready for Daraja API integration)
- Cash
- Bank Transfer

## License

This project is developed for Smart Drive Car Hire.

## Support

For support and inquiries, contact the development team.
