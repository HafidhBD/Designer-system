<?php
/**
 * Create Task Page — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/telegram.php';

requireManager();

$pageTitle = __('create_new_task');
$errors = [];

// Get designers for dropdown
$designers = getDesigners();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
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
    $status      = $_POST['status'] ?? STATUS_NEW;

    // Validation
    if (empty($title))      $errors[] = __('task_title') . ': ' . __('required_field');
    if (empty($clientName)) $errors[] = __('client_name') . ': ' . __('required_field');
    if (empty($designType) || !array_key_exists($designType, DESIGN_TYPES)) $errors[] = __('design_type') . ': ' . __('required_field');
    if (empty($assignedTo)) $errors[] = __('assigned_designer') . ': ' . __('required_field');
    if (!in_array($status, array_keys(TASK_STATUSES))) $status = STATUS_NEW;
    if (!in_array($progress, PROGRESS_OPTIONS)) $progress = 0;

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, client_name, design_type, notes, deadline, assigned_to, progress_percentage, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $clientName, $designType, $notes,
            $deadline ?: null, $assignedTo, $progress, $status,
            getCurrentUser()['id']
        ]);

        // Log initial status
        $taskId = $pdo->lastInsertId();
        logStatusChange($taskId, null, $status, getCurrentUser()['id']);

        // Send Telegram notification to assigned designer
        notifyDesignerNewTask(
            $assignedTo,
            $title,
            $clientName,
            getDesignTypeLabel($designType),
            $deadline ?: null
        );

        redirectWith('/manager/all_tasks.php', 'success', __('task_created'));
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('create_new_task') ?></h2>
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
                       value="<?= sanitize($title ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="client_name"><?= __('client_name') ?> *</label>
                <input type="text" id="client_name" name="client_name" class="form-control" 
                       value="<?= sanitize($clientName ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="design_type"><?= __('design_type') ?> *</label>
                <select id="design_type" name="design_type" class="form-control" required>
                    <option value=""><?= __('select_type') ?></option>
                    <?php foreach (DESIGN_TYPES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= (isset($designType) && $designType === $key) ? 'selected' : '' ?>>
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
                    <option value="<?= $designer['id'] ?>" <?= (isset($assignedTo) && $assignedTo == $designer['id']) ? 'selected' : '' ?>>
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
                       value="<?= sanitize($deadline ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="status"><?= __('status') ?></label>
                <select id="status" name="status" class="form-control">
                    <?php foreach (TASK_STATUSES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= (isset($status) && $status === $key) ? 'selected' : '' ?>>
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
                <option value="<?= $opt ?>" <?= (isset($progress) && $progress == $opt) ? 'selected' : '' ?>>
                    <?= $opt ?>%
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes"><?= __('notes') ?></label>
            <textarea id="notes" name="notes" class="form-control" rows="4"><?= sanitize($notes ?? '') ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= __('create') ?></button>
            <a href="/manager/all_tasks.php" class="btn btn-outline"><?= __('cancel') ?></a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
