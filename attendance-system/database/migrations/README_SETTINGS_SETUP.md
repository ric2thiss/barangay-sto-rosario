# Settings System Setup

## Overview
The settings system allows administrators to manage system-wide configurations, including maintenance mode which restricts access to only administrators.

## Setup Instructions

### Step 1: Create the Settings Table

Run the migration SQL file to create the `settings` table:

```sql
-- Option 1: Import via phpMyAdmin
-- Navigate to phpMyAdmin → Select 'attendance-system' database → Import → Choose 'create_settings_table.sql'

-- Option 2: Run via command line
mysql -u root -p attendance-system < database/migrations/create_settings_table.sql
```

Or execute the SQL directly in phpMyAdmin SQL tab:

The SQL file is located at: `database/migrations/create_settings_table.sql`

### Step 2: Default Settings

The migration automatically inserts default settings:
- `app_name`: "Attendance System"
- `maintenance_mode`: 0 (disabled)
- `maintenance_message`: Default maintenance message
- `timezone`: "Asia/Manila"
- `data_retention_days`: 365
- `allow_registration`: 1 (enabled)

## Features

### Maintenance Mode

When maintenance mode is enabled:
- ✅ Only administrators can log in
- ✅ All other users (including managers and staff) are blocked
- ✅ A maintenance message is displayed on the login page
- ✅ Non-admin users see a clear error message when attempting to log in

### Settings Management

Administrators can manage:
- Application name
- Timezone configuration
- Data retention period
- Maintenance mode toggle
- Maintenance message

## Access

- **Settings Page**: `admin/settings.php` (Administrator role required)
- **API Endpoint**: `api/settings/index.php` (Administrator role required)
- **Maintenance Check**: `api/settings/maintenance-check.php` (Public endpoint)

## File Structure

```
app/
  ├── models/
  │   └── Settings.php              # Settings model
  ├── repositories/
  │   └── SettingsRepository.php    # Settings repository
  └── controller/
      └── SettingsController.php    # Settings controller

api/
  └── settings/
      ├── index.php                 # Settings CRUD API
      └── maintenance-check.php     # Public maintenance check

admin/
  └── settings.php                  # Settings management UI

database/
  └── migrations/
      └── create_settings_table.sql # Database migration
```

## Usage

### Enabling Maintenance Mode

1. Log in as an administrator
2. Navigate to Settings → Maintenance Mode tab
3. Toggle "Enable Maintenance Mode"
4. Optionally customize the maintenance message
5. Click "Save Maintenance Settings"

### Managing General Settings

1. Navigate to Settings → General tab
2. Update application name, timezone, or data retention
3. Click "Save General Settings"

## Security

- Settings page requires administrator role
- Settings API requires authentication and administrator role
- Maintenance mode check is public (used by login page)
- All settings updates are logged with the admin ID who made the change

## Notes

- Maintenance mode blocks all non-admin users immediately
- Administrators can still log in during maintenance
- Settings are stored in the database and persist across sessions
- All settings have default values if not set
