<?php
// submit_carousel_news.php - Handle user-submitted carousel news
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
        die(json_encode(['success' => false, 'error' => 'You must be logged in to submit news']));
    }

    $user_id = get_current_user_id();
    $conn = get_db_connection();

    // Get URL from request
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';

    if (empty($url)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'URL is required']));
    }

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid URL format']));
    }

    // Try to scrape metadata from the URL
    $scraped_title = null;
    $scraped_description = null;
    $scraped_image = null;

    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PUNKtionary/1.0)');
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $html !== false) {
            // Extract og:title
            if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
                $scraped_title = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
            } elseif (preg_match('/<title>(.*?)<\/title>/i', $html, $matches)) {
                $scraped_title = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
            }

            // Extract og:description
            if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
                $scraped_description = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
            } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
                $scraped_description = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
            }

            // Extract og:image
            if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
                $image_url = $matches[1];

                // Make relative URLs absolute
                if (!preg_match('/^https?:\/\//i', $image_url)) {
                    $parsed_url = parse_url($url);
                    $base = $parsed_url['scheme'] . '://' . $parsed_url['host'];
                    if ($image_url[0] === '/') {
                        $image_url = $base . $image_url;
                    } else {
                        $path = dirname($parsed_url['path']);
                        $image_url = $base . $path . '/' . $image_url;
                    }
                }

                $scraped_image = $image_url;
            }
        }
    } catch (Exception $e) {
        // Scraping failed, but we'll still save the submission
        error_log('News scraping failed: ' . $e->getMessage());
    }

    // Insert into database
    $sql = "INSERT INTO pending_carousel_news (submitted_url, scraped_title, scraped_description, scraped_image_url, submitted_by, status)
            VALUES (?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $url, $scraped_title, $scraped_description, $scraped_image, $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'News submitted successfully for review'
        ]);
    } else {
        throw new Exception('Failed to save news submission');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log('News submission error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
