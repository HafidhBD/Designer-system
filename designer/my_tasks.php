<?php
/**
 * My Tasks Page — Designer Only
 * Designers can view their tasks and update status/progress.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/telegram.php';
require_once __DIR__ . '/../includes/notifications.php';

requireDesigner();

$pageTitle = __('nav_my_tasks');
$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle status/progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', __('error'));
    } else {
        $taskId    = (int)$_POST['task_id'];
        $newStatus = $_POST['status'] ?? '';
        $progress  = (int)($_POST['progress_percentage'] ?? 0);

        // Verify task belongs to this designer
        $checkStmt = $pdo->prepare("SELECT id, title, status FROM tasks WHERE id = ? AND assigned_to = ?");
        $checkStmt->execute([$taskId, $currentUser['id']]);
        $task = $checkStmt->fetch();

        if ($task) {
            // Designers can change to in_progress or delivered
            $allowedStatuses = ['in_progress', 'delivered'];
            if (!in_array($newStatus, $allowedStatuses)) {
                $newStatus = $task['status'];
            }
            if (!in_array($progress, PROGRESS_OPTIONS)) {
                $progress = 0;
            }

            // If delivering, file upload is required
            $uploadedFile = null;
            if ($newStatus === 'delivered') {
                if (empty($_FILES['design_file']) || $_FILES['design_file']['error'] === UPLOAD_ERR_NO_FILE) {
                    setFlash('error', __('upload_required'));
                    header('Location: /designer/my_tasks.php?update=' . $taskId);
                    exit;
                }
                $uploadResult = handleDesignUpload($_FILES['design_file'], $taskId);
                if (!$uploadResult['success']) {
                    setFlash('error', $uploadResult['error']);
                    header('Location: /designer/my_tasks.php?update=' . $taskId);
                    exit;
                }
                $uploadedFile = $uploadResult['filename'];
                $progress = 100;
            }

            // Update task
            if ($uploadedFile) {
                $updateStmt = $pdo->prepare("UPDATE tasks SET status = ?, progress_percentage = ?, file_path = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$newStatus, $progress, $uploadedFile, $taskId]);
            } else {
                $updateStmt = $pdo->prepare("UPDATE tasks SET status = ?, progress_percentage = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$newStatus, $progress, $taskId]);
            }

            if ($newStatus !== $task['status']) {
                logStatusChange($taskId, $task['status'], $newStatus, $currentUser['id']);
            }

            // Notify manager via Telegram when task is delivered
            if ($newStatus === 'delivered') {
                $filePath = $uploadedFile ? UPLOAD_DIR . $uploadedFile : null;
                notifyManagerTaskDelivered($currentUser['full_name'], $task['title'], $taskId, $filePath);

                // In-app notification to all managers
                notifyAllManagers(
                    'task_delivered',
                    __('notif_task_delivered'),
                    $currentUser['full_name'] . ' — ' . $task['title'],
                    '/manager/view_task.php?id=' . $taskId
                );
            }

            // Check if AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => __('task_updated'),
                    'status_html' => '<span class="badge ' . getStatusClass($newStatus) . '">' . getStatusLabel($newStatus) . '</span>',
                    'progress' => $progress
                ]);
                exit;
            }

            setFlash('success', __('task_updated'));
        } else {
            setFlash('error', __('error'));
        }
    }
    header('Location: /designer/my_tasks.php');
    exit;
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['design_type'] ?? '';

// Build query
$where = ["t.assigned_to = ?"];
$params = [$currentUser['id']];

if ($filterStatus) {
    $where[] = "t.status = ?";
    $params[] = $filterStatus;
}
if ($filterType) {
    $where[] = "t.design_type = ?";
    $params[] = $filterType;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT t.* 
    FROM tasks t 
    $whereSQL
    ORDER BY 
        CASE WHEN t.status = 'new' THEN 0 
             WHEN t.status = 'in_progress' THEN 1 
             ELSE 2 END,
        t.deadline ASC,
        t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Check if we have a specific task to update (modal)
$updateTaskId = (int)($_GET['update'] ?? 0);
$updateTask = null;
if ($updateTaskId) {
    $uStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND assigned_to = ?");
    $uStmt->execute([$updateTaskId, $currentUser['id']]);
    $updateTask = $uStmt->fetch();
}

include __DIR__ . '/../templates/header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="form-group mb-1">
                <label class="form-label"><?= __('status') ?></label>
                <select name="status" class="form-control">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach (TASK_STATUSES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= $labels[getCurrentLang()] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('design_type') ?></label>
                <select name="design_type" class="form-control">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach (DESIGN_TYPES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= $filterType === $key ? 'selected' : '' ?>><?= $labels[getCurrentLang()] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><?= __('apply_filter') ?></button>
                <a href="/designer/my_tasks.php" class="btn btn-outline btn-sm"><?= __('clear_filter') ?></a>
            </div>
        </div>
    </form>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('nav_my_tasks') ?> (<?= count($tasks) ?>)</h2>
    </div>

    <?php if (empty($tasks)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p><?= __('no_tasks') ?></p>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('task_title') ?></th>
                    <th><?= __('client_name') ?></th>
                    <th><?= __('design_type') ?></th>
                    <th><?= __('deadline') ?></th>
                    <th><?= __('notes') ?></th>
                    <th><?= __('progress') ?></th>
                    <th><?= __('status') ?></th>
                    <th><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= $task['id'] ?></td>
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
                    <td class="text-small"><?= sanitize(mb_substr($task['notes'] ?? '', 0, 60)) ?><?= mb_strlen($task['notes'] ?? '') > 60 ? '...' : '' ?></td>
                    <td>
                        <div class="progress-bar-container <?= getProgressColor($task['progress_percentage']) ?>">
                            <div class="progress-bar" style="width: <?= (int)$task['progress_percentage'] ?>%"></div>
                        </div>
                        <span class="progress-text"><?= (int)$task['progress_percentage'] ?>%</span>
                    </td>
                    <td><span class="badge status-badge <?= getStatusClass($task['status']) ?>"><?= getStatusLabel($task['status']) ?></span></td>
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

<!-- Update Task Modal -->
<?php if ($updateTask): ?>
<div class="modal-overlay active" id="updateModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><?= __('update_status') ?> — <?= sanitize($updateTask['title']) ?></h3>
            <a href="/designer/my_tasks.php" class="modal-close">&times;</a>
        </div>
        <form method="POST" action="/designer/my_tasks.php" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="task_id" value="<?= $updateTask['id'] ?>">
            <div class="modal-body">
                <!-- Task Info -->
                <div class="mb-2">
                    <div class="detail-item">
                        <div class="detail-label"><?= __('client_name') ?></div>
                        <div class="detail-value"><?= sanitize($updateTask['client_name']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><?= __('design_type') ?></div>
                        <div class="detail-value"><?= getDesignTypeLabel($updateTask['design_type']) ?></div>
                    </div>
                    <?php if ($updateTask['deadline']): ?>
                    <div class="detail-item">
                        <div class="detail-label"><?= __('deadline') ?></div>
                        <div class="detail-value">
                            <span class="<?= isOverdue($updateTask['deadline']) ? 'deadline-overdue' : '' ?>">
                                <?= formatDate($updateTask['deadline']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($updateTask['notes']): ?>
                    <div class="detail-item">
                        <div class="detail-label"><?= __('notes') ?></div>
                        <div class="detail-value text-small" style="white-space:pre-wrap;"><?= sanitize($updateTask['notes']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Status Update -->
                <div class="form-group">
                    <label class="form-label" for="modal_status"><?= __('status') ?></label>
                    <select id="modal_status" name="status" class="form-control" onchange="toggleUploadField()">
                        <option value="in_progress" <?= $updateTask['status'] === 'in_progress' ? 'selected' : '' ?>><?= getStatusLabel('in_progress') ?></option>
                        <option value="delivered" <?= $updateTask['status'] === 'delivered' ? 'selected' : '' ?>><?= getStatusLabel('delivered') ?></option>
                    </select>
                </div>

                <!-- Design File Upload (shown only when status = delivered) -->
                <div class="form-group" id="uploadField" style="display:<?= $updateTask['status'] === 'delivered' ? 'block' : 'none' ?>;">
                    <label class="form-label" for="design_file"><?= __('upload_design') ?> *</label>
                    <input type="file" id="design_file" name="design_file" class="form-control"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.ai,.psd,.svg,.eps,.zip,.rar,.mp4,.mov,.webp">
                    <span class="form-hint"><?= __('upload_required') ?></span>
                    <?php if (!empty($updateTask['file_path'])): ?>
                    <div class="mt-1" style="font-size:0.85rem;color:var(--text-secondary);">
                        📎 <?= __('design_file') ?>: <a href="<?= UPLOAD_URL . sanitize($updateTask['file_path']) ?>" target="_blank"><?= sanitize($updateTask['file_path']) ?></a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Progress Update -->
                <div class="form-group" id="progressField">
                    <label class="form-label" for="modal_progress"><?= __('progress') ?></label>
                    <select id="modal_progress" name="progress_percentage" class="form-control">
                        <?php foreach (PROGRESS_OPTIONS as $opt): ?>
                        <option value="<?= $opt ?>" <?= $updateTask['progress_percentage'] == $opt ? 'selected' : '' ?>>
                            <?= $opt ?>%
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-1">
                        <div class="progress-bar-container <?= getProgressColor($updateTask['progress_percentage']) ?>" style="max-width:200px;">
                            <div class="progress-bar" style="width: <?= (int)$updateTask['progress_percentage'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/designer/my_tasks.php" class="btn btn-outline modal-cancel"><?= __('cancel') ?></a>
                <button type="submit" class="btn btn-primary"><?= __('update') ?></button>
            </div>
        </form>
        <script>
        function toggleUploadField() {
            var status = document.getElementById('modal_status').value;
            var uploadField = document.getElementById('uploadField');
            var progressField = document.getElementById('progressField');
            if (status === 'delivered') {
                uploadField.style.display = 'block';
                document.getElementById('modal_progress').value = '100';
            } else {
                uploadField.style.display = 'none';
            }
        }
        </script>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
