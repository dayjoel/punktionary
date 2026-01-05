# Development to Production Workflow

This guide walks you through the complete process from local testing to production deployment.

## Step 1: Local Development & Testing

### Start your local server

```bash
cd /Users/joelday/.claude-worktrees/punktionary/elastic-hertz
./start_local_server.sh
```

Your site is now running at **http://localhost:8000**

### Test your changes

1. **Test the pages you modified**
   - Open the relevant pages in your browser
   - Check browser console for JavaScript errors (F12 → Console)
   - Verify functionality works as expected

2. **Test API endpoints**
   ```bash
   # Example: Test venue reviews API
   curl "http://localhost:8000/api/get_venue_reviews.php?venue_id=1"
   ```

3. **Check PHP errors**
   - Watch the terminal where `start_local_server.sh` is running
   - PHP errors will appear there in real-time

4. **Test database changes**
   ```bash
   mysql -u root punktionary_local
   # Run queries to verify data
   ```

### Common test scenarios

- Create/edit content (bands, venues, resources)
- Submit reviews (if testing review system)
- Test admin functions
- Verify filters and search work
- Check responsive design (resize browser)

## Step 2: Prepare for Production

### Switch to production configs

**IMPORTANT:** Before pushing to production, you need to restore production database and OAuth configs.

The config files are stored outside the git repo in `/Users/joelday/.claude-worktrees/punktionary/`:
- `db_config.php` - Currently pointing to local
- `oauth_config.php` - Currently pointing to local

**You have two options:**

#### Option A: Keep local configs as-is (Recommended)
Since these files are outside the git repo, they won't be pushed to GitHub. Your production server already has its own production configs in place. Just leave the local configs alone and they'll stay local.

#### Option B: Manually switch (if needed)
If you need to test with production configs locally:

```bash
# Backup local configs
cp /Users/joelday/.claude-worktrees/punktionary/db_config.php /Users/joelday/.claude-worktrees/punktionary/db_config.local.php.bak
cp /Users/joelday/.claude-worktrees/punktionary/oauth_config.php /Users/joelday/.claude-worktrees/punktionary/oauth_config.local.php.bak

# You'll need to get production configs from your server
# (The configs are stored outside the web root on production)
```

### Review your changes

```bash
# See what files you've modified
git status

# See the actual changes
git diff

# See changes in a specific file
git diff venue.html
```

## Step 3: Commit to Git

### Stage your changes

```bash
# Stage all changes
git add .

# Or stage specific files
git add venue.html
git add api/get_venue_reviews.php
git add auth/helpers.php
```

### Create a commit

```bash
git commit -m "Add venue review system with star ratings

- Created venue_reviews table with user/venue relationship
- Added review submission with 1-5 star ratings
- Text review required for ratings ≤3 stars
- Display average rating on venue cards
- Admin can delete fraudulent reviews
- Added get_user_account_type() helper function

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

### Review the commit

```bash
# See what will be pushed
git log -1 --stat

# See the full diff of what you're about to push
git log -1 -p
```

## Step 4: Push to GitHub

### Push to your branch

```bash
# Push to your current branch (elastic-hertz)
git push origin elastic-hertz

# If this is the first push of this branch
git push -u origin elastic-hertz
```

### Verify on GitHub

1. Go to https://github.com/YOUR_USERNAME/YOUR_REPO
2. Check that your branch appears
3. Review the changes in the GitHub UI
4. Verify all files are there

## Step 5: Deploy to Production (DreamHost)

### Option A: SSH and Pull (Recommended)

```bash
# SSH into your DreamHost server
ssh joeday1@iad1-shared-b8-46.dreamhost.com

# Navigate to your web root
cd ~/punktionary.com

# Check current status
git status

# Fetch latest changes
git fetch origin

# Pull your branch
git pull origin elastic-hertz

# Or if you merged to main, pull main
# git pull origin main

# Exit SSH
exit
```

### Option B: Using DreamHost Panel (if available)
Some DreamHost accounts have Git integration in the panel where you can trigger pulls from the web interface.

### After pulling to production

**IMPORTANT CHECKS:**

1. **Database migrations** - If you added new tables or columns:
   ```bash
   ssh joeday1@iad1-shared-b8-46.dreamhost.com
   cd ~/punktionary.com

   # Run migrations if needed
   mysql -h sql.punktionary.com -u dayjoel -p prod_punk < db/migrations/CREATE_VENUE_REVIEWS_TABLE.sql
   ```

2. **File permissions** - Ensure files have correct permissions:
   ```bash
   # If needed (usually DreamHost handles this)
   chmod 644 *.php
   chmod 755 api/
   ```

3. **Clear any caches** - If you use caching:
   ```bash
   # Example if you have a cache
   rm -rf cache/*
   ```

## Step 6: Test Production

### Test the live site

1. **Visit your production site**
   - https://punktionary.com
   - Test the pages you changed

2. **Check for errors**
   - Open browser console (F12)
   - Look for JavaScript errors
   - Check Network tab for failed requests

3. **Test functionality**
   - Create/edit content
   - Test any new features
   - Verify database operations work

4. **Check PHP error logs** (if you have access):
   ```bash
   ssh joeday1@iad1-shared-b8-46.dreamhost.com
   tail -f ~/logs/punktionary.com/http/error.log
   ```

## Quick Reference Commands

### Local Development
```bash
# Start server
./start_local_server.sh

# Test API
curl "http://localhost:8000/api/endpoint.php"

# Check database
mysql -u root punktionary_local
```

### Git Workflow
```bash
# Check status
git status

# Stage changes
git add .

# Commit
git commit -m "Your message"

# Push
git push origin elastic-hertz

# View log
git log --oneline -5
```

### Production Deployment
```bash
# SSH to server
ssh joeday1@iad1-shared-b8-46.dreamhost.com

# Pull changes
cd ~/punktionary.com && git pull origin elastic-hertz

# Run migration (if needed)
mysql -h sql.punktionary.com -u dayjoel -p prod_punk < db/migrations/FILE.sql

# Exit
exit
```

## Troubleshooting

### "Permission denied" on git push
```bash
# Make sure you have SSH key set up with GitHub
ssh -T git@github.com

# If using HTTPS, you may need to authenticate
```

### Changes not appearing on production
```bash
# SSH to server
ssh joeday1@iad1-shared-b8-46.dreamhost.com

# Check if pull actually happened
cd ~/punktionary.com
git log -1

# Force pull if needed (careful!)
git fetch --all
git reset --hard origin/elastic-hertz
```

### Database migration needed
```bash
# SSH to server
ssh joeday1@iad1-shared-b8-46.dreamhost.com

# Run the migration
mysql -h sql.punktionary.com -u dayjoel -p prod_punk < db/migrations/YOUR_MIGRATION.sql

# Or import interactively
mysql -h sql.punktionary.com -u dayjoel -p prod_punk
# Then paste SQL commands
```

### File not found on production
```bash
# Make sure file is committed
git status

# Make sure file isn't in .gitignore
cat .gitignore | grep filename

# If it's a new file, add it:
git add path/to/file.php
git commit -m "Add missing file"
git push origin elastic-hertz
```

## Best Practices

1. **Always test locally first** - Never push untested code to production
2. **Commit frequently** - Small, focused commits are easier to review and revert
3. **Write good commit messages** - Explain what and why, not just what
4. **Test on production** - Always verify after deploying
5. **Keep backups** - Before major changes, backup your database
6. **Use branches** - Keep experimental work in separate branches
7. **Review before pushing** - Use `git diff` and `git status` to review changes

## Emergency Rollback

If something breaks in production:

```bash
# SSH to server
ssh joeday1@iad1-shared-b8-46.dreamhost.com
cd ~/punktionary.com

# Find the last working commit
git log --oneline -5

# Reset to that commit (example)
git reset --hard abc1234

# Note: This loses uncommitted changes!
# Better approach: revert the bad commit
git revert HEAD
git push origin elastic-hertz
```

## Configuration Files Note

Remember:
- `db_config.php` and `oauth_config.php` live in `/home/joeday1/` on production
- They live in `/Users/joelday/.claude-worktrees/punktionary/` locally
- These are **OUTSIDE** the git repo, so they won't be pushed/pulled
- Local versions point to `localhost`, production versions point to production DB
- You never need to manually switch them when deploying
