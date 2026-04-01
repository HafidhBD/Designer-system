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

// Prevent timeout on slow servers
set_time_limit(120);

// Load app config for constants
require_once __DIR__ . '/config/app.php';

// Database credentials from config
require_once __DIR__ . '/config/database.php';

// ============================================
// Installation Logic
// ============================================

$step = $_GET['step'] ?? '';
$messages = [];
$errors = [];
$installed = false;

// Check if already installed
try {
    $pdo = getDBConnection();
    $check = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($check->rowCount() > 0) {
        $installed = true;
    }
} catch (PDOException $e) {
    // Database might not exist yet — that's OK, we'll create it
}

// ============================================
// Handle Installation
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {

    try {
        // Step 1: Connect without database name to create the DB
        $dsnNoDB = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdoRoot = new PDO($dsnNoDB, DB_USER, DB_PASS, $options);

        // Create database if not exists
        $dbName = DB_NAME;
        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = "✅ Database <strong>$dbName</strong> created (or already exists).";

        // Switch to the database
        $pdoRoot->exec("USE `$dbName`");
        $pdo = $pdoRoot;

        // Step 2: Create tables
        // --- Users Table ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('manager', 'designer') NOT NULL DEFAULT 'designer',
                `language_preference` ENUM('en', 'ar') NOT NULL DEFAULT 'en',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $messages[] = "✅ Table <strong>users</strong> created.";

        // --- Tasks Table ---
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
        $messages[] = "✅ Table <strong>tasks</strong> created.";

        // --- Task Status Logs Table ---
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
        $messages[] = "✅ Table <strong>task_status_logs</strong> created.";

        // Step 3: Insert seed data (only if users table is empty)
        $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        $userCount = $countStmt->fetch()['cnt'];

        if ($userCount == 0) {
            // Hash passwords
            $managerPass  = password_hash('Admin@123', PASSWORD_DEFAULT);
            $designerPass = password_hash('Designer@123', PASSWORD_DEFAULT);

            // Insert Manager
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, language_preference) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Ahmed Al-Manager', 'admin@design.com', $managerPass, 'manager', 'ar']);
            $managerId = $pdo->lastInsertId();

            // Insert Designers
            $stmt->execute(['Sara Designer', 'sara@design.com', $designerPass, 'designer', 'ar']);
            $designer1Id = $pdo->lastInsertId();

            $stmt->execute(['Omar Creative', 'omar@design.com', $designerPass, 'designer', 'en']);
            $designer2Id = $pdo->lastInsertId();

            $messages[] = "✅ Seed users created (1 manager + 2 designers).";

            // Insert Sample Tasks
            $taskStmt = $pdo->prepare("
                INSERT INTO tasks (title, client_name, design_type, notes, deadline, assigned_to, progress_percentage, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $sampleTasks = [
                ['Company Logo Redesign', 'ABC Corporation', 'logo', 'Modern minimal logo with blue color scheme', date('Y-m-d', strtotime('+5 days')), $designer1Id, 50, 'in_progress', $managerId],
                ['Marketing Brochure', 'XYZ Ltd', 'file', 'Tri-fold brochure for product launch', date('Y-m-d', strtotime('+3 days')), $designer1Id, 25, 'in_progress', $managerId],
                ['Social Media Kit', 'Tech Startup', 'design', 'Instagram and Twitter post templates', date('Y-m-d', strtotime('+7 days')), $designer2Id, 0, 'new', $managerId],
                ['Product Animation', 'E-Commerce Store', 'motion_design', '30 second product showcase animation', date('Y-m-d', strtotime('+10 days')), $designer2Id, 75, 'in_progress', $managerId],
                ['Company Profile', 'National Bank', 'profile', 'Full company profile document, 20 pages', date('Y-m-d', strtotime('-1 day')), $designer1Id, 100, 'delivered', $managerId],
                ['Event Banner', 'Conference Center', 'design', 'Large format banner for annual event', date('Y-m-d', strtotime('+2 days')), $designer2Id, 0, 'new', $managerId],
            ];

            foreach ($sampleTasks as $task) {
                $taskStmt->execute($task);
            }

            $messages[] = "✅ " . count($sampleTasks) . " sample tasks created.";
        } else {
            $messages[] = "ℹ️ Users already exist — skipped seed data insertion.";
        }

        $messages[] = "";
        $messages[] = "🎉 <strong>Installation completed successfully!</strong>";

    } catch (PDOException $e) {
        $errors[] = "❌ Database error: " . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
        $errors[] = "❌ Error: " . htmlspecialchars($e->getMessage());
    }
}

// ============================================
// HTML Output
// ============================================
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Design Task Manager</title>
    <link rel="icon" href="/logo.gif" type="image/gif">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            background: linear-gradient(135deg, #1E293B 0%, #334155 50%, #1E293B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1E293B;
        }
        .install-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        .install-header {
            background: #4F46E5;
            color: #fff;
            padding: 28px 32px;
            text-align: center;
        }
        .install-header img {
            height: 56px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .install-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
        }
        .install-header p {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-top: 4px;
        }
        .install-body {
            padding: 28px 32px;
        }
        .info-box {
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            line-height: 1.7;
        }
        .info-box strong {
            color: #4F46E5;
        }
        .info-box code {
            background: #E2E8F0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.84rem;
        }
        .config-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 0.85rem;
        }
        .config-table th, .config-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #E2E8F0;
        }
        .config-table th {
            color: #64748B;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
        }
        .config-table td:first-child {
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn-primary {
            background: #4F46E5;
            color: #fff;
            width: 100%;
        }
        .btn-primary:hover {
            background: #4338CA;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        }
        .btn-success {
            background: #10B981;
            color: #fff;
            width: 100%;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-warning {
            background: #F59E0B;
            color: #1E293B;
            width: 100%;
        }
        .btn-warning:hover {
            background: #D97706;
        }
        .msg-list {
            list-style: none;
            margin: 0 0 20px;
            padding: 0;
        }
        .msg-list li {
            padding: 8px 0;
            border-bottom: 1px solid #F1F5F9;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .msg-list li:last-child {
            border-bottom: none;
        }
        .error-box {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #991B1B;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .success-box {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .warning-box {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            color: #92400E;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .credentials {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .credentials h3 {
            font-size: 0.9rem;
            margin-bottom: 10px;
            color: #334155;
        }
        .cred-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.85rem;
            border-bottom: 1px solid #F1F5F9;
        }
        .cred-row:last-child { border: none; }
        .cred-label { color: #64748B; }
        .cred-value { font-weight: 600; font-family: monospace; }
        .mt-2 { margin-top: 16px; }
        .text-center { text-align: center; }
        .text-danger { color: #EF4444; font-weight: 600; }
        .text-small { font-size: 0.82rem; color: #64748B; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <img src="/logo.gif" alt="Logo">
            <h1>Design Task Manager — Installer</h1>
            <p>Automated database setup & system installation</p>
        </div>
        <div class="install-body">

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $err): ?>
                        <p><?= $err ?></p>
                    <?php endforeach; ?>
                </div>
                <p class="text-small">Please check your <code>config/database.php</code> settings and try again.</p>
                <div class="mt-2">
                    <a href="/install.php" class="btn btn-warning">Try Again</a>
                </div>

            <?php elseif (!empty($messages)): ?>
                <!-- Installation Result -->
                <div class="success-box">
                    <strong>Installation Log:</strong>
                </div>
                <ul class="msg-list">
                    <?php foreach ($messages as $msg): ?>
                        <?php if ($msg): ?><li><?= $msg ?></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <div class="credentials">
                    <h3>🔐 Login Credentials</h3>
                    <div class="cred-row">
                        <span class="cred-label">Manager</span>
                        <span class="cred-value">admin@design.com / Admin@123</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Designer</span>
                        <span class="cred-value">sara@design.com / Designer@123</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Designer</span>
                        <span class="cred-value">omar@design.com / Designer@123</span>
                    </div>
                </div>

                <a href="/login.php" class="btn btn-success">Go to Login Page →</a>

                <p class="text-small text-center text-danger mt-2">
                    ⚠️ DELETE this file (install.php) and setup_passwords.php immediately for security!
                </p>

            <?php elseif ($installed): ?>
                <!-- Already Installed -->
                <div class="warning-box">
                    <strong>⚠️ System is already installed.</strong><br>
                    The database tables already exist. If you want to reinstall, you must first drop the existing tables manually via phpMyAdmin.
                </div>

                <a href="/login.php" class="btn btn-success">Go to Login Page →</a>

                <p class="text-small text-center text-danger mt-2">
                    ⚠️ DELETE this file (install.php) for security!
                </p>

            <?php else: ?>
                <!-- Pre-Install Info -->
                <div class="info-box">
                    <strong>This installer will:</strong><br>
                    1. Create the database <code><?= htmlspecialchars(DB_NAME) ?></code> if it doesn't exist<br>
                    2. Create tables: <code>users</code>, <code>tasks</code>, <code>task_status_logs</code><br>
                    3. Insert seed users with hashed passwords<br>
                    4. Insert sample tasks for testing
                </div>

                <div class="info-box">
                    <strong>Current DB Configuration:</strong>
                    <table class="config-table">
                        <tr><th>Setting</th><th>Value</th></tr>
                        <tr><td>Host</td><td><code><?= htmlspecialchars(DB_HOST) ?></code></td></tr>
                        <tr><td>Database</td><td><code><?= htmlspecialchars(DB_NAME) ?></code></td></tr>
                        <tr><td>Username</td><td><code><?= htmlspecialchars(DB_USER) ?></code></td></tr>
                        <tr><td>Password</td><td><code>••••••••</code></td></tr>
                    </table>
                    <small>Edit <code>config/database.php</code> if these are incorrect.</small>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="install" value="1" class="btn btn-primary"
                            onclick="return confirm('Start installation? This will create the database and tables.')">
                        🚀 Install Now
                    </button>
                </form>

                <p class="text-small text-center">
                    Make sure your database credentials are correct before proceeding.
                </p>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
