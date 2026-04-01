<?php
/**
 * Designer Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireDesigner();

$pageTitle = __('nav_dashboard');
$currentUser = getCurrentUser();

// Get task counts for this designer
$counts = getTaskCounts($currentUser['id']);

// Get recent tasks for this designer
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT t.*, u.full_name AS creator_name 
    FROM tasks t 
    LEFT JOIN users u ON t.created_by = u.id 
    WHERE t.assigned_to = ?
    ORDER BY 
        CASE WHEN t.status = 'new' THEN 0 
             WHEN t.status = 'in_progress' THEN 1 
             ELSE 2 END,
        t.deadline ASC,
        t.created_at DESC
    LIMIT 10
");
$stmt->execute([$currentUser['id']]);
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

<!-- My Tasks -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('nav_my_tasks') ?></h2>
        <a href="/designer/my_tasks.php" class="btn btn-sm btn-outline"><?= __('view_all_tasks') ?></a>
    </div>

    <?php if (empty($recentTasks)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p><?= __('no_tasks') ?></p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th><?= __('task_title') ?></th>
                    <th><?= __('client_name') ?></th>
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
                    <td><?= getDesignTypeLabel($task['design_type']) ?></td>
                    <td>
                        <?php if ($task['deadline']): ?>
                            <span class="<?= isOverdue($task['deadline']) && $task['status'] !== 'delivered' ? 'deadline-overdue' : (isDueToday($task['deadline']) ? 'deadline-today' : '') ?>">
                                <?= formatDate($task['deadline']) ?>
                            </span>
                            <?php if (isOverdue($task['deadline']) && $task['status'] !== 'delivered'): ?>
                                <span class="badge badge-overdue"><?= __('overdue') ?></span>
                            <?php endif; ?>
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
                        <a href="/designer/my_tasks.php?update=<?= $task['id'] ?>" class="btn btn-sm btn-primary"><?= __('update_status') ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
