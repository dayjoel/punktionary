#!/bin/bash
# Pre-push checklist to ensure code quality before deployment

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

echo -e "${BLUE}=== Pre-Push Checklist ===${NC}"
echo ""

# Function to check something
check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((PASS++))
    else
        echo -e "${RED}✗${NC} $2"
        ((FAIL++))
    fi
}

# Check 1: No uncommitted changes
echo -e "${BLUE}Checking git status...${NC}"
git diff-index --quiet HEAD --
check $? "No uncommitted changes"

# Check 2: All files staged
UNSTAGED=$(git diff --name-only)
if [ -z "$UNSTAGED" ]; then
    check 0 "All changes are staged"
else
    check 1 "Unstaged changes found: $UNSTAGED"
fi

# Check 3: No PHP syntax errors in modified files
echo ""
echo -e "${BLUE}Checking PHP syntax...${NC}"
PHP_ERROR=0
for file in $(git diff --name-only --cached | grep '\.php$'); do
    if [ -f "$file" ]; then
        php -l "$file" > /dev/null 2>&1
        if [ $? -ne 0 ]; then
            echo -e "${RED}  Syntax error in: $file${NC}"
            PHP_ERROR=1
        fi
    fi
done
check $((! PHP_ERROR)) "PHP syntax is valid"

# Check 4: No console.log in JavaScript (warning only)
echo ""
echo -e "${BLUE}Checking for console.log...${NC}"
CONSOLE_LOGS=$(git diff --cached | grep -i "console\.log" | wc -l)
if [ $CONSOLE_LOGS -gt 0 ]; then
    echo -e "${YELLOW}⚠${NC}  Found $CONSOLE_LOGS console.log statements (consider removing)"
else
    echo -e "${GREEN}✓${NC} No console.log found"
fi

# Check 5: No TODO comments in committed code (warning only)
echo ""
echo -e "${BLUE}Checking for TODO comments...${NC}"
TODOS=$(git diff --cached | grep -i "// TODO" | wc -l)
if [ $TODOS -gt 0 ]; then
    echo -e "${YELLOW}⚠${NC}  Found $TODOS TODO comments"
else
    echo -e "${GREEN}✓${NC} No TODO comments found"
fi

# Check 6: Local server accessible (if running)
echo ""
echo -e "${BLUE}Checking local server...${NC}"
if lsof -i :8000 > /dev/null 2>&1; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/)
    if [ "$HTTP_CODE" = "200" ]; then
        check 0 "Local server is responding (http://localhost:8000)"
    else
        check 1 "Local server returned HTTP $HTTP_CODE"
    fi
else
    echo -e "${YELLOW}⚠${NC}  Local server not running (skipped)"
fi

# Check 7: Database migrations documented
echo ""
echo -e "${BLUE}Checking for new migrations...${NC}"
NEW_MIGRATIONS=$(git diff --cached --name-only | grep 'db/migrations/.*\.sql$')
if [ -n "$NEW_MIGRATIONS" ]; then
    echo -e "${YELLOW}⚠${NC}  New migration files detected:"
    echo "$NEW_MIGRATIONS" | sed 's/^/    /'
    echo -e "  ${BLUE}Remember to run these on production after deploying!${NC}"
else
    echo -e "${GREEN}✓${NC} No new migration files"
fi

# Summary
echo ""
echo -e "${BLUE}=== Summary ===${NC}"
echo -e "${GREEN}Passed: $PASS${NC}"
if [ $FAIL -gt 0 ]; then
    echo -e "${RED}Failed: $FAIL${NC}"
    echo ""
    echo -e "${RED}Please fix the issues above before pushing${NC}"
    exit 1
else
    echo -e "${GREEN}All checks passed!${NC}"
    echo ""
    echo -e "${GREEN}Ready to push to production${NC}"
    echo "Run: ${BLUE}./deploy.sh${NC}"
fi
