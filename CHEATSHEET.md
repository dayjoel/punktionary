# PUNKtionary Development Cheat Sheet

Quick reference for common development tasks.

## Local Development

### Start/Stop Server
```bash
# Start local server
./start_local_server.sh

# Stop server
# Press Ctrl+C in the terminal running the server

# Or kill it manually
pkill -f "php -S localhost:8000"
```

### Access Local Site
- **Homepage:** http://localhost:8000
- **Venues:** http://localhost:8000/venues.html
- **Bands:** http://localhost:8000/bands.html
- **Resources:** http://localhost:8000/resources.html
- **Admin:** http://localhost:8000/admin.html

### Database Access
```bash
# Connect to local database
mysql -u root punktionary_local

# Common queries
SHOW TABLES;
DESCRIBE venue_reviews;
SELECT * FROM users;
SELECT * FROM venues LIMIT 5;
```

## Git Workflow

### Quick Deploy (Recommended)
```bash
# Automated deploy script
./deploy.sh
```

This will:
1. Show uncommitted changes
2. Offer to commit them
3. Push to GitHub
4. Optionally deploy to production

### Manual Git Commands
```bash
# Check status
git status

# See changes
git diff
git diff venue.html

# Stage changes
git add .                    # Add all
git add venue.html           # Add specific file

# Commit
git commit -m "Your message"

# Push to GitHub
git push origin elastic-hertz

# View log
git log --oneline -5
```

## Database Migrations

### Run Migration Script
```bash
# Local
./run_migration.sh CREATE_VENUE_REVIEWS_TABLE.sql local

# Production (careful!)
./run_migration.sh CREATE_VENUE_REVIEWS_TABLE.sql production
```

### Manual Migration
```bash
# Local
mysql -u root punktionary_local < db/migrations/FILE.sql

# Production
mysql -h sql.punktionary.com -u dayjoel -p prod_punk < db/migrations/FILE.sql
```

## Testing

### Test API Endpoints
```bash
# Local
curl "http://localhost:8000/api/get_venue_reviews.php?venue_id=1"

# Production
curl "https://punktionary.com/api/get_venue_reviews.php?venue_id=1"
```

### Check PHP Errors
```bash
# Watch local server output
# Errors appear in terminal where start_local_server.sh is running

# Production error logs (if you have access)
ssh joeday1@punktionary.com
tail -f ~/logs/punktionary.com/http/error.log
```

### Browser Console
- Open DevTools: F12 or Cmd+Option+I
- **Console tab:** JavaScript errors
- **Network tab:** Failed HTTP requests
- **Application tab:** Cookies/Storage

## Deployment to Production

### Quick Deploy
```bash
./deploy.sh
```

### Manual Deploy
```bash
# 1. Push to GitHub
git push origin elastic-hertz

# 2. SSH to production
ssh joeday1@punktionary.com

# 3. Pull changes
cd ~/punktionary.com
git pull origin elastic-hertz

# 4. Exit
exit
```

### Post-Deployment Checklist
- [ ] Test the live site: https://punktionary.com
- [ ] Check browser console for errors
- [ ] Test modified functionality
- [ ] Run database migrations if needed
- [ ] Monitor error logs

## Common Tasks

### Create a New Feature Branch
```bash
git checkout -b feature/my-new-feature
# Work on feature...
git add .
git commit -m "Add new feature"
git push origin feature/my-new-feature
```

### Merge to Main Branch
```bash
git checkout main
git merge elastic-hertz
git push origin main
```

### Undo Last Commit (Not Pushed)
```bash
git reset --soft HEAD~1  # Keep changes
# or
git reset --hard HEAD~1  # Discard changes
```

### See What Changed
```bash
# Files changed in last commit
git show --name-only

# Detailed diff of last commit
git show

# Compare with production
git diff origin/main
```

## File Locations

### Local
- **Project:** `/Users/joelday/.claude-worktrees/punktionary/elastic-hertz/`
- **Database Config:** `/Users/joelday/.claude-worktrees/punktionary/db_config.php` (→ db_config.local.php)
- **OAuth Config:** `/Users/joelday/.claude-worktrees/punktionary/oauth_config.php` (→ oauth_config.local.php)
- **Database:** `punktionary_local`

### Production
- **Project:** `~/punktionary.com/` (on DreamHost)
- **Database Config:** `/home/joeday1/db_config.php`
- **OAuth Config:** `/home/joeday1/oauth_config.php`
- **Database:** `prod_punk` on `sql.punktionary.com`

## Troubleshooting

### Local server won't start
```bash
# Check if port 8000 is in use
lsof -i :8000

# Kill process using port 8000
kill -9 <PID>

# Or use different port
php -S localhost:8080
```

### Database connection error
```bash
# Check MySQL is running
brew services list | grep mysql

# Start MySQL
brew services start mysql

# Restart MySQL
brew services restart mysql
```

### Git push rejected
```bash
# Pull first
git pull origin elastic-hertz

# Then push
git push origin elastic-hertz
```

### Changes not showing on production
```bash
# SSH to server
ssh joeday1@punktionary.com

# Check current commit
cd ~/punktionary.com
git log -1

# Force pull if needed (careful!)
git fetch --all
git reset --hard origin/elastic-hertz
```

### Clear browser cache
- **Chrome/Edge:** Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
- **Firefox:** Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
- **Safari:** Cmd+Option+E, then Cmd+R

## Useful Aliases (Optional)

Add to `~/.zshrc` or `~/.bashrc`:

```bash
# PUNKtionary shortcuts
alias punk-start='cd /Users/joelday/.claude-worktrees/punktionary/elastic-hertz && ./start_local_server.sh'
alias punk-deploy='cd /Users/joelday/.claude-worktrees/punktionary/elastic-hertz && ./deploy.sh'
alias punk-db='mysql -u root punktionary_local'
alias punk-ssh='ssh joeday1@punktionary.com'
alias punk-logs='ssh joeday1@punktionary.com "tail -f ~/logs/punktionary.com/http/error.log"'
```

Then reload: `source ~/.zshrc`

## Emergency Commands

### Rollback Production
```bash
ssh joeday1@punktionary.com
cd ~/punktionary.com
git log --oneline -5  # Find last good commit
git reset --hard abc1234  # Use commit hash
```

### Backup Production Database
```bash
ssh joeday1@punktionary.com
mysqldump -h sql.punktionary.com -u dayjoel -p prod_punk > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore Local Database from Production
```bash
# 1. Dump production (on server)
ssh joeday1@punktionary.com
mysqldump -h sql.punktionary.com -u dayjoel -p prod_punk > prod_backup.sql

# 2. Download to local
scp joeday1@punktionary.com:~/prod_backup.sql .

# 3. Import to local
mysql -u root punktionary_local < prod_backup.sql
```

## Quick Links

- **GitHub Repo:** [Your repo URL]
- **Production:** https://punktionary.com
- **Local:** http://localhost:8000
- **DreamHost Panel:** [Your DreamHost panel URL]
- **Google Cloud Console:** https://console.cloud.google.com

## Need Help?

- Check `LOCAL_DEVELOPMENT.md` for local setup details
- Check `DEPLOYMENT_WORKFLOW.md` for detailed deployment steps
- Check browser console (F12) for JavaScript errors
- Check terminal output for PHP errors
- Check MySQL logs: `mysql.err` in MySQL data directory
