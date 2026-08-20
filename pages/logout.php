<?php
/**
 * Logout Script
 * Handles user session termination
 */

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE['remember_email'])) {
    setcookie('remember_email', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php');
exit;
?>