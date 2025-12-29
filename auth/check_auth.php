<?php
// Check authentication status endpoint
// Returns JSON with user authentication state and data

require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

// Clean up expired state tokens periodically (10% chance)
if (rand(1, 10) === 1) {
    cleanup_expired_states();
}

if (is_authenticated()) {
    $user_data = get_user_data(get_current_user_id());

    if ($user_data) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $user_data['id'],
                'display_name' => $user_data['display_name'],
                'profile_picture' => $user_data['profile_picture_url'],
                'oauth_provider' => $user_data['oauth_provider']
            ]
        ]);
    } else {
        // User ID in session but no user found - invalid session
        session_destroy();
        echo json_encode(['authenticated' => false]);
    }
} else {
    echo json_encode(['authenticated' => false]);
}
?>
