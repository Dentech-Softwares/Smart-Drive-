<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_name('SMARTDRIVE_SESSION');
session_start();
define('APP_NAME', 'Smart Drive Car Hire');
if (!defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($host === 'localhost' || $host === '127.0.0.1') {
        define('BASE_URL', 'http://localhost/hayven_carhire/');
    } else {
        $base = rtrim($scriptDir, '/\\') . '/';
        define('BASE_URL', 'http://' . $host . $base);
    }
}
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=smart_drive_db;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
require_once __DIR__ . '/../includes/functions.php';
