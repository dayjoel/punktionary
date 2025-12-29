<?php
// submit_resource.php - Handle resource submissions
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors to server log
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../db_config.php';
    require_once __DIR__ . '/auth/session_config.php';
    require_once __DIR__ . '/auth/helpers.php';

    // Require authentication
    if (!is_authenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'You must be logged in to submit resources']));
    }

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    if (empty($_POST['name'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Resource name is required']));
    }

    if (empty($_POST['link'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Link is required']));
    }

    if (empty($_POST['description'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Description is required']));
    }

    // Collect and sanitize data
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    $description = trim($_POST['description']);

    // Validate URL format
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid URL format']));
    }

    // Insert into database with user attribution
    $sql = "INSERT INTO resources (submitted_by, name, link, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('isss', $user_id, $name, $link, $description);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Resource submitted successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log('Resource submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit resource. Please try again.'
    ]);
}
?>