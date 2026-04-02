<?php
/**
 * Installation Script
 * 
 * This script will:
 * 1. Create the database (if it doesn't exist)
 * 2. Create all required tables
 * 3. Insert seed data (1 manager, 2 designers, sample tasks)
 * 4. Hash all passwords with bcrypt
 * 
 * Visit: https://yourdomain.com/install.php
 * DELETE this file immediately after successful installation!
 */

set_time_limit(120);
error_reporting(E_ALL);

// Load app config for constants
require_once __DIR__ . '/config/app.php';

// Load DB constants only — we will NOT call getDBConnection()
require_once __DIR__ . '/config/database.php';

// ============================================
// Variables
// ============================================
$messages = [];
$errors = [];
$installed = false;
$connectionOK = false;
$connectionError = '';

// ============================================
// Helper: connect to MySQL (without die())
// ============================================
function installConnect($includeDB = false) {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    if ($includeDB) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    return new PDO($dsn, DB_USER, DB_PASS, $opts);
}

// ============================================
// Test Connection
// ============================================
try {
    $testPdo = installConnect(false);
    $connectionOK = true;
} catch (PDOException $e) {
    $connectionError = $e->getMessage();
}

// Check if already installed
if ($connectionOK) {
    try {
        $checkPdo = installConnect(true);
        $check = $checkPdo->query("SHOW TABLES LIKE 'users'");
        if ($check->rowCount() > 0) {
            $installed = true;
        }
    } catch (PDOException $e) {
        // DB doesn't exist yet — fine
    }
}

// ============================================
// Handle Install
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {

    if (!$connectionOK) {
        $errors[] = "Cannot connect to MySQL server:<br><code>" . htmlspecialchars($connectionError) . "</code>";
    } else {
        try {
            $pdo = installConnect(false);

            // Step 1: Create database
            $dbName = DB_NAME;
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = "Database <strong>" . htmlspecialchars($dbName) . "</strong> ready.";

            $pdo->exec("USE `$dbName`");

            // Step 2: Create tables
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `full_name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(150) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `role` ENUM('manager', 'designer') NOT NULL DEFAULT 'designer',
                    `language_preference` ENUM('en', 'ar') NOT NULL DEFAULT 'en',
                    `telegram_chat_id` VARCHAR(50) NULL DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `idx_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Table <strong>users</strong> created.";

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `tasks` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `client_name` VARCHAR(150) NOT NULL,
                    `design_type` ENUM('file', 'logo', 'design', 'motion_design', 'profile') NOT NULL,
                    `notes` TEXT NULL,
                    `deadline` DATE NULL,
                    `assigned_to` INT UNSIGNED NOT NULL,
                    `progress_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    `status` ENUM('new', 'in_progress', 'delivered') NOT NULL DEFAULT 'new',
                    `file_path` VARCHAR(500) NULL DEFAULT NULL,
                    `created_by` INT UNSIGNED NOT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_assigned_to` (`assigned_to`),
                    KEY `idx_status` (`status`),
                    KEY `idx_design_type` (`design_type`),
                    KEY `idx_deadline` (`deadline`),
                    KEY `idx_created_by` (`created_by`),
                    CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Table <strong>tasks</strong> created.";

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `task_status_logs` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `task_id` INT UNSIGNED NOT NULL,
                    `old_status` VARCHAR(20) NULL,
                    `new_status` VARCHAR(20) NOT NULL,
                    `changed_by` INT UNSIGNED NOT NULL,
                    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_task_id` (`task_id`),
                    KEY `idx_changed_by` (`changed_by`),
                    CONSTRAINT `fk_logs_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_logs_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Table <strong>task_status_logs</strong> created.";

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` INT UNSIGNED NOT NULL,
                    `type` VARCHAR(30) NOT NULL DEFAULT 'info',
                    `title` VARCHAR(255) NOT NULL,
                    `message` TEXT NULL,
                    `link` VARCHAR(500) NULL DEFAULT NULL,
                    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_user_read` (`user_id`, `is_read`),
                    KEY `idx_created` (`created_at`),
                    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $messages[] = "Table <strong>notifications</strong> created.";

            // Step 2b: Upgrade — add new columns if missing (for existing installations)
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'telegram_chat_id'")->rowCount();
                if ($cols == 0) {
                    $pdo->exec("ALTER TABLE `users` ADD COLUMN `telegram_chat_id` VARCHAR(50) NULL DEFAULT NULL AFTER `language_preference`");
                    $messages[] = "Column <strong>telegram_chat_id</strong> added to users.";
                }
            } catch (PDOException $e) { /* column may already exist */ }

            try {
                $cols = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'file_path'")->rowCount();
                if ($cols == 0) {
                    $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `file_path` TEXT NULL DEFAULT NULL AFTER `status`");
                    $messages[] = "Column <strong>file_path</strong> added to tasks.";
                } else {
                    // Upgrade existing VARCHAR to TEXT for multi-file support
                    $colInfo = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'file_path'")->fetch();
                    if ($colInfo && stripos($colInfo['Type'], 'varchar') !== false) {
                        $pdo->exec("ALTER TABLE `tasks` MODIFY COLUMN `file_path` TEXT NULL DEFAULT NULL");
                        $messages[] = "Column <strong>file_path</strong> upgraded to TEXT for multi-file support.";
                    }
                }
            } catch (PDOException $e) { /* column may already exist */ }

            // Step 3: Seed data (only if users table is empty)
            $cnt = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];

            if ($cnt == 0) {
                $managerHash  = password_hash('Admin@123', PASSWORD_DEFAULT);
                $designerHash = password_hash('Designer@123', PASSWORD_DEFAULT);

                $ins = $pdo->prepare("INSERT INTO users (full_name, email, password, role, language_preference) VALUES (?, ?, ?, ?, ?)");

                $ins->execute(['Ahmed Al-Manager', 'admin@design.com', $managerHash, 'manager', 'ar']);
                $managerId = $pdo->lastInsertId();

                $ins->execute(['Sara Designer', 'sara@design.com', $designerHash, 'designer', 'ar']);
                $d1 = $pdo->lastInsertId();

                $ins->execute(['Omar Creative', 'omar@design.com', $designerHash, 'designer', 'en']);
                $d2 = $pdo->lastInsertId();

                $messages[] = "Seed users created (1 manager + 2 designers).";

                $tIns = $pdo->prepare("INSERT INTO tasks (title, client_name, design_type, notes, deadline, assigned_to, progress_percentage, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $tasks = [
                    ['Company Logo Redesign', 'ABC Corporation', 'logo', 'Modern minimal logo with blue color scheme', date('Y-m-d', strtotime('+5 days')), $d1, 50, 'in_progress', $managerId],
                    ['Marketing Brochure', 'XYZ Ltd', 'file', 'Tri-fold brochure for product launch', date('Y-m-d', strtotime('+3 days')), $d1, 25, 'in_progress', $managerId],
                    ['Social Media Kit', 'Tech Startup', 'design', 'Instagram and Twitter post templates', date('Y-m-d', strtotime('+7 days')), $d2, 0, 'new', $managerId],
                    ['Product Animation', 'E-Commerce Store', 'motion_design', '30 second product showcase animation', date('Y-m-d', strtotime('+10 days')), $d2, 75, 'in_progress', $managerId],
                    ['Company Profile', 'National Bank', 'profile', 'Full company profile document, 20 pages', date('Y-m-d', strtotime('-1 day')), $d1, 100, 'delivered', $managerId],
                    ['Event Banner', 'Conference Center', 'design', 'Large format banner for annual event', date('Y-m-d', strtotime('+2 days')), $d2, 0, 'new', $managerId],
                ];

                foreach ($tasks as $t) {
                    $tIns->execute($t);
                }

                $messages[] = count($tasks) . " sample tasks created.";
            } else {
                $messages[] = "Users already exist — skipped seed data.";
            }

            $messages[] = "<strong>Installation completed successfully!</strong>";

        } catch (PDOException $e) {
            $errors[] = "Database error:<br><code>" . htmlspecialchars($e->getMessage()) . "</code>";
        } catch (Exception $e) {
            $errors[] = "Error:<br><code>" . htmlspecialchars($e->getMessage()) . "</code>";
        }
    }
}

// ============================================
// HTML
// ============================================
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Design Task Manager</title>
    <link rel="icon" href="/logo-white.png" type="image/png">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,Arial,sans-serif;background:linear-gradient(135deg,#1E293B 0%,#334155 50%,#1E293B 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#1E293B}
        .card{background:#fff;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.15);width:100%;max-width:600px;overflow:hidden}
        .hdr{background:#4F46E5;color:#fff;padding:28px 32px;text-align:center}
        .hdr img{height:56px;border-radius:8px;margin-bottom:12px}
        .hdr h1{font-size:1.4rem;font-weight:600}
        .hdr p{font-size:.9rem;opacity:.85;margin-top:4px}
        .body{padding:28px 32px}
        .box{background:#F1F5F9;border:1px solid #E2E8F0;border-radius:8px;padding:16px;margin-bottom:20px;font-size:.88rem;line-height:1.7}
        .box strong{color:#4F46E5}
        .box code{background:#E2E8F0;padding:2px 6px;border-radius:4px;font-size:.84rem}
        table.cfg{width:100%;border-collapse:collapse;margin:12px 0;font-size:.85rem}
        table.cfg th,table.cfg td{padding:8px 12px;text-align:left;border-bottom:1px solid #E2E8F0}
        table.cfg th{color:#64748B;font-weight:600;font-size:.78rem;text-transform:uppercase}
        .btn{display:inline-block;padding:12px 28px;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;text-decoration:none;text-align:center;transition:all .2s;font-family:inherit;width:100%}
        .btn-primary{background:#4F46E5;color:#fff}.btn-primary:hover{background:#4338CA;transform:translateY(-1px)}
        .btn-success{background:#10B981;color:#fff}.btn-success:hover{background:#059669}
        .btn-warning{background:#F59E0B;color:#1E293B}.btn-warning:hover{background:#D97706}
        .err{background:#FEE2E2;border:1px solid #FECACA;color:#991B1B;border-radius:8px;padding:16px;margin-bottom:20px;font-size:.9rem;word-break:break-word}
        .ok{background:#D1FAE5;border:1px solid #A7F3D0;color:#065F46;border-radius:8px;padding:16px;margin-bottom:20px;font-size:.9rem}
        .warn{background:#FEF3C7;border:1px solid #FDE68A;color:#92400E;border-radius:8px;padding:16px;margin-bottom:20px;font-size:.9rem}
        .msgs{list-style:none;margin:0 0 20px;padding:0}
        .msgs li{padding:8px 0;border-bottom:1px solid #F1F5F9;font-size:.9rem;line-height:1.5}
        .msgs li:last-child{border-bottom:none}
        .creds{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:16px;margin:16px 0}
        .creds h3{font-size:.9rem;margin-bottom:10px;color:#334155}
        .cr{display:flex;justify-content:space-between;padding:6px 0;font-size:.85rem;border-bottom:1px solid #F1F5F9}
        .cr:last-child{border:none}
        .cr span:first-child{color:#64748B}
        .cr span:last-child{font-weight:600;font-family:monospace}
        .mt{margin-top:16px}
        .tc{text-align:center}
        .sm{font-size:.82rem;color:#64748B;margin-top:12px}
        .rd{color:#EF4444;font-weight:600}
        .conn-status{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.8rem;font-weight:600}
        .conn-ok{background:#D1FAE5;color:#065F46}
        .conn-fail{background:#FEE2E2;color:#991B1B}
    </style>
</head>
<body>
<div class="card">
    <div class="hdr">
        <img src="/logo-white.png" alt="Logo">
        <h1>Design Task Manager — Installer</h1>
        <p>Automated database setup & system installation</p>
    </div>
    <div class="body">

    <?php if (!empty($errors)): ?>
        <div class="err">
            <?php foreach ($errors as $e): ?>
                <p>❌ <?= $e ?></p>
            <?php endforeach; ?>
        </div>
        <p class="sm">Check your <code>config/database.php</code> settings and try again.</p>
        <div class="mt"><a href="/install.php" class="btn btn-warning">← Try Again</a></div>

    <?php elseif (!empty($messages)): ?>
        <div class="ok"><strong>✅ Installation Log:</strong></div>
        <ul class="msgs">
            <?php foreach ($messages as $m): ?>
                <?php if ($m): ?><li>✅ <?= $m ?></li><?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="creds">
            <h3>🔐 Login Credentials</h3>
            <div class="cr"><span>Manager</span><span>admin@design.com / Admin@123</span></div>
            <div class="cr"><span>Designer</span><span>sara@design.com / Designer@123</span></div>
            <div class="cr"><span>Designer</span><span>omar@design.com / Designer@123</span></div>
        </div>
        <a href="/login.php" class="btn btn-success">Go to Login Page →</a>
        <p class="sm tc rd mt">⚠️ DELETE install.php and setup_passwords.php immediately!</p>

    <?php elseif ($installed): ?>
        <div class="warn"><strong>⚠️ Already installed.</strong><br>Tables already exist. You can upgrade to add new columns (Telegram, file upload) or go to login.</div>
        <div style="display:flex;gap:10px;margin-top:16px;">
            <a href="/login.php" class="btn btn-success">Go to Login Page →</a>
        </div>
        <form method="POST" style="margin-top:12px;">
            <button type="submit" name="install" value="1" class="btn btn-warning"
                    onclick="return confirm('Run upgrade to add new columns?')">
                ↑ Upgrade Database (add new columns)
            </button>
        </form>
        <p class="sm tc rd mt">⚠️ DELETE install.php for security!</p>

    <?php else: ?>
        <div class="box">
            <strong>Connection Status:</strong>
            <?php if ($connectionOK): ?>
                <span class="conn-status conn-ok">✅ Connected to MySQL</span>
            <?php else: ?>
                <span class="conn-status conn-fail">❌ Cannot connect</span>
                <br><br><code><?= htmlspecialchars($connectionError) ?></code>
                <br><br>Fix your <code>config/database.php</code> and refresh this page.
            <?php endif; ?>
        </div>

        <div class="box">
            <strong>This installer will:</strong><br>
            1. Create database <code><?= htmlspecialchars(DB_NAME) ?></code><br>
            2. Create tables: <code>users</code>, <code>tasks</code>, <code>task_status_logs</code><br>
            3. Insert seed users with hashed passwords<br>
            4. Insert 6 sample tasks for testing
        </div>

        <div class="box">
            <strong>DB Configuration:</strong>
            <table class="cfg">
                <tr><th>Setting</th><th>Value</th></tr>
                <tr><td>Host</td><td><code><?= htmlspecialchars(DB_HOST) ?></code></td></tr>
                <tr><td>Database</td><td><code><?= htmlspecialchars(DB_NAME) ?></code></td></tr>
                <tr><td>Username</td><td><code><?= htmlspecialchars(DB_USER) ?></code></td></tr>
                <tr><td>Password</td><td><code>••••••••</code></td></tr>
            </table>
            <small>Edit <code>config/database.php</code> if incorrect.</small>
        </div>

        <?php if ($connectionOK): ?>
        <form method="POST">
            <button type="submit" name="install" value="1" class="btn btn-primary"
                    onclick="return confirm('Start installation?')">
                🚀 Install Now
            </button>
        </form>
        <?php else: ?>
        <div class="err">Cannot proceed — fix the database connection first.</div>
        <a href="/install.php" class="btn btn-warning">↻ Refresh</a>
        <?php endif; ?>

        <p class="sm tc">Make sure credentials are correct before proceeding.</p>
    <?php endif; ?>

    </div>
</div>
</body>
</html>
