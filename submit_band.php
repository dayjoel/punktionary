<?php
// submit_band.php - Handle band submissions
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors to server log
header('Content-Type: application/json');

try {
    // TODO: Move these to a config file outside web root
    $host = 'sql.punktionary.com';
    $user = 'dayjoel';
    $pass = 'TETherball99!';
    $db   = 'prod_punk';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
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

    // Validate JSON fields if provided
    if ($links && !empty($links)) {
        json_decode($links);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid JSON format for links']));
        }
    }

    // Insert into database
    $sql = "INSERT INTO bands (name, genre, city, state, country, albums, links, photo_references, active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ssssssssi', $name, $genre, $city, $state, $country, $albums, $links, $photo_references, $active);

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
        'error' => $e->getMessage() // Instead of the generic message
    ]);
}
?>