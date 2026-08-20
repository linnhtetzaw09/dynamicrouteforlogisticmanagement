<?php
/**
 * Login Processing Script
 * Handles user authentication and session management
 */

session_start();
require_once '../config/db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Get form data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember_me = isset($_POST['remember_me']) ? true : false;

// Validate input
if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'Email and password are required.';
    header('Location: login.php');
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = 'Invalid email format.';
    header('Location: login.php');
    exit;
}

// Query database for user
$query = "SELECT employee_id, first_name, last_name, email, password_hash, assigned_site_id, is_approved, role
          FROM employee 
          WHERE email = ? AND is_approved = 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['login_error'] = 'Database error. Please try again later.';
    header('Location: login.php');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found or not approved
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: login.php');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();


// Verify password
$password_correct = password_verify($password, $user['password_hash']);


if (!$password_correct) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: login.php');
    exit;
}

// Set session variables
$_SESSION['employee_id'] = $user['employee_id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['assigned_site_id'] = $user['assigned_site_id'];
$_SESSION['login_time'] = time();

// Set remember me cookie if checked
if ($remember_me) {
    setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
}

// Clear any previous login errors
unset($_SESSION['login_error']);

// Redirect based on role
if ($user['role'] === 'Admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../staff/dashboard.php');
}
exit;
?>
