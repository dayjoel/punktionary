<?php
// Facebook OAuth Login Initiation
// Redirects user to Facebook's authorization page

require_once __DIR__ . '/../../oauth_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session_config.php';

// Generate and store state token for CSRF protection
$state = generate_state_token();
store_state_token($state, 'facebook');

// Build Facebook OAuth URL
$params = [
    'client_id' => FACEBOOK_APP_ID,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'state' => $state,
    'scope' => 'email,public_profile'
];

$auth_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
header('Location: ' . $auth_url);
exit;
?>
