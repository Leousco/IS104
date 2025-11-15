<?php
// Set session lifetime before starting the session
$session_lifetime = 7200; // 2 hours

session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',       // adjust for your project folder if needed
    'secure' => false,   // true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.gc_maxlifetime', $session_lifetime);

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: refresh session cookie on each page load
setcookie(session_name(), session_id(), [
    'expires' => time() + $session_lifetime,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
?>
