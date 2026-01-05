#!/bin/bash
# Quick deploy script for pushing to production

set -e  # Exit on error

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== PUNKtionary Deployment Script ===${NC}"
echo ""

# Get current branch
BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo -e "${BLUE}Current branch:${NC} $BRANCH"
echo ""

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    echo -e "${YELLOW}Warning: You have uncommitted changes${NC}"
    git status -s
    echo ""
    read -p "Do you want to commit these changes? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Enter commit message: " COMMIT_MSG
        git add .
        git commit -m "$COMMIT_MSG

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
        echo -e "${GREEN}✓ Changes committed${NC}"
    else
        echo -e "${RED}Deployment cancelled${NC}"
        exit 1
    fi
fi

# Show what will be pushed
echo ""
echo -e "${BLUE}=== Commits to be pushed ===${NC}"
git log origin/$BRANCH..$BRANCH --oneline
echo ""

# Confirm push
read -p "Push $BRANCH to GitHub and merge to main? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}Deployment cancelled${NC}"
    exit 1
fi

# Push current branch to GitHub
echo ""
echo -e "${BLUE}Pushing $BRANCH to GitHub...${NC}"
git push origin $BRANCH

echo -e "${GREEN}✓ Pushed to GitHub${NC}"
echo ""

# Merge to main
echo -e "${BLUE}Merging $BRANCH into main...${NC}"

# Stash current branch info
CURRENT_BRANCH=$BRANCH

# Fetch latest main
git fetch origin main

# Switch to main
git checkout main

# Pull latest main
git pull origin main

# Merge the feature branch
git merge $CURRENT_BRANCH -m "Merge $CURRENT_BRANCH into main

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"

echo -e "${GREEN}✓ Merged $CURRENT_BRANCH into main${NC}"

# Push main to GitHub
echo -e "${BLUE}Pushing main to GitHub...${NC}"
git push origin main

echo -e "${GREEN}✓ Pushed main to GitHub${NC}"

# Switch back to original branch
git checkout $CURRENT_BRANCH

echo ""

# Ask about deploying to production
read -p "Deploy to production now? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo -e "${YELLOW}Deployment to production skipped${NC}"
    echo "Main branch has been updated. To deploy manually later, run:"
    echo -e "${BLUE}ssh joeday1@iad1-shared-b8-46.dreamhost.com 'cd ~/punktionary.com && git pull origin main'${NC}"
    exit 0
fi

# Deploy to production
echo ""
echo -e "${BLUE}Deploying to production...${NC}"
ssh joeday1@iad1-shared-b8-46.dreamhost.com << EOF
    set -e
    cd ~/punktionary.com
    echo "Pulling latest changes from main..."
    git pull origin main
    echo "Current commit:"
    git log -1 --oneline
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ Successfully deployed to production!${NC}"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo "1. Visit https://punktionary.com to test"
    echo "2. Check browser console for errors"
    echo "3. If you added database migrations, run them:"
    echo -e "   ${YELLOW}./run_migration.sh YOUR_FILE.sql production${NC}"
else
    echo ""
    echo -e "${RED}✗ Deployment failed${NC}"
    echo "Check the error messages above"
    exit 1
fi
