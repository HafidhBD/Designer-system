<?php
/**
 * Delete Task Handler — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$taskId = (int)($_GET['id'] ?? 0);
$csrfToken = $_GET['csrf_token'] ?? '';

if (!$taskId || !validateCSRFToken($csrfToken)) {
    redirectWith('/manager/all_tasks.php', 'error', __('error'));
}

$pdo = getDBConnection();

// Verify task exists
$stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
if (!$stmt->fetch()) {
    redirectWith('/manager/all_tasks.php', 'error', __('error'));
}

// Delete task (logs will cascade)
$delStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
$delStmt->execute([$taskId]);

redirectWith('/manager/all_tasks.php', 'success', __('task_deleted'));
