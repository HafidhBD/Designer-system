<?php
/**
 * Language Handler
 * Loads the appropriate language file based on session preference.
 */

function loadLanguage() {
    // Check if language is being switched
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    // Default to English if not set
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'en';
    }

    $langFile = __DIR__ . '/../config/lang_' . $_SESSION['lang'] . '.php';
    
    if (file_exists($langFile)) {
        require $langFile;
    } else {
        require __DIR__ . '/../config/lang_en.php';
    }

    return $lang;
}

/**
 * Get current language code
 */
function getCurrentLang() {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Get text direction
 */
function getDirection() {
    return (getCurrentLang() === 'ar') ? 'rtl' : 'ltr';
}

/**
 * Translate a key
 */
function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
