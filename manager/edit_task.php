<?php
/**
 * Edit Task Page — Manager Only
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
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    redirectWith('/manager/all_tasks.php', 'error', __('error'));
}

$pageTitle = __('edit_task') . ' #' . $task['id'];
$errors = [];
$designers = getDesigners();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('error');
    }

    $title       = trim($_POST['title'] ?? '');
    $clientName  = trim($_POST['client_name'] ?? '');
    $designType  = $_POST['design_type'] ?? '';
    $notes       = trim($_POST['notes'] ?? '');
    $deadline    = $_POST['deadline'] ?? '';
    $assignedTo  = (int)($_POST['assigned_to'] ?? 0);
    $progress    = (int)($_POST['progress_percentage'] ?? 0);
    $newStatus   = $_POST['status'] ?? $task['status'];

    if (empty($title))      $errors[] = __('task_title') . ': ' . __('required_field');
    if (empty($clientName)) $errors[] = __('client_name') . ': ' . __('required_field');
    if (empty($designType) || !array_key_exists($designType, DESIGN_TYPES)) $errors[] = __('design_type') . ': ' . __('required_field');
    if (empty($assignedTo)) $errors[] = __('assigned_designer') . ': ' . __('required_field');
    if (!in_array($newStatus, array_keys(TASK_STATUSES))) $newStatus = $task['status'];
    if (!in_array($progress, PROGRESS_OPTIONS)) $progress = $task['progress_percentage'];

    if (empty($errors)) {
        $updateStmt = $pdo->prepare("
            UPDATE tasks SET title = ?, client_name = ?, design_type = ?, notes = ?, 
            deadline = ?, assigned_to = ?, progress_percentage = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([
            $title, $clientName, $designType, $notes,
            $deadline ?: null, $assignedTo, $progress, $newStatus, $taskId
        ]);

        // Log status change if changed
        if ($newStatus !== $task['status']) {
            logStatusChange($taskId, $task['status'], $newStatus, getCurrentUser()['id']);
        }

        redirectWith('/manager/all_tasks.php', 'success', __('task_updated'));
    }
} else {
    // Pre-fill from existing data
    $title       = $task['title'];
    $clientName  = $task['client_name'];
    $designType  = $task['design_type'];
    $notes       = $task['notes'];
    $deadline    = $task['deadline'];
    $assignedTo  = $task['assigned_to'];
    $progress    = $task['progress_percentage'];
    $newStatus   = $task['status'];
}

include __DIR__ . '/../templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('edit_task') ?> #<?= $task['id'] ?></h2>
        <a href="/manager/all_tasks.php" class="btn btn-sm btn-outline"><?= __('back') ?></a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:20px;">
            <?php foreach ($errors as $err): ?>
            <li><?= sanitize($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>

    <form method="POST" action="" data-validate="true">
        <?= csrfField() ?>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="title"><?= __('task_title') ?> *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?= sanitize($title) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="client_name"><?= __('client_name') ?> *</label>
                <input type="text" id="client_name" name="client_name" class="form-control" 
                       value="<?= sanitize($clientName) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="design_type"><?= __('design_type') ?> *</label>
                <select id="design_type" name="design_type" class="form-control" required>
                    <option value=""><?= __('select_type') ?></option>
                    <?php foreach (DESIGN_TYPES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= $designType === $key ? 'selected' : '' ?>>
                        <?= $labels[getCurrentLang()] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="assigned_to"><?= __('assigned_designer') ?> *</label>
                <select id="assigned_to" name="assigned_to" class="form-control" required>
                    <option value=""><?= __('select_designer') ?></option>
                    <?php foreach ($designers as $designer): ?>
                    <option value="<?= $designer['id'] ?>" <?= $assignedTo == $designer['id'] ? 'selected' : '' ?>>
                        <?= sanitize($designer['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="deadline"><?= __('deadline') ?></label>
                <input type="date" id="deadline" name="deadline" class="form-control" 
                       value="<?= sanitize($deadline) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="status"><?= __('status') ?></label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (TASK_STATUSES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= $newStatus === $key ? 'selected' : '' ?>>
                        <?= $labels[getCurrentLang()] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="progress_percentage"><?= __('progress') ?></label>
            <select id="progress_percentage" name="progress_percentage" class="form-control" style="max-width:200px;">
                <?php foreach (PROGRESS_OPTIONS as $opt): ?>
                <option value="<?= $opt ?>" <?= $progress == $opt ? 'selected' : '' ?>>
                    <?= $opt ?>%
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes"><?= __('notes') ?></label>
            <textarea id="notes" name="notes" class="form-control" rows="4"><?= sanitize($notes) ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= __('update') ?></button>
            <a href="/manager/all_tasks.php" class="btn btn-outline"><?= __('cancel') ?></a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
