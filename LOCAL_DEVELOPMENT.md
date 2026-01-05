# Local Development Setup

This guide will help you set up PUNKtionary for local development on macOS.

## Prerequisites

✅ **Already installed:**
- PHP 8.5.1 (via Homebrew)
- MySQL 9.5.0 (via Homebrew)
- Local database: `punktionary_local`

## Quick Start

### 1. Start the development server

```bash
./start_local_server.sh
```

The server will be available at: **http://localhost:8000**

### 2. Access the site

Open your browser and navigate to:
- **Homepage:** http://localhost:8000
- **Venues:** http://localhost:8000/venues.html
- **Bands:** http://localhost:8000/bands.html
- **Resources:** http://localhost:8000/resources.html

### 3. Testing with a user account

A test admin user has been created for you:
- **Email:** test@punktionary.local
- **User ID:** 1
- **Account Type:** Admin (1)

To simulate being logged in, you can manually set the session in PHP or use the browser developer tools.

## Database

### Connection Details
- **Host:** localhost
- **User:** root
- **Password:** (empty)
- **Database:** punktionary_local

### Access MySQL

```bash
mysql -u root punktionary_local
```

### View tables

```sql
SHOW TABLES;
```

### Reset database

If you need to reset the database:

```bash
mysql -u root punktionary_local < db/init_local_schema.sql
```

## Configuration Files

The local development setup uses separate config files:

### Database Config
- **Production:** `/Users/joelday/.claude-worktrees/punktionary/db_config.php` (symlink to production)
- **Local:** `/Users/joelday/.claude-worktrees/punktionary/db_config.local.php`

Currently, `db_config.php` is symlinked to `db_config.local.php` for local development.

### OAuth Config
- **Production:** `/Users/joelday/.claude-worktrees/punktionary/oauth_config.php`
- **Local:** `/Users/joelday/.claude-worktrees/punktionary/oauth_config.local.php`

Currently, `oauth_config.php` is symlinked to `oauth_config.local.php` for local development.

## Switching Between Local and Production

### For Local Development

```bash
# Already configured! The symlinks point to local configs
```

### For Production Deployment

**Important:** Before deploying to production, restore the production config files:

```bash
# Remove local symlinks
rm /Users/joelday/.claude-worktrees/punktionary/db_config.php
rm /Users/joelday/.claude-worktrees/punktionary/oauth_config.php

# Restore production configs (you'll need to get these from your production server)
# Or update the symlinks to point to the production configs
```

## OAuth Setup for Local Development

OAuth won't work locally by default because the redirect URIs are registered for `punktionary.com`.

### Option 1: Add localhost to Google OAuth (Recommended)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Navigate to "APIs & Services" → "Credentials"
4. Edit your OAuth 2.0 Client ID
5. Add to "Authorized redirect URIs":
   - `http://localhost:8000/auth/google_callback.php`
6. Save changes

### Option 2: Create a test user in the database

```sql
INSERT INTO users (oauth_provider, oauth_provider_id, email, display_name, account_type)
VALUES ('google', 'test_user_123', 'test@punktionary.local', 'Test User', 1);
```

Then manually set the session in your browser or create a login script.

## Testing Changes

### Test a specific page

```bash
# The server is running at http://localhost:8000
# Just open the page in your browser:
open http://localhost:8000/venue.html?id=1
```

### Check PHP errors

PHP errors will appear in the terminal where you ran `./start_local_server.sh`

### Check database queries

You can add debug output to your PHP files:

```php
error_log("Debug: " . print_r($data, true));
```

## Useful Commands

### Stop MySQL

```bash
brew services stop mysql
```

### Start MySQL

```bash
brew services start mysql
```

### Check what's running

```bash
brew services list
```

### View PHP configuration

```bash
php --ini
```

## Troubleshooting

### Port 8000 already in use

Change the port in `start_local_server.sh`:

```bash
php -S localhost:8080  # Use port 8080 instead
```

### Database connection errors

Check MySQL is running:

```bash
brew services list | grep mysql
```

If it's not running:

```bash
brew services start mysql
```

### OAuth errors

Remember that OAuth requires the redirect URI to match exactly. For local development, either:
1. Add localhost URIs to your OAuth app configuration
2. Use a test user account (already created)
3. Or test non-auth features

## Next Steps

You're all set! You can now:
- Make changes to PHP files and refresh the browser
- Test API endpoints
- Modify the database
- Debug issues locally before pushing to production

Happy coding! 🎸
