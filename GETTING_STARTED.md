# Getting Started with PUNKtionary Development

## Welcome! 👋

This guide will get you up and running in under 5 minutes.

## ✅ Setup Complete

Your local development environment is **already configured** with:
- PHP 8.5.1
- MySQL 9.5.0
- Local database with all tables
- Configuration files
- Helper scripts

## 🚀 Start Coding Right Now

### 1. Start the local server

```bash
cd /Users/joelday/.claude-worktrees/punktionary/elastic-hertz
./start_local_server.sh
```

**That's it!** Your site is now running at http://localhost:8000

### 2. Make your changes

Edit any file in the project:
- `venue.html` - Venue detail page
- `api/get_venue_reviews.php` - Reviews API
- `venues.js` - Venue list JavaScript
- etc.

Changes are **instant** - just refresh your browser!

### 3. Deploy to production

```bash
./deploy.sh
```

This single command will:
- Commit your changes
- Push to GitHub
- Deploy to production
- Show you the status

## 📖 Documentation

Pick what you need:

| Document | Best For |
|----------|----------|
| **[CHEATSHEET.md](CHEATSHEET.md)** | Quick command reference |
| **[WORKFLOW_VISUAL.md](WORKFLOW_VISUAL.md)** | Visual guide to the process |
| **[DEPLOYMENT_WORKFLOW.md](DEPLOYMENT_WORKFLOW.md)** | Step-by-step deployment |
| **[LOCAL_DEVELOPMENT.md](LOCAL_DEVELOPMENT.md)** | Deep dive on local setup |

## 🎯 Common Tasks

### Test a change locally

1. Edit a file
2. Refresh http://localhost:8000
3. Check browser console (F12) for errors
4. Test the functionality

### Deploy a change

```bash
./deploy.sh
```

Follow the prompts!

### Run a database migration

```bash
./run_migration.sh YOUR_FILE.sql local    # Test locally
./run_migration.sh YOUR_FILE.sql production  # Deploy to production
```

### Check if everything is working

```bash
./pre_push_checklist.sh
```

### Access the database

```bash
mysql -u root punktionary_local
```

## 🔍 Key Files to Know

### Frontend
- `index.html` - Homepage
- `venues.html`, `bands.html`, `resources.html` - Directory pages
- `venue.html`, `band.html` - Detail pages
- `admin.html` - Admin panel

### Backend
- `api/*.php` - API endpoints
- `auth/*.php` - Authentication system
- `get_*.php` - Data fetching scripts

### Configuration (outside git repo)
- `/Users/joelday/.claude-worktrees/punktionary/db_config.php` → Local DB
- `/Users/joelday/.claude-worktrees/punktionary/oauth_config.php` → Local OAuth

## 💡 Pro Tips

1. **Keep the server running** in one terminal while you work
2. **Use browser DevTools** (F12) to debug JavaScript
3. **Check the terminal** where the server is running for PHP errors
4. **Test locally before deploying** - always!
5. **Commit often** - small commits are easier to manage

## 🐛 Something Not Working?

### Server won't start?
```bash
# Kill any existing server
pkill -f "php -S localhost:8000"

# Try again
./start_local_server.sh
```

### Database connection error?
```bash
# Start MySQL
brew services start mysql
```

### Changes not showing?
- Clear browser cache: **Cmd+Shift+R** (Mac) or **Ctrl+Shift+R** (Windows)
- Check if file was saved
- Check terminal for PHP errors

### Deploy failed?
```bash
# Check git status
git status

# Pull latest changes first
git pull origin elastic-hertz

# Try deploy again
./deploy.sh
```

## 🎸 The Workflow in 3 Steps

```
1. Code locally  →  2. Test  →  3. Deploy
   (edit files)     (refresh)    (./deploy.sh)
```

## 📞 Need More Help?

- **Quick answers:** Check [CHEATSHEET.md](CHEATSHEET.md)
- **Visual guide:** See [WORKFLOW_VISUAL.md](WORKFLOW_VISUAL.md)
- **Detailed steps:** Read [DEPLOYMENT_WORKFLOW.md](DEPLOYMENT_WORKFLOW.md)
- **Local setup:** See [LOCAL_DEVELOPMENT.md](LOCAL_DEVELOPMENT.md)

## 🎉 You're Ready!

Your development environment is ready to go. Start coding!

```bash
# Start the server
./start_local_server.sh

# Make changes
# Test them at http://localhost:8000

# Deploy when ready
./deploy.sh
```

**Keep it simple. Keep it punk.** 🎸
