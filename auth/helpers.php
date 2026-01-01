<?php
// Authentication Helper Functions
// Core authentication logic for OAuth and user management

require_once __DIR__ . '/../../db_config.php';

/**
 * Generate cryptographically secure state token for OAuth CSRF protection
 */
function generate_state_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Store state token in database with expiration
 */
function store_state_token($state, $provider) {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $stmt = $conn->prepare(
        "INSERT INTO oauth_states (state_token, provider, expires_at) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('sss', $state, $provider, $expires_at);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $success;
}

/**
 * Verify state token and consume it (one-time use)
 */
function verify_state_token($state, $provider) {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    // Check if token exists and hasn't expired
    $stmt = $conn->prepare(
        "SELECT id FROM oauth_states
         WHERE state_token = ? AND provider = ? AND expires_at > NOW()"
    );
    $stmt->bind_param('ss', $state, $provider);
    $stmt->execute();
    $result = $stmt->get_result();
    $valid = $result->num_rows > 0;

    // Delete token (one-time use for security)
    if ($valid) {
        $delete_stmt = $conn->prepare("DELETE FROM oauth_states WHERE state_token = ?");
        $delete_stmt->bind_param('s', $state);
        $delete_stmt->execute();
        $delete_stmt->close();
    }

    $stmt->close();
    $conn->close();
    return $valid;
}

/**
 * Clean up expired state tokens (call periodically)
 */
function cleanup_expired_states() {
    $conn = get_db_connection();
    if (!$conn) {
        return;
    }

    $conn->query("DELETE FROM oauth_states WHERE expires_at < NOW()");
    $conn->close();
}

/**
 * Create or update user from OAuth data
 * Returns user ID on success, false on failure
 */
function upsert_user($provider, $provider_id, $email, $display_name, $profile_picture) {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    // Check if user exists
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE oauth_provider = ? AND oauth_provider_id = ?"
    );
    $stmt->bind_param('ss', $provider, $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing user
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $update_stmt = $conn->prepare(
            "UPDATE users SET email = ?, display_name = ?, profile_picture_url = ?,
             last_login = NOW(), updated_at = NOW() WHERE id = ?"
        );
        $update_stmt->bind_param('sssi', $email, $display_name, $profile_picture, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Create new user
        $insert_stmt = $conn->prepare(
            "INSERT INTO users (oauth_provider, oauth_provider_id, email, display_name,
             profile_picture_url, last_login) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $insert_stmt->bind_param('sssss', $provider, $provider_id, $email, $display_name, $profile_picture);
        $insert_stmt->execute();
        $user_id = $insert_stmt->insert_id;
        $insert_stmt->close();
    }

    $stmt->close();
    $conn->close();

    return $user_id;
}

/**
 * Check if user is authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID from session
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get user data by user ID
 */
function get_user_data($user_id) {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT id, email, display_name, profile_picture_url, oauth_provider, created_at, account_type
         FROM users WHERE id = ?"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $user;
}

/**
 * Require authentication - redirect to home if not logged in
 */
function require_auth() {
    if (!is_authenticated()) {
        header('Location: /?login_required=1');
        exit;
    }
}

/**
 * Require authentication for API - return JSON error if not logged in
 */
function require_auth_api() {
    if (!is_authenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}
?>
