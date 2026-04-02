<?php
/**
 * In-App Notifications Helper
 * Handles creating, fetching, and managing notifications.
 */

/**
 * Create a notification for a user
 */
function createNotification($userId, $type, $title, $message, $link = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $title, $message, $link]);
    return $pdo->lastInsertId();
}

/**
 * Create notifications for all managers
 */
function notifyAllManagers($type, $title, $message, $link = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'manager'");
    $managers = $stmt->fetchAll();
    foreach ($managers as $m) {
        createNotification($m['id'], $type, $title, $message, $link);
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['cnt'];
}

/**
 * Get recent notifications for a user
 */
function getRecentNotifications($userId, $limit = 10) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all notifications for a user with pagination
 */
function getAllNotifications($userId, $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    $offset = ($page - 1) * $perPage;

    $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $total = (int)$countStmt->fetch()['cnt'];

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'notifications' => $stmt->fetchAll(),
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page,
    ];
}

/**
 * Mark a single notification as read
 */
function markNotificationRead($notificationId, $userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsRead($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon($type) {
    $icons = [
        'new_task'       => '📋',
        'task_delivered'  => '✅',
        'task_updated'   => '🔄',
        'info'           => 'ℹ️',
    ];
    return $icons[$type] ?? '🔔';
}

/**
 * Format notification time as relative (e.g., "5 min ago")
 */
function timeAgo($datetime) {
    $now = time();
    $diff = $now - strtotime($datetime);

    if ($diff < 60) return __('just_now');
    if ($diff < 3600) return floor($diff / 60) . ' ' . __('minutes_ago');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . __('hours_ago');
    if ($diff < 604800) return floor($diff / 86400) . ' ' . __('days_ago');
    return formatDate($datetime);
}
