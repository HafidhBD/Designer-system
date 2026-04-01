# Design Task Manager — Setup Guide

## Overview
A lightweight internal task management system for design departments. Built with PHP, MySQL, HTML, CSS, and JavaScript. Supports Arabic & English with RTL.

---

## Default Login Credentials

| Role     | Email              | Password       |
|----------|--------------------|----------------|
| Manager  | admin@design.com   | Admin@123      |
| Designer | sara@design.com    | Designer@123   |
| Designer | omar@design.com    | Designer@123   |

---

## Deployment Instructions (Hostinger)

### Step 1: Create MySQL Database
1. Log into Hostinger control panel
2. Go to **Databases → MySQL Databases**
3. Create a new database (note the database name)
4. The user `u983353360_Designers` should already exist

### Step 2: Import Database Schema
1. Go to **Databases → phpMyAdmin**
2. Select your database
3. Click the **Import** tab
4. Upload the file: `database/schema.sql`
5. Click **Go** to execute

### Step 3: Update Database Configuration
1. Open `config/database.php`
2. Update these values:
   ```php
   define('DB_HOST', 'localhost');           // Usually localhost on Hostinger
   define('DB_NAME', 'your_database_name'); // The database name you created
   define('DB_USER', 'u983353360_Designers');
   define('DB_PASS', 'jMemyT6H8Q');
   ```

### Step 4: Upload Files
1. Go to **Files → File Manager** (or use FTP)
2. Navigate to `public_html/` (or your domain's root folder)
3. Upload **all project files** maintaining the folder structure
4. Make sure `logo.gif` is in the root directory

### Step 5: Hash Passwords
1. Visit `https://yourdomain.com/setup_passwords.php` in your browser
2. This will generate proper bcrypt password hashes for all seed users
3. **IMPORTANT: Delete `setup_passwords.php` immediately after running it!**

### Step 6: Test Login
1. Visit `https://yourdomain.com/login.php`
2. Log in with the manager credentials above
3. Test creating tasks, managing users, etc.

---

## File Structure
```
Designer-system/
├── config/
│   ├── .htaccess          # Blocks direct access
│   ├── app.php            # App settings, constants
│   ├── database.php       # DB credentials & connection
│   ├── lang_ar.php        # Arabic translations
│   └── lang_en.php        # English translations
├── includes/
│   ├── .htaccess          # Blocks direct access
│   ├── auth.php           # Auth, session, CSRF
│   ├── helpers.php        # Utility functions
│   └── language.php       # Language loader
├── templates/
│   ├── header.php         # HTML header + nav
│   ├── sidebar.php        # Sidebar navigation
│   └── footer.php         # HTML footer
├── assets/
│   ├── css/
│   │   ├── style.css      # Main stylesheet
│   │   └── rtl.css        # RTL overrides for Arabic
│   └── js/
│       └── app.js         # Client-side JavaScript
├── manager/
│   ├── dashboard.php      # Manager dashboard
│   ├── create_task.php    # Create new task
│   ├── all_tasks.php      # View/filter all tasks
│   ├── view_task.php      # View task details
│   ├── edit_task.php      # Edit task
│   ├── delete_task.php    # Delete task handler
│   ├── reports.php        # Performance reports
│   └── users.php          # User management
├── designer/
│   ├── dashboard.php      # Designer dashboard
│   └── my_tasks.php       # View & update own tasks
├── database/
│   ├── .htaccess          # Blocks direct access
│   └── schema.sql         # DB schema + seed data
├── uploads/               # Reserved for future use
├── .htaccess              # Root security rules
├── index.php              # Router (redirects by role)
├── login.php              # Login page
├── logout.php             # Logout handler
├── setup_passwords.php    # One-time password setup (DELETE after use!)
├── logo.gif               # Company logo
└── README.md              # This file
```

---

## Configuration Files to Edit

| File                 | What to Edit                    |
|----------------------|---------------------------------|
| `config/database.php`| DB_HOST, DB_NAME, DB_USER, DB_PASS |
| `config/app.php`     | APP_URL, timezone, design types    |

---

## Adding New Design Types
Edit `config/app.php` and add to the `DESIGN_TYPES` array:
```php
define('DESIGN_TYPES', [
    'file'          => ['en' => 'File', 'ar' => 'ملف'],
    'logo'          => ['en' => 'Logo', 'ar' => 'شعار'],
    // Add new types here:
    'banner'        => ['en' => 'Banner', 'ar' => 'بانر'],
]);
```
Then add the matching ENUM value to the `tasks.design_type` column in the database.

---

## Security Notes
- All passwords are hashed with `password_hash()` (bcrypt)
- All queries use PDO prepared statements (SQL injection protected)
- CSRF tokens on all forms
- Output escaped with `htmlspecialchars()` (XSS protected)
- Config/includes directories blocked via `.htaccess`
- Role-based access control on every page
- Session regeneration on login
