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
    <link rel="icon" href="/logo-white.png" type="image/png">
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
            <img src="/logo-white.png" alt="Logo" class="logo-img-sm">
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

                <!-- Notification Bell -->
                <div class="notif-wrapper" id="notifWrapper" style="position:relative;">
                    <button class="notif-bell" id="notifBell" type="button" title="<?= __('notifications') ?>" style="background:none;border:1px solid #e2e8f0;cursor:pointer;position:relative;width:38px;height:38px;border-radius:50%;color:#64748b;display:flex;align-items:center;justify-content:center;transition:all 0.2s ease;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                        <span id="notifBadge" style="display:none;position:absolute;top:-2px;right:-2px;background:#EF4444;color:#fff;font-size:0.6rem;font-weight:700;min-width:18px;height:18px;border-radius:9px;align-items:center;justify-content:center;padding:0 4px;line-height:1;border:2px solid #fff;">0</span>
                    </button>
                    <div id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);<?= $dir === 'rtl' ? 'left:0;' : 'right:0;' ?>width:360px;max-height:480px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.12),0 4px 10px rgba(0,0,0,0.08);z-index:9999;overflow:hidden;flex-direction:column;">
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                            <strong style="font-size:0.95rem;color:#1e293b;"><?= __('notifications') ?></strong>
                            <button id="notifMarkAll" type="button" style="background:none;border:none;color:#4F46E5;font-size:0.78rem;cursor:pointer;padding:4px 10px;border-radius:4px;transition:all 0.2s;"><?= __('mark_all_read') ?></button>
                        </div>
                        <div id="notifList" style="overflow-y:auto;max-height:340px;" data-empty="<?= __('no_notifications') ?>">
                            <div style="padding:40px 16px;text-align:center;color:#94a3b8;font-size:0.88rem;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 10px;display:block;"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                                <?= __('no_notifications') ?>
                            </div>
                        </div>
                        <a href="/notifications.php" style="display:block;text-align:center;padding:12px;font-size:0.82rem;font-weight:600;color:#4F46E5;border-top:1px solid #e2e8f0;text-decoration:none;transition:all 0.2s;"><?= __('view_all_notifications') ?></a>
                    </div>
                </div>

                <div class="user-info">
                    <span class="user-name"><?= sanitize($currentUser['full_name']) ?></span>
                    <span class="user-role badge badge-role"><?= isManager() ? __('role_manager') : (isSupervisor() ? __('role_supervisor') : __('role_designer')) ?></span>
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
