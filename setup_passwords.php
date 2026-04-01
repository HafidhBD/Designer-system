<?php
/**
 * Password Setup Script
 * 
 * Run this ONCE after importing schema.sql to hash the seed passwords.
 * Then DELETE this file for security.
 * 
 * Visit: https://yourdomain.com/setup_passwords.php
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

$users = [
    ['email' => 'admin@design.com', 'password' => 'Admin@123'],
    ['email' => 'sara@design.com',  'password' => 'Designer@123'],
    ['email' => 'omar@design.com',  'password' => 'Designer@123'],
];

echo "<h2>Setting up passwords...</h2><ul>";

foreach ($users as $user) {
    $hash = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $user['email']]);
    $affected = $stmt->rowCount();
    echo "<li>" . htmlspecialchars($user['email']) . " — " . ($affected ? "✅ Updated" : "⚠️ Not found") . "</li>";
}

echo "</ul>";
echo "<h3 style='color:red;'>⚠️ DELETE this file (setup_passwords.php) immediately after use!</h3>";
echo "<p>Default credentials:</p>";
echo "<ul>";
echo "<li><strong>Manager:</strong> admin@design.com / Admin@123</li>";
echo "<li><strong>Designer:</strong> sara@design.com / Designer@123</li>";
echo "<li><strong>Designer:</strong> omar@design.com / Designer@123</li>";
echo "</ul>";
