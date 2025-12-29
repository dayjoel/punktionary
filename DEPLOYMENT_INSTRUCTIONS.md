# OAuth Config Deployment Instructions

## Summary
All OAuth credentials have been moved from `/auth/oauth_config.php` to a secure location outside the web root at `/home/joeday1/oauth_config.php` (same location as `db_config.php`).

## Files Changed
The following files now reference the new OAuth config location:
- `/auth/google_login.php`
- `/auth/google_callback.php`
- `/auth/facebook_login.php`
- `/auth/facebook_callback.php`
- `/auth/apple_login.php`
- `/auth/apple_callback.php`
- `/auth/helpers.php` (already updated for db_config.php path)
- `/auth/test_setup.php` (diagnostic tool)

## Deployment Steps

### 1. Upload the new oauth_config.php file
**Location:** `/home/joeday1/oauth_config.php` (OUTSIDE the web root)

This file contains your OAuth credentials:
- Google Client ID and Secret (already configured)
- Facebook App ID and Secret (needs your real credentials)
- Apple credentials (not configured yet)

**IMPORTANT:** This file should be at the same level as `db_config.php`:
```
/home/joeday1/
├── db_config.php          (already here)
├── oauth_config.php       (NEW - upload here)
└── punktionary.com/       (web root)
    └── auth/
        ├── google_login.php
        ├── helpers.php
        └── ...
```

### 2. Delete the old oauth_config.php from web root
**CRITICAL FOR SECURITY:** After uploading the new file, delete:
`/home/joeday1/punktionary.com/auth/oauth_config.php`

This file contains sensitive credentials and should NOT be accessible from the web.

### 3. Update Facebook credentials
Edit `/home/joeday1/oauth_config.php` and replace these placeholder values with your actual Facebook credentials:
```php
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET');
```

### 4. Upload updated auth files
Upload all the updated files in the `/auth/` directory to replace the old ones.

### 5. Test the setup
Visit: `https://punktionary.com/auth/test_setup.php`

This will verify:
- Database connection
- OAuth config file is accessible
- Credentials are configured
- Database tables exist
- Helper functions work
- PHP extensions are loaded

**After testing, DELETE `/auth/test_setup.php` for security!**

### 6. Test OAuth login
Try logging in with Google and Facebook to verify everything works.

## Security Notes
- The `oauth_config.php` file is now outside the web root and cannot be accessed via HTTP
- Only PHP files in the web root can access it using `require_once`
- Make sure file permissions are set correctly (typically 644 or 640)
- Never commit `oauth_config.php` to version control

## Troubleshooting

### If you see "500 Internal Server Error":
1. Check PHP error logs on the server
2. Run the test_setup.php diagnostic script
3. Verify the file path is correct: `/home/joeday1/oauth_config.php`
4. Check file permissions (should be readable by PHP)

### If test_setup.php shows "oauth_config.php not found":
The file path might be wrong. Try these alternatives:
- `/home/joeday1/oauth_config.php` (most likely)
- Verify with SSH: `ls -la /home/joeday1/oauth_config.php`

### Still having issues?
Check the server's PHP error log for the exact error message.
