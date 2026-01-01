<?php
// submit_edit.php - Submit edit suggestions for existing bands/venues
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    // Require authentication
    if (!is_authenticated()) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'You must be logged in to suggest edits']));
    }

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    $entity_type = isset($_POST['entity_type']) ? $_POST['entity_type'] : 'band';
    $entity_id = isset($_POST['band_id']) ? intval($_POST['band_id']) : (isset($_POST['venue_id']) ? intval($_POST['venue_id']) : 0);

    if ($entity_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid entity ID']));
    }

    // Handle logo upload or URL
    $logo_url = null;
    $logo_source = isset($_POST['logo_source']) ? $_POST['logo_source'] : 'url';

    if ($logo_source === 'upload' && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $upload_dir = __DIR__ . '/../../uploads/band-logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file = $_FILES['logo_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid file type. Please upload JPG, PNG, GIF, or WebP']));
        }

        if ($file['size'] > $max_size) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']));
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'band_' . uniqid() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        error_log('Attempting file upload - tmp: ' . $file['tmp_name'] . ' to: ' . $filepath);
        error_log('Temp file exists: ' . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
        error_log('Upload dir exists: ' . (is_dir($upload_dir) ? 'yes' : 'no'));
        error_log('Upload dir writable: ' . (is_writable($upload_dir) ? 'yes' : 'no'));

        $move_result = move_uploaded_file($file['tmp_name'], $filepath);
        error_log('Move result: ' . ($move_result ? 'success' : 'failed'));
        error_log('File exists after move: ' . (file_exists($filepath) ? 'yes' : 'no'));

        if ($move_result) {
            // Set proper permissions for web server to read the file
            chmod($filepath, 0644);
            $logo_url = '/uploads/band-logos/' . $filename;
            error_log('Logo URL set to: ' . $logo_url);
        } else {
            error_log('Failed to move uploaded file: ' . $file['tmp_name'] . ' to ' . $filepath);
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'Failed to save uploaded file']));
        }
    } elseif ($logo_source === 'url' && isset($_POST['logo_url']) && !empty(trim($_POST['logo_url']))) {
        $logo_url = trim($_POST['logo_url']);
    }

    // Collect all changed fields
    $field_changes = [];
    $allowed_fields = ['name', 'genre', 'city', 'state', 'country', 'albums', 'links', 'active'];

    foreach ($allowed_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $field_changes[$field] = trim($_POST[$field]);
        }
    }

    // Add logo if provided
    if ($logo_url !== null) {
        $field_changes['logo'] = $logo_url;
    }

    if (empty($field_changes)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'No changes submitted']));
    }

    // Insert into pending_edits table
    $sql = "INSERT INTO pending_edits (entity_type, entity_id, submitted_by, field_changes, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $changes_json = json_encode($field_changes);
    $stmt->bind_param('ssis', $entity_type, $entity_id, $user_id, $changes_json);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Edit suggestion submitted successfully',
            'id' => $stmt->insert_id
        ];

        // Debug: include upload details if logo was uploaded
        if ($logo_source === 'upload' && $logo_url !== null) {
            $full_path = __DIR__ . '/../../' . $logo_url;
            $response['debug'] = [
                'logo_url' => $logo_url,
                'full_path_checked' => $full_path,
                'file_exists' => file_exists($full_path),
                '__DIR__' => __DIR__,
                'realpath_of_upload_dir' => realpath(__DIR__ . '/../../uploads/band-logos/')
            ];
        }

        echo json_encode($response);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('Edit submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit edit suggestion. Please try again.'
    ]);
}
?>
