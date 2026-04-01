<?php
/**
 * Manager Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$pageTitle = __('nav_dashboard');

// Get task counts
$counts = getTaskCounts();

// Get recent tasks (last 10)
$pdo = getDBConnection();
$stmt = $pdo->query("
    SELECT t.*, u.full_name AS designer_name 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
");
$recentTasks = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon total">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= (int)$counts['total'] ?></div>
            <div class="kpi-label"><?= __('total_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon new">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= (int)$counts['new_count'] ?></div>
            <div class="kpi-label"><?= __('new_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon progress">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= (int)$counts['in_progress_count'] ?></div>
            <div class="kpi-label"><?= __('in_progress_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon delivered">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= (int)$counts['delivered_count'] ?></div>
            <div class="kpi-label"><?= __('delivered_tasks') ?></div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title"><?= __('quick_actions') ?></h2>
    </div>
    <div class="quick-actions">
        <a href="/manager/create_task.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= __('create_new_task') ?>
        </a>
        <a href="/manager/all_tasks.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <?= __('view_all_tasks') ?>
        </a>
        <a href="/manager/reports.php" class="quick-action-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            <?= __('view_reports') ?>
        </a>
    </div>
</div>

<!-- Recent Tasks Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('recent_tasks') ?></h2>
        <a href="/manager/all_tasks.php" class="btn btn-sm btn-outline"><?= __('view_all_tasks') ?></a>
    </div>

    <?php if (empty($recentTasks)): ?>
    <div class="empty-state">
        <p><?= __('no_tasks') ?></p>
        <a href="/manager/create_task.php" class="btn btn-primary"><?= __('create_new_task') ?></a>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th><?= __('task_title') ?></th>
                    <th><?= __('client_name') ?></th>
                    <th><?= __('assigned_to') ?></th>
                    <th><?= __('design_type') ?></th>
                    <th><?= __('deadline') ?></th>
                    <th><?= __('progress') ?></th>
                    <th><?= __('status') ?></th>
                    <th><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTasks as $task): ?>
                <tr>
                    <td><strong><?= sanitize($task['title']) ?></strong></td>
                    <td><?= sanitize($task['client_name']) ?></td>
                    <td><?= sanitize($task['designer_name']) ?></td>
                    <td><?= getDesignTypeLabel($task['design_type']) ?></td>
                    <td>
                        <?php if ($task['deadline']): ?>
                            <span class="<?= isOverdue($task['deadline']) && $task['status'] !== 'delivered' ? 'deadline-overdue' : (isDueToday($task['deadline']) ? 'deadline-today' : '') ?>">
                                <?= formatDate($task['deadline']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="progress-bar-container <?= getProgressColor($task['progress_percentage']) ?>">
                            <div class="progress-bar" style="width: <?= (int)$task['progress_percentage'] ?>%"></div>
                        </div>
                        <span class="progress-text"><?= (int)$task['progress_percentage'] ?>%</span>
                    </td>
                    <td><span class="badge <?= getStatusClass($task['status']) ?>"><?= getStatusLabel($task['status']) ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="/manager/edit_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-outline"><?= __('edit') ?></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
