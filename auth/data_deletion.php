<?php
// Data Deletion Request Handler
// Handles user requests to delete their personal data

header('Content-Type: application/json');
require_once __DIR__ . '/../../db_config.php';
require_once __DIR__ . '/helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['email']) || empty($input['email'])) {
        throw new Exception('Email address is required');
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email address');
    }

    $reason = isset($input['reason']) ? trim($input['reason']) : '';

    // Get database connection
    $conn = get_db_connection();

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Don't reveal whether email exists for privacy
        echo json_encode([
            'success' => true,
            'message' => 'If an account exists with this email, a deletion request has been submitted.'
        ]);
        exit;
    }

    // Log the deletion request
    $stmt = $conn->prepare("
        INSERT INTO data_deletion_requests (user_id, email, reason, requested_at, status)
        VALUES (?, ?, ?, NOW(), 'pending')
    ");
    $stmt->bind_param("iss", $user['id'], $email, $reason);
    $stmt->execute();

    // Send email notification to admin
    $admin_email = 'privacy@punktionary.com';
    $subject = 'Data Deletion Request - PUNKtionary';
    $message = "A data deletion request has been submitted:\n\n";
    $message .= "User ID: " . $user['id'] . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Name: " . $user['name'] . "\n";
    $message .= "Reason: " . ($reason ?: 'Not provided') . "\n";
    $message .= "Request Time: " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "Please process this request within 30 days.\n";

    $headers = "From: noreply@punktionary.com\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";

    mail($admin_email, $subject, $message, $headers);

    // Send confirmation email to user
    $user_subject = 'Data Deletion Request Received - PUNKtionary';
    $user_message = "Hello " . $user['name'] . ",\n\n";
    $user_message .= "We have received your request to delete your personal data from PUNKtionary.\n\n";
    $user_message .= "Your request will be processed within 30 days. You will receive a confirmation email once the deletion is complete.\n\n";
    $user_message .= "If you did not submit this request, please contact us immediately at privacy@punktionary.com.\n\n";
    $user_message .= "Thank you,\nThe PUNKtionary Team\n";

    $user_headers = "From: noreply@punktionary.com\r\n";
    mail($email, $user_subject, $user_message, $user_headers);

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Your data deletion request has been submitted successfully.'
    ]);

} catch (Exception $e) {
    error_log('Data deletion error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
