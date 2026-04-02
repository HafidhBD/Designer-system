<?php
/**
 * Notifications API Endpoint
 * Handles AJAX requests for notifications.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/notifications.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUser()['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'recent';

try {

    switch ($action) {
        case 'count':
            echo json_encode(['count' => getUnreadNotificationCount($userId)]);
            break;

        case 'recent':
            $notifications = getRecentNotifications($userId, 10);
            $unread = getUnreadNotificationCount($userId);
            $out = [];
            foreach ($notifications as $n) {
                $out[] = [
                    'id'      => $n['id'],
                    'type'    => $n['type'],
                    'icon'    => getNotificationIcon($n['type']),
                    'title'   => $n['title'],
                    'message' => $n['message'],
                    'link'    => $n['link'],
                    'is_read' => (int)$n['is_read'],
                    'time'    => timeAgo($n['created_at']),
                ];
            }
            echo json_encode(['unread' => $unread, 'notifications' => $out]);
            break;

        case 'read':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $notifId = (int)($_POST['id'] ?? 0);
                if ($notifId) {
                    markNotificationRead($notifId, $userId);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false]);
                }
            }
            break;

        case 'read_all':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                markAllNotificationsRead($userId);
                echo json_encode(['success' => true]);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }

} catch (PDOException $e) {
    // Table may not exist yet — return empty data gracefully
    if ($action === 'count') {
        echo json_encode(['count' => 0]);
    } elseif ($action === 'recent') {
        echo json_encode(['unread' => 0, 'notifications' => []]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database not ready. Run install.php to upgrade.']);
    }
}
