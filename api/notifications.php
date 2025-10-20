<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            // Lấy danh sách thông báo
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
            $offset = ($page - 1) * $limit;
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == '1';
            
            // Build WHERE clause
            $where = "user_id = ?";
            if ($unread_only) {
                $where .= " AND is_read = 0";
            }
            
            $stmt = $pdo->prepare("
                SELECT id, title, message, type, related_id, is_read, created_at
                FROM notifications 
                WHERE {$where}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Đếm tổng số thông báo
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE {$where}");
            $count_stmt->execute([$user_id]);
            $total = $count_stmt->fetchColumn();
            
            echo json_encode([
                'notifications' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Đánh dấu thông báo đã đọc
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            if ($action === 'mark_read') {
                $notification_id = (int)($input['id'] ?? 0);
                if ($notification_id > 0) {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$notification_id, $user_id]);
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid notification ID']);
                }
            } elseif ($action === 'mark_all_read') {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            // Xóa thông báo
            $notification_id = (int)($_GET['id'] ?? 0);
            if ($notification_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                $stmt->execute([$notification_id, $user_id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid notification ID']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Notification API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
