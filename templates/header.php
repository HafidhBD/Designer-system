<?php
/**
 * Header Template
 */
$currentUser = getCurrentUser();
$dir = getDirection();
$currentLang = getCurrentLang();
$switchLang = ($currentLang === 'en') ? 'ar' : 'en';
$pageName = isset($pageTitle) ? $pageTitle : __('app_name');
$htmlTitle = isset($pageTitle) ? $pageTitle . ' - ' . __('app_name') : __('app_name');

// Build lang switch URL preserving current page
$langSwitchUrl = strtok($_SERVER['REQUEST_URI'], '?');
$queryParams = $_GET;
$queryParams['lang'] = $switchLang;
$langSwitchUrl .= '?' . http_build_query($queryParams);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($htmlTitle) ?></title>
    <link rel="icon" href="/logo.gif" type="image/gif">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($dir === 'rtl'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="<?= $dir ?>">
<?php if (isLoggedIn()): ?>
<div class="app-layout">
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <div class="mobile-logo">
            <img src="/logo.gif" alt="Logo" class="logo-img-sm">
        </div>
        <a href="<?= $langSwitchUrl ?>" class="lang-toggle-mobile"><?= __('nav_language') ?></a>
    </div>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1 class="page-title"><?= sanitize($pageName) ?></h1>
            </div>
            <div class="top-bar-right">
                <a href="<?= $langSwitchUrl ?>" class="btn btn-sm btn-outline lang-switch"><?= __('nav_language') ?></a>
                <div class="user-info">
                    <span class="user-name"><?= sanitize($currentUser['full_name']) ?></span>
                    <span class="user-role badge badge-role"><?= isManager() ? __('role_manager') : __('role_designer') ?></span>
                </div>
                <a href="/logout.php" class="btn btn-sm btn-danger"><?= __('logout') ?></a>
            </div>
        </div>

        <?php
        // Flash messages
        $flash = getFlash();
        if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= sanitize($flash['message']) ?>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <div class="content-wrapper">
<?php endif; ?>
