<?php
/**
 * Login Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// If already logged in, redirect
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = __('login_error');
    } elseif (authenticate($email, $password)) {
        header('Location: /index.php');
        exit;
    } else {
        $error = __('login_error');
    }
}

$dir = getDirection();
$currentLang = getCurrentLang();
$switchLang = ($currentLang === 'en') ? 'ar' : 'en';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?> - <?= __('app_name') ?></title>
    <link rel="icon" href="/logo.gif" type="image/gif">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if ($dir === 'rtl'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="<?= $dir ?>">
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">
                <img src="/logo.gif" alt="Logo">
            </div>
            <h1 class="login-title"><?= __('login_title') ?></h1>
            <p class="login-subtitle"><?= __('login_subtitle') ?></p>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= sanitize($error) ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" data-validate="true">
                <div class="form-group">
                    <label class="form-label" for="email"><?= __('email') ?></label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= sanitize($email ?? '') ?>" 
                           placeholder="name@example.com" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password"><?= __('password') ?></label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg"><?= __('login_btn') ?></button>
                </div>
            </form>

            <div class="login-lang">
                <a href="?lang=<?= $switchLang ?>"><?= __('switch_lang') ?></a>
            </div>
        </div>
    </div>
</body>
</html>
