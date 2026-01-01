<?php
// carousel_items.php - CRUD operations for carousel items
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../db_config.php';
    require_once __DIR__ . '/../auth/session_config.php';
    require_once __DIR__ . '/../auth/helpers.php';

    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    // GET - Fetch carousel items (public or admin)
    if ($method === 'GET') {
        $admin_view = isset($_GET['admin']) && $_GET['admin'] === 'true';

        if ($admin_view) {
            // Admin view - check permissions
            if (!is_authenticated()) {
                http_response_code(401);
                die(json_encode(['success' => false, 'error' => 'Authentication required']));
            }

            $user_id = get_current_user_id();
            $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || $user['account_type'] < 1) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Admin access required']));
            }

            // Fetch all items for admin
            $sql = "SELECT c.*, u.display_name as created_by_name
                    FROM carousel_items c
                    LEFT JOIN users u ON c.created_by = u.id
                    ORDER BY c.display_order ASC, c.created_at DESC";
        } else {
            // Public view - only active, non-expired items
            $sql = "SELECT id, title, description, image_url, link_url
                    FROM carousel_items
                    WHERE active = 1
                      AND (publish_date IS NULL OR publish_date <= CURDATE())
                      AND (expire_date IS NULL OR expire_date >= CURDATE())
                    ORDER BY display_order ASC, created_at DESC
                    LIMIT 10";
        }

        $result = $conn->query($sql);
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    }

    // POST - Create new carousel item
    elseif ($method === 'POST') {
        if (!is_authenticated()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Authentication required']));
        }

        $user_id = get_current_user_id();
        $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || $user['account_type'] < 1) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Admin access required']));
        }

        // Validate required fields
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';

        if (empty($title) || empty($image_url)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Title and image URL are required']));
        }

        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $link_url = isset($_POST['link_url']) ? trim($_POST['link_url']) : null;
        $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
        $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
        $publish_date = isset($_POST['publish_date']) && !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
        $expire_date = isset($_POST['expire_date']) && !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;

        $sql = "INSERT INTO carousel_items (title, description, image_url, link_url, display_order, active, publish_date, expire_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiissi', $title, $description, $image_url, $link_url, $display_order, $active, $publish_date, $expire_date, $user_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Carousel item created successfully',
                'id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception('Failed to create carousel item');
        }

        $stmt->close();
    }

    // PUT - Update carousel item
    elseif ($method === 'PUT') {
        if (!is_authenticated()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Authentication required']));
        }

        $user_id = get_current_user_id();
        $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || $user['account_type'] < 1) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Admin access required']));
        }

        // Parse PUT data
        parse_str(file_get_contents("php://input"), $_PUT);

        $id = isset($_PUT['id']) ? intval($_PUT['id']) : 0;
        if ($id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid item ID']));
        }

        $title = isset($_PUT['title']) ? trim($_PUT['title']) : '';
        $image_url = isset($_PUT['image_url']) ? trim($_PUT['image_url']) : '';

        if (empty($title) || empty($image_url)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Title and image URL are required']));
        }

        $description = isset($_PUT['description']) ? trim($_PUT['description']) : null;
        $link_url = isset($_PUT['link_url']) ? trim($_PUT['link_url']) : null;
        $display_order = isset($_PUT['display_order']) ? intval($_PUT['display_order']) : 0;
        $active = isset($_PUT['active']) ? intval($_PUT['active']) : 1;
        $publish_date = isset($_PUT['publish_date']) && !empty($_PUT['publish_date']) ? $_PUT['publish_date'] : null;
        $expire_date = isset($_PUT['expire_date']) && !empty($_PUT['expire_date']) ? $_PUT['expire_date'] : null;

        $sql = "UPDATE carousel_items
                SET title = ?, description = ?, image_url = ?, link_url = ?, display_order = ?, active = ?, publish_date = ?, expire_date = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssiissi', $title, $description, $image_url, $link_url, $display_order, $active, $publish_date, $expire_date, $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Carousel item updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update carousel item');
        }

        $stmt->close();
    }

    // DELETE - Delete carousel item
    elseif ($method === 'DELETE') {
        if (!is_authenticated()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Authentication required']));
        }

        $user_id = get_current_user_id();
        $stmt = $conn->prepare("SELECT account_type FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || $user['account_type'] < 1) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Admin access required']));
        }

        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;

        if ($id <= 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid item ID']));
        }

        $stmt = $conn->prepare("DELETE FROM carousel_items WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Carousel item deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete carousel item');
        }

        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    error_log('Carousel items error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
