<?php
// Apple OAuth Callback Handler
// Processes the authorization code and creates/updates user session
// Note: Apple's OAuth is more complex as it requires JWT client secret generation

require_once __DIR__ . '/oauth_config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/session_config.php';

/**
 * Generate Apple client secret JWT
 * Apple requires a JWT signed with your private key as the client secret
 */
function generate_apple_client_secret() {
    $header = [
        'alg' => 'ES256',
        'kid' => APPLE_KEY_ID
    ];

    $payload = [
        'iss' => APPLE_TEAM_ID,
        'iat' => time(),
        'exp' => time() + 86400 * 180, // 6 months
        'aud' => 'https://appleid.apple.com',
        'sub' => APPLE_CLIENT_ID
    ];

    // Base64url encode header and payload
    $base64url_header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $base64url_payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

    $signature_input = $base64url_header . '.' . $base64url_payload;

    // Load private key
    $private_key = file_get_contents(APPLE_PRIVATE_KEY_PATH);
    if (!$private_key) {
        throw new Exception('Failed to load Apple private key');
    }

    // Sign with ES256 (ECDSA with SHA-256)
    $signature = '';
    $success = openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);

    if (!$success) {
        throw new Exception('Failed to sign Apple client secret');
    }

    $base64url_signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return $signature_input . '.' . $base64url_signature;
}

try {
    // Apple uses POST for callback (not GET)
    if (!isset($_POST['state']) || !verify_state_token($_POST['state'], 'apple')) {
        throw new Exception('Invalid state token - possible CSRF attack');
    }

    if (!isset($_POST['code'])) {
        throw new Exception('No authorization code received from Apple');
    }

    // Generate client secret JWT
    $client_secret = generate_apple_client_secret();

    // Exchange authorization code for tokens
    $token_url = 'https://appleid.apple.com/auth/token';
    $token_params = [
        'client_id' => APPLE_CLIENT_ID,
        'client_secret' => $client_secret,
        'code' => $_POST['code'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => APPLE_REDIRECT_URI
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Failed to get access token from Apple');
    }

    $token_data = json_decode($token_response, true);

    if (!isset($token_data['id_token'])) {
        throw new Exception('No ID token in Apple response');
    }

    // Decode id_token to get user info (Apple provides user info in the JWT)
    $id_token_parts = explode('.', $token_data['id_token']);
    if (count($id_token_parts) !== 3) {
        throw new Exception('Invalid ID token format from Apple');
    }

    // Decode the payload (middle part)
    $payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

    if (!isset($payload['sub']) || !isset($payload['email'])) {
        throw new Exception('Invalid user info in Apple ID token');
    }

    // Apple only provides user name on FIRST sign-in, in a separate 'user' parameter
    $display_name = $payload['email']; // Default to email
    if (isset($_POST['user'])) {
        $user_data = json_decode($_POST['user'], true);
        if (isset($user_data['name'])) {
            $first_name = $user_data['name']['firstName'] ?? '';
            $last_name = $user_data['name']['lastName'] ?? '';
            $display_name = trim($first_name . ' ' . $last_name);
            if (empty($display_name)) {
                $display_name = $payload['email'];
            }
        }
    }

    // Create or update user in database
    // Note: Apple doesn't provide profile pictures
    $user_id = upsert_user(
        'apple',
        $payload['sub'],  // Apple's unique user ID
        $payload['email'],
        $display_name,
        null  // No profile picture from Apple
    );

    if (!$user_id) {
        throw new Exception('Failed to create/update user in database');
    }

    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['oauth_provider'] = 'apple';

    // Regenerate session ID for security (prevent session fixation)
    session_regenerate_id(true);

    // Redirect to home page
    header('Location: /');
    exit;

} catch (Exception $e) {
    // Log error for debugging
    error_log('Apple OAuth error: ' . $e->getMessage());

    // Redirect to home with error message
    header('Location: /?error=auth_failed');
    exit;
}
?>
