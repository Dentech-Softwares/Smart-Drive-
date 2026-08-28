<?php
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn() && $_SESSION['role'] === 'client') {
    redirect('/client/dashboard.php');
} elseif (isLoggedIn() && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    redirect('/admin/dashboard.php');
} elseif (isLoggedIn() && $_SESSION['role'] === 'driver') {
    redirect('/driver/dashboard.php');
}
