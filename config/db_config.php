<?php
/**
 * Database Configuration File
 * This file contains the database connection settings for the Logistics Web Application
 */

// Database connection parameters
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', 'L!nhtetz@w492OO2');
define('DB_NAME', 'lhz_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8
$conn->set_charset("utf8");

// Define base URL for the application
define('BASE_URL', '/finalpj/');

// Define session timeout (in seconds)
define('SESSION_TIMEOUT', 3600); // 1 hour

?>
