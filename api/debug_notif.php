<?php
/**
 * Debug Notifications — DELETE THIS FILE AFTER TESTING!
 * Visit: /api/debug_notif.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$results = [];

// Step 1: Test DB connection
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    $results['db_connection'] = 'OK';
} catch (Exception $e) {
    $results['db_connection'] = 'FAIL: ' . $e->getMessage();
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 2: Check if notifications table exists
try {
    $check = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $exists = $check->rowCount() > 0;
    $results['table_exists'] = $exists ? 'YES' : 'NO — run install.php upgrade!';
    
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) as c FROM notifications")->fetch();
        $results['total_notifications'] = (int)$count['c'];
        
        // Show last 3 notifications
        $last = $pdo->query("SELECT id, user_id, type, title, message, is_read, created_at FROM notifications ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        $results['last_notifications'] = $last;
    }
} catch (Exception $e) {
    $results['table_check'] = 'ERROR: ' . $e->getMessage();
}

// Step 3: Check session
try {
    session_start();
    $results['session_active'] = isset($_SESSION['user_id']) ? 'YES (user_id=' . $_SESSION['user_id'] . ', role=' . ($_SESSION['role'] ?? 'unknown') . ')' : 'NO — not logged in';
} catch (Exception $e) {
    $results['session'] = 'ERROR: ' . $e->getMessage();
}

// Step 4: Test createNotification
if (isset($_GET['test_create']) && isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../includes/auth.php';
        require_once __DIR__ . '/../includes/helpers.php';
        require_once __DIR__ . '/../includes/notifications.php';
        
        $testId = createNotification(
            $_SESSION['user_id'],
            'info',
            'Test Notification',
            'This is a test notification created at ' . date('H:i:s'),
            '/notifications.php'
        );
        $results['test_create'] = $testId ? 'OK — created notification ID: ' . $testId : 'FAIL — returned false';
    } catch (Exception $e) {
        $results['test_create'] = 'ERROR: ' . $e->getMessage();
    }
}

// Step 5: Check users table for designers
try {
    $users = $pdo->query("SELECT id, full_name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $results['users'] = $users;
} catch (Exception $e) {
    $results['users_check'] = 'ERROR: ' . $e->getMessage();
}

$results['instructions'] = 'Add ?test_create=1 to URL to create a test notification for your account. DELETE this file after testing!';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
