<?php
// Google OAuth Callback Handler
// Processes the authorization code and creates/updates user session

require_once __DIR__ . '/../../oauth_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session_config.php';

try {
    // Verify state token (CSRF protection)
    if (!isset($_GET['state']) || !verify_state_token($_GET['state'], 'google')) {
        throw new Exception('Invalid state token - possible CSRF attack');
    }

    if (!isset($_GET['code'])) {
        throw new Exception('No authorization code received from Google');
    }

    // Exchange authorization code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_params = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Failed to get access token from Google');
    }

    $token_data = json_decode($token_response, true);
    if (!isset($token_data['access_token'])) {
        throw new Exception('No access token in Google response');
    }

    // Get user info from Google
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($user_info_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token_data['access_token']
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user_info_response = curl_exec($ch);
    curl_close($ch);

    $user_info = json_decode($user_info_response, true);

    if (!isset($user_info['id']) || !isset($user_info['email'])) {
        throw new Exception('Invalid user info from Google');
    }

    // Create or update user in database
    $user_id = upsert_user(
        'google',
        $user_info['id'],
        $user_info['email'],
        $user_info['name'] ?? $user_info['email'],
        $user_info['picture'] ?? null
    );

    if (!$user_id) {
        throw new Exception('Failed to create/update user in database');
    }

    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['oauth_provider'] = 'google';

    // Regenerate session ID for security (prevent session fixation)
    session_regenerate_id(true);

    // Redirect to home page
    header('Location: /');
    exit;

} catch (Exception $e) {
    // Log error for debugging
    error_log('Google OAuth error: ' . $e->getMessage());

    // Redirect to home with error message
    header('Location: /?error=auth_failed');
    exit;
}
?>
