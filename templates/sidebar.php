<?php
/**
 * Sidebar Navigation Template
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/index.php" class="sidebar-brand">
            <img src="/logo.gif" alt="Logo" class="logo-img">
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?= ($currentPage === 'index' && $currentDir !== 'manager' && $currentDir !== 'designer') ? 'active' : '' ?>">
                <a href="/index.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span><?= __('nav_dashboard') ?></span>
                </a>
            </li>

            <?php if (isManager()): ?>
            <li class="nav-item <?= ($currentPage === 'create_task') ? 'active' : '' ?>">
                <a href="/manager/create_task.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span><?= __('nav_create_task') ?></span>
                </a>
            </li>
            <li class="nav-item <?= ($currentPage === 'all_tasks' || $currentPage === 'edit_task' || $currentPage === 'view_task') ? 'active' : '' ?>">
                <a href="/manager/all_tasks.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span><?= __('nav_all_tasks') ?></span>
                </a>
            </li>
            <li class="nav-item <?= ($currentPage === 'reports') ? 'active' : '' ?>">
                <a href="/manager/reports.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span><?= __('nav_reports') ?></span>
                </a>
            </li>
            <li class="nav-item <?= ($currentPage === 'users' || $currentPage === 'edit_user') ? 'active' : '' ?>">
                <a href="/manager/users.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    <span><?= __('nav_users') ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (isDesigner()): ?>
            <li class="nav-item <?= ($currentPage === 'my_tasks') ? 'active' : '' ?>">
                <a href="/designer/my_tasks.php" class="nav-link">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span><?= __('nav_my_tasks') ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="/logout.php" class="nav-link logout-link">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span><?= __('logout') ?></span>
        </a>
    </div>
</aside>
