<?php
/**
 * db_connection.php
 * Handles the database connection for the application using MySQLi (Object-Oriented Style).
 * This file is included at the beginning of any script that needs to interact with the database.
 * * IMPORTANT: Replace the placeholder values with your actual database credentials.
 */

// --- Database Configuration ---
define('DB_SERVER', '127.0.0.1');     // Database host (usually 'localhost' or '127.0.0.1')
define('DB_USERNAME', 'root');         // Database username
define('DB_PASSWORD', '');             // Database password (often empty for local XAMPP/WAMP setups)
define('DB_NAME', 'transportation_management'); // Database name

// --- Establish Connection ---
// Create connection object
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Stop script execution and display a user-friendly error message
    die("ERROR: Could not connect to the database. Please try again later. (Error Code: " . $conn->connect_errno . ")");
}

// Optional: Set character set to UTF-8 for proper data handling
$conn->set_charset("utf8mb4");

// Note: The $conn object is now available to any file that includes this one.
?>