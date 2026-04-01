<?php
/**
 * Telegram Notification Helper
 * Sends notifications via Telegram Bot API
 */

/**
 * Send a message via Telegram Bot
 */
function sendTelegram($chatId, $message) {
    if (empty($chatId) || empty(TELEGRAM_BOT_TOKEN)) return false;

    $url = TELEGRAM_API_URL . '/sendMessage';
    $data = [
        'chat_id'    => $chatId,
        'text'       => $message,
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
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return true;
    }
    error_log("Telegram send failed: HTTP $httpCode — $result");
    return false;
}

/**
 * Send a document/file via Telegram Bot
 */
function sendTelegramDocument($chatId, $filePath, $caption = '') {
    if (empty($chatId) || empty(TELEGRAM_BOT_TOKEN) || !file_exists($filePath)) return false;

    $url = TELEGRAM_API_URL . '/sendDocument';
    $data = [
        'chat_id'  => $chatId,
        'document' => new CURLFile($filePath),
        'caption'  => $caption,
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return true;
    }
    error_log("Telegram document send failed: HTTP $httpCode — $result");
    return false;
}

/**
 * Notify designer about a new task assignment
 */
function notifyDesignerNewTask($designerId, $taskTitle, $clientName, $designType, $deadline) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT telegram_chat_id, language_preference FROM users WHERE id = ?");
    $stmt->execute([$designerId]);
    $designer = $stmt->fetch();

    if (!$designer || empty($designer['telegram_chat_id'])) return false;

    $isAr = ($designer['language_preference'] === 'ar');

    if ($isAr) {
        $msg  = "📋 <b>مهمة جديدة!</b>\n\n";
        $msg .= "📌 العنوان: <b>{$taskTitle}</b>\n";
        $msg .= "👤 العميل: {$clientName}\n";
        $msg .= "🎨 النوع: {$designType}\n";
        if ($deadline) {
            $msg .= "📅 الموعد النهائي: {$deadline}\n";
        }
        $msg .= "\n🔗 سجل دخولك للنظام لعرض التفاصيل.";
    } else {
        $msg  = "📋 <b>New Task Assigned!</b>\n\n";
        $msg .= "📌 Title: <b>{$taskTitle}</b>\n";
        $msg .= "👤 Client: {$clientName}\n";
        $msg .= "🎨 Type: {$designType}\n";
        if ($deadline) {
            $msg .= "📅 Deadline: {$deadline}\n";
        }
        $msg .= "\n🔗 Log in to the system to view details.";
    }

    return sendTelegram($designer['telegram_chat_id'], $msg);
}

/**
 * Notify manager(s) that a designer completed a task
 */
function notifyManagerTaskDelivered($designerName, $taskTitle, $taskId, $filePath = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, telegram_chat_id, language_preference FROM users WHERE role = 'manager' AND telegram_chat_id IS NOT NULL AND telegram_chat_id != ''");
    $managers = $stmt->fetchAll();

    foreach ($managers as $manager) {
        $isAr = ($manager['language_preference'] === 'ar');

        if ($isAr) {
            $msg  = "✅ <b>تم تسليم مهمة!</b>\n\n";
            $msg .= "👤 المصمم: <b>{$designerName}</b>\n";
            $msg .= "📌 المهمة: {$taskTitle}\n";
            $msg .= "🆔 رقم المهمة: #{$taskId}\n";
            $msg .= "\n🔗 سجل دخولك للنظام لعرض التصميم.";
        } else {
            $msg  = "✅ <b>Task Delivered!</b>\n\n";
            $msg .= "👤 Designer: <b>{$designerName}</b>\n";
            $msg .= "📌 Task: {$taskTitle}\n";
            $msg .= "🆔 Task ID: #{$taskId}\n";
            $msg .= "\n🔗 Log in to the system to view the design.";
        }

        // Send message first
        sendTelegram($manager['telegram_chat_id'], $msg);

        // If there's a file, send it too
        if ($filePath && file_exists($filePath)) {
            $caption = $isAr ? "📎 ملف التصميم — {$taskTitle}" : "📎 Design File — {$taskTitle}";
            sendTelegramDocument($manager['telegram_chat_id'], $filePath, $caption);
        }
    }
}

/**
 * Notify designer when a task is assigned/updated
 */
function notifyDesignerTaskUpdated($designerId, $taskTitle, $message) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT telegram_chat_id FROM users WHERE id = ?");
    $stmt->execute([$designerId]);
    $designer = $stmt->fetch();

    if (!$designer || empty($designer['telegram_chat_id'])) return false;

    return sendTelegram($designer['telegram_chat_id'], $message);
}
