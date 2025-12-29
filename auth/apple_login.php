<?php
// Apple OAuth Login Initiation
// Redirects user to Apple's authorization page

require_once __DIR__ . '/oauth_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session_config.php';

// Generate and store state token for CSRF protection
$state = generate_state_token();
store_state_token($state, 'apple');

// Build Apple OAuth URL
$params = [
    'client_id' => APPLE_CLIENT_ID,
    'redirect_uri' => APPLE_REDIRECT_URI,
    'response_type' => 'code',
    'state' => $state,
    'scope' => 'name email',
    'response_mode' => 'form_post'  // Apple uses POST for callback
];

$auth_url = 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
header('Location: ' . $auth_url);
exit;
?>
