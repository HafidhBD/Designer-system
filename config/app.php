<?php
/**
 * Application Configuration
 */

// Application settings
define('APP_NAME', 'Design Task Manager');
define('APP_VERSION', '1.0.0');
define('APP_URL', '');  // Set to your domain, e.g. https://yourdomain.com

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// User roles
define('ROLE_MANAGER', 'manager');
define('ROLE_DESIGNER', 'designer');

// Task statuses
define('STATUS_NEW', 'new');
define('STATUS_IN_PROGRESS', 'in_progress');
define('STATUS_DELIVERED', 'delivered');

// Design types (configurable list)
define('DESIGN_TYPES', [
    'file'          => ['en' => 'File', 'ar' => 'ملف'],
    'logo'          => ['en' => 'Logo', 'ar' => 'شعار'],
    'design'        => ['en' => 'Design', 'ar' => 'تصميم'],
    'motion_design' => ['en' => 'Motion Design', 'ar' => 'موشن ديزاين'],
    'profile'       => ['en' => 'Profile', 'ar' => 'بروفايل'],
]);

// Progress options
define('PROGRESS_OPTIONS', [0, 25, 50, 75, 100]);

// Task status list
define('TASK_STATUSES', [
    'new'         => ['en' => 'New', 'ar' => 'جديد'],
    'in_progress' => ['en' => 'In Progress', 'ar' => 'قيد التنفيذ'],
    'delivered'   => ['en' => 'Delivered', 'ar' => 'تم التسليم'],
]);

// Timezone
date_default_timezone_set('Asia/Riyadh');
