# Authentication Setup Instructions

## Overview
This document explains how to set up the authentication system for the Attendance System.

## Files Created

1. **`database/admins_table.sql`** - SQL file to create the `admins` table and seed the default administrator account
2. **`app/models/Admin.php`** - Admin model for database operations
3. **`app/controller/AuthController.php`** - Authentication controller with login/logout logic
4. **`auth/login.php`** - Login page
5. **`auth/logout.php`** - Logout handler
6. **`auth/helpers.php`** - Helper functions for authentication checks

## Setup Steps

### Step 1: Create the Admins Table

Run the SQL file in your MySQL/MariaDB database:

```sql
-- Option 1: Import via phpMyAdmin
-- Navigate to phpMyAdmin → Select 'attendance-system' database → Import → Choose 'admins_table.sql'

-- Option 2: Run via command line
mysql -u root -p attendance-system < database/admins_table.sql
```

Or execute the SQL directly in phpMyAdmin SQL tab:

```sql
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('administrator','manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`username`, `email`, `password`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@attendance-system.local', '$2y$10$B9GCvqBQKBdMOhWbNr6uX.aaet97gsu1DgiXQvnBv.lfyruQV2w9i', 'System Administrator', 'administrator', 1);
```

### Step 2: Default Administrator Credentials

After running the SQL file, you can login with:

- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@attendance-system.local`

⚠️ **IMPORTANT**: Change the default password after first login in a production environment!

### Step 3: Access the Login Page

Navigate to:
```
http://localhost/attendance-system/auth/login.php
```

### Step 4: Protect Your Admin Pages

To protect admin pages, add this at the top of your PHP files:

```php
<?php
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Redirects to login if not authenticated
?>
```

Or use the helper functions:

```php
<?php
require_once __DIR__ . "/../auth/helpers.php";

// Check if authenticated
if (!isAuthenticated()) {
    header("Location: /attendance-system/auth/login.php");
    exit;
}

// Get current user
$user = currentUser();
echo "Welcome, " . $user["full_name"];

// Check role
if (hasRole("administrator")) {
    // Admin-only code
}

// Require specific role
requireRole("administrator"); // Redirects if not admin
?>
```

## Database Schema

### `admins` Table Structure

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) | Primary key, auto-increment |
| `username` | VARCHAR(100) | Unique username |
| `email` | VARCHAR(255) | Unique email address |
| `password` | VARCHAR(255) | Bcrypt hashed password |
| `full_name` | VARCHAR(255) | Full name of admin |
| `role` | ENUM | 'administrator', 'manager', or 'staff' |
| `is_active` | TINYINT(1) | Account active status (1 = active, 0 = inactive) |
| `last_login` | TIMESTAMP | Last login timestamp |
| `created_at` | TIMESTAMP | Account creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

## Features

- ✅ Secure password hashing using PHP's `password_hash()` with bcrypt
- ✅ Session-based authentication
- ✅ Role-based access control (administrator, manager, staff)
- ✅ Last login tracking
- ✅ Account activation/deactivation
- ✅ Helper functions for easy authentication checks
- ✅ Automatic redirect to login if not authenticated

## Security Notes

1. **Password Hashing**: Passwords are hashed using `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
2. **Session Security**: Sessions are used to store authentication state
3. **SQL Injection**: Protected by using the Model's query builder (PDO prepared statements)
4. **XSS Protection**: Use `htmlspecialchars()` when displaying user input

## Logout

To logout, simply navigate to:
```
http://localhost/attendance-system/auth/logout.php
```

Or create a logout link in your pages:
```html
<a href="/attendance-system/auth/logout.php">Logout</a>
```

## Troubleshooting

### "Class 'AuthController' not found"
- Make sure `bootstrap.php` is included before using AuthController
- Check that `autoloader.php` includes the controller path

### "Table 'admins' doesn't exist"
- Run the SQL file `database/admins_table.sql` in your database

### "Invalid username or password"
- Verify the admin account exists in the database
- Check that the password hash matches (run the seed SQL again if needed)

### Session not persisting
- Check PHP session configuration in `php.ini`
- Ensure cookies are enabled in the browser
- Check file permissions on session storage directory

## Next Steps

1. ✅ Run the SQL seed file
2. ✅ Test login with default credentials
3. ✅ Add `requireAuth()` to your admin pages
4. ✅ Change the default password
5. ✅ Create additional admin accounts as needed

