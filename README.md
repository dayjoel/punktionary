# PUNKtionary

A punk rock directory for venues, bands, and resources.

## 🚀 Quick Start

### Local Development

1. **Start the development server:**
   ```bash
   ./start_local_server.sh
   ```
   Visit: http://localhost:8000

2. **Make your changes** and test locally

3. **Deploy to production:**
   ```bash
   ./deploy.sh
   ```

That's it! 🎸

## 📚 Documentation

- **[LOCAL_DEVELOPMENT.md](LOCAL_DEVELOPMENT.md)** - Complete local setup guide
- **[DEPLOYMENT_WORKFLOW.md](DEPLOYMENT_WORKFLOW.md)** - Detailed deployment process
- **[CHEATSHEET.md](CHEATSHEET.md)** - Quick reference for common tasks

## 🛠️ Helper Scripts

- `./start_local_server.sh` - Start local PHP development server
- `./deploy.sh` - Automated deployment to production
- `./run_migration.sh <file> <env>` - Run database migrations
- `./pre_push_checklist.sh` - Pre-deployment checks
- `./setup_local_db.sh` - Initial database setup (already done)

## 🗂️ Project Structure

```
elastic-hertz/
├── index.html              # Homepage
├── venues.html             # Venues directory
├── bands.html              # Bands directory
├── resources.html          # Resources directory
├── venue.html              # Individual venue page
├── band.html               # Individual band page
├── admin.html              # Admin panel
├── submit.html             # Submission form
│
├── api/                    # Backend API endpoints
│   ├── get_venue_reviews.php
│   ├── submit_venue_review.php
│   ├── delete_venue_review.php
│   └── ...
│
├── auth/                   # Authentication system
│   ├── helpers.php
│   ├── session_config.php
│   └── ...
│
├── db/
│   ├── migrations/         # Database migrations
│   └── init_local_schema.sql
│
├── js/                     # JavaScript files
├── css/                    # Stylesheets
└── uploads/                # User uploads
```

## 🔧 Technology Stack

- **Frontend:** HTML, Tailwind CSS, Vanilla JavaScript
- **Backend:** PHP 8.5
- **Database:** MySQL 9.5
- **Authentication:** OAuth 2.0 (Google, Facebook, Apple)
- **Hosting:** DreamHost
- **Version Control:** Git, GitHub

## 💾 Database

### Local
- **Host:** localhost
- **Database:** punktionary_local
- **User:** root
- **Password:** (empty)

### Production
- **Host:** sql.punktionary.com
- **Database:** prod_punk
- **User:** dayjoel

### Tables
- `users` - User accounts (OAuth)
- `venues` - Venue directory
- `bands` - Band directory
- `resources` - Resource directory
- `venue_reviews` - Venue reviews and ratings
- `carousel` - Homepage carousel
- `pending_edits` - Edit suggestions
- `pending_carousel_news` - News submissions

## 🔐 Configuration

Configuration files are stored **outside** the git repository for security:

### Local
- `/Users/joelday/.claude-worktrees/punktionary/db_config.php` → `db_config.local.php`
- `/Users/joelday/.claude-worktrees/punktionary/oauth_config.php` → `oauth_config.local.php`

### Production
- `/home/joeday1/db_config.php`
- `/home/joeday1/oauth_config.php`

These files are automatically used by the application through relative path includes.

## 🚢 Deployment Workflow

### Simple Version (Recommended)

```bash
# 1. Make changes and test locally
./start_local_server.sh

# 2. Deploy everything
./deploy.sh
```

### Manual Version

```bash
# 1. Commit changes
git add .
git commit -m "Your message"

# 2. Push to GitHub
git push origin elastic-hertz

# 3. Deploy to production
ssh joeday1@punktionary.com
cd ~/punktionary.com
git pull origin elastic-hertz
exit
```

## ✅ Pre-Deployment Checklist

Before deploying to production:

- [ ] Test changes locally (http://localhost:8000)
- [ ] Check browser console for errors (F12)
- [ ] Test all modified functionality
- [ ] Run `./pre_push_checklist.sh`
- [ ] Review changes with `git diff`
- [ ] Commit with descriptive message
- [ ] If database changes: note migrations needed

After deploying:

- [ ] Test on production (https://punktionary.com)
- [ ] Run any database migrations
- [ ] Check production error logs
- [ ] Verify all features work

## 🐛 Debugging

### Local Development

**PHP Errors:** Appear in terminal where `start_local_server.sh` is running

**JavaScript Errors:** Browser console (F12 → Console tab)

**Network Errors:** Browser DevTools (F12 → Network tab)

**Database Queries:**
```bash
mysql -u root punktionary_local
```

### Production

**SSH Access:**
```bash
ssh joeday1@punktionary.com
```

**Error Logs:**
```bash
tail -f ~/logs/punktionary.com/http/error.log
```

**Database Access:**
```bash
mysql -h sql.punktionary.com -u dayjoel -p prod_punk
```

## 🆘 Troubleshooting

### Port 8000 in use
```bash
pkill -f "php -S localhost:8000"
# Or use different port: php -S localhost:8080
```

### MySQL not running
```bash
brew services start mysql
```

### Changes not appearing
```bash
# Clear browser cache: Cmd+Shift+R
# Or force refresh in browser
```

### Git conflicts
```bash
git fetch origin
git merge origin/elastic-hertz
# Resolve conflicts in editor
git add .
git commit -m "Resolve conflicts"
```

## 📋 Recent Features

- ✅ Venue review system with 1-5 star ratings
- ✅ Resource type filtering
- ✅ Admin moderation tools
- ✅ Carousel news submission
- ✅ OAuth authentication (Google, Facebook, Apple)
- ✅ User profile management
- ✅ Edit suggestion system

## 🤝 Contributing

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Make your changes and test locally
3. Commit: `git commit -m "Add my feature"`
4. Push: `git push origin feature/my-feature`
5. Deploy to production when ready

## 📞 Support

- **Local Setup Issues:** See `LOCAL_DEVELOPMENT.md`
- **Deployment Issues:** See `DEPLOYMENT_WORKFLOW.md`
- **Quick Reference:** See `CHEATSHEET.md`

## 🎸 Keep it Punk!

This is a community resource. Keep code clean, test before deploying, and remember: patches are welcome, but so is constructive feedback!