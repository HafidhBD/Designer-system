<?php
/**
 * Notifications Page — All Users
 * Displays all notifications with pagination.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/notifications.php';

requireLogin();

$pageTitle = __('notifications');
$currentUser = getCurrentUser();

// Handle mark all as read
if (isset($_GET['mark_all'])) {
    markAllNotificationsRead($currentUser['id']);
    header('Location: /notifications.php');
    exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$data = getAllNotifications($currentUser['id'], $page, 20);

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('notifications') ?> (<?= $data['total'] ?>)</h2>
        <?php if ($data['total'] > 0): ?>
        <a href="/notifications.php?mark_all=1" class="btn btn-sm btn-outline"><?= __('mark_all_read') ?></a>
        <?php endif; ?>
    </div>

    <?php if (empty($data['notifications'])): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:var(--text-muted);">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>
        </svg>
        <p><?= __('no_notifications') ?></p>
    </div>
    <?php else: ?>
    <div>
        <?php foreach ($data['notifications'] as $notif): ?>
        <?php
            $link = $notif['link'] ?: '#';
            $isUnread = !(int)$notif['is_read'];
        ?>
        <a href="<?= sanitize($link) ?>" class="notif-page-link" <?php if ($isUnread): ?>onclick="fetch('/api/notifications.php?action=read',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id=<?= $notif['id'] ?>'})"<?php endif; ?>>
            <div class="notif-page-item <?= $isUnread ? 'unread' : '' ?>">
                <div class="notif-page-icon"><?= getNotificationIcon($notif['type']) ?></div>
                <div class="notif-page-content">
                    <div class="notif-page-title"><?= sanitize($notif['title']) ?></div>
                    <div class="notif-page-message"><?= sanitize($notif['message']) ?></div>
                    <div class="notif-page-time"><?= timeAgo($notif['created_at']) ?></div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($data['pages'] > 1): ?>
    <div style="padding:16px;display:flex;justify-content:center;gap:8px;">
        <?php if ($page > 1): ?>
        <a href="/notifications.php?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline">&laquo; <?= __('back') ?></a>
        <?php endif; ?>
        <span class="btn btn-sm" style="background:var(--primary-light);color:var(--primary);"><?= $page ?> / <?= $data['pages'] ?></span>
        <?php if ($page < $data['pages']): ?>
        <a href="/notifications.php?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline"><?= __('view') ?> &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
