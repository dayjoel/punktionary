<?php
/**
 * API endpoint to scrape venue information from a URL
 * Returns structured data that can be used to pre-fill the venue form
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? '';

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'URL is required']);
    exit;
}

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid URL']);
    exit;
}

try {
    // Fetch the webpage
    $context = stream_context_create([
        'http' => [
            'header' => 'User-Agent: Mozilla/5.0 (compatible; PUNKtionary/1.0)',
            'timeout' => 10
        ]
    ]);

    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch URL']);
        exit;
    }

    // Parse HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Initialize extracted data
    $data = [
        'name' => '',
        'street_address' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'phone' => '',
        'website' => $url,
        'description' => '',
        'social_links' => []
    ];

    // Extract from meta tags
    $metaTags = $xpath->query('//meta');
    foreach ($metaTags as $meta) {
        $property = $meta->getAttribute('property');
        $name = $meta->getAttribute('name');
        $content = $meta->getAttribute('content');

        // Open Graph tags
        if ($property === 'og:title' && empty($data['name'])) {
            $data['name'] = $content;
        }
        if ($property === 'og:description' && empty($data['description'])) {
            $data['description'] = $content;
        }
        if ($property === 'og:street-address') {
            $data['street_address'] = $content;
        }
        if ($property === 'og:locality') {
            $data['city'] = $content;
        }
        if ($property === 'og:region') {
            $data['state'] = $content;
        }
        if ($property === 'og:postal-code') {
            $data['postal_code'] = $content;
        }

        // Standard meta tags
        if ($name === 'description' && empty($data['description'])) {
            $data['description'] = $content;
        }
    }

    // Extract from Schema.org JSON-LD
    $jsonLdScripts = $xpath->query('//script[@type="application/ld+json"]');
    foreach ($jsonLdScripts as $script) {
        $jsonData = json_decode($script->nodeValue, true);
        if ($jsonData && isset($jsonData['@type'])) {
            $types = is_array($jsonData['@type']) ? $jsonData['@type'] : [$jsonData['@type']];

            if (in_array('LocalBusiness', $types) || in_array('MusicVenue', $types) || in_array('BarOrPub', $types)) {
                if (isset($jsonData['name']) && empty($data['name'])) {
                    $data['name'] = $jsonData['name'];
                }
                if (isset($jsonData['description']) && empty($data['description'])) {
                    $data['description'] = $jsonData['description'];
                }
                if (isset($jsonData['telephone'])) {
                    $data['phone'] = $jsonData['telephone'];
                }
                if (isset($jsonData['address'])) {
                    $address = $jsonData['address'];
                    if (isset($address['streetAddress'])) {
                        $data['street_address'] = $address['streetAddress'];
                    }
                    if (isset($address['addressLocality'])) {
                        $data['city'] = $address['addressLocality'];
                    }
                    if (isset($address['addressRegion'])) {
                        $data['state'] = $address['addressRegion'];
                    }
                    if (isset($address['postalCode'])) {
                        $data['postal_code'] = $address['postalCode'];
                    }
                }
            }
        }
    }

    // Extract title if name still empty
    if (empty($data['name'])) {
        $titleNodes = $xpath->query('//title');
        if ($titleNodes->length > 0) {
            $data['name'] = trim($titleNodes->item(0)->nodeValue);
        }
    }

    // Extract from common HTML patterns
    if (empty($data['street_address'])) {
        // Look for address in common patterns
        $addressPatterns = [
            '//address',
            '//*[contains(@class, "address")]',
            '//*[contains(@itemprop, "address")]'
        ];
        foreach ($addressPatterns as $pattern) {
            $nodes = $xpath->query($pattern);
            if ($nodes->length > 0) {
                $addressText = trim($nodes->item(0)->nodeValue);
                // Simple extraction - can be improved
                if (!empty($addressText) && empty($data['street_address'])) {
                    $data['street_address'] = $addressText;
                    break;
                }
            }
        }
    }

    // Extract social media links
    $links = $xpath->query('//a[@href]');
    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        if (stripos($href, 'facebook.com') !== false) {
            $data['social_links']['facebook'] = $href;
        } elseif (stripos($href, 'instagram.com') !== false) {
            $data['social_links']['instagram'] = $href;
        } elseif (stripos($href, 'twitter.com') !== false || stripos($href, 'x.com') !== false) {
            $data['social_links']['twitter'] = $href;
        } elseif (stripos($href, 'youtube.com') !== false) {
            $data['social_links']['youtube'] = $href;
        }
    }

    // Clean up data
    $data['name'] = html_entity_decode($data['name']);
    $data['description'] = html_entity_decode($data['description']);

    // Truncate description if too long
    if (strlen($data['description']) > 500) {
        $data['description'] = substr($data['description'], 0, 497) . '...';
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log('Venue scraper error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to parse venue information'
    ]);
}
