<?php
/**
 * Telegram Bot Webhook Handler
 * 
 * Commands:
 *   /start       — Get your Chat ID
 *   /id          — Get your Chat ID
 *   /tasks       — View all pending tasks (managers only)
 *   /designer    — View tasks for a specific designer (managers only)
 *   /designers   — List all designers (managers only)
 *   /help        — Show available commands
 * 
 * SETUP:
 * 1. Upload this file to your server
 * 2. Visit: https://yourdomain.com/telegram_setup.php to register the webhook
 */

require_once __DIR__ . '/config/database.php';

define('BOT_TOKEN', '8640672656:AAEG4SVN2yHqnTZT6eYP45q5XqnWAsmPdvQ');

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$chatId   = $update['message']['chat']['id'];
$text     = trim($update['message']['text'] ?? '');
$firstName = $update['message']['from']['first_name'] ?? '';

// Check if this chat_id belongs to a manager
function isManagerChat($chatId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT role FROM users WHERE telegram_chat_id = ? LIMIT 1");
        $stmt->execute([(string)$chatId]);
        $user = $stmt->fetch();
        return $user && $user['role'] === 'manager';
    } catch (Exception $e) {
        return false;
    }
}

// ========== COMMAND ROUTING ==========

if ($text === '/start') {
    $msg  = "مرحباً {$firstName}! 👋\n\n";
    $msg .= "🤖 هذا بوت إشعارات نظام إدارة التصميم\n\n";
    $msg .= "🆔 معرّف المحادثة الخاص بك (Chat ID):\n";
    $msg .= "<code>{$chatId}</code>\n\n";
    $msg .= "📋 انسخ هذا الرقم وأدخله في صفحة إدارة المستخدمين في النظام.\n\n";
    $msg .= "---\n";
    $msg .= "Hello {$firstName}! 👋\n";
    $msg .= "🆔 Your Chat ID is:\n";
    $msg .= "<code>{$chatId}</code>\n\n";
    $msg .= "📋 Copy this number and enter it in the User Management page.\n\n";
    $msg .= "أرسل /help لعرض الأوامر المتاحة\nSend /help to see available commands.";
    sendMsg($chatId, $msg);

} elseif ($text === '/id') {
    sendMsg($chatId, "🆔 Chat ID: <code>{$chatId}</code>");

} elseif ($text === '/help') {
    $msg  = "📖 <b>الأوامر المتاحة:</b>\n\n";
    $msg .= "/start — عرض معرّف المحادثة\n";
    $msg .= "/id — عرض معرّف المحادثة\n";
    $msg .= "/tasks — عرض جميع المهام المعلّقة 📋\n";
    $msg .= "/designer — عرض تاسكات مصمم معين 🎨\n";
    $msg .= "/designers — عرض قائمة المصممين 👥\n";
    $msg .= "/help — عرض هذه الرسالة\n\n";
    $msg .= "---\n";
    $msg .= "📖 <b>Available Commands:</b>\n\n";
    $msg .= "/start — Show your Chat ID\n";
    $msg .= "/id — Show your Chat ID\n";
    $msg .= "/tasks — View all pending tasks 📋\n";
    $msg .= "/designer — View tasks by designer 🎨\n";
    $msg .= "/designers — List all designers 👥\n";
    $msg .= "/help — Show this message";
    sendMsg($chatId, $msg);

} elseif ($text === '/tasks') {
    if (!isManagerChat($chatId)) {
        sendMsg($chatId, "⛔ هذا الأمر متاح للمدراء فقط.\nThis command is for managers only.");
        http_response_code(200);
        exit;
    }
    handleTasksCommand($chatId);

} elseif ($text === '/designers') {
    if (!isManagerChat($chatId)) {
        sendMsg($chatId, "⛔ هذا الأمر متاح للمدراء فقط.\nThis command is for managers only.");
        http_response_code(200);
        exit;
    }
    handleDesignersCommand($chatId);

} elseif (strpos($text, '/designer') === 0) {
    if (!isManagerChat($chatId)) {
        sendMsg($chatId, "⛔ هذا الأمر متاح للمدراء فقط.\nThis command is for managers only.");
        http_response_code(200);
        exit;
    }
    // Extract designer name or ID after /designer
    $param = trim(substr($text, strlen('/designer')));
    if (empty($param)) {
        // Show designer selection prompt
        handleDesignersCommand($chatId, true);
    } else {
        handleDesignerTasksCommand($chatId, $param);
    }

} else {
    $msg  = "🤖 أرسل /help لعرض الأوامر المتاحة.\n";
    $msg .= "Send /help to see available commands.";
    sendMsg($chatId, $msg);
}

// ========== COMMAND HANDLERS ==========

/**
 * /tasks — Show all pending tasks (new + in_progress)
 */
function handleTasksCommand($chatId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT t.id, t.title, t.client_name, t.status, t.deadline, t.progress_percentage,
                   u.full_name AS designer_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.status IN ('new', 'in_progress')
            ORDER BY 
                CASE WHEN t.status = 'new' THEN 0 ELSE 1 END,
                t.deadline ASC,
                t.created_at DESC
            LIMIT 30
        ");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tasks)) {
            sendMsg($chatId, "✅ لا توجد مهام معلّقة حالياً!\nNo pending tasks at the moment!");
            return;
        }

        $statusIcons = ['new' => '🆕', 'in_progress' => '🔄'];
        $statusLabels = ['new' => 'جديد', 'in_progress' => 'قيد التنفيذ'];

        $msg = "📋 <b>المهام المعلّقة (" . count($tasks) . ")</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━\n\n";

        foreach ($tasks as $i => $t) {
            $icon = $statusIcons[$t['status']] ?? '📌';
            $label = $statusLabels[$t['status']] ?? $t['status'];
            $deadline = $t['deadline'] ? "📅 {$t['deadline']}" : '';
            $overdue = '';
            if ($t['deadline'] && strtotime($t['deadline']) < strtotime('today') && $t['status'] !== 'delivered') {
                $overdue = ' ⚠️ متأخر!';
            }

            $msg .= ($i + 1) . ". {$icon} <b>{$t['title']}</b>\n";
            $msg .= "   👤 العميل: {$t['client_name']}\n";
            $msg .= "   🎨 المصمم: {$t['designer_name']}\n";
            $msg .= "   📊 الحالة: {$label} ({$t['progress_percentage']}%)\n";
            if ($deadline) {
                $msg .= "   {$deadline}{$overdue}\n";
            }
            $msg .= "\n";
        }

        // Split long messages (Telegram limit is 4096 chars)
        if (mb_strlen($msg) > 4000) {
            $chunks = mb_str_split($msg, 4000);
            foreach ($chunks as $chunk) {
                sendMsg($chatId, $chunk);
            }
        } else {
            sendMsg($chatId, $msg);
        }

    } catch (Exception $e) {
        sendMsg($chatId, "❌ خطأ في قراءة البيانات.\nError reading data.");
    }
}

/**
 * /designers — List all designers
 */
function handleDesignersCommand($chatId, $showHint = false) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT u.id, u.full_name,
                   COUNT(CASE WHEN t.status IN ('new','in_progress') THEN 1 END) AS pending_tasks,
                   COUNT(t.id) AS total_tasks
            FROM users u
            LEFT JOIN tasks t ON t.assigned_to = u.id
            WHERE u.role = 'designer'
            GROUP BY u.id, u.full_name
            ORDER BY u.full_name
        ");
        $designers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($designers)) {
            sendMsg($chatId, "لا يوجد مصممين في النظام.\nNo designers found.");
            return;
        }

        $msg = "👥 <b>المصممين</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━\n\n";

        foreach ($designers as $i => $d) {
            $msg .= ($i + 1) . ". <b>{$d['full_name']}</b>\n";
            $msg .= "   📋 المعلّقة: {$d['pending_tasks']} | الإجمالي: {$d['total_tasks']}\n";
            $msg .= "   ↪ <code>/designer {$d['id']}</code>\n\n";
        }

        if ($showHint) {
            $msg .= "💡 اختر مصمم بإرسال الأمر أعلاه\nChoose a designer by sending the command above.";
        }

        sendMsg($chatId, $msg);

    } catch (Exception $e) {
        sendMsg($chatId, "❌ خطأ في قراءة البيانات.\nError reading data.");
    }
}

/**
 * /designer {id or name} — Show tasks for a specific designer
 */
function handleDesignerTasksCommand($chatId, $param) {
    try {
        $pdo = getDBConnection();

        // Try by ID first, then by name
        if (is_numeric($param)) {
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'designer' LIMIT 1");
            $stmt->execute([(int)$param]);
        } else {
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE full_name LIKE ? AND role = 'designer' LIMIT 1");
            $stmt->execute(['%' . $param . '%']);
        }
        $designer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$designer) {
            sendMsg($chatId, "❌ المصمم غير موجود. أرسل /designers لعرض القائمة.\nDesigner not found. Send /designers to see the list.");
            return;
        }

        $tStmt = $pdo->prepare("
            SELECT id, title, client_name, status, deadline, progress_percentage
            FROM tasks
            WHERE assigned_to = ?
            ORDER BY 
                CASE WHEN status = 'new' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END,
                deadline ASC
            LIMIT 20
        ");
        $tStmt->execute([$designer['id']]);
        $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        $statusIcons = ['new' => '🆕', 'in_progress' => '🔄', 'delivered' => '✅'];
        $statusLabels = ['new' => 'جديد', 'in_progress' => 'قيد التنفيذ', 'delivered' => 'تم التسليم'];

        $msg = "🎨 <b>مهام: {$designer['full_name']}</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━\n\n";

        if (empty($tasks)) {
            $msg .= "لا توجد مهام لهذا المصمم.\nNo tasks for this designer.";
        } else {
            $pending = 0;
            foreach ($tasks as $i => $t) {
                $icon = $statusIcons[$t['status']] ?? '📌';
                $label = $statusLabels[$t['status']] ?? $t['status'];
                $deadline = $t['deadline'] ? " | 📅 {$t['deadline']}" : '';
                $overdue = '';
                if ($t['deadline'] && strtotime($t['deadline']) < strtotime('today') && $t['status'] !== 'delivered') {
                    $overdue = ' ⚠️';
                }
                if ($t['status'] !== 'delivered') $pending++;

                $msg .= ($i + 1) . ". {$icon} <b>{$t['title']}</b>\n";
                $msg .= "   👤 {$t['client_name']} | {$label} ({$t['progress_percentage']}%){$deadline}{$overdue}\n\n";
            }
            $msg .= "━━━━━━━━━━━━━━━━\n";
            $msg .= "📊 المعلّقة: <b>{$pending}</b> | الإجمالي: <b>" . count($tasks) . "</b>";
        }

        sendMsg($chatId, $msg);

    } catch (Exception $e) {
        sendMsg($chatId, "❌ خطأ في قراءة البيانات.\nError reading data.");
    }
}

// ========== TELEGRAM API ==========

function sendMsg($chatId, $text) {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage';
    $data = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

http_response_code(200);
