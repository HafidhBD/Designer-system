<?php
/**
 * User Management Page — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$pageTitle = __('user_management');
$pdo = getDBConnection();
$errors = [];
$editUser = null;

// Handle delete user
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    $deleteId = (int)$_GET['delete'];
    $csrfToken = $_GET['csrf_token'];

    if (validateCSRFToken($csrfToken)) {
        if ($deleteId == getCurrentUser()['id']) {
            setFlash('error', __('cannot_delete_self'));
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', __('user_deleted'));
        }
    }
    header('Location: /manager/users.php');
    exit;
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editUser = getUserById($editId);
}

// Handle form submission (create or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('error');
    }

    $userId    = (int)($_POST['user_id'] ?? 0);
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? ROLE_DESIGNER;

    // Validation
    if (empty($fullName)) $errors[] = __('full_name') . ': ' . __('required_field');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = __('email') . ': ' . __('invalid_email');
    if (!in_array($role, [ROLE_MANAGER, ROLE_DESIGNER])) $role = ROLE_DESIGNER;

    // Check email uniqueness
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $userId]);
        if ($checkStmt->fetch()) {
            $errors[] = __('email_exists');
        }
    }

    if ($userId === 0) {
        // Creating new user
        if (empty($password) || strlen($password) < 6) {
            $errors[] = __('password') . ': ' . __('password_min');
        }
    }

    if (empty($errors)) {
        if ($userId > 0) {
            // Update existing user
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, role = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$fullName, $email, $hash, $role, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$fullName, $email, $role, $userId]);
            }
            redirectWith('/manager/users.php', 'success', __('user_updated'));
        } else {
            // Create new user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $hash, $role]);
            redirectWith('/manager/users.php', 'success', __('user_created'));
        }
    } else {
        // If editing, keep edit mode
        if ($userId > 0) {
            $editUser = ['id' => $userId, 'full_name' => $fullName, 'email' => $email, 'role' => $role];
        }
    }
}

// Get all users
$users = getAllUsers();

include __DIR__ . '/../templates/header.php';
?>

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-title"><?= $editUser ? __('edit_user') : __('add_user') ?></h2>
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

    <form method="POST" action="/manager/users.php" data-validate="true">
        <?= csrfField() ?>
        <input type="hidden" name="user_id" value="<?= $editUser ? $editUser['id'] : 0 ?>">

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="full_name"><?= __('full_name') ?> *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?= sanitize($editUser['full_name'] ?? $fullName ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="email"><?= __('email') ?> *</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= sanitize($editUser['email'] ?? $email ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="password"><?= __('password') ?> <?= $editUser ? '' : '*' ?></label>
                <input type="password" id="password" name="password" class="form-control" 
                       <?= $editUser ? '' : 'required' ?> minlength="6">
                <?php if ($editUser): ?>
                <span class="form-hint"><?= __('leave_blank') ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="role"><?= __('role') ?> *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="<?= ROLE_DESIGNER ?>" <?= (isset($editUser) && $editUser['role'] === ROLE_DESIGNER) || (!isset($editUser) && ($role ?? '') === ROLE_DESIGNER) ? 'selected' : '' ?>><?= __('role_designer') ?></option>
                    <option value="<?= ROLE_MANAGER ?>" <?= (isset($editUser) && $editUser['role'] === ROLE_MANAGER) || (!isset($editUser) && ($role ?? '') === ROLE_MANAGER) ? 'selected' : '' ?>><?= __('role_manager') ?></option>
                </select>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $editUser ? __('update') : __('create') ?></button>
            <?php if ($editUser): ?>
            <a href="/manager/users.php" class="btn btn-outline"><?= __('cancel') ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('users') ?> (<?= count($users) ?>)</h2>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= __('full_name') ?></th>
                    <th><?= __('email') ?></th>
                    <th><?= __('role') ?></th>
                    <th><?= __('created_at') ?></th>
                    <th><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><strong><?= sanitize($user['full_name']) ?></strong></td>
                    <td><?= sanitize($user['email']) ?></td>
                    <td>
                        <span class="badge <?= $user['role'] === ROLE_MANAGER ? 'badge-progress' : 'badge-new' ?>">
                            <?= $user['role'] === ROLE_MANAGER ? __('role_manager') : __('role_designer') ?>
                        </span>
                    </td>
                    <td class="text-small text-muted"><?= formatDateTime($user['created_at']) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/manager/users.php?edit=<?= $user['id'] ?>" class="btn btn-sm btn-outline"><?= __('edit') ?></a>
                            <?php if ($user['id'] != getCurrentUser()['id']): ?>
                            <a href="/manager/users.php?delete=<?= $user['id'] ?>&csrf_token=<?= urlencode(generateCSRFToken()) ?>" 
                               class="btn btn-sm btn-danger" 
                               data-confirm="<?= __('confirm_delete_user') ?>"><?= __('delete') ?></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
