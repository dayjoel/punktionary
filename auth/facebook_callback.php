<?php
// Facebook OAuth Callback Handler
// Processes the authorization code and creates/updates user session

require_once __DIR__ . '/oauth_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session_config.php';

try {
    // Verify state token (CSRF protection)
    if (!isset($_GET['state']) || !verify_state_token($_GET['state'], 'facebook')) {
        throw new Exception('Invalid state token - possible CSRF attack');
    }

    if (!isset($_GET['code'])) {
        throw new Exception('No authorization code received from Facebook');
    }

    // Exchange authorization code for access token
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $token_params = [
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $_GET['code']
    ];

    $ch = curl_init($token_url . '?' . http_build_query($token_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Failed to get access token from Facebook');
    }

    $token_data = json_decode($token_response, true);
    if (!isset($token_data['access_token'])) {
        throw new Exception('No access token in Facebook response');
    }

    // Get user info from Facebook
    $user_info_url = 'https://graph.facebook.com/v18.0/me';
    $user_params = [
        'fields' => 'id,name,email,picture.type(large)',
        'access_token' => $token_data['access_token']
    ];

    $ch = curl_init($user_info_url . '?' . http_build_query($user_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user_info_response = curl_exec($ch);
    curl_close($ch);

    $user_info = json_decode($user_info_response, true);

    if (!isset($user_info['id'])) {
        throw new Exception('Invalid user info from Facebook');
    }

    // Facebook may not provide email for all users
    $email = $user_info['email'] ?? 'facebook_' . $user_info['id'] . '@punktionary.local';
    $profile_picture = $user_info['picture']['data']['url'] ?? null;

    // Create or update user in database
    $user_id = upsert_user(
        'facebook',
        $user_info['id'],
        $email,
        $user_info['name'] ?? 'Facebook User',
        $profile_picture
    );

    if (!$user_id) {
        throw new Exception('Failed to create/update user in database');
    }

    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['oauth_provider'] = 'facebook';

    // Regenerate session ID for security (prevent session fixation)
    session_regenerate_id(true);

    // Redirect to home page
    header('Location: /');
    exit;

} catch (Exception $e) {
    // Log error for debugging
    error_log('Facebook OAuth error: ' . $e->getMessage());

    // Redirect to home with error message
    header('Location: /?error=auth_failed');
    exit;
}
?>
