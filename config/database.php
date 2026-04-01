<?php
/**
 * Database Configuration
 * 
 * Update these values with your actual Hostinger database credentials.
 * NEVER expose this file publicly.
 */

define('DB_HOST', 'localhost');           // Hostinger DB host (usually localhost or specific host)
define('DB_NAME', 'u983353360_Designers'); // Your database name on Hostinger
define('DB_USER', 'u983353360_Designers');
define('DB_PASS', 'jMemyT6H8Q');
define('DB_CHARSET', 'utf8mb4');

/**
 * Create PDO connection
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
                PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please contact the administrator.");
        }
    }
    return $pdo;
}
