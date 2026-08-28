<?php
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'] ?? null, 'Logout', 'User logged out');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

redirect('/');
