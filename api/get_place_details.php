<?php
// Place Details API Proxy
// Gets detailed address information for a selected place

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get the place_id from the request
    $place_id = isset($_POST['place_id']) ? trim($_POST['place_id']) : '';

    if (empty($place_id)) {
        throw new Exception('Place ID is required');
    }

    // Load API key from config file (not in git)
    $config_file = __DIR__ . '/../../google_api_config.php';
    if (!file_exists($config_file)) {
        throw new Exception('API configuration not found');
    }

    require_once $config_file;

    if (!defined('GOOGLE_MAPS_API_KEY')) {
        throw new Exception('API key not configured');
    }

    // Build Google Places Details API request
    $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
        'place_id' => $place_id,
        'fields' => 'address_components',
        'key' => GOOGLE_MAPS_API_KEY
    ]);

    // Make request to Google API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Google API request failed');
    }

    // Return the response from Google
    echo $response;

} catch (Exception $e) {
    error_log('Place details error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Place details service unavailable']);
}
?>
