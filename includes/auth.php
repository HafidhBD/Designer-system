<?php
/**
 * Authentication & Authorization Helper
 */

session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/language.php';

// Load language
$lang = loadLanguage();

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
        'role'      => $_SESSION['role'],
    ];
}

/**
 * Check if current user is a manager
 */
function isManager() {
    return isLoggedIn() && $_SESSION['role'] === ROLE_MANAGER;
}

/**
 * Check if current user is a designer
 */
function isDesigner() {
    return isLoggedIn() && $_SESSION['role'] === ROLE_DESIGNER;
}

/**
 * Check if current user is a supervisor
 */
function isSupervisor() {
    return isLoggedIn() && $_SESSION['role'] === ROLE_SUPERVISOR;
}

/**
 * Require login — redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require manager role
 */
function requireManager() {
    requireLogin();
    if (!isManager()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Require designer role
 */
function requireDesigner() {
    requireLogin();
    if (!isDesigner()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Require manager or supervisor role
 */
function requireManagerOrSupervisor() {
    requireLogin();
    if (!isManager() && !isSupervisor()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Authenticate user
 */
function authenticate($email, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role, language_preference FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        if ($user['language_preference']) {
            $_SESSION['lang'] = $user['language_preference'];
        }
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        return true;
    }
    return false;
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: /login.php');
    exit;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
