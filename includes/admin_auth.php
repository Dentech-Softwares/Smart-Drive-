<?php
require_once __DIR__ . '/../config/database.php';
if (!isLoggedIn()) {
    redirect('/login.php');
}
if (!isAdmin()) {
    redirect('/login.php');
}
$user = getCurrentUser();
