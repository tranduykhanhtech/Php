<?php
require_once __DIR__ . '/../config/database.php';

// Server-Sent Events (SSE) endpoint for real-time notifications
// Notes:
// - Keeps connection open for up to 120 seconds, sending events when new notifications appear
// - Client should automatically reconnect after the server closes the stream

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Disable output buffering for proxies/servers (nginx/apache)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // For Nginx

// Prevent PHP from timing out and continue running if client disconnects
ignore_user_abort(true);
set_time_limit(0);

$userId = (int)$_SESSION['user_id'];

// Retrieve Last-Event-ID header or query param as starting point
$lastEventId = 0;
if (!empty($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastEventId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
} elseif (isset($_GET['last_id'])) {
    $lastEventId = (int)$_GET['last_id'];
}

// Helper to send an SSE event
function sse_event($event, $data, $id = null) {
    if ($id !== null) {
        echo "id: {$id}\n";
    }
    echo "event: {$event}\n";
    // Ensure data is a single line (SSE requires lines starting with 'data:')
    $payload = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
    $lines = preg_split("/\r?\n/", $payload);
    foreach ($lines as $line) {
        echo 'data: ' . $line . "\n";
    }
    echo "\n";
    @ob_flush();
    @flush();
}

// Heartbeat to keep connection alive
function heartbeat() {
    echo ": ping\n\n"; // SSE comment line as heartbeat
    @ob_flush();
    @flush();
}

$start = time();
$timeout = 120; // seconds
$pollInterval = 3; // seconds

// On connect, prime lastEventId with current newest notification if unknown
if ($lastEventId === 0) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $lastEventId = (int)$row['id'];
        }
    } catch (Exception $e) {
        // Send an error event and exit
        sse_event('error', ['message' => 'Init failed']);
        exit;
    }
}

// Immediately send a ready event
sse_event('ready', ['last_id' => $lastEventId]);

while (!connection_aborted() && (time() - $start) < $timeout) {
    try {
        // Fetch new notifications after lastEventId
        $stmt = $pdo->prepare("SELECT id, title, message, type, related_id, is_read, created_at
                               FROM notifications
                               WHERE user_id = ? AND id > ?
                               ORDER BY id ASC LIMIT 50");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $lastEventId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $eventData = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'related_id' => $row['related_id'],
                'is_read' => (int)$row['is_read'],
                'created_at' => $row['created_at']
            ];
            $lastEventId = (int)$row['id'];
            sse_event('notification', $eventData, $lastEventId);
        }

        // Send heartbeat if no events
        if (empty($rows)) {
            heartbeat();
        }

    } catch (Exception $e) {
        sse_event('error', ['message' => 'Query failed']);
        break;
    }

    sleep($pollInterval);
}

// Graceful close so client reconnects
sse_event('close', ['last_id' => $lastEventId]);
exit;
