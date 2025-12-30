<?php
// Debug endpoint to check session status
require_once __DIR__ . '/../auth/session_config.php';
require_once __DIR__ . '/../auth/helpers.php';

header('Content-Type: application/json');

echo json_encode([
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'session_id' => session_id(),
    'is_authenticated' => is_authenticated(),
    'user_id' => get_current_user_id(),
    'session_data' => $_SESSION,
    'cookie_params' => session_get_cookie_params(),
    'session_name' => session_name()
]);
?>
