<?php

// Use the relative path your file is already using:
include("config.php");
include("auth.php");

header('Content-Type: application/json');

// CRITICAL CHECK: Check for the connection failure (set to null in config.php)
if ($conn === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed. Check config.php.']);
    exit();
}

// Validate User Session
if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

$userID = $_SESSION['UserID'];

try {
    
    $sql = "SELECT balance FROM users WHERE UserID = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
         // This catches an error like a wrong table name
         throw new Exception("SQL Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();
    $stmt->close();

    $balance = 0.00; 

    if ($user) {
        $balance = $user['balance'] ?? 0.00; 
        
        // Return ONLY the balance.
        echo json_encode([
            'success' => true,
            'user' => [
                'balance' => $balance
            ]
        ]);
    } else {
         echo json_encode(['success' => false, 'error' => 'User not found.']);
    }

} catch (Exception $e) {
    // This catches execution errors
    http_response_code(500);
    error_log("Passenger Data Fetch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal Server Error.']);
}

// Safely close the connection
if ($conn) {
    $conn->close();
}
?>