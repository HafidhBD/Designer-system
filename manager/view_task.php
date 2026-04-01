<?php
/**
 * View Task Details — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$taskId = (int)($_GET['id'] ?? 0);
if (!$taskId) {
    header('Location: /manager/all_tasks.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.full_name AS designer_name, 
           c.full_name AS creator_name 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    LEFT JOIN users c ON t.created_by = c.id 
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    redirectWith('/manager/all_tasks.php', 'error', __('error'));
}

// Get status logs
$logStmt = $pdo->prepare("
    SELECT l.*, u.full_name AS changed_by_name 
    FROM task_status_logs l 
    LEFT JOIN users u ON l.changed_by = u.id 
    WHERE l.task_id = ? 
    ORDER BY l.changed_at DESC
");
$logStmt->execute([$taskId]);
$logs = $logStmt->fetchAll();

$pageTitle = __('task_details') . ' #' . $task['id'];

include __DIR__ . '/../templates/header.php';
?>

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title"><?= sanitize($task['title']) ?></h2>
        <div class="btn-group">
            <a href="/manager/edit_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary"><?= __('edit') ?></a>
            <a href="/manager/all_tasks.php" class="btn btn-sm btn-outline"><?= __('back') ?></a>
        </div>
    </div>

    <div class="task-detail-grid">
        <div>
            <div class="detail-item">
                <div class="detail-label"><?= __('task_title') ?></div>
                <div class="detail-value"><?= sanitize($task['title']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('client_name') ?></div>
                <div class="detail-value"><?= sanitize($task['client_name']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('design_type') ?></div>
                <div class="detail-value"><?= getDesignTypeLabel($task['design_type']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('assigned_to') ?></div>
                <div class="detail-value"><?= sanitize($task['designer_name']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('created_by') ?></div>
                <div class="detail-value"><?= sanitize($task['creator_name']) ?></div>
            </div>
        </div>
        <div>
            <div class="detail-item">
                <div class="detail-label"><?= __('status') ?></div>
                <div class="detail-value"><span class="badge <?= getStatusClass($task['status']) ?>"><?= getStatusLabel($task['status']) ?></span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('progress') ?></div>
                <div class="detail-value">
                    <div class="d-flex align-center gap-1">
                        <div class="progress-bar-container <?= getProgressColor($task['progress_percentage']) ?>" style="max-width:150px;">
                            <div class="progress-bar" style="width: <?= (int)$task['progress_percentage'] ?>%"></div>
                        </div>
                        <span><?= (int)$task['progress_percentage'] ?>%</span>
                    </div>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('deadline') ?></div>
                <div class="detail-value">
                    <?php if ($task['deadline']): ?>
                        <span class="<?= isOverdue($task['deadline']) && $task['status'] !== 'delivered' ? 'deadline-overdue' : '' ?>">
                            <?= formatDate($task['deadline']) ?>
                        </span>
                        <?php if (isOverdue($task['deadline']) && $task['status'] !== 'delivered'): ?>
                            <span class="badge badge-overdue"><?= __('overdue') ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('created_at') ?></div>
                <div class="detail-value"><?= formatDateTime($task['created_at']) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label"><?= __('updated_at') ?></div>
                <div class="detail-value"><?= formatDateTime($task['updated_at']) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($task['notes'])): ?>
    <div class="mt-2">
        <div class="detail-label"><?= __('notes') ?></div>
        <div class="detail-value" style="white-space: pre-wrap;"><?= sanitize($task['notes']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Design File -->
    <div class="mt-2" style="border-top:1px solid var(--border);padding-top:16px;">
        <div class="detail-label"><?= __('design_file') ?></div>
        <div class="detail-value">
            <?php if (!empty($task['file_path'])): ?>
                <?php
                    $fileUrl = UPLOAD_URL . $task['file_path'];
                    $fileExt = strtolower(pathinfo($task['file_path'], PATHINFO_EXTENSION));
                    $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                ?>
                <?php if ($isImage): ?>
                <div class="mb-2">
                    <img src="<?= sanitize($fileUrl) ?>" alt="Design" style="max-width:100%;max-height:400px;border-radius:8px;border:1px solid var(--border);">
                </div>
                <?php endif; ?>
                <a href="<?= sanitize($fileUrl) ?>" target="_blank" class="btn btn-sm btn-primary" download>
                    📎 <?= __('download_file') ?> (<?= sanitize($task['file_path']) ?>)
                </a>
            <?php else: ?>
                <span style="color:var(--text-secondary);"><?= __('no_file_uploaded') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($logs)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('status') ?> Log</h2>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>From</th>
                    <th>To</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= count($logs) - $i ?></td>
                    <td><?= $log['old_status'] ? getStatusLabel($log['old_status']) : '—' ?></td>
                    <td><span class="badge <?= getStatusClass($log['new_status']) ?>"><?= getStatusLabel($log['new_status']) ?></span></td>
                    <td><?= sanitize($log['changed_by_name']) ?></td>
                    <td class="text-small"><?= formatDateTime($log['changed_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
