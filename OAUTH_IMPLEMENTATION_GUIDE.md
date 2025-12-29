# OAuth Authentication Implementation Guide

## Overview
This guide explains the OAuth authentication system implementation for punktionary.com, which allows users to log in with Google, Facebook, and Apple.

## Implementation Status

### ✅ Completed
1. **Database Schema** - SQL migration file created
2. **Backend Authentication** - All OAuth providers implemented
3. **Session Management** - PHP sessions with secure configuration
4. **User API Endpoints** - Profile, update, submissions, delete
5. **Frontend Integration** - Navbar, login modal, submit form auth checks
6. **Submission Attribution** - All submissions now require login and track user_id

### ⏳ To Do (Profile Pages)
- Create `profile.html` for user profile management
- Create `my-submissions.html` to display user's submissions

## Setup Instructions

### 1. Run Database Migration

SSH into your server and run the SQL migration:

```bash
mysql -u dayjoel -p prod_punk < database_migration_oauth.sql
```

This creates:
- `users` table
- `oauth_states` table
- Adds `submitted_by` and `edited_by` columns to existing tables

### 2. Upload All Files

Upload these new files to your server:

**Auth System:**
- `/auth/session_config.php`
- `/auth/helpers.php`
- `/auth/check_auth.php`
- `/auth/logout.php`
- `/auth/google_login.php`
- `/auth/google_callback.php`
- `/auth/facebook_login.php`
- `/auth/facebook_callback.php`
- `/auth/apple_login.php`
- `/auth/apple_callback.php`

**User API:**
- `/api/user_profile.php`
- `/api/update_profile.php`
- `/api/user_submissions.php`
- `/api/delete_account.php`

**Updated Files:**
- `submit_band.php` (now requires auth)
- `submit_venue.php` (now requires auth)
- `submit_resource.php` (now requires auth)
- `navbar.html` (OAuth buttons + logged-in state)
- `navbar.js` (auth state management)
- `submit.js` (auth check before submit)

### 3. Configure OAuth Credentials

⚠️ **IMPORTANT**: You must obtain OAuth credentials from each provider before the system will work.

#### Google OAuth Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable "Google+ API"
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Application type: "Web application"
6. Authorized redirect URIs: `https://punktionary.com/auth/google_callback.php`
7. Copy Client ID and Client Secret

#### Facebook OAuth Setup
1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Create an app → "Consumer" type
3. Add "Facebook Login" product
4. Settings → Valid OAuth Redirect URIs: `https://punktionary.com/auth/facebook_callback.php`
5. Copy App ID and App Secret

#### Apple OAuth Setup
1. Go to [Apple Developer Portal](https://developer.apple.com/)
2. Create an App ID with "Sign in with Apple" capability
3. Create a Service ID
4. Configure return URL: `https://punktionary.com/auth/apple_callback.php`
5. Create a private key (.p8 file) and note the Key ID
6. Copy Team ID, Service ID (Client ID), Key ID
7. Upload the .p8 private key file to your server

#### Update `oauth_config.php`

⚠️ **SECURITY**: The `oauth_config.php` file is now stored OUTSIDE the web root at `/home/joeday1/oauth_config.php` (same location as `db_config.php`) for security.

Replace the placeholder values in `/home/joeday1/oauth_config.php` with your actual credentials:

```php
// Google
define('GOOGLE_CLIENT_ID', 'your-actual-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-actual-secret');

// Facebook
define('FACEBOOK_APP_ID', 'your-actual-app-id');
define('FACEBOOK_APP_SECRET', 'your-actual-secret');

// Apple
define('APPLE_CLIENT_ID', 'your-service-id');
define('APPLE_TEAM_ID', 'your-team-id');
define('APPLE_KEY_ID', 'your-key-id');
define('APPLE_PRIVATE_KEY_PATH', '/path/to/AuthKey_XXX.p8');
```

✅ **Security**: The `oauth_config.php` file is already configured to be stored outside the web root at `/home/joeday1/oauth_config.php`.

### 4. Set File Permissions

```bash
chmod 600 ~/db_config.php
chmod 600 ~/oauth_config.php
chmod 644 ~/punktionary.com/auth/*.php
chmod 644 ~/punktionary.com/api/*.php
```

### 5. Test OAuth Flows

1. Visit your site: `https://punktionary.com`
2. Click "Login" in the navbar
3. Try each OAuth provider (Google, Facebook, Apple)
4. Verify you're redirected back and logged in
5. Check navbar shows your name/avatar
6. Try submitting content (should work when logged in)
7. Try logging out

## How It Works

### Authentication Flow

1. **User clicks "Login"** → Modal opens with OAuth buttons
2. **User selects provider** → Redirected to Google/Facebook/Apple
3. **User authorizes** → Provider redirects back with auth code
4. **Callback processes code** → Exchanges for access token
5. **Fetch user info** → Gets email, name, picture from provider
6. **Create/update user** → Upserts user in database
7. **Create session** → Sets `$_SESSION['user_id']`
8. **Redirect to home** → User is now logged in

### Session Management

- **Secure cookies**: HttpOnly, Secure (HTTPS only), SameSite=Lax
- **24-hour lifetime**: Sessions expire after 1 day
- **CSRF protection**: State tokens for OAuth flows
- **Session regeneration**: New session ID after login (prevents fixation)

### Submission Attribution

All submissions now require authentication:

```php
// Before submission
if (!is_authenticated()) {
    die(json_encode(['error' => 'You must be logged in']));
}

// Submissions include user_id
INSERT INTO bands (submitted_by, name, ...) VALUES (?, ?, ...)
```

Existing submissions have `submitted_by = NULL` (anonymous/legacy).

## API Endpoints

### Auth Endpoints

- **`GET /auth/check_auth.php`** - Returns JSON with auth status and user data
- **`GET /auth/logout.php`** - Destroys session and redirects to home
- **`GET /auth/google_login.php`** - Initiates Google OAuth flow
- **`POST /auth/google_callback.php`** - Handles Google OAuth callback
- **`GET /auth/facebook_login.php`** - Initiates Facebook OAuth flow
- **`POST /auth/facebook_callback.php`** - Handles Facebook OAuth callback
- **`GET /auth/apple_login.php`** - Initiates Apple OAuth flow
- **`POST /auth/apple_callback.php`** - Handles Apple OAuth callback

### User API Endpoints

- **`GET /api/user_profile.php`** - Returns user profile + statistics (requires auth)
- **`POST /api/update_profile.php`** - Updates display name/picture (requires auth)
- **`GET /api/user_submissions.php`** - Returns user's bands/venues/resources (requires auth)
- **`POST /api/delete_account.php`** - Deletes user account (requires auth)

## Frontend JavaScript

### Navbar (`navbar.js`)

- `checkAuthStatus()` - Fetches auth status from `/auth/check_auth.php`
- `updateNavbarForLoggedIn(user)` - Shows avatar, name, profile/submissions links
- `updateNavbarForLoggedOut()` - Shows login/register buttons
- Logout handlers - Calls `/auth/logout.php` and redirects

### Submit Form (`submit.js`)

- Auth check before submission
- Opens login modal if not authenticated
- Displays error message prompting user to log in

## Troubleshooting

### "Database connection failed"
- Check `db_config.php` is accessible
- Verify database credentials are correct
- Ensure `users` and `oauth_states` tables exist

### "Invalid state token"
- OAuth flow interrupted or expired (10-minute limit)
- Try logging in again
- Check `oauth_states` table exists

### "Failed to get access token"
- Check OAuth credentials in `/home/joeday1/oauth_config.php` (outside web root)
- Verify redirect URIs match exactly in provider settings
- Check server error logs: `~/logs/punktionary.com/http/error.log`

### Login works but submissions fail with 401
- Check `submit_*.php` files include auth session files
- Verify session cookies are being sent (check browser DevTools)
- Ensure HTTPS is enabled (sessions require secure cookies)

### Apple OAuth fails
- Verify `.p8` private key file exists and path is correct
- Check Team ID, Service ID, and Key ID are accurate
- Ensure OpenSSL PHP extension is enabled

## Security Notes

1. **HTTPS Required**: Session cookies are marked "Secure" - site must use HTTPS
2. **OAuth Secrets**: The `oauth_config.php` file is stored outside web root and should NEVER be committed to git
3. **State Tokens**: CSRF protection via cryptographic random tokens with expiry
4. **SQL Injection**: All queries use prepared statements
5. **XSS Protection**: Session cookies are HttpOnly (no JavaScript access)

## Next Steps

To complete the implementation, you still need to create:

1. **`profile.html`** - User profile page showing:
   - Avatar and display name
   - Email and OAuth provider
   - Statistics (submissions, edits, member since)
   - Form to update display name and profile picture
   - Delete account button

2. **`my-submissions.html`** - User submissions page showing:
   - List of user's bands
   - List of user's venues
   - List of user's resources
   - Links to view/edit each submission

See the implementation plan file for detailed page structures.

## Support

- OAuth setup issues: Check provider documentation
- Database errors: Review SQL migration file
- Session problems: Check PHP session configuration
- General errors: Monitor server error logs

---

**Implementation completed**: Backend authentication, session management, submission attribution, navbar integration
**Remaining**: Profile and My Submissions HTML pages
