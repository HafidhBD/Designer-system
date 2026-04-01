<?php
/**
 * Helper Functions
 */

/**
 * Get base URL path
 */
function basePath($path = '') {
    return '/' . ltrim($path, '/');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($date) {
    if (empty($date)) return '';
    return date('Y-m-d H:i', strtotime($date));
}

/**
 * Get design type label
 */
function getDesignTypeLabel($type) {
    $currentLang = getCurrentLang();
    $types = DESIGN_TYPES;
    return isset($types[$type]) ? $types[$type][$currentLang] : $type;
}

/**
 * Get status label
 */
function getStatusLabel($status) {
    $currentLang = getCurrentLang();
    $statuses = TASK_STATUSES;
    return isset($statuses[$status]) ? $statuses[$status][$currentLang] : $status;
}

/**
 * Get status CSS class
 */
function getStatusClass($status) {
    $classes = [
        'new'         => 'badge-new',
        'in_progress' => 'badge-progress',
        'delivered'   => 'badge-delivered',
    ];
    return $classes[$status] ?? 'badge-default';
}

/**
 * Get progress bar color class
 */
function getProgressColor($percentage) {
    if ($percentage >= 100) return 'progress-complete';
    if ($percentage >= 75)  return 'progress-high';
    if ($percentage >= 50)  return 'progress-mid';
    if ($percentage >= 25)  return 'progress-low';
    return 'progress-zero';
}

/**
 * Check if deadline is overdue
 */
function isOverdue($deadline) {
    if (empty($deadline)) return false;
    return strtotime($deadline) < strtotime('today');
}

/**
 * Check if deadline is today
 */
function isDueToday($deadline) {
    if (empty($deadline)) return false;
    return date('Y-m-d', strtotime($deadline)) === date('Y-m-d');
}

/**
 * Get all designers from database
 */
function getDesigners() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE role = ? ORDER BY full_name");
    $stmt->execute([ROLE_DESIGNER]);
    return $stmt->fetchAll();
}

/**
 * Get all users from database
 */
function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY role, full_name");
    return $stmt->fetchAll();
}

/**
 * Get user by ID
 */
function getUserById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, role, language_preference, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Redirect with message
 */
function redirectWith($url, $type, $message) {
    setFlash($type, $message);
    header("Location: $url");
    exit;
}

/**
 * Get task counts for dashboard
 */
function getTaskCounts($designerId = null) {
    $pdo = getDBConnection();
    $where = '';
    $params = [];

    if ($designerId) {
        $where = ' WHERE assigned_to = ?';
        $params[] = $designerId;
    }

    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
            FROM tasks" . $where;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Log task status change
 */
function logStatusChange($taskId, $oldStatus, $newStatus, $changedBy) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO task_status_logs (task_id, old_status, new_status, changed_by, changed_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$taskId, $oldStatus, $newStatus, $changedBy]);
}
