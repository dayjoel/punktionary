<?php
// Logout endpoint
// Destroys session and redirects to home page

require_once __DIR__ . '/session_config.php';

// Destroy session
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session on server
session_destroy();

// Redirect to home page
header('Location: /');
exit;
?>
