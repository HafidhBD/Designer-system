<?php
/**
 * Telegram Webhook Setup
 * 
 * Visit this page ONCE to register the webhook with Telegram.
 * DELETE this file after setup!
 */

$botToken = '8640672656:AAEG4SVN2yHqnTZT6eYP45q5XqnWAsmPdvQ';

// Auto-detect domain
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$webhookUrl = $protocol . '://' . $domain . '/telegram_webhook.php';

$action = $_GET['action'] ?? 'info';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Telegram Bot Setup</title>";
echo "<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;background:#f5f5f5;}";
echo ".box{background:#fff;padding:20px;border-radius:8px;margin:16px 0;border:1px solid #ddd;}";
echo "code{background:#e8e8e8;padding:2px 8px;border-radius:4px;font-size:14px;}";
echo ".btn{display:inline-block;padding:12px 24px;border-radius:6px;text-decoration:none;color:#fff;margin:6px;}";
echo ".btn-green{background:#22c55e;} .btn-red{background:#ef4444;} .btn-blue{background:#3b82f6;}";
echo ".ok{color:#22c55e;font-weight:bold;} .err{color:#ef4444;font-weight:bold;}";
echo "</style></head><body>";
echo "<h1>🤖 Telegram Bot Setup</h1>";

if ($action === 'set') {
    // Register webhook
    $url = "https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($webhookUrl);
    $result = json_decode(file_get_contents($url), true);
    
    echo "<div class='box'>";
    if ($result && $result['ok']) {
        echo "<p class='ok'>✅ Webhook registered successfully!</p>";
        echo "<p>URL: <code>{$webhookUrl}</code></p>";
        echo "<p>The bot will now respond to /start with the Chat ID.</p>";
    } else {
        echo "<p class='err'>❌ Failed to register webhook</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
        echo "<p><strong>Note:</strong> Telegram requires HTTPS. Make sure your domain has SSL.</p>";
    }
    echo "</div>";

} elseif ($action === 'remove') {
    // Remove webhook
    $url = "https://api.telegram.org/bot{$botToken}/deleteWebhook";
    $result = json_decode(file_get_contents($url), true);
    
    echo "<div class='box'>";
    if ($result && $result['ok']) {
        echo "<p class='ok'>✅ Webhook removed.</p>";
    } else {
        echo "<p class='err'>❌ Failed</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    }
    echo "</div>";

} elseif ($action === 'status') {
    // Check webhook status
    $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
    $result = json_decode(file_get_contents($url), true);
    
    echo "<div class='box'>";
    echo "<h3>Webhook Status</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    echo "</div>";
}

echo "<div class='box'>";
echo "<h3>Webhook URL</h3>";
echo "<p><code>{$webhookUrl}</code></p>";
echo "<p><strong>⚠️ Telegram requires HTTPS!</strong> Make sure SSL is active on your domain.</p>";
echo "</div>";

echo "<div class='box'>";
echo "<h3>Actions</h3>";
echo "<a href='?action=set' class='btn btn-green'>✅ Register Webhook</a> ";
echo "<a href='?action=status' class='btn btn-blue'>ℹ️ Check Status</a> ";
echo "<a href='?action=remove' class='btn btn-red'>🗑 Remove Webhook</a>";
echo "</div>";

echo "<div class='box' style='background:#fff3cd;border-color:#ffc107;'>";
echo "<strong>⚠️ DELETE this file (telegram_setup.php) after registering the webhook!</strong>";
echo "</div>";

echo "</body></html>";
