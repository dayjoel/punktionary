<?php
// get_resources.php - Fetch all resources
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get all resources, ordered by most recent first
    $sql = "SELECT id, name, link, description, resource_type, created_at
            FROM resources
            ORDER BY created_at DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $resources = [];
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'resources' => $resources
    ]);

} catch (Exception $e) {
    error_log('Get resources error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load resources'
    ]);
}
?>
