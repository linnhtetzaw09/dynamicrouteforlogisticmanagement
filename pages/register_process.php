<?php
/**
 * Registration Processing Script
 * Handles new user registration with admin approval requirement
 */

session_start();
require_once '../config/db_config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    header('Location: register.php');
    exit;
}

// Get form data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$assigned_site_id = isset($_POST['assigned_site_id']) ? intval($_POST['assigned_site_id']) : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$terms_accepted = isset($_POST['terms_accepted']) ? true : false;

// Validate input
$errors = [];

if (empty($first_name)) {
    $errors[] = 'First name is required.';
}

if (empty($last_name)) {
    $errors[] = 'Last name is required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

if ($assigned_site_id === 0) {
    $errors[] = 'Please select a site.';
}

if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
} elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain uppercase, lowercase, and numbers.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!$terms_accepted) {
    $errors[] = 'You must accept the terms and conditions.';
}

// If validation failed, redirect back
if (!empty($errors)) {
    $_SESSION['registration_errors'] = $errors;
    header('Location: register.php');
    exit;
}

// Check if email already exists
$query = "SELECT employee_id FROM employee WHERE email = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['registration_error'] = 'Database error. Please try again later.';
    header('Location: register.php');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['registration_error'] = 'Email address is already registered.';
    $stmt->close();
    header('Location: register.php');
    exit;
}
$stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert new user with is_approved = 0 (pending)
// Ensure the table structure matches: first_name, last_name, email, password_hash, phone_number, role, site_id, is_approved
$query = "INSERT INTO employee (first_name, last_name, email, password_hash, phone_number, role, assigned_site_id, is_approved) 
          VALUES (?, ?, ?, ?, ?, 'Staff', ?, 0)";

$stmt = $conn->prepare($query);
if (!$stmt) {
    $_SESSION['registration_error'] = 'Database error. Please try again later.';
    header('Location: register.php');
    exit;
}

// Bind the 6 variables for the 6 placeholders (?)
$stmt->bind_param('sssssi', $first_name, $last_name, $email, $password_hash, $phone_number, $assigned_site_id);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['registration_success'] = true;
    header('Location: register.php');
    exit;
} else {
    $_SESSION['registration_error'] = 'Failed to create account. Please try again.';
    $stmt->close();
    header('Location: register.php');
    exit;
}
?>
