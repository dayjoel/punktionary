<?php
// submit_band.php - Handle band submissions
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
        die(json_encode(['success' => false, 'error' => 'You must be logged in to submit bands']));
    }

    $user_id = get_current_user_id();

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Validate required fields
    if (empty($_POST['name'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Band name is required']));
    }

    // Collect and sanitize data
    $name = trim($_POST['name']);
    $genre = isset($_POST['genre']) ? trim($_POST['genre']) : null;
    $city = isset($_POST['city']) ? trim($_POST['city']) : null;
    $state = isset($_POST['state']) ? trim($_POST['state']) : null;
    $country = isset($_POST['country']) ? trim($_POST['country']) : null;
    $albums = isset($_POST['albums']) ? $_POST['albums'] : null; // Already JSON from JS
    $links = isset($_POST['links']) ? trim($_POST['links']) : null;
    $photo_references = isset($_POST['photo_references']) ? $_POST['photo_references'] : null; // Already JSON from JS
    $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
    $logo = null;

    // Validate JSON fields if provided
    if ($links && !empty($links)) {
        json_decode($links);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid JSON format for links']));
        }
    }

    // Handle logo upload or URL
    $image_source = isset($_POST['image_source']) ? $_POST['image_source'] : null;

    if ($image_source === 'upload' && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $upload_dir = __DIR__ . '/uploads/band-logos/';
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

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $logo = '/uploads/band-logos/' . $filename;
        } else {
            error_log('Failed to move uploaded file: ' . $file['tmp_name'] . ' to ' . $filepath);
        }
    } elseif ($image_source === 'url' && isset($_POST['logo_url']) && !empty(trim($_POST['logo_url']))) {
        // Handle URL download
        $url = trim($_POST['logo_url']);

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid image URL']));
        }

        $upload_dir = __DIR__ . '/uploads/band-logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Download the image
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PUNKtionary/1.0');
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($http_code === 200 && $image_data !== false) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($content_type, $allowed_types)) {
                http_response_code(400);
                die(json_encode(['success' => false, 'error' => 'URL does not point to a valid image']));
            }

            // Determine extension from content type
            $extension_map = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $extension = $extension_map[$content_type] ?? 'jpg';

            $filename = 'band_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (file_put_contents($filepath, $image_data) !== false) {
                $logo = '/uploads/band-logos/' . $filename;
            } else {
                error_log('Failed to save downloaded image to: ' . $filepath);
            }
        } else {
            error_log('Failed to download image from URL: ' . $url . ' (HTTP ' . $http_code . ')');
        }
    }

    // Insert into database with user attribution
    $sql = "INSERT INTO bands (submitted_by, name, genre, city, state, country, albums, links, photo_references, logo, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('isssssssssi', $user_id, $name, $genre, $city, $state, $country, $albums, $links, $photo_references, $logo, $active);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Band submitted successfully',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log('Band submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit band. Please try again.'
    ]);
}
?>