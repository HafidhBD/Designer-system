<?php
/**
 * Telegram Bot Webhook Handler
 * 
 * This script handles incoming messages from Telegram.
 * When a user sends /start, the bot replies with their Chat ID.
 * 
 * SETUP:
 * 1. Upload this file to your server
 * 2. Visit: https://yourdomain.com/telegram_setup.php to register the webhook
 * 3. The bot will now respond to /start with the Chat ID
 */

// Bot token
define('BOT_TOKEN', '8640672656:AAEG4SVN2yHqnTZT6eYP45q5XqnWAsmPdvQ');

// Get the incoming update from Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$chatId = $update['message']['chat']['id'];
$text   = trim($update['message']['text'] ?? '');
$firstName = $update['message']['from']['first_name'] ?? '';

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
    $msg .= "📋 Copy this number and enter it in the User Management page.";

    sendMessage($chatId, $msg);
} elseif ($text === '/id') {
    sendMessage($chatId, "🆔 Your Chat ID: <code>{$chatId}</code>");
} else {
    $msg  = "🤖 هذا بوت إشعارات فقط.\n";
    $msg .= "أرسل /start للحصول على معرّف المحادثة.\n\n";
    $msg .= "This is a notification-only bot.\nSend /start to get your Chat ID.";
    sendMessage($chatId, $msg);
}

function sendMessage($chatId, $text) {
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
