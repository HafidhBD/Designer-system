<?php
/**
 * All Tasks Page — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$pageTitle = __('nav_all_tasks');

// Get filter values
$filterDesigner = $_GET['designer'] ?? '';
$filterStatus   = $_GET['status'] ?? '';
$filterType     = $_GET['design_type'] ?? '';
$filterClient   = trim($_GET['client'] ?? '');
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';
$search         = trim($_GET['search'] ?? '');

// Build query
$pdo = getDBConnection();
$where = [];
$params = [];

if ($filterDesigner) {
    $where[] = "t.assigned_to = ?";
    $params[] = (int)$filterDesigner;
}
if ($filterStatus) {
    $where[] = "t.status = ?";
    $params[] = $filterStatus;
}
if ($filterType) {
    $where[] = "t.design_type = ?";
    $params[] = $filterType;
}
if ($filterClient) {
    $where[] = "t.client_name LIKE ?";
    $params[] = "%$filterClient%";
}
if ($filterDateFrom) {
    $where[] = "t.created_at >= ?";
    $params[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo) {
    $where[] = "t.created_at <= ?";
    $params[] = $filterDateTo . ' 23:59:59';
}
if ($search) {
    $where[] = "(t.title LIKE ? OR t.client_name LIKE ? OR t.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT t.*, u.full_name AS designer_name 
        FROM tasks t 
        LEFT JOIN users u ON t.assigned_to = u.id 
        $whereSQL 
        ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get designers for filter dropdown
$designers = getDesigners();

include __DIR__ . '/../templates/header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="form-group mb-1">
                <label class="form-label"><?= __('search') ?></label>
                <input type="text" name="search" class="form-control" value="<?= sanitize($search) ?>" placeholder="<?= __('search') ?>...">
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('assigned_designer') ?></label>
                <select name="designer" class="form-control">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach ($designers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDesigner == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
            <div class="form-group mb-1">
                <label class="form-label"><?= __('date_from') ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= sanitize($filterDateFrom) ?>">
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('date_to') ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= sanitize($filterDateTo) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><?= __('apply_filter') ?></button>
                <a href="/manager/all_tasks.php" class="btn btn-outline btn-sm"><?= __('clear_filter') ?></a>
            </div>
        </div>
    </form>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('tasks') ?> (<?= count($tasks) ?>)</h2>
        <a href="/manager/create_task.php" class="btn btn-sm btn-primary"><?= __('create_new_task') ?></a>
    </div>

    <?php if (empty($tasks)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p><?= __('no_tasks') ?></p>
        <a href="/manager/create_task.php" class="btn btn-primary"><?= __('create_new_task') ?></a>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('task_title') ?></th>
                    <th><?= __('client_name') ?></th>
                    <th><?= __('assigned_to') ?></th>
                    <th><?= __('design_type') ?></th>
                    <th><?= __('deadline') ?></th>
                    <th><?= __('progress') ?></th>
                    <th><?= __('status') ?></th>
                    <th><?= __('created_at') ?></th>
                    <th><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= $task['id'] ?></td>
                    <td><strong><?= sanitize($task['title']) ?></strong></td>
                    <td><?= sanitize($task['client_name']) ?></td>
                    <td><?= sanitize($task['designer_name']) ?></td>
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
                    <td class="text-small text-muted"><?= formatDateTime($task['created_at']) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/manager/view_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-outline"><?= __('view') ?></a>
                            <a href="/manager/edit_task.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary"><?= __('edit') ?></a>
                            <a href="/manager/delete_task.php?id=<?= $task['id'] ?>&csrf_token=<?= urlencode(generateCSRFToken()) ?>" 
                               class="btn btn-sm btn-danger" 
                               data-confirm="<?= __('confirm_delete') ?>"><?= __('delete') ?></a>
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
