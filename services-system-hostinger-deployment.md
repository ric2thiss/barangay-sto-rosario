# Detailed Deployment Guide: Services System to Hostinger

This guide provides a step-by-step walkthrough for deploying the **Services System** (Laravel-based) to a Hostinger Shared Hosting or VPS environment, specifically optimized for subdirectories (e.g., `yourdomain.com/services-system`).

---

## 1. Local Preparation

Before uploading, ensure your local project is production-ready.

### A. Export Databases
Since the system uses two databases, you need to export both:
1.  **Main Database**: `services-system`
2.  **Profiling Database**: `profiling-system`

Use phpMyAdmin or the terminal to export them as `.sql` files.

### B. Optimize Assets
Compile your CSS and JS for production to ensure the "Premium" look and performance:
```bash
npm run build
```

### C. Clean Up
Delete unnecessary files to reduce upload time:
-   Delete `node_modules`.
-   Delete `tests` folder.
-   **CRITICAL**: Delete `bootstrap/cache/config.php` and `bootstrap/cache/routes-v7.php` if they exist locally.

---

## 2. Hostinger Setup (hPanel)

### A. Create Databases
1.  Log in to your **Hostinger hPanel**.
2.  Navigate to **Databases** > **Management**.
3.  Create **two** new databases. Note down the **Database Name**, **Username**, and **Password** for both.

### B. Set PHP Version
1.  Go to **Advanced** > **PHP Configuration**.
2.  Select **PHP 8.2** or **8.3**.
3.  Ensure extensions like `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, and `xml` are enabled.

---

## 3. Uploading & Clean URL Configuration

### A. Upload Method
1.  Navigate to `public_html/` in File Manager.
2.  Create a folder named `services-system`.
3.  Upload **ALL** your project files (including the `public` folder) into `public_html/services-system/`.

### B. Clean URL & Security (.htaccess)
To remove `/public/` from the URL securely without moving core files:

1.  **Root .htaccess**: Create a file at `public_html/services-system/.htaccess` and add:
    ```apache
    <IfModule mod_rewrite.c>
        RewriteEngine On
        # Redirect all traffic to the public/ folder
        RewriteCond %{REQUEST_URI} !^/services-system/public/
        RewriteRule ^(.*)$ public/$1 [L]
    </IfModule>

    # Block access to sensitive files
    <FilesMatch "^\.env">
        Order allow,deny
        Deny from all
    </FilesMatch>
    ```

2.  **Public .htaccess**: Open `public_html/services-system/public/.htaccess`. Find `RewriteEngine On` and add the base path right below it:
    ```apache
    RewriteEngine On
    RewriteBase /services-system/public/
    ```

### C. The 404 Routing Fix
To prevent Laravel from throwing a 404 on the root URL, open `public_html/services-system/public/index.php` and add this line right after the opening `<?php` tag:
```php
<?php
// Fix routing for shared hosting subdirectory
$_SERVER['SCRIPT_NAME'] = '/services-system/index.php';
```

---

## 4. Configuration (.env)

Edit the `.env` file inside `public_html/services-system/` with these specific production values:

```env
APP_NAME=STO.ROSARIO
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sr-magallanes.com/services-system

# Livewire Subdirectory Fix
LIVEWIRE_ASSET_URL=/services-system

# Main Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123_services
DB_USERNAME=u123_user
DB_PASSWORD=YourStrongPassword

# Secondary Database
DB_SECOND_CONNECTION=mysql
DB_SECOND_HOST=127.0.0.1
DB_SECOND_PORT=3306
DB_SECOND_DATABASE=u123_profiling
DB_SECOND_USERNAME=u123_user
DB_SECOND_PASSWORD=YourStrongPassword

# Mail (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@sr-magallanes.com
MAIL_PASSWORD=YourEmailPassword
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="admin@sr-magallanes.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 5. HTTPS & Permissions

### A. Force HTTPS (Fixes Login Reloading)
Open `app/Providers/AppServiceProvider.php` and update the `boot` method:
```php
public function boot(): void
{
    if (app()->environment('production')) {
        \URL::forceScheme('https');
    }
}
```

### B. Permissions
Ensure these folders have **775** permissions. In Hostinger File Manager, right-click the folder > **Permissions**, and ensure **Write** is checked for **Group**:
-   `storage/` (Apply recursively to all subfolders)
-   `bootstrap/cache/`

---

## 6. Optimization & Cache Clearing

After any change to `.env` or `.htaccess`, you **must** clear the cache manually if you don't have SSH:

1.  Go to `public_html/services-system/bootstrap/cache/`.
2.  **Delete** `config.php` and `routes-v7.php`.
3.  Go to `public_html/services-system/storage/framework/sessions/`.
4.  **Delete** all files to clear old, broken login sessions.

---

## 7. Laravel Task Scheduling (Cron Jobs)

1.  In Hostinger hPanel, go to **Advanced** > **Cron Jobs**.
2.  Set frequency to **Every Minute** (`* * * * *`).
3.  Command:
    ```bash
    /usr/local/bin/php /home/u123456789/public_html/services-system/artisan schedule:run >> /dev/null 2>&1
    ```

---

## 8. Summary Checklist

1. [ ] Uploaded ALL files to `public_html/services-system/`.
2. [ ] Created Root `.htaccess` for redirection.
3. [ ] Added `SCRIPT_NAME` fix to `public/index.php`.
4. [ ] Updated `.env` with `LIVEWIRE_ASSET_URL` and `APP_URL`.
5. [ ] Set `storage` permissions to **775** recursively.
6. [ ] Forced HTTPS in `AppServiceProvider`.
7. [ ] Deleted `bootstrap/cache/*.php` files on the server.
