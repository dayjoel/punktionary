<?php
// Address Autocomplete API Proxy
// Proxies requests to Google Places Autocomplete API to keep API key secure

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get the input from the request
    $input = isset($_POST['input']) ? trim($_POST['input']) : '';

    if (empty($input)) {
        echo json_encode(['predictions' => []]);
        exit;
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

    // Build Google Places Autocomplete API request
    $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?' . http_build_query([
        'input' => $input,
        'types' => 'address',
        'components' => 'country:us',
        'key' => GOOGLE_MAPS_API_KEY
    ]);

    // Make request to Google API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('cURL error: ' . $curl_error);
    }

    if ($http_code !== 200) {
        error_log('Google API returned HTTP ' . $http_code . ': ' . $response);
        throw new Exception('Google API request failed with HTTP ' . $http_code);
    }

    // Parse response to check for API errors
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS') {
        error_log('Google API error status: ' . $data['status'] . (isset($data['error_message']) ? ' - ' . $data['error_message'] : ''));
    }

    // Return the response from Google
    echo $response;

} catch (Exception $e) {
    error_log('Address autocomplete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Autocomplete service unavailable', 'predictions' => []]);
}
?>
