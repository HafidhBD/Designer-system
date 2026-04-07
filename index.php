<?php
/**
 * Index / Dashboard Router
 * Redirects to the appropriate dashboard based on user role.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();

if (isManager() || isSupervisor()) {
    header('Location: /manager/dashboard.php');
} else {
    header('Location: /designer/dashboard.php');
}
exit;
