<?php
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'] ?? null, 'Logout', 'User logged out');
    session_destroy();
}

redirect('/');
