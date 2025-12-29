<?php
// Session configuration for secure authentication
// This file should be included at the start of any file that needs authentication

// Secure session configuration
ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access to session cookie (XSS protection)
ini_set('session.cookie_secure', 1);         // Require HTTPS for session cookie
ini_set('session.cookie_samesite', 'Lax');   // CSRF protection
ini_set('session.use_strict_mode', 1);       // Reject uninitialized session IDs
ini_set('session.gc_maxlifetime', 86400);    // Session lifetime: 24 hours

// Use custom session name
session_name('PUNKTIONARY_SESSION');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
